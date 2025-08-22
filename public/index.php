<?php
session_start();
ob_start(); // ✅ permet les header() même si du HTML est émis

function session_user_hydrate(PDO $pdo, int $userId): bool {
    $st = $pdo->prepare("
        SELECT userId, firstName, lastName, email, role, picture
        FROM user
        WHERE userId = :id
        LIMIT 1
    ");
    $st->execute([':id' => $userId]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u) return false;

    $_SESSION['user'] = [
        'id'        => (int)$u['userId'],
        'firstName' => $u['firstName'],
        'lastName'  => $u['lastName'],
        'email'     => $u['email'],
        'role'      => (int)$u['role'],
        'picture'   => $u['picture'] ?: 'default.png',
    ];
    $_SESSION['_last_user_sync'] = time();

    // debug uniquement au besoin :
    // if (!empty($_GET['debug'])) var_dump($_SESSION['user']);

    return true;
}

function session_user_refresh(PDO $pdo, int $ttl = 0): void {
    if (empty($_SESSION['user']['id'])) return;

    $needs = !isset($_SESSION['_last_user_sync']) || (time() - (int)$_SESSION['_last_user_sync']) > $ttl;

    if ($ttl === 0 || $needs) {
        $ok = session_user_hydrate($pdo, (int)$_SESSION['user']['id']);
        if (!$ok) {
            if (function_exists('clear_remember_cookie')) clear_remember_cookie();
            $_SESSION = [];
            session_destroy();
            header("Location: /login");
            exit;
        }
    }
}

function flash_set(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}
function flash_take(string $key): ?string {
    if (empty($_SESSION['flash'][$key])) return null;
    $msg = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $msg;
}

require_once "../app/Config/databaseConnexion.php";

/* ✅ on rafraîchit la session user à CHAQUE requête, sans changer ta structure */
if (!empty($_SESSION['user']['id'])) {
    session_user_refresh($pdo, 0); // 0 = toujours; mets 60 pour 1x/min si tu préfères
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karting EBISU</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>

<?php
    // on garde tes contrôleurs ici
    require_once "../app/Controllers/pollController.php";
    require_once "../app/Controllers/userController.php";
    require_once "../app/Controllers/raceController.php";

    ob_end_flush(); // ✅ on envoie tout à la fin (headers OK jusque-là)
?>
</body>
</html>
