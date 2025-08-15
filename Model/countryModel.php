<?php

    function getAllCountries($pdo) {
        $query = "SELECT * FROM country ORDER BY name";
        $countries = $pdo->prepare($query);
        $countries->execute([]);
        return $countries->fetchAll();
    }
