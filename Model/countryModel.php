<?php

function getAllCountries(PDO $pdo): array {
    $query = "SELECT countryId, name, flag FROM country ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCountryById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT id, name, iso2 FROM country WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getCountryMap(PDO $pdo): array {
    $rows = getAllCountries($pdo);
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['id']] = ['name' => $r['name'], 'iso2' => $r['iso2']];
    }
    return $map;
}

function flagEmojiFromIso2(?string $iso2): string {
    if (!$iso2 || strlen($iso2) !== 2) return '';
    $iso2 = strtoupper($iso2);
    $codePoints = [
        0x1F1E6 + (ord($iso2[0]) - ord('A')),
        0x1F1E6 + (ord($iso2[1]) - ord('A')),
    ];
    return mb_convert_encoding('&#' . $codePoints[0] . ';', 'UTF-8', 'HTML-ENTITIES')
         . mb_convert_encoding('&#' . $codePoints[1] . ';', 'UTF-8', 'HTML-ENTITIES');
}
