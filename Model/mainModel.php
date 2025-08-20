<?php
// Model/mainModel.php
// Récupère les données d'accueil.
// NOTE: pas de logique métier “lourde” ici, juste des SELECT clairs et robustes.

if (!function_exists('e')) {
  function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function getHomepageUpcomingRaces(PDO $pdo, int $limit = 6): array {
    $sql = "
      SELECT rc.raceId, rc.date, rc.description,
             rc.registration_open, rc.registration_close,
             rc.price_cents, rc.capacity_min, rc.capacity_max,
             c.circuitId, c.nameCircuit AS circuitName,
             ci.name AS cityName,
             co.name AS countryName,
             s.seasonId, s.year AS seasonYear
      FROM race rc
      LEFT JOIN circuit c ON c.circuitId = rc.circuit
      LEFT JOIN address a ON a.addressId = c.address
      LEFT JOIN city    ci ON ci.cityId = a.city
      LEFT JOIN country co ON co.countryId = ci.country
      LEFT JOIN season  s  ON s.seasonId  = rc.season
      WHERE rc.date >= NOW()
      ORDER BY rc.date ASC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getLatestSeason(PDO $pdo): ?array {
    $st = $pdo->query("SELECT seasonId, year FROM season ORDER BY year DESC LIMIT 1");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getHomepageStandings(PDO $pdo, int $seasonId, int $limit = 10): array {
    $sql = "
      SELECT r.rankingId, r.points,
             u.userId, u.firstName, u.lastName, u.picture, u.numero, u.role
      FROM ranking r
      JOIN user u ON u.userId = r.pilot
      WHERE r.season = :s
      ORDER BY r.points DESC, u.lastName ASC, u.firstName ASC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':s', $seasonId, PDO::PARAM_INT);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getFeaturedDrivers(PDO $pdo, int $seasonId, int $limit = 8): array {
    // On prend les mieux classés de la dernière saison si dispo, sinon fallback pilotes/organisateurs récents.
    $top = getHomepageStandings($pdo, $seasonId, $limit);
    if (!empty($top)) return $top;

    $sql = "
      SELECT u.userId, u.firstName, u.lastName, u.picture, u.numero, u.role
      FROM user u
      WHERE u.role IN (1,2)  -- organisateurs & pilotes
      ORDER BY u.created_at DESC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getHomepageCounters(PDO $pdo): array {
    $drivers = (int)$pdo->query("SELECT COUNT(*) FROM user WHERE role IN (1,2)")->fetchColumn();
    $circuits = (int)$pdo->query("SELECT COUNT(*) FROM circuit")->fetchColumn();
    $racesTotal = (int)$pdo->query("SELECT COUNT(*) FROM race")->fetchColumn();
    $seasons = (int)$pdo->query("SELECT COUNT(*) FROM season")->fetchColumn();
    $pastRaces = (int)$pdo->query("SELECT COUNT(*) FROM race WHERE date < NOW()")->fetchColumn();

    return [
        'drivers'   => $drivers,
        'circuits'  => $circuits,
        'races'     => $racesTotal,
        'seasons'   => $seasons,
        'pastRaces' => $pastRaces,
    ];
}

function getHomepageLatestVideos(PDO $pdo, int $limit = 3): array {
    $sql = "
      SELECT raceId, date, video,
             c.nameCircuit AS circuitName,
             s.year AS seasonYear
      FROM race rc
      LEFT JOIN circuit c ON c.circuitId = rc.circuit
      LEFT JOIN season  s ON s.seasonId  = rc.season
      WHERE rc.video IS NOT NULL AND rc.video <> ''
      ORDER BY rc.date DESC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getHomepageCircuits(PDO $pdo, int $limit = 12): array {
    $sql = "
      SELECT c.circuitId, c.nameCircuit, c.picture,
             ci.name AS cityName, co.name AS countryName
      FROM circuit c
      LEFT JOIN address a ON a.addressId = c.address
      LEFT JOIN city    ci ON ci.cityId  = a.city
      LEFT JOIN country co ON co.countryId = ci.country
      ORDER BY c.nameCircuit ASC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Dernières courses avec vainqueur calculé (somme des lapTime par pilote).
 * Retourne raceId, date, circuitName, seasonYear, winnerName, winnerId.
 */
function getHomepageRecentWinners(PDO $pdo, int $limit = 3): array {
    $sql = "
      SELECT rMain.raceId, rMain.date, c.nameCircuit AS circuitName, s.year AS seasonYear,
             u.userId AS winnerId, CONCAT(u.firstName, ' ', u.lastName) AS winnerName
      FROM (
          SELECT rc.raceId, rc.date,
                 (
                   SELECT r2.pilot
                   FROM resultat r2
                   JOIN lap l2 ON l2.resultat = r2.resultatId
                   WHERE r2.race = rc.raceId
                   GROUP BY r2.pilot
                   ORDER BY SUM(l2.lapTime) ASC
                   LIMIT 1
                 ) AS winnerPilot
          FROM race rc
          WHERE rc.date < NOW()
          ORDER BY rc.date DESC
          LIMIT :lim
      ) rMain
      LEFT JOIN user u ON u.userId = rMain.winnerPilot
      LEFT JOIN race rc ON rc.raceId = rMain.raceId
      LEFT JOIN circuit c ON c.circuitId = rc.circuit
      LEFT JOIN season  s ON s.seasonId  = rc.season
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
