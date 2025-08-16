<?php
// Model/racesModel.php

/* =============== SAISONS =============== */
function getAllSeasons(PDO $pdo): array {
    $st = $pdo->query("SELECT seasonId, year FROM season ORDER BY year DESC");
    return $st->fetchAll(PDO::FETCH_ASSOC);
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
        SELECT r.rankingId, r.pilot AS pilotId, r.point,
               u.firstName, u.lastName, u.email
        FROM ranking r
        JOIN user u ON u.userId = r.pilot
        WHERE r.season = :s
        ORDER BY r.point DESC, u.lastName ASC, u.firstName ASC
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
    $sql = "SELECT status, COUNT(*) as c FROM registration WHERE race = :r GROUP BY status";
    $st = $pdo->prepare($sql); $st->execute([':r'=>$raceId]);
    $rows = $st->fetchAll(PDO::FETCH_KEY_PAIR);
    return [
        'no-valide' => (int)($rows['no-valide'] ?? 0),
        'waited'    => (int)($rows['waited']    ?? 0),
        'valide'    => (int)($rows['valide']    ?? 0),
    ];
}
function getRaceRegistrations(PDO $pdo, int $raceId): array {
    $sql = "
      SELECT rg.registrationId, rg.status, rg.date,
             u.userId AS pilotId, u.firstName, u.lastName, u.email
      FROM registration rg
      JOIN user u ON u.userId = rg.user
      WHERE rg.race = :r
      ORDER BY rg.date DESC
    ";
    $st = $pdo->prepare($sql); $st->execute([':r'=>$raceId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
