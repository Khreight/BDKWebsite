<?php
// Model/circuitModel.php

function getAllCircuitsWithAddress(PDO $pdo): array {
    $sql = "
      SELECT
        c.circuitId, c.nameCircuit, c.picture,
        a.addressId, a.street AS address_street, a.number AS address_number,
        ci.cityId, ci.name AS city_name,
        co.countryId, co.name AS country_name
      FROM circuit c
      LEFT JOIN address a ON a.addressId = c.address
      LEFT JOIN city    ci ON ci.cityId  = a.city
      LEFT JOIN country co ON co.countryId = ci.country
      ORDER BY c.nameCircuit ASC
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getCircuitByIdWithAddress(PDO $pdo, int $circuitId): ?array {
    $sql = "
      SELECT
        c.circuitId, c.nameCircuit, c.picture, c.address,
        a.addressId, a.street, a.number, a.city AS address_cityId,
        ci.cityId, ci.name AS city_name,
        co.countryId, co.name AS country_name
      FROM circuit c
      LEFT JOIN address a ON a.addressId = c.address
      LEFT JOIN city    ci ON ci.cityId  = a.city
      LEFT JOIN country co ON co.countryId = ci.country
      WHERE c.circuitId = :id
      LIMIT 1
    ";
    $st = $pdo->prepare($sql); $st->execute([':id'=>$circuitId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function createCircuitWithAddress(PDO $pdo, array $data): int {
    // data: nameCircuit, cityId, street|null, number|null, picture|null
    $pdo->beginTransaction();
    try {
        $insA = $pdo->prepare("INSERT INTO address (city, street, number) VALUES (:city, :street, :num)");
        $insA->execute([
            ':city'  => (int)$data['cityId'],
            ':street'=> $data['street'],
            ':num'   => $data['number'],
        ]);
        $addressId = (int)$pdo->lastInsertId();

        $insC = $pdo->prepare("INSERT INTO circuit (nameCircuit, address, picture) VALUES (:n, :a, :p)");
        $insC->execute([
            ':n' => $data['nameCircuit'],
            ':a' => $addressId,
            ':p' => $data['picture'] ?? null,
        ]);
        $circuitId = (int)$pdo->lastInsertId();
        $pdo->commit();
        return $circuitId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function updateCircuitWithAddress(PDO $pdo, int $circuitId, array $data): void {
    // On récupère l'adresse existante pour décider si on update ou on crée
    $cur = getCircuitByIdWithAddress($pdo, $circuitId);
    if (!$cur) throw new RuntimeException("Circuit introuvable.");

    $addressId = $cur['addressId'] ?? null;

    $pdo->beginTransaction();
    try {
        if ($addressId) {
            $upA = $pdo->prepare("UPDATE address SET city=:city, street=:street, number=:num WHERE addressId=:id");
            $upA->execute([
                ':city' => (int)$data['cityId'],
                ':street' => $data['street'],
                ':num'    => $data['number'],
                ':id'     => $addressId
            ]);
        } else {
            $insA = $pdo->prepare("INSERT INTO address (city, street, number) VALUES (:city, :street, :num)");
            $insA->execute([
                ':city' => (int)$data['cityId'],
                ':street' => $data['street'],
                ':num'    => $data['number'],
            ]);
            $addressId = (int)$pdo->lastInsertId();
        }

        $upC = $pdo->prepare("UPDATE circuit SET nameCircuit=:n, picture=:p, address=:a WHERE circuitId=:id");
        $upC->execute([
            ':n' => $data['nameCircuit'],
            ':p' => $data['picture'] ?? null,
            ':a' => $addressId,
            ':id'=> $circuitId
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function deleteCircuit(PDO $pdo, int $circuitId): void {
    // Vérifier si lié à des courses
    $st = $pdo->prepare("SELECT COUNT(*) FROM race WHERE circuit = :c");
    $st->execute([':c'=>$circuitId]);
    if ($st->fetchColumn() > 0) {
        throw new RuntimeException("Ce circuit est lié à des courses existantes.");
    }
    // On peut supprimer le circuit. (Adresse laissée pour éviter suppressions en cascade non voulues)
    $pdo->prepare("DELETE FROM circuit WHERE circuitId = :id")->execute([':id'=>$circuitId]);
}
