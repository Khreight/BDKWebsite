<?php

function createUser(PDO $pdo, array $old, $cityId): int {
    try {
        $query = "INSERT INTO user (
            firstName, lastName, birthday, email, picture, phone, role, city, nationality, password, created_at, emailVerified
        ) VALUES (
            :first_name, :last_name, :birthday, :email, :picture, :phone, 4, :city, :nationality, :password, NOW(), 0
        )";
        $stmt = $pdo->prepare($query);

        $stmt->execute([
            'first_name'  => $old['first_name'],
            'last_name'   => $old['last_name'],
            'birthday'    => $old['birthdate'],
            'email'       => $old['email'],
            'picture'     => "default.png",
            'phone'       => $old['phone'],
            'city'        => $cityId,
            'nationality' => $old['nationality'],
            'password'    => password_hash($old['password'], PASSWORD_DEFAULT),
        ]);

        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        die("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
    }
}

function addTokenEmail(PDO $pdo, int $userId): string {
    $pdo->prepare("
        UPDATE tokenUser
        SET status = 0
        WHERE user = :userId AND typeToken = 'verificationEmail'
    ")->execute(['userId' => $userId]);

    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("
        INSERT INTO tokenUser (typeToken, user, token, dateToken, status)
        VALUES ('verificationEmail', :userId, :token, NOW(), 1)
    ");
    $stmt->execute([
        'userId' => $userId,
        'token'  => $token
    ]);

    return $token;
}


function userEmailExists(PDO $pdo, string $email): bool {
    $sql = "SELECT 1 FROM user WHERE LOWER(email) = LOWER(:email) LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    return (bool)$stmt->fetchColumn();
}

function userPhoneExists(PDO $pdo, string $phone): bool {
    $sql = "SELECT 1 FROM user WHERE phone = :phone LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':phone' => $phone]);
    return (bool)$stmt->fetchColumn();
}

function getUserByEmail(PDO $pdo, string $email) {
    $stmt = $pdo->prepare("SELECT userId AS id, firstName, lastName, email, role, picture, password, emailVerified
                           FROM user
                           WHERE email = ? AND password IS NOT NULL AND password != ''
                        ");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



function createCookieToken(PDO $pdo, int $userId, string $token): void {
    $sql = "INSERT INTO tokenUser (typeToken, user, token, dateToken, status)
            VALUES ('cookieToken', :userId, :token, NOW(), 1)";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':userId' => $userId,
        ':token'  => $token
    ]);
}

function getCookieToken(PDO $pdo, string $token): ?array {
    $sql = "SELECT t.*, u.*
            FROM tokenUser t
            JOIN user u ON u.userId = t.user
            WHERE t.typeToken = 'cookieToken'
              AND t.token = :token
              AND t.status = 1
              AND t.dateToken >= (NOW() - INTERVAL 10 DAY)
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':token' => $token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function changeStatusCookies($pdo, $userId) {
    $sql = "UPDATE tokenUser SET status = 0 WHERE user = :user AND typeToken = 'cookieToken'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user' => $userId]);
}


function getTokenEmail(PDO $pdo, string $token): ?array {
    $stmt = $pdo->prepare("
        SELECT tokenUserId, user AS userId
        FROM tokenUser
        WHERE token = :token
          AND typeToken = 'verificationEmail'
          AND status = 1
          AND dateToken >= NOW() - INTERVAL 15 MINUTE
        LIMIT 1
    ");
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function verifyEmailUser(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("UPDATE user SET emailVerified = 1 WHERE userId = :id");
    $stmt->execute(['id' => $userId]);
}

function changeStatusToken(PDO $pdo, int $tokenUserId): void {
    $stmt = $pdo->prepare("UPDATE tokenUser SET status = 0 WHERE tokenUserId = :id");
    $stmt->execute(['id' => $tokenUserId]);
}

function addTokenPasswordForget(PDO $pdo, int $userId): string {
    $token = bin2hex(random_bytes(32));
    $st = $pdo->prepare("INSERT INTO tokenUser (typeToken, user, token, dateToken, status)
                         VALUES ('passwordForget', :u, :tok, NOW(), 1)");
    $st->execute([':u' => $userId, ':tok' => $token]);
    return $token;
}

function getPasswordForgetToken(PDO $pdo, string $token): ?array {
    $st = $pdo->prepare("
        SELECT t.tokenUserId, t.user AS userId, t.dateToken, u.emailVerified
        FROM tokenUser t
        JOIN user u ON u.userId = t.user
        WHERE t.typeToken = 'passwordForget'
          AND t.token = :tok
          AND t.status = 1
          AND t.dateToken >= (NOW() - INTERVAL 30 MINUTE)
        LIMIT 1
    ");
    $st->execute([':tok' => $token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function updateUserPassword(PDO $pdo, int $userId, string $hash): void {
    $st = $pdo->prepare("UPDATE user SET password = :p WHERE userId = :u");
    $st->execute([':p' => $hash, ':u' => $userId]);
}

function getUsersWithJoins(PDO $pdo, int $limit = 50, int $offset = 0, ?string $q = null): array
{
    $sql = "
        SELECT
        u.userId AS id,
        u.firstName, u.lastName, u.birthday, u.email, u.phone,
        u.city, u.nationality, u.role, u.dateRequestMember, u.created_at,
        ci.name  AS city_name,
        co.name  AS country_name,
        co.flag  AS country_flag,
        r.nameRole AS role_name
        FROM user u
        LEFT JOIN city    ci ON ci.cityId    = u.city
        LEFT JOIN country co ON co.countryId = u.nationality
        LEFT JOIN role    r  ON r.roleId     = u.role
        WHERE u.password IS NOT NULL AND password != ''
    ";

    $params = [];
    if ($q !== null && $q !== '') {
        $sql .= " WHERE u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q ";
        $params[':q'] = "%{$q}%";
    }

    $sql .= " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    if (isset($params[':q'])) {
        $stmt->bindValue(':q', $params[':q'], PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



function countUsers(PDO $pdo, ?string $q = null): int {
    $sql = "SELECT COUNT(*) AS c FROM user";
    $params = [];
    if ($q) {
        $sql .= " WHERE firstName LIKE :q OR lastName LIKE :q OR email LIKE :q OR city LIKE :q AND password IS NOT NULL AND password != ''";
        $params[':q'] = "%{$q}%";
    }
    $stmt = $pdo->prepare($sql);
    if ($q) $stmt->bindValue(':q', $params[':q'], PDO::PARAM_STR);
    $stmt->execute();
    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['c'];
}



function getUserByIdWithJoins(PDO $pdo, int $userId): ?array
{
    $sql = "
        SELECT
            u.userId            AS id,
            u.firstName,
            u.lastName,
            u.birthday,
            u.email,
            u.picture,
            u.phone,
            u.poids,
            u.taille,
            u.numero,
            u.description,
            u.role,
            u.city,
            u.nationality,
            u.dateRequestMember,
            u.created_at,
            u.emailVerified,

            ci.cityId           AS city_id,
            ci.name             AS city_name,
            ci.zip              AS city_zip,

            co.countryId        AS country_id,
            co.name             AS country_name,
            co.flag             AS country_flag,

            r.roleId            AS role_id,
            r.nameRole          AS role_name,

            /* ------------------- Stats courses ------------------- */

            /* Victoires (P1) */
            IFNULL((
                SELECT COUNT(*) FROM (
                    SELECT my.race, SUM(myLap.lapTime) AS my_total
                    FROM resultat my
                    JOIN lap myLap ON myLap.resultat = my.resultatId
                    WHERE my.pilot = :id
                    GROUP BY my.race
                ) AS me
                WHERE (
                    SELECT COUNT(*) FROM (
                        SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                        FROM resultat r2
                        JOIN lap l2 ON l2.resultat = r2.resultatId
                        WHERE r2.race = me.race
                        GROUP BY r2.pilot
                        HAVING total_time < me.my_total
                    ) AS better
                ) = 0
            ), 0) AS wins_count,

            /* Podiums (P1–P3) */
            IFNULL((
                SELECT COUNT(*) FROM (
                    SELECT my.race, SUM(myLap.lapTime) AS my_total
                    FROM resultat my
                    JOIN lap myLap ON myLap.resultat = my.resultatId
                    WHERE my.pilot = :id
                    GROUP BY my.race
                ) AS me
                WHERE (
                    SELECT COUNT(*) FROM (
                        SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                        FROM resultat r2
                        JOIN lap l2 ON l2.resultat = r2.resultatId
                        WHERE r2.race = me.race
                        GROUP BY r2.pilot
                        HAVING total_time < me.my_total
                    ) AS better
                ) <= 2
            ), 0) AS podiums_count,

            /* Meilleurs tours (par course) */
            IFNULL((
                SELECT COUNT(*) FROM (
                    SELECT DISTINCT r.race
                    FROM resultat r
                    JOIN lap l ON l.resultat = r.resultatId
                    JOIN (
                        SELECT r2.race, MIN(l2.lapTime) AS min_time
                        FROM resultat r2
                        JOIN lap l2 ON l2.resultat = r2.resultatId
                        GROUP BY r2.race
                    ) m ON m.race = r.race
                    WHERE r.pilot = :id
                      AND l.lapTime = m.min_time
                ) AS races_with_fastest_lap
            ), 0) AS fastest_laps_count,

            /* ------------------- Top 3 détails (année, place, nom circuit) ------------------- */

            /* Part 1: meilleur classement */
            (
              SELECT ranked.season_year
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 0,1
            ) AS BestOf3Part1_year,
            (
              SELECT ranked.my_rank
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 0,1
            ) AS BestOf3Part1_place,
            (
              SELECT ranked.circuit_name
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 0,1
            ) AS BestOf3Part1_nameCircuit,

            /* Part 2: 2e meilleur */
            (
              SELECT ranked.season_year
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 1,1
            ) AS BestOf3Part2_year,
            (
              SELECT ranked.my_rank
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 1,1
            ) AS BestOf3Part2_place,
            (
              SELECT ranked.circuit_name
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 1,1
            ) AS BestOf3Part2_nameCircuit,

            /* Part 3: 3e meilleur */
            (
              SELECT ranked.season_year
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 2,1
            ) AS BestOf3Part3_year,
            (
              SELECT ranked.my_rank
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 2,1
            ) AS BestOf3Part3_place,
            (
              SELECT ranked.circuit_name
              FROM (
                SELECT me.race, rc.date AS race_date, s.year AS season_year, c.nameCircuit AS circuit_name,
                       1 + (
                         SELECT COUNT(*) FROM (
                           SELECT r2.pilot, SUM(l2.lapTime) AS total_time
                           FROM resultat r2
                           JOIN lap l2 ON l2.resultat = r2.resultatId
                           WHERE r2.race = me.race
                           GROUP BY r2.pilot
                           HAVING total_time < me.my_total
                         ) AS better
                       ) AS my_rank
                FROM (
                  SELECT my.race, SUM(myLap.lapTime) AS my_total
                  FROM resultat my
                  JOIN lap myLap ON myLap.resultat = my.resultatId
                  WHERE my.pilot = :id
                  GROUP BY my.race
                ) AS me
                JOIN race rc      ON rc.raceId    = me.race
                LEFT JOIN season s  ON s.seasonId   = rc.season
                LEFT JOIN circuit c ON c.circuitId  = rc.circuit
              ) AS ranked
              ORDER BY ranked.my_rank ASC, ranked.race_date DESC
              LIMIT 2,1
            ) AS BestOf3Part3_nameCircuit

        FROM user u
        LEFT JOIN city    ci ON ci.cityId    = u.city
        LEFT JOIN country co ON co.countryId = u.nationality
        LEFT JOIN role    r  ON r.roleId     = u.role
        WHERE u.userId = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}


function getPilotSeasonsWithRaces(PDO $pdo, int $pilotId): array
{
    $sqlSeasons = "
        SELECT s.seasonId, s.year
        FROM season s
        WHERE s.seasonId IN (
            SELECT DISTINCT rc.season
            FROM race rc
            JOIN resultat r ON r.race = rc.raceId
            WHERE r.pilot = :pilot
            UNION
            SELECT DISTINCT season
            FROM ranking
            WHERE pilot = :pilot
        )
        ORDER BY s.year DESC
    ";
    $stSeasons = $pdo->prepare($sqlSeasons);
    $stSeasons->execute([':pilot' => $pilotId]);
    $seasons = $stSeasons->fetchAll(PDO::FETCH_ASSOC);

    // Prépare les statements réutilisables

    // 2) Points et rang (position) du pilote pour une saison donnée
    $stPoints = $pdo->prepare("
        SELECT point
        FROM ranking
        WHERE pilot = :pilot AND season = :season
        LIMIT 1
    ");
    $stRank = $pdo->prepare("
        SELECT 1 + COUNT(*) AS rank_pos
        FROM ranking r2
        WHERE r2.season = :season
          AND r2.point > :myPoints
    ");

    // 3) Courses de la saison + place du pilote
    // - on ne liste que les courses où le pilote a un 'resultat' (sinon pas de place)
    // - place = 1 + nb de pilotes avec SUM(lapTime) strictement < au total du pilote sur cette course
    $sqlRaces = "
        SELECT
          rc.raceId,
          DATE_FORMAT(rc.date, '%d/%m/%Y')           AS date,
          c.nameCircuit                               AS circuitName,
          ci.name                                     AS circuitCity,
          (
            SELECT 1 + COUNT(*) FROM (
              SELECT r2.pilot, SUM(l2.lapTime) AS total_time
              FROM resultat r2
              JOIN lap l2 ON l2.resultat = r2.resultatId
              WHERE r2.race = rc.raceId
              GROUP BY r2.pilot
              HAVING total_time < (
                SELECT SUM(lm.lapTime)
                FROM resultat rMe
                JOIN lap lm ON lm.resultat = rMe.resultatId
                WHERE rMe.pilot = :pilot AND rMe.race = rc.raceId
              )
            ) AS better
          ) AS place
        FROM race rc
        JOIN circuit  c  ON c.circuitId  = rc.circuit
        JOIN address  a  ON a.addressId  = c.address
        JOIN city     ci ON ci.cityId    = a.city
        WHERE rc.season = :season
          AND EXISTS (
              SELECT 1 FROM resultat r WHERE r.race = rc.raceId AND r.pilot = :pilot
          )
        ORDER BY rc.date ASC
    ";
    $stRaces = $pdo->prepare($sqlRaces);

    // Construire la structure finale
    $out = [];
    foreach ($seasons as $s) {
        $seasonId = (int)$s['seasonId'];
        $year     = (int)$s['year'];

        // Points
        $stPoints->execute([':pilot' => $pilotId, ':season' => $seasonId]);
        $rowPoint = $stPoints->fetch(PDO::FETCH_ASSOC);
        $points   = $rowPoint ? (int)$rowPoint['point'] : null;

        // Rang (position) si on a des points
        $rankPos = null;
        if ($points !== null) {
            $stRank->execute([':season' => $seasonId, ':myPoints' => $points]);
            $rowRank = $stRank->fetch(PDO::FETCH_ASSOC);
            $rankPos = $rowRank ? (int)$rowRank['rank_pos'] : null;
        }

        // Races de cette saison pour ce pilote
        $stRaces->execute([':season' => $seasonId, ':pilot' => $pilotId]);
        $races = $stRaces->fetchAll(PDO::FETCH_ASSOC);

        // Ajoute pointWin = null (tu rempliras manuellement si tu veux afficher quelque chose)
        foreach ($races as &$r) {
            $r['pointWin'] = null; // placeholder, attribution manuelle
        }
        unset($r);

        $out[] = [
            'seasonId' => $seasonId,
            'year'     => $year,
            'points'   => $points,
            'rank'     => $rankPos,
            'races'    => $races,
        ];
    }

    return $out;
}



function updateUserProfile(PDO $pdo, int $userId, array $fields): void {
    if (!$fields) return;
    $set = [];
    $params = [':id' => $userId];
    foreach ($fields as $col => $val) {
        $set[] = "`$col` = :$col";
        $params[":$col"] = $val;
    }
    $sql = "UPDATE user SET " . implode(', ', $set) . " WHERE userId = :id LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute($params);
}

function userEmailExistsForOther(PDO $pdo, string $email, int $exceptUserId): bool {
    $sql = "SELECT 1 FROM user WHERE LOWER(email) = LOWER(:e) AND userId <> :id LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':e' => $email, ':id' => $exceptUserId]);
    return (bool)$st->fetchColumn();
}

function userPhoneExistsForOther(PDO $pdo, string $phone, int $exceptUserId): bool {
    $sql = "SELECT 1 FROM user WHERE phone = :p AND userId <> :id LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':p' => $phone, ':id' => $exceptUserId]);
    return (bool)$st->fetchColumn();
}

function promoteUserToAdmin($pdo, $targetUserId) {
    $sql = "UPDATE user SET role = 1 WHERE userId = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $targetUserId]);
}

function promoteUserToDriver($pdo, $targetUserId) {
    $sql = "UPDATE user SET role = 2 WHERE userId = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $targetUserId]);
}

function deleteUserAccount($pdo, $targetUserId) {
    $pdo->prepare("UPDATE user SET password = '' WHERE userId = :id")->execute([':id' => $targetUserId]);
    $pdo->prepare("UPDATE user SET email = '' WHERE userId = :id")->execute([':id' => $targetUserId]);
}

function downgradeUserFromDriver($pdo, $targetUserId) {
    $sql = "UPDATE user SET role = 4 WHERE userId = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $targetUserId]);
}
