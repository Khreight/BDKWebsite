<?php
// Controllers/pollController.php
require_once __DIR__ . '/../Model/pollModel.php';
require_once __DIR__ . "/../Functions/auth.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

/* ===== Helpers ===== */
function ensure_admin_or_redirect_polls() {
    if (empty($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
        header("Location: /"); exit;
    }
}
function ensure_pilot_or_admin_or_redirect_polls() {
    if (empty($_SESSION['user'])) { header("Location: /login"); exit; }
    $role = (int)$_SESSION['user']['role'];
    if (!in_array($role, [1,2], true)) { header("Location: /"); exit; }
}
function csrf_poll(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function parse_dt_local(?string $v): ?string {
    $v = trim((string)$v);
    if ($v === "") return null;
    $v = str_replace('T', ' ', $v);
    $dt = DateTime::createFromFormat('Y-m-d H:i', $v);
    if (!$dt) return null;
    return $dt->format('Y-m-d H:i:s');
}
function getSimpleCircuits(PDO $pdo): array {
    $st = $pdo->query("SELECT circuitId AS id, nameCircuit FROM circuit ORDER BY nameCircuit");
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
/** Sauvegarde upload vidéo de sondage */
function saveUploadedPollVideo(array $file): ?string {
    if (empty($file['tmp_name']) || (int)$file['error'] !== UPLOAD_ERR_OK) return null;
    $extOk = ['mp4','webm','mov','qt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extOk, true)) throw new RuntimeException("Format vidéo invalide (mp4/webm/mov).");
    $dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . "/Uploads/polls";
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = "poll_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $dest = $dir . "/" . $name;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Échec upload vidéo.");
    }
    return "/Uploads/polls/" . $name;
}

/* ====================== ROUTES ====================== */
switch ($uri) {

    /* ===== ADMIN: Liste ===== */
    case "/admin/polls":
        ensure_admin_or_redirect_polls();
        $csrf  = csrf_poll();
        $polls = getAllPolls($pdo); // avec total des votes
        require_once __DIR__ . "/../Views/admin/polls/index.php";
        break;

    /* ===== ADMIN: Formulaire création ===== */
    case "/admin/polls/new":
        ensure_admin_or_redirect_polls();
        $csrf     = csrf_poll();
        $mode     = 'create';
        $poll     = [];
        $options  = [];
        $circuits = getSimpleCircuits($pdo);
        require_once __DIR__ . "/../Views/admin/polls/form.php";
        break;

    /* ===== ADMIN: Création (POST) ===== */
    case "/admin/polls/create":
        ensure_admin_or_redirect_polls();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/polls/new"); break; }
        if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
            flash_set('error', "Jeton CSRF invalide."); header("Location: /admin/polls/new"); break;
        }

        $title        = trim($_POST['titlePoll'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $pollType     = trim($_POST['pollType'] ?? 'text'); // 'date'|'circuit'|'text'|'picture'
        $startDate    = parse_dt_local($_POST['startDate'] ?? null);
        $endDate      = parse_dt_local($_POST['endDate'] ?? null);
        $isManyChoice = !empty($_POST['isManyChoice']) ? 1 : 0;

        $optionsIn = (array)($_POST['options'] ?? []);
        $errors = [];
        if ($title === '') $errors[] = "Le titre est requis.";
        if (!in_array($pollType, ['date','circuit','text','picture'], true)) {
            $errors[] = "Type de sondage invalide.";
        }

        // Normalisation des options
        $normalizedOptions = [];
        foreach ($optionsIn as $opt) {
            $row = ['type'=>$pollType,'date'=>null,'circuit'=>null,'text'=>null,'picture'=>null];
            switch ($pollType) {
                case 'date':
                    $d = parse_dt_local($opt['date'] ?? null);
                    if ($d) { $row['date'] = $d; $normalizedOptions[] = $row; }
                    break;
                case 'circuit':
                    $cid = (int)($opt['circuit'] ?? 0);
                    if ($cid > 0) { $row['circuit'] = $cid; $normalizedOptions[] = $row; }
                    break;
                case 'text':
                    $t = trim($opt['text'] ?? '');
                    if ($t !== '') { $row['text'] = $t; $normalizedOptions[] = $row; }
                    break;
                case 'picture':
                    $u = trim($opt['picture'] ?? '');
                    if ($u !== '') { $row['picture'] = $u; $normalizedOptions[] = $row; }
                    break;
            }
        }
        if (count($normalizedOptions) === 0) $errors[] = "Ajoute au moins une option.";

        // Upload vidéo (optionnel)
        $videoPath = null;
        if (!empty($_FILES['video']) && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
            try { $videoPath = saveUploadedPollVideo($_FILES['video']); }
            catch (Throwable $e) { $errors[] = $e->getMessage(); }
        }

        if ($errors) {
            flash_set('error', implode(' ', $errors));
            $csrf     = csrf_poll();
            $mode     = 'create';
            $poll     = compact('title','description','pollType','startDate','endDate','isManyChoice');
            $options  = $optionsIn;
            $circuits = getSimpleCircuits($pdo);
            require_once __DIR__ . "/../Views/admin/polls/form.php";
            break;
        }

        try {
            $pdo->beginTransaction();
            $pollId = createPoll($pdo, [
                'titlePoll'    => $title,
                'description'  => $description,
                'pollType'     => $pollType,
                'startDate'    => $startDate,
                'endDate'      => $endDate,
                'video'        => $videoPath,
                'isManyChoice' => $isManyChoice,
            ]);
            foreach ($normalizedOptions as $row) createPollOption($pdo, $pollId, $row);
            $pdo->commit();
            flash_set('success', "Sondage créé.");
            header("Location: /admin/polls");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash_set('error', "Erreur: ".$e->getMessage());
            header("Location: /admin/polls/new");
        }
        break;

    default:
        /* ===== ADMIN: Edition ===== */
        if (preg_match('#^/admin/polls/(\d+)/edit$#', $uri, $m)) {
            ensure_admin_or_redirect_polls();
            $pollId = (int)$m[1];
            $poll   = getPollByIdWithOptions($pdo, $pollId);
            if (!$poll) { flash_set('error',"Sondage introuvable."); header("Location:/admin/polls"); exit; }
            $csrf     = csrf_poll();
            $mode     = 'edit';
            $options  = $poll['options'];
            $circuits = getSimpleCircuits($pdo);
            require_once __DIR__ . "/../Views/admin/polls/form.php";
            exit;
        }

        /* ===== ADMIN: Mise à jour ===== */
        if (preg_match('#^/admin/polls/(\d+)/update$#', $uri, $m)) {
            ensure_admin_or_redirect_polls();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/polls"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location:/admin/polls"); exit;
            }
            $pollId = (int)$m[1];

            $title        = trim($_POST['titlePoll'] ?? '');
            $description  = trim($_POST['description'] ?? '');
            $pollType     = trim($_POST['pollType'] ?? 'text');
            $startDate    = parse_dt_local($_POST['startDate'] ?? null);
            $endDate      = parse_dt_local($_POST['endDate'] ?? null);
            $isManyChoice = !empty($_POST['isManyChoice']) ? 1 : 0;

            $optionsIn = (array)($_POST['options'] ?? []);

            $errors = [];
            if ($title === '') $errors[] = "Le titre est requis.";
            if (!in_array($pollType, ['date','circuit','text','picture'], true)) {
                $errors[] = "Type de sondage invalide.";
            }

            // Normaliser options (garde id quand présent)
            $normalizedOptions = [];
            $keepIds = [];
            foreach ($optionsIn as $opt) {
                $id = isset($opt['id']) && ctype_digit((string)$opt['id']) ? (int)$opt['id'] : null;
                $row = ['type'=>$pollType,'date'=>null,'circuit'=>null,'text'=>null,'picture'=>null];
                switch ($pollType) {
                    case 'date':
                        $d = parse_dt_local($opt['date'] ?? null);
                        if ($d) $row['date'] = $d;
                        break;
                    case 'circuit':
                        $cid = (int)($opt['circuit'] ?? 0);
                        if ($cid > 0) $row['circuit'] = $cid;
                        break;
                    case 'text':
                        $t = trim($opt['text'] ?? '');
                        if ($t !== '') $row['text'] = $t;
                        break;
                    case 'picture':
                        $u = trim($opt['picture'] ?? '');
                        if ($u !== '') $row['picture'] = $u;
                        break;
                }
                if ($row['date'] || $row['circuit'] || $row['text'] || $row['picture']) {
                    $row['_id'] = $id;
                    $normalizedOptions[] = $row;
                    if ($id) $keepIds[] = $id;
                }
            }
            if (count($normalizedOptions) === 0) $errors[] = "Ajoute au moins une option.";

            // Upload vidéo (remplacement si fourni)
            $newVideoPath = null;
            if (!empty($_FILES['video']) && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
                try { $newVideoPath = saveUploadedPollVideo($_FILES['video']); }
                catch (Throwable $e) { $errors[] = $e->getMessage(); }
            }

            if ($errors) {
                flash_set('error', implode(' ', $errors));
                header("Location: /admin/polls/{$pollId}/edit"); exit;
            }

            try {
                $pdo->beginTransaction();

                // ancienne vidéo (pour suppression si remplacée)
                $old = getPollById($pdo, $pollId);
                $oldVideo = $old['video'] ?? null;

                updatePoll($pdo, $pollId, [
                    'titlePoll'    => $title,
                    'description'  => $description,
                    'pollType'     => $pollType,
                    'startDate'    => $startDate,
                    'endDate'      => $endDate,
                    'video'        => $newVideoPath ?: $oldVideo,
                    'isManyChoice' => $isManyChoice,
                ]);

                // Supprimer options disparues
                deletePollOptionsNotIn($pdo, $pollId, $keepIds);

                // Upsert options
                foreach ($normalizedOptions as $row) {
                    $id = $row['_id'] ?? null;
                    unset($row['_id']);
                    if ($id) updatePollOption($pdo, $id, $row);
                    else     createPollOption($pdo, $pollId, $row);
                }

                $pdo->commit();

                // Supprimer physiquement l’ancienne vidéo si remplacée
                $starts = function_exists('str_starts_with')
                    ? str_starts_with($oldVideo ?? '', "/Uploads/polls/")
                    : (substr((string)$oldVideo,0,14) === "/Uploads/polls/");
                if ($newVideoPath && $oldVideo && $starts) {
                    $fs = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $oldVideo;
                    if (is_file($fs)) @unlink($fs);
                }

                flash_set('success', "Sondage mis à jour.");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/polls");
            exit;
        }

        /* ===== ADMIN: Suppression ===== */
        if (preg_match('#^/admin/polls/(\d+)/delete$#', $uri, $m)) {
            ensure_admin_or_redirect_polls();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/polls"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location:/admin/polls"); exit;
            }
            try {
                deletePoll($pdo, (int)$m[1]);
                flash_set('success', "Sondage supprimé.");
            } catch (Throwable $e) {
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/polls");
            exit;
        }

        /* ===== ADMIN: Voir les votants ===== */
        if (preg_match('#^/admin/polls/(\d+)/voters$#', $uri, $m)) {
            ensure_admin_or_redirect_polls();
            $pollId = (int)$m[1];
            $poll   = getPollById($pdo, $pollId);
            if (!$poll) { flash_set('error', "Sondage introuvable."); header("Location:/admin/polls"); exit; }
            $groups = getPollVoters($pdo, $pollId);
            require_once __DIR__ . "/../Views/admin/polls/voters.php";
            exit;
        }

        /* ===== PILOTES/ADMIN: page /polls ===== */
        if ($uri === "/polls") {
            ensure_pilot_or_admin_or_redirect_polls();
            $csrf   = csrf_poll();
            $userId = (int)$_SESSION['user']['id'];
            $polls  = getPollCardsForUser($pdo, $userId);

            // Prépare statut (early/open/closed) + formats FR
            $now = new DateTime('now');
            foreach ($polls as &$p) {
                $start = !empty($p['startDate']) ? new DateTime($p['startDate']) : null;
                $end   = !empty($p['endDate'])   ? new DateTime($p['endDate'])   : null;

                $state = 'open';
                if ($start && $now < $start) { $state = 'early'; }
                elseif ($end && $now > $end) { $state = 'closed'; }

                $p['_state']   = $state;                 // early | open | closed
                $p['_startFr'] = $start ? $start->format('d/m/Y H:i') : null;
                $p['_endFr']   = $end   ? $end->format('d/m/Y H:i')   : null;
                $p['canVote']  = ($state === 'open');
            }
            unset($p);

            // Votants sous chaque option
            $allOptIds = [];
            foreach ($polls as $pp) foreach ($pp['options'] as $oo) $allOptIds[] = (int)$oo['id'];
            $votersByOpt = getVotersForOptions($pdo, $allOptIds);

            require_once __DIR__ . "/../Views/polls/index.php";
            exit;
        }
if (preg_match('#^/polls/(\d+)/vote$#', $uri, $m)) {
    ensure_pilot_or_admin_or_redirect_polls();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /polls"); exit; }
    if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
        flash_set('error', "Jeton CSRF invalide."); header("Location: /polls"); exit;
    }
    $pollId = (int)$m[1];
    $userId = (int)$_SESSION['user']['id'];

    $poll = getPollByIdBasic($pdo, $pollId);
    if (!$poll) {
        flash_set('error', "Sondage introuvable.");
        header("Location: /polls"); exit;
    }
    $now   = new DateTime('now');
    $start = !empty($poll['startDate']) ? new DateTime($poll['startDate']) : null;
    $end   = !empty($poll['endDate'])   ? new DateTime($poll['endDate'])   : null;

    if ($start && $now < $start) {
        flash_set('error', "Ce sondage n'est pas encore ouvert.");
        header("Location: /polls"); exit;
    }
    if ($end && $now > $end) {
        flash_set('error', "Ce sondage est clôturé.");
        header("Location: /polls"); exit;
    }

    $isManyChoice = (bool)$poll['isManyChoice'];
    $validOptionIds = getPollOptionIds($pdo, $pollId);

    $selected = (array)($_POST['options'] ?? []);
    $selected = array_values(array_unique(array_map('intval', $selected)));
    $selected = array_values(array_intersect($selected, $validOptionIds));
    if (!$isManyChoice && count($selected) > 1) {
        $selected = array_slice($selected, 0, 1);
    }

    try {
        $pdo->beginTransaction();
        saveUserVotes($pdo, $pollId, $userId, $selected, $isManyChoice);
        $pdo->commit();
        flash_set('success', "Ton vote a bien été enregistré.");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('error', "Erreur lors de l’enregistrement du vote : " . $e->getMessage());
    }
    header("Location: /polls");
    exit;
}
}
