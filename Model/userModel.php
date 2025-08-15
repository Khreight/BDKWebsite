<?php

function createUser(PDO $pdo, array $old): int {
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
            'city'        => $old['city'],
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
                           WHERE email = ?");
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
