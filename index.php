<?php
    session_start();

    function flash_set(string $key, string $msg): void {
        $_SESSION['flash'][$key] = $msg;
    }

    function flash_take(string $key): ?string {
        if (empty($_SESSION['flash'][$key])) return null;
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    require_once "Config/databaseConnexion.php";
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
    require_once "Controllers/userController.php";

?>
</body>
</html>
