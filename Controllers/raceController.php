<?php
// Controllers/raceController.php
// ❗ Version sans switch(true) — on utilise switch($uri) + bloc regex dans default

require_once "Model/racesModel.php";
require_once "Model/circuitModel.php";
require_once "Model/countryModel.php";
require_once "Model/cityModel.php";
require_once "Model/userModel.php";
require_once "Functions/auth.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

/* ---------- Helpers ---------- */
function ensure_admin_or_redirect() {
    if (empty($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
        header("Location: /"); exit;
    }
}
function ensure_csrf(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
/** Trouve (ou crée) une ville par (name,countryId) et retourne cityId */
function ensureCityId(PDO $pdo, string $cityName, int $countryId): int {
    $cityName = trim($cityName);
    if ($cityName === '' || $countryId <= 0) throw new RuntimeException("Ville/Pays requis.");
    $st = $pdo->prepare("SELECT cityId FROM city WHERE name = :n AND country = :c LIMIT 1");
    $st->execute([':n' => $cityName, ':c' => $countryId]);
    $id = $st->fetchColumn();
    if ($id) return (int)$id;
    $ins = $pdo->prepare("INSERT INTO city (name, zip, country) VALUES (:n, NULL, :c)");
    $ins->execute([':n' => $cityName, ':c' => $countryId]);
    return (int)$pdo->lastInsertId();
}
/** Sauvegarde d’un fichier vidéo uploadé, retourne le chemin public ou null si rien */
function saveUploadedVideo(array $file): ?string {
    if (empty($file['tmp_name']) || (int)$file['error'] !== UPLOAD_ERR_OK) return null;
    $extOk = ['mp4','webm','mov','qt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extOk, true)) throw new RuntimeException("Format vidéo invalide.");
    $dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . "/Uploads/videos";
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = "race_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $dest = $dir . "/" . $name;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Échec upload vidéo.");
    }
    return "/Uploads/videos/" . $name;
}

/* =======================================================
   ROUTES (statiques via switch($uri))
   ======================================================= */
switch ($uri) {

    /* ===================== DASHBOARD ==================== */
    case "/dashboard-races":
        ensure_admin_or_redirect();
        $circuits  = getAllCircuitsWithAddress($pdo);
        $seasons   = getAllSeasons($pdo);
        $races     = getAllRacesWithJoins($pdo);
        require_once "Views/admin/dashboard-races.php";
        break;

    /* ====================== CIRCUITS ==================== */
    case "/admin/circuits/new":
        ensure_admin_or_redirect();
        $csrf = ensure_csrf();
        $countries = getAllCountries($pdo);
        $circuit = []; $mode='create';
        require_once "Views/admin/circuit-form.php";
        break;

    case "/admin/circuits/create":
        ensure_admin_or_redirect();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/circuits/new"); break; }
        if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
            flash_set('error', "Jeton CSRF invalide."); header("Location: /admin/circuits/new"); break;
        }
        try {
            $nameCircuit = trim($_POST['nameCircuit'] ?? '');
            $countryId   = (int)($_POST['countryId'] ?? 0);
            $cityName    = trim($_POST['cityName'] ?? '');
            $street      = trim($_POST['street'] ?? '');
            $number      = ($_POST['number'] === '' ? null : (int)$_POST['number']);
            $picture     = trim($_POST['picture'] ?? '');

            if ($nameCircuit === '' || $countryId <= 0 || $cityName === '') {
                throw new RuntimeException("Nom, pays et ville sont requis.");
            }
            $cityId    = ensureCityId($pdo, $cityName, $countryId);
            $circuitId = createCircuitWithAddress($pdo, [
                'nameCircuit' => $nameCircuit,
                'cityId'      => $cityId,
                'street'      => $street ?: null,
                'number'      => $number,
                'picture'     => $picture ?: null,
            ]);
            flash_set('success', "Circuit créé (#$circuitId).");
        } catch (Throwable $e) { flash_set('error', $e->getMessage()); }
        header("Location: /dashboard-races");
        break;

    /* ======================= SAISONS ==================== */
    case "/admin/seasons/new":
        ensure_admin_or_redirect();
        $csrf = ensure_csrf();
        $season = []; $mode='create';
        require_once "Views/admin/season-form.php";
        break;

    case "/admin/seasons/create":
        ensure_admin_or_redirect();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/seasons/new"); break; }
        if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
            flash_set('error', "Jeton CSRF invalide."); header("Location: /admin/seasons/new"); break;
        }
        try {
            $year = (int)($_POST['year'] ?? 0);
            if ($year < 1900 || $year > 2100) throw new RuntimeException("Année invalide.");
            $id = createSeason($pdo, $year);
            flash_set('success', "Saison $year créée (#$id).");
            header("Location: /dashboard-races");
        } catch (Throwable $e) {
            flash_set('error', $e->getMessage());
            header("Location: /admin/seasons/new");
        }
        break;

    /* ======================== RACES ===================== */
    case "/admin/races/new":
        ensure_admin_or_redirect();
        $csrf     = ensure_csrf();
        $circuits = getAllCircuitsWithAddress($pdo);
        $seasons  = getAllSeasons($pdo);
        $race=[]; $mode='create';
        require_once "Views/admin/race-form.php";
        break;

    case "/admin/races/create":
        ensure_admin_or_redirect();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/races/new"); break; }
        if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
            flash_set('error', "Jeton CSRF invalide."); header("Location: /admin/races/new"); break;
        }
        try {
            $payload = [
                'circuitId'          => (int)($_POST['circuitId'] ?? 0),
                'seasonId'           => (int)($_POST['seasonId'] ?? 0),
                'date'               => trim($_POST['date'] ?? ''),
                'description'        => trim($_POST['description'] ?? ''),
                'price_cents'        => ($_POST['price_cents'] === '' ? null : (int)$_POST['price_cents']),
                'capacity_min'       => ($_POST['capacity_min'] === '' ? null : (int)$_POST['capacity_min']),
                'capacity_max'       => ($_POST['capacity_max'] === '' ? null : (int)$_POST['capacity_max']),
                'registration_open'  => trim($_POST['registration_open'] ?? '') ?: null,
                'registration_close' => trim($_POST['registration_close'] ?? '') ?: null,
                'video'              => null,
            ];
            if (!empty($_FILES['video'])) $payload['video'] = saveUploadedVideo($_FILES['video']);
            $id = createRace($pdo, $payload);
            flash_set('success', "Course créée (#$id).");
            header("Location: /admin/races/$id");
        } catch (Throwable $e) {
            flash_set('error', $e->getMessage());
            header("Location: /admin/races/new");
        }
        break;

    default:
        /* =======================================================
           ROUTES DYNAMIQUES (regex) — gardées ici pour éviter switch(true)
           ======================================================= */
        // CIRCUITS
        if (preg_match('#^/admin/circuits/(\d+)/edit$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $circuitId = (int)$m[1];
            $csrf = ensure_csrf();
            $countries = getAllCountries($pdo);
            $circuit   = getCircuitByIdWithAddress($pdo, $circuitId);
            if (!$circuit) { flash_set('error', "Circuit introuvable."); header("Location: /dashboard-races"); exit; }
            $mode='edit';
            require_once "Views/admin/circuit-form.php";
            break;
        }
        if (preg_match('#^/admin/circuits/(\d+)/update$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); break; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); break;
            }
            $circuitId = (int)$m[1];
            try {
                $nameCircuit = trim($_POST['nameCircuit'] ?? '');
                $countryId   = (int)($_POST['countryId'] ?? 0);
                $cityName    = trim($_POST['cityName'] ?? '');
                $street      = trim($_POST['street'] ?? '');
                $number      = ($_POST['number'] === '' ? null : (int)$_POST['number']);
                $picture     = trim($_POST['picture'] ?? '');

                if ($nameCircuit === '' || $countryId <= 0 || $cityName === '') {
                    throw new RuntimeException("Nom, pays et ville sont requis.");
                }
                $cityId = ensureCityId($pdo, $cityName, $countryId);
                updateCircuitWithAddress($pdo, $circuitId, [
                    'nameCircuit' => $nameCircuit,
                    'cityId'      => $cityId,
                    'street'      => $street ?: null,
                    'number'      => $number,
                    'picture'     => $picture ?: null,
                ]);
                flash_set('success', "Circuit mis à jour.");
            } catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races");
            break;
        }
        if (preg_match('#^/admin/circuits/(\d+)/delete$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); break; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); break;
            }
            try { deleteCircuit($pdo, (int)$m[1]); flash_set('success', "Circuit supprimé."); }
            catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races");
            break;
        }

        // SAISONS
        if (preg_match('#^/admin/seasons/(\d+)/edit$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $csrf = ensure_csrf();
            $seasonId = (int)$m[1];
            $season = getSeasonById($pdo, $seasonId);
            if (!$season) { flash_set('error', "Saison introuvable."); header("Location: /dashboard-races"); exit; }
            $mode='edit';
            require_once "Views/admin/season-form.php";
            break;
        }
        if (preg_match('#^/admin/seasons/(\d+)/update$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); break; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); break;
            }
            $seasonId = (int)$m[1];
            try {
                $year = (int)($_POST['year'] ?? 0);
                if ($year < 1900 || $year > 2100) throw new RuntimeException("Année invalide.");
                updateSeasonYear($pdo, $seasonId, $year);
                flash_set('success', "Saison mise à jour.");
            } catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races");
            break;
        }
        if (preg_match('#^/admin/seasons/(\d+)/delete$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); break; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); break;
            }
            try { deleteSeason($pdo, (int)$m[1]); flash_set('success', "Saison supprimée."); }
            catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races");
            break;
        }
        if (preg_match('#^/admin/seasons/(\d+)/ranking$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $seasonId = (int)$m[1];
            $season   = getSeasonById($pdo, $seasonId);
            if (!$season) { flash_set('error', "Saison introuvable."); header("Location: /dashboard-races"); exit; }
            $ranking  = getSeasonRanking($pdo, $seasonId);
            $csrf     = ensure_csrf();
            require_once "Views/admin/season-ranking.php";
            break;
        }
        if (preg_match('#^/admin/seasons/(\d+)/ranking/save$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/seasons/{$m[1]}/ranking"); break; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /admin/seasons/{$m[1]}/ranking"); break;
            }
            $seasonId = (int)$m[1];
            $points   = $_POST['points'] ?? [];
            try {
                $pdo->beginTransaction();
                updateSeasonPoints($pdo, $seasonId, $points);
                $pdo->commit();
                flash_set('success', "Points enregistrés.");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/seasons/$seasonId/ranking");
            break;
        }
        if (preg_match('#^/admin/seasons/(\d+)/attach-drivers$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $seasonId = (int)$m[1];

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $season = getSeasonById($pdo, $seasonId);
                if (!$season) { flash_set('error', "Saison introuvable."); header("Location: /dashboard-races"); exit; }


                $participants = $pdo->query("
                    SELECT userId AS id, firstName, lastName, email, role
                    FROM user
                    WHERE role IN (1,2)
                    ORDER BY role ASC, lastName ASC, firstName ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

                $attached = getSeasonRanking($pdo, $seasonId);
                $attachedIds = array_map(fn($r) => (int)$r['pilotId'], $attached);

                $csrf = ensure_csrf();
                require_once "Views/admin/season-attach-drivers.php";
                break;

            }

            // POST: synchroniser
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); break;
            }
            // on accepte driverIds[] comme nom de champ pour compatibilité
            $participantIds = array_map('intval', (array)($_POST['driverIds'] ?? []));
            try {
                $pdo->beginTransaction();
                // La table `ranking` référence déjà `user(userId)`: organisateurs & pilotes OK
                syncSeasonDrivers($pdo, $seasonId, $participantIds);
                $pdo->commit();
                flash_set('success', "Participants mis à jour.");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/seasons/$seasonId/attach-drivers");
            break;

        }

        // RACES
        if (preg_match('#^/admin/races/(\d+)$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $raceId = (int)$m[1];
            $race = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }
            $regStats      = getRegistrationStats($pdo, $raceId);
            $registrations = getRaceRegistrations($pdo, $raceId);
            require_once "Views/admin/race-show.php";
            break;
        }
        if (preg_match('#^/admin/races/(\d+)/edit$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $csrf = ensure_csrf();
            $raceId = (int)$m[1];
            $race = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }
            $circuits = getAllCircuitsWithAddress($pdo);
            $seasons  = getAllSeasons($pdo);
            $mode='edit';
            require_once "Views/admin/race-form.php";
            break;
        }
        if (preg_match('#^/admin/races/(\d+)/update$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); break; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); break;
            }
            $raceId = (int)$m[1];
            try {
                $payload = [
                    'circuitId'          => (int)($_POST['circuitId'] ?? 0),
                    'seasonId'           => (int)($_POST['seasonId'] ?? 0),
                    'date'               => trim($_POST['date'] ?? ''),
                    'description'        => trim($_POST['description'] ?? ''),
                    'price_cents'        => ($_POST['price_cents'] === '' ? null : (int)$_POST['price_cents']),
                    'capacity_min'       => ($_POST['capacity_min'] === '' ? null : (int)$_POST['capacity_min']),
                    'capacity_max'       => ($_POST['capacity_max'] === '' ? null : (int)$_POST['capacity_max']),
                    'registration_open'  => trim($_POST['registration_open'] ?? '') ?: null,
                    'registration_close' => trim($_POST['registration_close'] ?? '') ?: null,
                ];
                updateRace($pdo, $raceId, $payload);
                flash_set('success', "Course mise à jour.");
            } catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /admin/races/$raceId");
            break;
        }
        if (preg_match('#^/admin/races/(\d+)/delete$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); break; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); break;
            }
            try { deleteRace($pdo, (int)$m[1]); flash_set('success', "Course supprimée."); }
            catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races");
            break;
        }

        // Pas de route correspondante ici : laisser un autre contrôleur gérer
        break;
}
