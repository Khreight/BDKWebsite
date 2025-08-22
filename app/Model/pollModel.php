<?php

function getAllPolls(PDO $pdo): array {
    $sql = "
        SELECT
          p.pollId      AS id,
          p.titlePoll,
          p.description,
          p.pollType,
          p.startDate,
          p.endDate,
          p.video,
          p.pollDate,
          COALESCE(p.isManyChoice, p.isManyChoice, 0) AS isManyChoice,
          (SELECT COUNT(*) FROM pollVote v WHERE v.poll = p.pollId) AS votes
        FROM poll p
        ORDER BY COALESCE(p.startDate, p.endDate, p.pollDate) DESC, p.pollId DESC
    ";
    $st = $pdo->query($sql);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function getPollById(PDO $pdo, int $pollId): ?array {
    $st = $pdo->prepare("
        SELECT p.*, COALESCE(p.isManyChoice, p.isManyChoice, 0) AS isManyChoice
        FROM poll p WHERE p.pollId = :id LIMIT 1
    ");
    $st->execute([':id' => $pollId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getPollByIdWithOptions(PDO $pdo, int $pollId): ?array {
    $poll = getPollById($pdo, $pollId);
    if (!$poll) return null;

    $opts = $pdo->prepare("
        SELECT pollOptionsId AS id, poll, proposedDate, proposedCircuit, proposedText, proposedPicture
        FROM pollOptions
        WHERE poll = :p
        ORDER BY pollOptionsId ASC
    ");
    $opts->execute([':p' => $pollId]);
    $options = $opts->fetchAll(PDO::FETCH_ASSOC);

    return [
        'id'           => (int)$poll['pollId'],
        'titlePoll'    => $poll['titlePoll'],
        'description'  => $poll['description'],
        'pollType'     => $poll['pollType'],
        'startDate'    => $poll['startDate'],
        'endDate'      => $poll['endDate'],
        'video'        => $poll['video'],
        'pollDate'     => $poll['pollDate'],
        'isManyChoice' => (int)$poll['isManyChoice'],
        'options'      => $options,
    ];
}

function createPoll(PDO $pdo, array $data): int {
    $sql = "INSERT INTO poll (titlePoll, description, pollType, startDate, endDate, video, pollDate, isManyChoice)
            VALUES (:t, :d, :pt, :sd, :ed, :v, NOW(), :mc)";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':t'  => $data['titlePoll'],
        ':d'  => $data['description'] ?? null,
        ':pt' => $data['pollType'],
        ':sd' => $data['startDate'] ?? null,
        ':ed' => $data['endDate'] ?? null,
        ':v'  => $data['video'] ?? null,
        ':mc' => !empty($data['isManyChoice']) ? 1 : 0,
    ]);
    return (int)$pdo->lastInsertId();
}

function updatePoll(PDO $pdo, int $pollId, array $data): void {
    $sql = "UPDATE poll
            SET titlePoll=:t, description=:d, pollType=:pt, startDate=:sd, endDate=:ed, video=:v, isManyChoice=:mc
            WHERE pollId=:id";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':t'  => $data['titlePoll'],
        ':d'  => $data['description'] ?? null,
        ':pt' => $data['pollType'],
        ':sd' => $data['startDate'] ?? null,
        ':ed' => $data['endDate'] ?? null,
        ':v'  => $data['video'] ?? null,
        ':mc' => !empty($data['isManyChoice']) ? 1 : 0,
        ':id' => $pollId
    ]);
}

function deletePoll(PDO $pdo, int $pollId): void {
    // récupérer vidéo pour suppression disque
    $poll = getPollById($pdo, $pollId);
    $video = $poll['video'] ?? null;

    // votes -> options -> poll
    $pdo->prepare("DELETE FROM pollVote WHERE optionChose IN (SELECT pollOptionsId FROM pollOptions WHERE poll=:p)")
        ->execute([':p'=>$pollId]);
    $pdo->prepare("DELETE FROM pollOptions WHERE poll = :p")->execute([':p'=>$pollId]);
    $pdo->prepare("DELETE FROM poll WHERE pollId = :p")->execute([':p'=>$pollId]);

    $starts = function_exists('str_starts_with') ? str_starts_with($video ?? '', "/Uploads/polls/") : (substr((string)$video,0,14)==="/Uploads/polls/");
    if ($video && $starts) {
        $fs = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $video;
        if (is_file($fs)) @unlink($fs);
    }
}

function createPollOption(PDO $pdo, int $pollId, array $row): int {
    $sql = "INSERT INTO pollOptions (poll, proposedDate, proposedCircuit, proposedText, proposedPicture)
            VALUES (:p, :d, :c, :t, :pi)";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':p'  => $pollId,
        ':d'  => $row['date'] ?? null,
        ':c'  => $row['circuit'] ?? null,
        ':t'  => $row['text'] ?? null,
        ':pi' => $row['picture'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

function updatePollOption(PDO $pdo, int $optionId, array $row): void {
    $sql = "UPDATE pollOptions
            SET proposedDate=:d, proposedCircuit=:c, proposedText=:t, proposedPicture=:pi
            WHERE pollOptionsId = :id";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':d'  => $row['date'] ?? null,
        ':c'  => $row['circuit'] ?? null,
        ':t'  => $row['text'] ?? null,
        ':pi' => $row['picture'] ?? null,
        ':id' => $optionId,
    ]);
}

function deletePollOptionsNotIn(PDO $pdo, int $pollId, array $keepIds): void {
    if (empty($keepIds)) {
        $pdo->prepare("DELETE FROM pollVote WHERE optionChose IN (SELECT pollOptionsId FROM pollOptions WHERE poll=:p)")
            ->execute([':p'=>$pollId]);
        $pdo->prepare("DELETE FROM pollOptions WHERE poll=:p")->execute([':p'=>$pollId]);
        return;
    }
    $in = implode(',', array_map('intval', $keepIds));
    $pdo->prepare("
        DELETE FROM pollVote
        WHERE optionChose IN (
            SELECT pollOptionsId FROM pollOptions WHERE poll=:p AND pollOptionsId NOT IN ($in)
        )
    ")->execute([':p'=>$pollId]);
    $pdo->prepare("DELETE FROM pollOptions WHERE poll=:p AND pollOptionsId NOT IN ($in)")
        ->execute([':p'=>$pollId]);
}

/** ====== PARTIE VOTE (pilotes/admin) ====== */

function getAllPollsForVoting(PDO $pdo, int $userId): array {
    // 1) Sondages
    $pollRows = $pdo->query("
        SELECT
          p.pollId,
          p.titlePoll,
          p.description,
          p.pollType,
          p.video,
          COALESCE(p.isManyChoice, p.isManyChoice, 0) AS isManyChoice
        FROM poll p
        ORDER BY p.pollId DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!$pollRows) return [];

    $pollIds = array_map(fn($r) => (int)$r['pollId'], $pollRows);
    $in = implode(',', array_fill(0, count($pollIds), '?'));

    // 2) Options
    $stOpt = $pdo->prepare("
        SELECT
          o.pollOptionsId AS id,
          o.poll          AS pollId,
          o.proposedDate,
          o.proposedCircuit,
          o.proposedText,
          o.proposedPicture,
          c.nameCircuit   AS circuitName
        FROM pollOptions o
        LEFT JOIN circuit c ON c.circuitId = o.proposedCircuit
        WHERE o.poll IN ($in)
        ORDER BY o.pollOptionsId ASC
    ");
    $stOpt->execute($pollIds);
    $optRows = $stOpt->fetchAll(PDO::FETCH_ASSOC);

    $optionsByPoll = [];
    foreach ($optRows as $r) {
        $pid = (int)$r['pollId'];
        if (!isset($optionsByPoll[$pid])) $optionsByPoll[$pid] = [];
        $optionsByPoll[$pid][] = $r;
    }

    // 3) Votes + votants
    $stVote = $pdo->prepare("
        SELECT v.poll, v.optionChose, v.driver,
               u.firstName, u.lastName
        FROM pollVote v
        JOIN user u ON u.userId = v.driver
        WHERE v.poll IN ($in)
        ORDER BY u.lastName ASC, u.firstName ASC
    ");
    $stVote->execute($pollIds);
    $voteRows = $stVote->fetchAll(PDO::FETCH_ASSOC);

    $votersByPoll = [];
    $userVotesByPoll = [];
    foreach ($voteRows as $v) {
        $pid = (int)$v['poll'];
        if (!isset($votersByPoll[$pid])) $votersByPoll[$pid] = [];
        $votersByPoll[$pid][(int)$v['driver']] = [
            'driver'    => (int)$v['driver'],
            'firstName' => $v['firstName'] ?? '',
            'lastName'  => $v['lastName'] ?? ''
        ];
        if ((int)$v['driver'] === $userId) {
            if (!isset($userVotesByPoll[$pid])) $userVotesByPoll[$pid] = [];
            $userVotesByPoll[$pid][] = (int)$v['optionChose'];
        }
    }
    foreach ($votersByPoll as $pid => $map) {
        $votersByPoll[$pid] = array_values($map);
    }

    $out = [];
    foreach ($pollRows as $p) {
        $pid = (int)$p['pollId'];
        $opts = $optionsByPoll[$pid] ?? [];
        $voters = $votersByPoll[$pid] ?? [];
        $userVotes = $userVotesByPoll[$pid] ?? [];
        $hasVoted = !empty($userVotes);

        $out[] = [
            'pollId'       => $pid,
            'title'        => $p['titlePoll'],
            'description'  => $p['description'],
            'type'         => $p['pollType'],
            'video'        => $p['video'],
            'isManyChoice' => (bool)$p['isManyChoice'],
            'options'      => $opts,
            'voters'       => $voters,
            'userVotes'    => $userVotes,
            'hasVoted'     => $hasVoted,
            'votesCount'   => count($voters),
        ];
    }
    return $out;
}

function getPollByIdBasic(PDO $pdo, int $pollId): ?array {
    $st = $pdo->prepare("
        SELECT pollId AS id, isManyChoice, startDate, endDate
        FROM poll
        WHERE pollId = :id
        LIMIT 1
    ");
    $st->execute([':id' => $pollId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getPollOptionIds(PDO $pdo, int $pollId): array {
    $st = $pdo->prepare("SELECT pollOptionsId FROM pollOptions WHERE poll = :p");
    $st->execute([':p' => $pollId]);
    return array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'pollOptionsId'));
}

/** Remplace les votes de l’utilisateur pour ce sondage par la nouvelle sélection */
function saveUserVotes(PDO $pdo, int $pollId, int $userId, array $optionIds, bool $isMany): void {
    // sécurité : valider options -> déjà fait côté controller, on supprime et on réinsère
    $del = $pdo->prepare("DELETE FROM pollVote WHERE poll = :p AND driver = :u");
    $del->execute([':p'=>$pollId, ':u'=>$userId]);

    if (empty($optionIds)) return;

    if (!$isMany && count($optionIds) > 1) {
        $optionIds = [ $optionIds[0] ];
    }

    $ins = $pdo->prepare("INSERT INTO pollVote (poll, optionChose, driver) VALUES (:p, :o, :u)");
    foreach ($optionIds as $oid) {
        $ins->execute([':p'=>$pollId, ':o'=>$oid, ':u'=>$userId]);
    }
}

/** Liste des votants groupés par option. */
function getPollVoters(PDO $pdo, int $pollId): array {
    $sql = "
        SELECT
          o.pollOptionsId   AS optionId,
          o.proposedDate,
          o.proposedCircuit,
          o.proposedText,
          o.proposedPicture,
          u.userId          AS userId,
          u.firstName,
          u.lastName,
          u.numero,
          u.role
        FROM pollOptions o
        LEFT JOIN pollVote v ON v.optionChose = o.pollOptionsId
        LEFT JOIN user u     ON u.userId = v.driver
        WHERE o.poll = :p
        ORDER BY o.pollOptionsId ASC, u.lastName ASC, u.firstName ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':p'=>$pollId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $byOption = [];
    foreach ($rows as $r) {
        $oid = (int)$r['optionId'];
        if (!isset($byOption[$oid])) $byOption[$oid] = ['option'=>$r,'voters'=>[]];
        if (!empty($r['userId'])) {
            $byOption[$oid]['voters'][] = [
                'id'        => (int)$r['userId'],
                'firstName' => $r['firstName'],
                'lastName'  => $r['lastName'],
                'numero'    => $r['numero'],
                'role'      => (int)$r['role'],
            ];
        }
    }
    return $byOption;
}


function getPollCardsForUser(PDO $pdo, int $userId): array {
    // Sondages
    $polls = $pdo->query("
        SELECT
          p.pollId            AS id,
          p.titlePoll,
          p.description,
          p.pollType,
          p.startDate,
          p.endDate,
          p.video,
          p.isManyChoice,
          (SELECT COUNT(*) FROM pollVote v WHERE v.poll = p.pollId) AS totalVotes
        FROM poll p
        ORDER BY COALESCE(p.startDate, p.endDate, p.pollDate) DESC, p.pollId DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!$polls) return [];

    // Options + label circuit si besoin
    $pollIds = implode(',', array_map('intval', array_column($polls, 'id')));
    $sqlOpt = "
        SELECT
          o.pollOptionsId AS id,
          o.poll,
          o.proposedDate,
          o.proposedCircuit,
          o.proposedText,
          o.proposedPicture,
          c.nameCircuit AS circuitName
        FROM pollOptions o
        LEFT JOIN circuit c ON c.circuitId = o.proposedCircuit
        WHERE o.poll IN ($pollIds)
        ORDER BY o.poll, o.pollOptionsId
    ";
    $opts = $pdo->query($sqlOpt)->fetchAll(PDO::FETCH_ASSOC);

    // Votes de l'utilisateur pour marquer ses choix
    $sqlVotes = "
        SELECT v.optionChose AS optionId
        FROM pollVote v
        WHERE v.driver = :u AND v.poll IN ($pollIds)
    ";
    $st = $pdo->prepare($sqlVotes);
    $st->execute([':u' => $userId]);
    $myOptIds = array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'optionId'));
    $mySet    = array_fill_keys($myOptIds, true);

    // grouper
    $byPoll = [];
    foreach ($opts as $o) {
        $pid = (int)$o['poll'];
        if (!isset($byPoll[$pid])) $byPoll[$pid] = [];
        $o['checkedByUser'] = isset($mySet[(int)$o['id']]);
        $byPoll[$pid][] = $o;
    }

    // attacher aux polls
    foreach ($polls as &$p) {
        $p['options'] = $byPoll[(int)$p['id']] ?? [];
    }
    unset($p);

    return $polls;
}

function normalizePollOptionIds(PDO $pdo, int $pollId, array $optionIds): array {
    if (!$optionIds) return [];
    $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
    $in = implode(',', array_fill(0, count($optionIds), '?'));
    $sql = "SELECT pollOptionsId FROM pollOptions WHERE poll = ? AND pollOptionsId IN ($in)";
    $st  = $pdo->prepare($sql);
    $params = array_merge([$pollId], $optionIds);
    $st->execute($params);
    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN, 0));
}

/** Liste des votants (si tu veux afficher côté /polls également) */
function getVotersForOptions(PDO $pdo, array $optionIds): array {
    if (empty($optionIds)) return [];
    $in = implode(',', array_map('intval', $optionIds));
    $sql = "
        SELECT
          v.optionChose AS optionId,
          u.userId, u.firstName, u.lastName, u.numero
        FROM pollVote v
        JOIN user u ON u.userId = v.driver
        WHERE v.optionChose IN ($in)
        ORDER BY u.lastName, u.firstName
    ";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $oid = (int)$r['optionId'];
        if (!isset($out[$oid])) $out[$oid] = [];
        $out[$oid][] = $r;
    }
    return $out;
}
