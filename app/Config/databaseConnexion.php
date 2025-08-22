<?php
    try {
        $strConnection = "mysql:host=localhost;dbname=bdk;port=3306";
        $pdo = new PDO($strConnection, "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]);
    } catch (PDOException $e) {
        $msg = 'ERREUR PDO dans ' .  $e->getMessage();
        die($msg);
    }
