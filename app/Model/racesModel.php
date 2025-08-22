<?php
// Model/racesModel.php

function registration_counted_statuses(): array {
    // “waited” (en attente) et “valide” (accepté) comptent dans la capacité.
    return ['waited','valide'];
}

/* =============== SAISONS =============== */
function getAllSeasons(PDO $pdo): array {
    $st = $pdo->query("SELECT seasonId, year FROM season ORDER BY year DESC");
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/* Met à jour le statut d'une inscription */
function setRegistrationStatus(PDO $pdo, int $registrationId, string $status): void {
    if (!in_array($status, ['waited','valide','no-valide'], true)) {
        throw new InvalidArgumentException('Statut invalide.');
    }
    $st = $pdo->prepare("UPDATE registration SET status = :s, date = NOW() WHERE registrationId = :id");
    $st->execute([':s'=>$status, ':id'=>$registrationId]);
}

/* Renvoie le statut de l'utilisateur pour une course (ou null) */
function getUserRegistrationStatus(PDO $pdo, int $raceId, int $userId): ?string {
    $st = $pdo->prepare("SELECT status FROM registration WHERE race = :r AND user = :u ORDER BY date DESC LIMIT 1");
    $st->execute([':r'=>$raceId, ':u'=>$userId]);
    $v = $st->fetchColumn();
    return $v !== false ? (string)$v : null;
}

function getUpcomingRacesAll(PDO $pdo, int $limit = 6): array {
    $sql = "
      SELECT
        rc.raceId,
        rc.date,
        rc.description,
        rc.price_cents,
        rc.capacity_max,
        rc.registration_open,
        rc.registration_close,
        s.year        AS seasonYear,
        c.nameCircuit AS circuitName,
        ci.name       AS cityName,
        co.name       AS countryName,
        /* compteur participants (valide + en attente), juste pour info si tu veux tag 'Complet' */
        (
          SELECT COUNT(*) FROM registration rg
          WHERE rg.race = rc.raceId AND rg.status IN ('waited','valide')
        ) AS regCount
      FROM race rc
      LEFT JOIN season  s  ON s.seasonId  = rc.season
      LEFT JOIN circuit c  ON c.circuitId = rc.circuit
      LEFT JOIN address a  ON a.addressId = c.address
      LEFT JOIN city    ci ON ci.cityId   = a.city
      LEFT JOIN country co ON co.countryId= ci.country
      WHERE rc.date >= NOW()
      ORDER BY rc.date ASC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}


function getSeasonById(PDO $pdo, int $seasonId): ?array {
    $st = $pdo->prepare("SELECT seasonId, year FROM season WHERE seasonId = :id");
    $st->execute([':id'=>$seasonId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
function createSeason(PDO $pdo, int $year): int {
    // éviter doublon
    $st = $pdo->prepare("SELECT COUNT(*) FROM season WHERE year = :y");
    $st->execute([':y'=>$year]);
    if ($st->fetchColumn() > 0) throw new RuntimeException("Cette année existe déjà.");
    $ins = $pdo->prepare("INSERT INTO season (year) VALUES (:y)");
    $ins->execute([':y'=>$year]);
    return (int)$pdo->lastInsertId();
}
function updateSeasonYear(PDO $pdo, int $seasonId, int $year): void {
    $st = $pdo->prepare("SELECT COUNT(*) FROM season WHERE year = :y AND seasonId <> :id");
    $st->execute([':y'=>$year, ':id'=>$seasonId]);
    if ($st->fetchColumn() > 0) throw new RuntimeException("Cette année existe déjà.");
    $up = $pdo->prepare("UPDATE season SET year = :y WHERE seasonId = :id");
    $up->execute([':y'=>$year, ':id'=>$seasonId]);
}
function deleteSeason(PDO $pdo, int $seasonId): void {
    // Supprimer ranking liés (sinon contrainte)
    $pdo->prepare("DELETE FROM ranking WHERE season = :s")->execute([':s'=>$seasonId]);
    // (optionnel) races liées : interdire/supprimer selon besoin. Ici on interdit si races existent.
    $st = $pdo->prepare("SELECT COUNT(*) FROM race WHERE season = :s");
    $st->execute([':s'=>$seasonId]);
    if ($st->fetchColumn() > 0) throw new RuntimeException("Impossible: des courses existent pour cette saison.");
    $pdo->prepare("DELETE FROM season WHERE seasonId = :s")->execute([':s'=>$seasonId]);
}

/* ======== RANKING (classement / points) ======== */
function getSeasonRanking(PDO $pdo, int $seasonId): array {
    $sql = "
        SELECT r.rankingId, r.pilot AS pilotId, r.points,
               u.firstName, u.lastName, u.email
        FROM ranking r
        JOIN user u ON u.userId = r.pilot
        WHERE r.season = :s
        ORDER BY r.points DESC, u.lastName ASC, u.firstName ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':s'=>$seasonId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * $points peut être:
 * - [rankingId => points] (clé = rankingId existant)
 * - [pilotId => points]   (clé = id du pilote, on upsert)
 */
function updateSeasonPoints(PDO $pdo, int $seasonId, array $points): void {
    $selRank = $pdo->prepare("SELECT rankingId FROM ranking WHERE rankingId = :id AND season = :s");
    $updById = $pdo->prepare("UPDATE ranking SET point = :p WHERE rankingId = :id");
    $selByPilot = $pdo->prepare("SELECT rankingId FROM ranking WHERE season = :s AND pilot = :p");
    $ins = $pdo->prepare("INSERT INTO ranking (pilot, season, point) VALUES (:p, :s, :pt)");
    $updByPilot = $pdo->prepare("UPDATE ranking SET point = :pt WHERE rankingId = :id");

    foreach ($points as $key => $val) {
        $pts = (int)$val;
        $id  = (int)$key;

        // d'abord essayer comme rankingId
        $selRank->execute([':id'=>$id, ':s'=>$seasonId]);
        $exists = $selRank->fetchColumn();
        if ($exists) { $updById->execute([':p'=>$pts, ':id'=>$id]); continue; }

        // sinon comme pilotId
        $pilotId = $id;
        $selByPilot->execute([':s'=>$seasonId, ':p'=>$pilotId]);
        $rid = $selByPilot->fetchColumn();
        if ($rid) {
            $updByPilot->execute([':pt'=>$pts, ':id'=>$rid]);
        } else {
            $ins->execute([':p'=>$pilotId, ':s'=>$seasonId, ':pt'=>$pts]);
        }
    }
}

/** Synchronise pilotes attachés à une saison (table ranking). */
function syncSeasonDrivers(PDO $pdo, int $seasonId, array $driverIds): void {
    // Nettoyer doublons
    $driverIds = array_values(array_unique(array_filter($driverIds, fn($v)=>$v>0)));

    // Récupère existants
    $st = $pdo->prepare("SELECT pilot FROM ranking WHERE season = :s");
    $st->execute([':s'=>$seasonId]);
    $existing = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

    // A supprimer
    $toDelete = array_diff($existing, $driverIds);
    if (!empty($toDelete)) {
        $in = implode(',', array_fill(0, count($toDelete), '?'));
        $del = $pdo->prepare("DELETE FROM ranking WHERE season = ? AND pilot IN ($in)");
        $del->execute(array_merge([$seasonId], array_values($toDelete)));
    }

    // A insérer
    $toInsert = array_diff($driverIds, $existing);
    if (!empty($toInsert)) {
        $ins = $pdo->prepare("INSERT INTO ranking (pilot, season, point) VALUES (:p,:s,0)");
        foreach ($toInsert as $pid) { $ins->execute([':p'=>$pid, ':s'=>$seasonId]); }
    }
}

/* =============== RACES (courses) =============== */
function getAllRacesWithJoins(PDO $pdo): array {
    $sql = "
      SELECT rc.raceId, rc.date, rc.description, rc.video,
             rc.price_cents, rc.capacity_min, rc.capacity_max,
             rc.registration_open, rc.registration_close,
             c.nameCircuit AS circuitName,
             s.year        AS seasonYear
      FROM race rc
      LEFT JOIN circuit c ON c.circuitId = rc.circuit
      LEFT JOIN season  s ON s.seasonId  = rc.season
      ORDER BY rc.date DESC
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function getRaceByIdWithJoins(PDO $pdo, int $raceId): ?array {
    $sql = "
      SELECT rc.raceId, rc.date, rc.description, rc.video,
             rc.price_cents, rc.capacity_min, rc.capacity_max,
             rc.registration_open, rc.registration_close,
             rc.circuit AS circuitId, rc.season AS seasonId,
             c.nameCircuit AS circuitName,
             s.year AS seasonYear
      FROM race rc
      LEFT JOIN circuit c ON c.circuitId = rc.circuit
      LEFT JOIN season  s ON s.seasonId  = rc.season
      WHERE rc.raceId = :id
      LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':id'=>$raceId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}


function createRace(PDO $pdo, array $data): int {
    if (empty($data['circuitId']) || empty($data['seasonId']) || empty($data['date'])) {
        throw new RuntimeException("Circuit, saison et date sont requis.");
    }
    $sql = "INSERT INTO race
      (circuit, description, date, season, video, price_cents, capacity_min, capacity_max, registration_open, registration_close)
      VALUES (:circuit, :desc, :date, :season, :video, :pc, :cmin, :cmax, :ropen, :rclose)";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':circuit' => (int)$data['circuitId'],
        ':desc'    => $data['description'] ?? null,
        ':date'    => $data['date'],
        ':season'  => (int)$data['seasonId'],
        ':video'   => $data['video'] ?? null,
        ':pc'      => $data['price_cents'] ?? null,
        ':cmin'    => $data['capacity_min'] ?? null,
        ':cmax'    => $data['capacity_max'] ?? null,
        ':ropen'   => $data['registration_open'] ?? null,
        ':rclose'  => $data['registration_close'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}
function updateRace(PDO $pdo, int $raceId, array $data): void {
    $sql = "UPDATE race SET
        circuit=:circuit, description=:desc, date=:date, season=:season,
        price_cents=:pc, capacity_min=:cmin, capacity_max=:cmax,
        registration_open=:ropen, registration_close=:rclose
      WHERE raceId=:id";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':id'     => $raceId,
        ':circuit'=> (int)$data['circuitId'],
        ':desc'   => $data['description'] ?? null,
        ':date'   => $data['date'] ?? null,
        ':season' => (int)$data['seasonId'],
        ':pc'     => $data['price_cents'] ?? null,
        ':cmin'   => $data['capacity_min'] ?? null,
        ':cmax'   => $data['capacity_max'] ?? null,
        ':ropen'  => $data['registration_open'] ?? null,
        ':rclose' => $data['registration_close'] ?? null,
    ]);
}
function deleteRace(PDO $pdo, int $raceId): void {
    // Supprimer inscriptions & résultats liés d'abord (si utilisés)
    $pdo->prepare("DELETE FROM registration WHERE race = :r")->execute([':r'=>$raceId]);
    // résultat/lap si existants
    $res = $pdo->prepare("SELECT resultatId FROM resultat WHERE race = :r");
    $res->execute([':r'=>$raceId]);
    $ids = $res->fetchAll(PDO::FETCH_COLUMN);
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM lap WHERE resultat IN ($in)")->execute($ids);
        $pdo->prepare("DELETE FROM resultat WHERE race = :r")->execute([':r'=>$raceId]);
    }
    $pdo->prepare("DELETE FROM race WHERE raceId = :r")->execute([':r'=>$raceId]);
}

/* ====== Inscriptions (statistiques & liste) ====== */
function getRegistrationStats(PDO $pdo, int $raceId): array {
    $st = $pdo->prepare("
        SELECT status, COUNT(*) AS c
        FROM registration
        WHERE race = :r
        GROUP BY status
    ");
    $st->execute([':r'=>$raceId]);

    $out = [
        'waited_count'  => 0,
        'valid_count'   => 0,
        'novalid_count' => 0,
        'total'         => 0,
    ];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $s = (string)$row['status'];
        $c = (int)$row['c'];
        if ($s === 'waited')     $out['waited_count']  = $c;
        elseif ($s === 'valide') $out['valid_count']   = $c;
        elseif ($s === 'no-valide') $out['novalid_count'] = $c;
        $out['total'] += $c;
    }
    return $out;
}
function getRaceRegistrations(PDO $pdo, int $raceId): array {
    $st = $pdo->prepare("
        SELECT
          rg.registrationId,
          rg.status,
          rg.date           AS date,
          u.userId          AS userId,
          u.firstName,
          u.lastName,
          u.email,
          u.numero
        FROM registration rg
        JOIN user u ON u.userId = rg.user
        WHERE rg.race = :r
        ORDER BY rg.date ASC
    ");
    $st->execute([':r'=>$raceId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function raceHasResults(PDO $pdo, int $raceId): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM resultat WHERE race = :r");
    $st->execute([':r'=>$raceId]);
    return (int)$st->fetchColumn() > 0;
}

/** Participants attachés à la saison (table ranking), avec identité. */
function getSeasonParticipants(PDO $pdo, int $seasonId): array {
    $sql = "
      SELECT u.userId AS id, u.firstName, u.lastName, u.numero, u.role
      FROM ranking r
      JOIN user u ON u.userId = r.pilot
      WHERE r.season = :s
      ORDER BY u.lastName, u.firstName
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':s'=>$seasonId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function insertResultat(PDO $pdo, int $raceId, int $pilotId, int $position, ?float $avgSpeed, ?float $gapWithFront, float $points): int {
    $hasAvg   = _tableHasColumn($pdo, 'resultat', 'averageSpeed');
    $gapCol   = _tableHasColumn($pdo, 'resultat', 'gapWithFront') ? 'gapWithFront'
             : (_tableHasColumn($pdo, 'resultat', 'gap') ? 'gap' : null);
    $hasPts   = _tableHasColumn($pdo, 'resultat', 'points');

    $cols = ['pilot','race','position'];
    $vals = [':p', ':r', ':pos'];
    $params = [
        ':p'   => $pilotId,
        ':r'   => $raceId,
        ':pos' => $position,
    ];

    if ($hasAvg) { $cols[] = 'averageSpeed'; $vals[] = ':avg'; $params[':avg'] = $avgSpeed; }
    if ($gapCol) { $cols[] = $gapCol;        $vals[] = ':gap'; $params[':gap'] = $gapWithFront ?? 0.0; }
    if ($hasPts) { $cols[] = 'points';       $vals[] = ':pts'; $params[':pts'] = $points; }

    $sql = "INSERT INTO resultat (".implode(',',$cols).") VALUES (".implode(',',$vals).")";
    $st  = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$pdo->lastInsertId();
}



function insertLap(PDO $pdo, int $resultatId, int $lapNumber, float $lapTime): void {
    $st = $pdo->prepare("INSERT INTO lap (resultat, lapNumber, lapTime) VALUES (:res, :num, :time)");
    $st->execute([':res'=>$resultatId, ':num'=>$lapNumber, ':time'=>$lapTime]);
}

function saveRaceResults(PDO $pdo, int $raceId, int $seasonId, array $entries, ?int $fastestPilotId, float $fastestLapPoints = 0.5): void {
    if (empty($entries)) throw new RuntimeException("Aucune entrée.");

    // total par pilote pour calculer l'écart
    $totals = []; // pilotId => totalSeconds
    foreach ($entries as $e) {
        $sum = 0.0;
        foreach (($e['laps'] ?? []) as $lt) $sum += (float)$lt;
        $totals[(int)$e['pilotId']] = $sum;
    }
    $leaderTotal = $totals ? min($totals) : 0.0;

    // indexer pour ajouter le bonus plus tard
    $byPilotIdx = [];
    foreach ($entries as $i => $e) {
        $byPilotIdx[(int)$e['pilotId']] = $i;
    }

    // si meilleur tour, ajouter le bonus directement dans les points du pilote concerné
    if ($fastestPilotId && isset($byPilotIdx[$fastestPilotId])) {
        $i = $byPilotIdx[$fastestPilotId];
        $entries[$i]['points'] = (isset($entries[$i]['points']) ? (float)$entries[$i]['points'] : max(22 - ((int)$entries[$i]['position'] - 1), 0)) + (float)$fastestLapPoints;
    }

    // insérer lignes + tours
    foreach ($entries as $e) {
        $pilotId = (int)$e['pilotId'];
        $pos     = (int)$e['position'];
        $avg     = isset($e['avg']) ? (float)$e['avg'] : null;
        $pts     = isset($e['points']) ? (float)$e['points'] : max(22 - ($pos - 1), 0);

            $gap = null;

            if (array_key_exists('gap', $e) && $e['gap'] !== null && $e['gap'] !== '') {
                $gap = (float)$e['gap'];
            } else {
                if (isset($totals[$pilotId]) && $leaderTotal > 0) {
                    $gap = max($totals[$pilotId] - $leaderTotal, 0.0);
                } else {
                    $gap = 0.0;
                }
            }

            $rid = insertResultat($pdo, $raceId, $pilotId, $pos, $avg, $gap, $pts);


        $lapNum = 1;
        foreach (($e['laps'] ?? []) as $lt) insertLap($pdo, $rid, $lapNum++, (float)$lt);
    }
}


function getRaceResults(PDO $pdo, int $raceId): array {
    // Détecter colonnes pour être compatible avec plusieurs schémas
    $hasAvg  = _tableHasColumn($pdo, 'resultat', 'averageSpeed');
    $gapCol  = _tableHasColumn($pdo, 'resultat', 'gapWithFront') ? 'gapWithFront'
             : (_tableHasColumn($pdo, 'resultat', 'gap') ? 'gap' : null);
    $hasPts  = _tableHasColumn($pdo, 'resultat', 'points');

    $selAvg  = $hasAvg ? "r.`averageSpeed` AS `avg`" : "NULL AS `avg`";
    $selGap  = $gapCol ? "r.`$gapCol`      AS `gap`" : "0.0  AS `gap`";
    $selPts  = $hasPts ? "r.`points`       AS `points`" : "0.0 AS `points`";

    $sql = "SELECT
              r.`resultatId`,
              r.`position`,
              r.`pilot`        AS `pilotId`,
              u.`firstName`,
              u.`lastName`,
              u.`numero`,
              $selAvg,
              $selGap,
              $selPts
            FROM `resultat` r
            JOIN `user` u ON u.`userId` = r.`pilot`
            WHERE r.`race` = :raceId
            ORDER BY r.`position` ASC";

    $st = $pdo->prepare($sql);
    $st->execute([':raceId' => $raceId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['resultatId'] = (int)$row['resultatId'];
        $row['position']   = (int)$row['position'];
        $row['pilotId']    = (int)$row['pilotId'];
        $row['avg']        = isset($row['avg']) ? (float)$row['avg'] : null;
        $row['gap']        = isset($row['gap']) ? (float)$row['gap'] : 0.0;
        $row['points']     = isset($row['points']) ? (float)$row['points'] : 0.0;
    }
    return $rows;
}





function getRaceResultsWithLaps(PDO $pdo, int $raceId): array {
    // D'abord les résultats "simples"
    $rows = getRaceResults($pdo, $raceId);

    if (!$rows) {
        return [];
    }

    // Récupère tous les resultatId pour cette course
    $resultatIds = array_map(fn($r) => (int)$r['resultatId'], $rows);

    // Construire les placeholders pour l'IN (...)
    $placeholders = implode(',', array_fill(0, count($resultatIds), '?'));

    // Charger les tours pour ces resultatId
    $sqlLaps = "SELECT
                    l.`resultat`   AS `resultatId`,
                    l.`lapNumber`,
                    l.`lapTime`
                FROM `lap` l
                WHERE l.`resultat` IN ($placeholders)
                ORDER BY l.`resultat` ASC, l.`lapNumber` ASC";
    $ls = $pdo->prepare($sqlLaps);
    $ls->execute($resultatIds);

    $byResultat = [];
    while ($lap = $ls->fetch(PDO::FETCH_ASSOC)) {
        $rid = (int)$lap['resultatId'];
        $byResultat[$rid][] = (float)$lap['lapTime'];
    }

    // Attacher les tours à chaque ligne
    foreach ($rows as &$r) {
        $rid = (int)$r['resultatId'];
        $r['laps'] = $byResultat[$rid] ?? [];
    }

    return $rows;
}



function recalcSeasonRanking(PDO $pdo, int $seasonId): void {
    $pdo->prepare("UPDATE ranking SET point = 0 WHERE season = :sid")
        ->execute([':sid' => $seasonId]);

    // total par pilote
    $st = $pdo->prepare("
        SELECT r.pilot AS pilotId, SUM(r.points) AS totalPts
        FROM resultat r
        JOIN race ra ON ra.raceId = r.race
        WHERE ra.season = :sid
        GROUP BY r.pilot
    ");
    $st->execute([':sid' => $seasonId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // injection dans ranking
    $up = $pdo->prepare("UPDATE ranking SET point = :pts WHERE season = :sid AND pilot = :pid");
    foreach ($rows as $row) {
        $up->execute([
            ':pts' => (float)$row['totalPts'],
            ':sid' => $seasonId,
            ':pid' => (int)$row['pilotId'],
        ]);
    }
}


if (!function_exists('_tableHasColumn')) {
    function _tableHasColumn(PDO $pdo, string $table, string $column): bool {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :c");
        $st->execute([':c' => $column]);
        return (bool)$st->fetch(PDO::FETCH_NUM);
    }
}

function getLatestWinners(PDO $pdo, int $limit = 3): array {
    // Détecter les bons noms de colonnes sur race
    $raceCircuitCol = _tableHasColumn($pdo, 'race', 'circuitId') ? 'circuitId'
                   : (_tableHasColumn($pdo, 'race', 'circuit')   ? 'circuit' : null);

    $raceSeasonCol  = _tableHasColumn($pdo, 'race', 'seasonId')  ? 'seasonId'
                   : (_tableHasColumn($pdo, 'race', 'season')    ? 'season'  : null);

    // SELECT/JOIN conditionnels
    $selectSeason  = $raceSeasonCol  ? ", s.`year` AS `seasonYear`"      : ", NULL AS `seasonYear`";
    $selectCircuit = $raceCircuitCol ? ", c.`nameCircuit` AS `circuitName`" : ", '' AS `circuitName`";
    $joinSeason    = $raceSeasonCol  ? " LEFT JOIN `season`  s ON s.`seasonId`  = ra.`$raceSeasonCol` " : "";
    $joinCircuit   = $raceCircuitCol ? " LEFT JOIN `circuit` c ON c.`circuitId` = ra.`$raceCircuitCol` " : "";

    $sql = "
        SELECT
            ra.`raceId`,
            ra.`date`
            $selectSeason
            $selectCircuit,
            u.`userId` AS `winnerId`,
            CONCAT(u.`firstName`, ' ', u.`lastName`) AS `winnerName`
        FROM `race` ra
        JOIN `resultat` r
          ON r.`race` = ra.`raceId` AND r.`position` = 1
        $joinCircuit
        $joinSeason
        JOIN `user` u ON u.`userId` = r.`pilot`
        WHERE ra.`date` IS NOT NULL
        ORDER BY ra.`date` DESC
        LIMIT 3
    ";

    $st = $pdo->prepare($sql);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}


function updateRaceResults(PDO $pdo, int $raceId, int $seasonId, array $entries, ?int $fastestPilotId, float $fastestLapPoints = 0.5): void {
    deleteRaceResults($pdo, $raceId);
    saveRaceResults($pdo, $raceId, $seasonId, $entries, $fastestPilotId, $fastestLapPoints);
}



function deleteRaceResults(PDO $pdo, int $raceId): void {
    // récupérer les ids pour effacer les tours
    $st = $pdo->prepare("SELECT resultatId FROM resultat WHERE race = :r");
    $st->execute([':r'=>$raceId]);
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $delLaps = $pdo->prepare("DELETE FROM lap WHERE resultat IN ($in)");
        $delLaps->execute($ids);
    }
    $pdo->prepare("DELETE FROM resultat WHERE race = :r")->execute([':r'=>$raceId]);
}


// Stats profil
function getPilotSummaryStats(PDO $pdo, int $pilotId): array {
    // Victoires et podiums depuis resultat
    $sql = "
      SELECT
        SUM(CASE WHEN r.position = 1 THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN r.position BETWEEN 1 AND 3 THEN 1 ELSE 0 END) AS podiums
      FROM resultat r
      WHERE r.pilot = :pid
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':pid' => $pilotId]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['wins'=>0,'podiums'=>0];

    // Meilleurs tours via race.fastDriver
    $st2 = $pdo->prepare("SELECT COUNT(*) FROM race WHERE fastDriver = :pid");
    $st2->execute([':pid' => $pilotId]);
    $fast = (int)$st2->fetchColumn();

    return [
        'wins'     => (int)($row['wins'] ?? 0),
        'podiums'  => (int)($row['podiums'] ?? 0),
        'fastlaps' => $fast,
    ];
}

// Top 3 meilleures places du pilote (priorité meilleure place, récence en tie-break)
function getPilotTopFinishes(PDO $pdo, int $pilotId, int $limit = 3): array {
    $sql = "
      SELECT
        r.position,
        ra.date,
        s.year            AS seasonYear,
        s.seasonId,
        c.nameCircuit     AS circuitName,
        ci.name           AS cityName,
        co.name           AS countryName
      FROM resultat r
      JOIN race    ra ON ra.raceId   = r.race
      LEFT JOIN season  s ON s.seasonId = ra.season
      LEFT JOIN circuit c ON c.circuitId= ra.circuit
      LEFT JOIN address a ON a.addressId= c.address
      LEFT JOIN city    ci ON ci.cityId = a.city
      LEFT JOIN country co ON co.countryId = ci.country
      WHERE r.pilot = :pid
      ORDER BY r.position ASC, ra.date DESC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':pid', $pilotId, PDO::PARAM_INT);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}



// Résultats d’un pilote, groupés par saison (pour la vue profil)
function getPilotResultsGroupedBySeason(PDO $pdo, int $pilotId): array {
    // certaines bases ont r.points; on gère le fallback
    $hasPoints = _tableHasColumn($pdo, 'resultat', 'points');
    $pointsSel = $hasPoints ? "r.points" : "NULL";

    $sql = "
      SELECT
        s.seasonId, s.year AS seasonYear,
        ra.raceId, ra.date,
        r.position,
        $pointsSel AS points,
        c.nameCircuit     AS circuitName,
        ci.name           AS cityName,
        co.name           AS countryName
      FROM resultat r
      JOIN race    ra ON ra.raceId   = r.race
      LEFT JOIN season  s ON s.seasonId = ra.season
      LEFT JOIN circuit c ON c.circuitId= ra.circuit
      LEFT JOIN address a ON a.addressId= c.address
      LEFT JOIN city    ci ON ci.cityId = a.city
      LEFT JOIN country co ON co.countryId = ci.country
      WHERE r.pilot = :pid
      ORDER BY s.year DESC, ra.date DESC, r.position ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':pid' => $pilotId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Groupage par saison
    $out = [];
    foreach ($rows as $r) {
        $sid = (int)$r['seasonId'];
        if (!isset($out[$sid])) {
            $out[$sid] = [
                'seasonId'   => $sid,
                'seasonYear' => (int)$r['seasonYear'],
                'results'    => [],
            ];
        }
        $out[$sid]['results'][] = [
            'raceId'      => (int)$r['raceId'],
            'date'        => $r['date'],
            'position'    => (int)$r['position'],
            'points'      => isset($r['points']) ? (float)$r['points'] : null,
            'circuitName' => $r['circuitName'],
            'cityName'    => $r['cityName'],
            'countryName' => $r['countryName'],
        ];
    }
    // On veut du plus récent au plus ancien
    usort($out, fn($a,$b)=> $b['seasonYear'] <=> $a['seasonYear']);
    return array_values($out);
}



// Standings d’une saison calculés depuis resultat (utile si ranking pas synchro)
function getSeasonStandingsFromResults(PDO $pdo, int $seasonId): array {
    $sql = "
      SELECT
        u.userId AS pilotId,
        u.firstName, u.lastName, u.picture, u.numero,
        SUM(r.points) AS point
      FROM resultat r
      JOIN race ra ON ra.raceId = r.race
      JOIN user u  ON u.userId  = r.pilot
      WHERE ra.season = :sid
      GROUP BY u.userId
      ORDER BY point DESC, u.lastName ASC, u.firstName ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':sid' => $seasonId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Helper rang “dense” (1,1,3…) sur un tableau [{pilotId, point}, ...]
function buildDenseRanks(array $rows, string $scoreKey = 'point', string $idKey = 'pilotId'): array {
    $ranks = [];
    $rank = 0; $prev = null; $i = 0;
    foreach ($rows as $row) {
        $i++;
        $score = (float)($row[$scoreKey] ?? 0);
        if ($prev === null || $score < $prev) { $rank = $i; $prev = $score; }
        $ranks[(int)$row[$idKey]] = $rank;
    }
    return $ranks;
}

// --- à coller dans Model/racesModel.php ---

if (!function_exists('_tableHasColumn')) {
  function _tableHasColumn(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :c");
    $st->execute([':c' => $column]);
    return (bool)$st->fetch(PDO::FETCH_NUM);
  }
}

/**
 * Classement d'une saison (public) calculé depuis `resultat.points`.
 * Retour: [ ['pilotId'=>..,'firstName'=>..,'lastName'=>..,'numero'=>..,'picture'=>..,'point'=>float], ... ]
 */
function getSeasonStandingsPublic(PDO $pdo, int $seasonId): array {
    // détection du nom de colonne de la FK saison dans `race`
    $seasonCol = _tableHasColumn($pdo, 'race', 'seasonId') ? 'seasonId'
               : (_tableHasColumn($pdo, 'race', 'season')   ? 'season'   : null);
    if (!$seasonCol) throw new RuntimeException("Colonne de saison introuvable dans `race`.");

    $sql = "
      SELECT
        u.`userId`   AS pilotId,
        u.`firstName`, u.`lastName`, u.`picture`, u.`numero`,
        COALESCE(SUM(r.`points`),0) AS point
      FROM `resultat` r
      JOIN `race` ra ON ra.`raceId` = r.`race`
      JOIN `user` u  ON u.`userId` = r.`pilot`
      WHERE ra.`$seasonCol` = :sid
      GROUP BY u.`userId`, u.`firstName`, u.`lastName`, u.`picture`, u.`numero`
      ORDER BY point DESC, u.`lastName` ASC, u.`firstName` ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':sid' => $seasonId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getRacesBySeason(PDO $pdo, int $seasonId, bool $onlyWithResults = false): array {
    // Détection des colonnes réellement présentes
    $raceSeasonCol   = _tableHasColumn($pdo, 'race', 'season')   ? 'season'
                     : (_tableHasColumn($pdo, 'race', 'seasonId') ? 'seasonId' : null);
    $raceCircuitCol  = _tableHasColumn($pdo, 'race', 'circuit')  ? 'circuit'
                     : (_tableHasColumn($pdo, 'race', 'circuitId')? 'circuitId' : null);
    if ($raceSeasonCol === null) {
        throw new RuntimeException("Impossible de déterminer la colonne de saison sur `race`.");
    }

    // Joins optionnels (circuit + ville si dispo)
    $joinCircuit   = $raceCircuitCol
        ? "LEFT JOIN `circuit` c ON c.`circuitId` = rc.`$raceCircuitCol`"
        : "";
    $selectCircuit = $raceCircuitCol ? "c.`nameCircuit` AS circuitName" : "NULL AS circuitName";

    // Détection city/cityId sur circuit
    $circuitCityCol = $raceCircuitCol
        ? (_tableHasColumn($pdo, 'circuit', 'city') ? 'city'
          : (_tableHasColumn($pdo, 'circuit', 'cityId') ? 'cityId' : null))
        : null;

    $joinCity   = ($raceCircuitCol && $circuitCityCol)
        ? "LEFT JOIN `city` ci ON ci.`cityId` = c.`$circuitCityCol`"
        : "";
    $selectCity = ($raceCircuitCol && $circuitCityCol) ? "ci.`name` AS cityName" : "NULL AS cityName";

    // Filtre résultats SQL (évite les soucis de empty('0') côté PHP)
    $andHasResults = $onlyWithResults ? "AND EXISTS(SELECT 1 FROM `resultat` r WHERE r.`race` = rc.`raceId`)" : "";

    $sql = "
        SELECT
            rc.`raceId`,
            rc.`date`,
            $selectCircuit,
            $selectCity,
            EXISTS(SELECT 1 FROM `resultat` r WHERE r.`race` = rc.`raceId`) AS hasResults
        FROM `race` rc
        $joinCircuit
        $joinCity
        WHERE rc.`$raceSeasonCol` = :sid
        $andHasResults
        ORDER BY rc.`date` DESC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':sid' => $seasonId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getPublicRaces(PDO $pdo): array {
    $sql = "
        SELECT
          rc.raceId               AS id,
          rc.date,
          rc.description,
          rc.price_cents,
          rc.capacity_min,
          rc.capacity_max,
          rc.registration_open,
          rc.registration_close,
          rc.season,

          c.nameCircuit           AS circuitName,
          ci.name                 AS cityName,

          (
            SELECT COUNT(*) FROM registration rg
            WHERE rg.race = rc.raceId AND rg.status IN ('waited','valide')
          ) AS regCount
        FROM race rc
        JOIN circuit c ON c.circuitId = rc.circuit
        LEFT JOIN address a ON a.addressId = c.address
        LEFT JOIN city    ci ON ci.cityId = a.city
        ORDER BY rc.date ASC
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getRacePublicById(PDO $pdo, int $raceId): ?array {
    $st = $pdo->prepare("
        SELECT
          r.raceId                 AS id,
          r.date,
          r.description,
          r.price_cents,
          r.capacity_min,
          r.capacity_max,
          r.registration_open,
          r.registration_close,
          s.seasonId,
          s.year                  AS seasonYear,
          c.circuitId,
          c.nameCircuit           AS circuitName,
          ci.name                 AS cityName,
          (
            SELECT COUNT(*)
            FROM registration g
            WHERE g.race = r.raceId
              AND g.status IN ('waited','valide')
          ) AS regCount
        FROM race r
        LEFT JOIN season  s  ON s.seasonId   = r.season
        LEFT JOIN circuit c  ON c.circuitId  = r.circuit
        LEFT JOIN address a  ON a.addressId  = c.address
        LEFT JOIN city    ci ON ci.cityId    = a.city
        WHERE r.raceId = :id
        LIMIT 1
    ");
    $st->execute([':id'=>$raceId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getRaceRegistrationCount(PDO $pdo, int $raceId, array $statuses = ['waited','valide']): int {
    if (empty($statuses)) $statuses = ['waited','valide'];
    $in = implode(",", array_fill(0, count($statuses), "?"));
    $params = $statuses;
    array_unshift($params, $raceId);
    $sql = "SELECT COUNT(*) FROM registration WHERE race = ? AND status IN ($in)";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
}

function userHasRegistration(PDO $pdo, int $raceId, int $userId): bool {
    $st = $pdo->prepare("
        SELECT 1
        FROM registration
        WHERE race = :r AND user = :u
          AND status IN ('waited','valide')
        LIMIT 1
    ");
    $st->execute([':r'=>$raceId, ':u'=>$userId]);
    return (bool)$st->fetchColumn();
}

function createRaceRegistration(PDO $pdo, int $raceId, int $userId): int {
    // status par défaut = 'waited'
    $st = $pdo->prepare("
        INSERT INTO registration (race, user, status, date)
        VALUES (:r, :u, 'waited', NOW())
    ");
    $st->execute([':r'=>$raceId, ':u'=>$userId]);
    return (int)$pdo->lastInsertId();
}

/** Renvoie l’état d’inscription: early|open|closed selon dates & capacité */
function computeRegistrationState(array $r): string {
    $now  = new DateTime('now');
    $open = !empty($r['registration_open'])  ? new DateTime($r['registration_open'])  : null;
    $close= !empty($r['registration_close']) ? new DateTime($r['registration_close']) : null;

    if ($open && $now < $open) return 'early';
    if ($close && $now > $close) return 'closed';
    return 'open';
}

function createOrRequestRegistration(PDO $pdo, int $raceId, int $userId): string {
    // Si déjà en attente/accepté => rien à faire
    $st = $pdo->prepare("
        SELECT registrationId, status
        FROM registration
        WHERE race = :r AND user = :u
        ORDER BY date DESC
        LIMIT 1
    ");
    $st->execute([':r'=>$raceId, ':u'=>$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if ($row['status'] === 'no-valide') {
            // refusé précédemment => on redemande
            $up = $pdo->prepare("UPDATE registration
                                 SET status='waited', date=NOW()
                                 WHERE registrationId = :id");
            $up->execute([':id' => (int)$row['registrationId']]);
            return 'reactivated'; // redemande envoyée
        }
        // déjà “waited” ou “valide”
        return 'exists';
    }

    // Sinon on crée une nouvelle demande
    $ins = $pdo->prepare("
        INSERT INTO registration (user, race, status, date)
        VALUES (:u, :r, 'waited', NOW())
    ");
    $ins->execute([':u'=>$userId, ':r'=>$raceId]);
    return 'created';
}

function getEligibleSeasonPilotsForRace(PDO $pdo, int $seasonId, int $raceId): array {
    $sql = "
      SELECT u.userId AS id, u.firstName, u.lastName, u.numero, u.role
      FROM ranking r
      JOIN user u ON u.userId = r.pilot
      WHERE r.season = :s
        AND NOT EXISTS (
            SELECT 1 FROM registration rg
            WHERE rg.user = u.userId
              AND rg.race = :r
              AND rg.status IN ('waited','valide')
        )
      ORDER BY u.lastName ASC, u.firstName ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':s'=>$seasonId, ':r'=>$raceId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function adminAddDirectRegistration(PDO $pdo, int $raceId, int $userId): void {
    $st = $pdo->prepare("SELECT registrationId, status FROM registration WHERE race = :r AND user = :u ORDER BY date DESC LIMIT 1");
    $st->execute([':r'=>$raceId, ':u'=>$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $up = $pdo->prepare("UPDATE registration SET status='valide', date=NOW() WHERE registrationId = :id");
        $up->execute([':id' => (int)$row['registrationId']]);
        return;
    }
    $ins = $pdo->prepare("INSERT INTO registration (race, user, status, date) VALUES (:r, :u, 'valide', NOW())");
    $ins->execute([':r'=>$raceId, ':u'=>$userId]);
}

function getLatestRacesPublic(PDO $pdo, int $limit = 5, ?int $excludeRaceId = null): array {
    $sql = "
        SELECT
            rc.raceId,
            rc.date,
            c.nameCircuit AS circuitName,
            ci.name       AS cityName,
            EXISTS(SELECT 1 FROM resultat r WHERE r.race = rc.raceId) AS hasResults
        FROM race rc
        LEFT JOIN circuit c ON c.circuitId = rc.circuit
        LEFT JOIN address a ON a.addressId = c.address
        LEFT JOIN city    ci ON ci.cityId  = a.city
        /** exclusion éventuelle de la course courante **/
        WHERE (:ex IS NULL OR rc.raceId <> :ex)
        ORDER BY rc.date DESC
        LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $ex = $excludeRaceId !== null ? (int)$excludeRaceId : null;
    $st->bindValue(':ex', $ex, $ex === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $st->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
