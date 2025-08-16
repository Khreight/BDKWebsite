<?php

function getAllCities(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT cityId AS id, name, country AS country_id
        FROM city
        ORDER BY name
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCityById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("
        SELECT cityId AS id, name, country AS country_id
        FROM city
        WHERE cityId = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getCityMap(PDO $pdo): array {
    $rows = getAllCities($pdo);
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['id']] = [
            'name'       => $r['name'],
            'country_id' => (int)$r['country_id'],
        ];
    }
    return $map;
}

function cityAlreadyExists(PDO $pdo, string $cityName, int $countryId): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM city
        WHERE name = :name AND country = :country
    ");
    $stmt->execute([':name' => $cityName, ':country' => $countryId]);
    return (bool)$stmt->fetchColumn();
}

function createCity(PDO $pdo, string $cityName, int $countryId): int {
    $stmt = $pdo->prepare("
        INSERT INTO city (name, country)
        VALUES (:name, :country)
    ");
    $stmt->execute([':name' => $cityName, ':country' => $countryId]);
    return (int)$pdo->lastInsertId();
}
