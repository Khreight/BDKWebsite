<?php
// Controllers/raceController.php

require_once __DIR__ . "/../Model/racesModel.php";
require_once __DIR__ . "/../Model/circuitModel.php";
require_once __DIR__ . "/../Model/countryModel.php";
require_once __DIR__ . "/../Model/cityModel.php";
require_once __DIR__ . "/../Model/userModel.php";
require_once __DIR__ . "/../Functions/auth.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

/* ---------------- Helpers cohérents ---------------- */
function ensure_logged_in_or_redirect(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user'])) { header("Location: /login"); exit; }
}
function ensure_driver_or_admin_or_redirect(): void {
    ensure_logged_in_or_redirect();
    $role = (int)$_SESSION['user']['role'];
    if (!in_array($role, [1,2], true)) { header("Location: /become-driver"); exit; }
}
function csrf_races(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}



/** City ensure */
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
/** Upload vidéo */
function saveUploadedVideo(array $file): ?string {
    if (empty($file['tmp_name']) || (int)$file['error'] !== UPLOAD_ERR_OK) return null;
    $extOk = ['mp4','webm','mov','qt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extOk, true)) throw new RuntimeException("Format vidéo invalide.");
    $dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . "/Uploads/videos";
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = "race_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    if (!@move_uploaded_file($file['tmp_name'], $dir."/".$name)) throw new RuntimeException("Échec upload vidéo.");
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
        require_once __DIR__ . "/../Views/admin/dashboard-races.php";
        break;

    /* ====================== CIRCUITS ==================== */
    case "/admin/circuits/new":
        ensure_admin_or_redirect();
        $csrf = ensure_csrf();
        $countries = getAllCountries($pdo);
        $circuit = []; $mode='create';
        require_once __DIR__ . "/../Views/admin/circuit-form.php";
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
        require_once __DIR__ . "/../Views/admin/season-form.php";
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

    /* ======================== PUBLIC /RACES ============= */
    case "/races":
        $csrf = csrf_races();
        $all  = getPublicRaces($pdo);
        $userId = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

        foreach ($all as &$r) {
            $r['_state']  = computeRegistrationState($r); // early|open|closed
            $r['_dateFr'] = !empty($r['date']) ? (new DateTime($r['date']))->format('d/m/Y H:i') : null;
            $r['_price']  = isset($r['price_cents']) && $r['price_cents'] !== null
                            ? number_format(((int)$r['price_cents'])/100, 2, ',', ' ') . ' €'
                            : '—';
            $r['canRegister'] = ($r['_state'] === 'open');
            $r['myRegStatus'] = null;
            $r['hasMyRegistration'] = false;

            if ($userId) {
                $r['myRegStatus'] = getUserRegistrationStatus($pdo, (int)$r['id'], $userId); // null|waited|valide|no-valide
                $r['hasMyRegistration'] = in_array($r['myRegStatus'], ['waited','valide'], true);
                if ($r['hasMyRegistration']) $r['canRegister'] = false; // déjà en attente/accepté
            }
        }
        unset($r);

        $openRaces  = array_values(array_filter($all, fn($x)=>$x['_state']==='open'));
        $early      = array_values(array_filter($all, fn($x)=>$x['_state']==='early'));
        $closed     = array_values(array_filter($all, fn($x)=>$x['_state']==='closed'));
        usort($early,  fn($a,$b)=>strcmp($b['date'] ?? '', $a['date'] ?? ''));   // récentes -> anciennes
        usort($closed, fn($a,$b)=>strcmp($b['date'] ?? '', $a['date'] ?? ''));
        $otherRaces = array_merge($early, $closed); // ⬅️ inscriptions expirées tout en bas

        require_once __DIR__ . "/../Views/races/index.php";
        break;

    /* ======================== RACES (admin/new) ========= */
    case "/admin/races/new":
        ensure_admin_or_redirect();
        $csrf     = ensure_csrf();
        $circuits = getAllCircuitsWithAddress($pdo);
        $seasons  = getAllSeasons($pdo);
        $race=[]; $mode='create';
        require_once __DIR__ . "/../Views/admin/race-form.php";
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
           ROUTES DYNAMIQUES (regex)
           ======================================================= */

        /* ---------- ADMIN SHOW RACE (inscriptions + actions) ---------- */
        if (preg_match('#^/admin/races/(\d+)$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $raceId = (int)$m[1];
            $race = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }

            $csrf            = ensure_csrf();
            $regStats        = getRegistrationStats($pdo, $raceId);
            $registrations   = getRaceRegistrations($pdo, $raceId);
            $raceHasResults  = raceHasResults($pdo, $raceId);
            $results         = getRaceResultsWithLaps($pdo, $raceId);
            $eligibleSeasonPilots = getEligibleSeasonPilotsForRace($pdo, (int)$race['seasonId'], $raceId);

            require_once __DIR__ . "/../Views/admin/race-show.php";
            exit;
        }

        if (preg_match('#^/admin/circuits/(\d+)/edit$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $csrf = ensure_csrf();
            $circuitId = (int)$m[1];

            // nécessite getCircuitByIdWithAddress(...) dans circuitModel.php
            $circuit = getCircuitByIdWithAddress($pdo, $circuitId);
            if (!$circuit) { flash_set('error', "Circuit introuvable."); header("Location: /dashboard-races"); exit; }

            $countries = getAllCountries($pdo);
            $mode = 'edit';
            require_once __DIR__ . "/../Views/admin/circuit-form.php";
            exit;
        }

        if (preg_match('#^/admin/circuits/(\d+)/update$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
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

                // cityId cohérente (utilise ton helper existant)
                $cityId = ensureCityId($pdo, $cityName, $countryId);

                // nécessite updateCircuitWithAddress(...) dans circuitModel.php
                updateCircuitWithAddress($pdo, $circuitId, [
                    'nameCircuit' => $nameCircuit,
                    'cityId'      => $cityId,
                    'street'      => $street ?: null,
                    'number'      => $number,
                    'picture'     => $picture ?: null,
                ]);

                flash_set('success', "Circuit mis à jour.");
            } catch (Throwable $e) {
                flash_set('error', $e->getMessage());
            }
            header("Location: /dashboard-races"); exit;
        }

        if (preg_match('#^/admin/circuits/(\d+)/delete$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }

            $circuitId = (int)$m[1];
            try {
                deleteCircuit($pdo, $circuitId);
                flash_set('success', "Circuit supprimé.");
            } catch (Throwable $e) {
                flash_set('error', $e->getMessage());
            }
            header("Location: /dashboard-races"); exit;
        }

        // ADMIN: ajout direct d’un participant (valide immédiat)
        if (preg_match('#^/admin/races/(\d+)/registrations/add$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }

            $raceId = (int)$m[1];
            $userId = (int)($_POST['userId'] ?? 0);

            $race = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race || $userId <= 0) { flash_set('error',"Données invalides."); header("Location: /admin/races/$raceId"); exit; }

            // capacité max (on compte waited+valide)
            if ($race['capacity_max'] !== null) {
                $st = $pdo->prepare("SELECT COUNT(*) FROM registration WHERE race=:r AND status IN ('waited','valide')");
                $st->execute([':r'=>$raceId]);
                if ((int)$st->fetchColumn() >= (int)$race['capacity_max']) {
                    flash_set('error', "Capacité atteinte.");
                    header("Location: /admin/races/$raceId"); exit;
                }
            }

            try {
                adminAddDirectRegistration($pdo, $raceId, $userId);
                flash_set('success', "Participant validé et ajouté à la course.");
            } catch (Throwable $e) {
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/races/$raceId"); exit;
        }

        // ADMIN: kick (dé-valider -> no-valide)
        if (preg_match('#^/admin/races/(\d+)/registrations/(\d+)/kick$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }

            $raceId = (int)$m[1];
            $regId  = (int)$m[2];

            try {
                setRegistrationStatus($pdo, $regId, 'no-valide');
                flash_set('success', "Participant retiré (kick).");
            } catch (Throwable $e) {
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/races/$raceId"); exit;
        }



        /* ---------- ADMIN: valider/refuser une inscription ---------- */
        if (preg_match('#^/admin/races/(\d+)/registrations/(\d+)/(approve|reject)$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }
            $raceId = (int)$m[1];
            $regId  = (int)$m[2];
            $act    = $m[3];
            try {
                if ($act === 'approve') setRegistrationStatus($pdo, $regId, 'valide');
                else                     setRegistrationStatus($pdo, $regId, 'no-valide');
                flash_set('success', "Inscription ".($act==='approve'?'validée':'refusée').".");
            } catch (Throwable $e) {
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/races/$raceId");
            exit;
        }

        // -------- PUBLIC: classement d'une saison --------
        if (preg_match('#^/seasons/(\d+)/ranking$#', $uri, $m)) {
            $seasonId = (int)$m[1];
            $season   = getSeasonById($pdo, $seasonId);
            if (!$season) { header("Location: /"); exit; }

            // Classement + courses de la saison (uniquement celles qui ont des résultats)
            $standings        = getSeasonStandingsPublic($pdo, $seasonId);
            $seasonRaces      = getRacesBySeason($pdo, $seasonId, true); // <-- IMPORTANT: variable attendue par la vue
            $allSeasons       = getAllSeasons($pdo);

            $csrf = csrf_races(); // si ta vue l’utilise
            require_once __DIR__ . "/../Views/public/season-ranking.php";
            exit;
        }


        if (preg_match('#^/races/(\d+)$#', $uri, $m)) {
            $raceId  = (int)$m[1];
            $race    = getRacePublicById($pdo, $raceId);
            if (!$race) { header("Location: /races"); exit; }

            $csrf       = csrf_races();
            $results    = getRaceResultsWithLaps($pdo, $raceId);

            // fenêtre d'inscription
            $state      = computeRegistrationState($race); // early|open|closed
            $capMax     = $race['capacity_max'] !== null ? (int)$race['capacity_max'] : null;
            $regCount   = getRaceRegistrationCount($pdo, $raceId, ['waited','valide']);
            $isFull     = ($capMax !== null && $regCount >= $capMax);

            // utilisateur & statut personnel
            $userId     = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
            $myStatus   = $userId ? getUserRegistrationStatus($pdo, $raceId, $userId) : null; // null|waited|valide|no-valide
            $already    = in_array($myStatus, ['waited','valide'], true);

            // bouton s’inscrire (seulement si connecté + pilote/admin)
            $logged     = !empty($_SESSION['user']);
            $role       = $logged ? (int)$_SESSION['user']['role'] : 0;
            $isDriver   = in_array($role, [1,2], true);
            $canRegister= ($state === 'open') && !$isFull && !$already && $logged && $isDriver;

            // liste des inscrits pour affichage
            $registrations = getRaceRegistrations($pdo, $raceId);

            require_once __DIR__ . "/../Views/public/race-details.php";
            exit;
        }


        /* ---------- PUBLIC: inscription à une course ---------- */
        if (preg_match('#^/races/(\d+)/register$#', $uri, $m)) {
            ensure_driver_or_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /races"); exit;
            }
            $raceId = (int)$m[1];
            $userId = (int)$_SESSION['user']['id'];

            $r = getRacePublicById($pdo, $raceId);
            if (!$r) { flash_set('error', "Course introuvable."); header("Location: /races"); exit; }

            $state = computeRegistrationState($r);
            if ($state !== 'open') {
                flash_set('error', "Les inscriptions ne sont pas ouvertes pour cette course.");
                header("Location: /races"); exit;
            }

            if (userHasRegistration($pdo, $raceId, $userId)) {
                flash_set('error', "Tu as déjà une demande en cours/acceptée.");
                header("Location: /races"); exit;
            }

            // capacité
            if ($r['capacity_max'] !== null) {
                $st = $pdo->prepare("SELECT COUNT(*) FROM registration WHERE race=:r AND status IN ('waited','valide')");
                $st->execute([':r'=>$raceId]);
                if ((int)$st->fetchColumn() >= (int)$r['capacity_max']) {
                    flash_set('error', "La course est complète.");
                    header("Location: /races"); exit;
                }
            }

            try { createRaceRegistration($pdo, $raceId, $userId);
                  flash_set('success', "Inscription enregistrée. En attente de validation.");
            } catch (Throwable $e) {
                  flash_set('error', "Erreur : ".$e->getMessage());
            }
            header("Location: /races"); exit;
        }

        /* ---------- ADMIN: éditer course ---------- */
        if (preg_match('#^/admin/races/(\d+)/edit$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $csrf = ensure_csrf();
            $raceId = (int)$m[1];
            $race = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }
            $circuits = getAllCircuitsWithAddress($pdo);
            $seasons  = getAllSeasons($pdo);
            $mode='edit';
            require_once __DIR__ . "/../Views/admin/race-form.php";
            exit;
        }
        if (preg_match('#^/admin/races/(\d+)/update$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
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
            exit;
        }
        if (preg_match('#^/admin/races/(\d+)/delete$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }
            try { deleteRace($pdo, (int)$m[1]); flash_set('success', "Course supprimée."); }
            catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races"); exit;
        }

        /* ---------- ADMIN: résultats new/save ---------- */
        if (preg_match('#^/admin/races/(\d+)/results/new$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $raceId = (int)$m[1];
            $race = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }
            $isPast = !empty($race['date']) && (new DateTime($race['date'])) < new DateTime('now');
            if (!$isPast) { flash_set('error', "La course n’est pas encore terminée."); header("Location: /admin/races/$raceId"); exit; }
            if (raceHasResults($pdo, $raceId)) { flash_set('error', "Des résultats existent déjà."); header("Location: /admin/races/$raceId"); exit; }

            $csrf = ensure_csrf();
            $seasonId     = (int)($race['seasonId'] ?? 0);
            $participants = getSeasonParticipants($pdo, $seasonId);
            $defaultRows  = count($participants);
            require_once __DIR__ . "/../Views/admin/race-results-form.php";
            exit;
        }
        if (preg_match('#^/admin/races/(\d+)/results/save$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }
            $raceId = (int)$m[1];
            $race   = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }
            $isPast = !empty($race['date']) && (new DateTime($race['date'])) < new DateTime('now');
            if (!$isPast) { flash_set('error', "La course n’est pas encore terminée."); header("Location: /admin/races/$raceId"); exit; }
            if (raceHasResults($pdo, $raceId)) { flash_set('error', "Des résultats existent déjà."); header("Location: /admin/races/$raceId"); exit; }

            $seasonId  = (int)$race['seasonId'];
            $positions = array_map('intval', (array)($_POST['position'] ?? []));
            $pilotIds  = array_map('intval', (array)($_POST['pilotId']  ?? []));
            $avgSpeeds = (array)($_POST['avg'] ?? []);
            $pointsArr = (array)($_POST['points'] ?? []);
            $laps      = (array)($_POST['laps'] ?? []);
            $gaps      = (array)($_POST['gap'] ?? []);

            $fastestPilotId   = isset($_POST['fastest_pilot']) ? (int)$_POST['fastest_pilot'] : 0;
            $fastestLapPoints = isset($_POST['fastest_points']) ? (float)str_replace(',', '.', (string)$_POST['fastest_points']) : 0.5;

            $errors = [];
            if (empty($positions)) $errors[] = "Aucun classement fourni.";
            if (count($positions) !== count(array_unique($positions))) $errors[] = "Positions en double.";
            $nonEmptyPilots = array_values(array_filter($pilotIds));
            if (count($nonEmptyPilots) !== count(array_unique($nonEmptyPilots))) $errors[] = "Pilote en double.";
            if ($errors) { flash_set('error', implode(' ', $errors)); header("Location: /admin/races/$raceId/results/new"); exit; }

            $entries = [];
            foreach ($positions as $idx => $pos) {
                $pilotId = (int)($pilotIds[$idx] ?? 0);
                if ($pilotId <= 0) continue;
                $avg = isset($avgSpeeds[$idx]) ? (float)$avgSpeeds[$idx] : null;
                $pts = isset($pointsArr[$idx]) ? (float)$pointsArr[$idx] : null;
                if ($pts === null || $pts == 0.0) $pts = max(22 - ((int)$pos - 1), 0);
                $rowLaps = [];
                if (!empty($laps[$pos]) && is_array($laps[$pos])) {
                    foreach ($laps[$pos] as $lapStr) { $lapStr = trim((string)$lapStr); if ($lapStr!=='') $rowLaps[] = (float)$lapStr; }
                }
                $gap = isset($gaps[$idx]) && $gaps[$idx] !== '' ? (float)$gaps[$idx] : 0.0;

                $entries[] = ['position'=>(int)$pos,'pilotId'=>$pilotId,'avg'=>$avg,'points'=>$pts,'laps'=>$rowLaps,'gap'=>$gap];
            }
            usort($entries, fn($a,$b)=>$a['position']<=>$b['position']);

            if ($fastestPilotId > 0 && $fastestLapPoints > 0) {
                foreach ($entries as &$e) { if ((int)$e['pilotId'] === $fastestPilotId) { $e['points'] += $fastestLapPoints; break; } }
                unset($e);
            }

            try {
                $pdo->beginTransaction();
                saveRaceResults($pdo, $raceId, $seasonId, $entries, null, 0.0); // bonus déjà ajouté
                $pdo->prepare("UPDATE race SET fastDriver = :p WHERE raceId = :r")->execute([':p'=>$fastestPilotId ?: null, ':r'=>$raceId]);
                $pdo->commit();
                flash_set('success', "Résultats enregistrés.");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', "Erreur: ".$e->getMessage());
                header("Location: /admin/races/$raceId/results/new"); exit;
            }
            header("Location: /admin/races/$raceId"); exit;
        }

        /* ---------- ADMIN: résultats edit/update ---------- */
        if (preg_match('#^/admin/races/(\d+)/results/edit$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $raceId = (int)$m[1];
            $race = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }
            $isPast = !empty($race['date']) && (new DateTime($race['date'])) < new DateTime('now');
            if (!$isPast) { flash_set('error', "Course pas encore terminée."); header("Location: /admin/races/$raceId"); exit; }
            if (!raceHasResults($pdo, $raceId)) { flash_set('error', "Aucun résultat à modifier."); header("Location: /admin/races/$raceId"); exit; }

            $csrf = ensure_csrf();
            $seasonId     = (int)$race['seasonId'];
            $participants = getSeasonParticipants($pdo, $seasonId);
            $existing     = getRaceResultsWithLaps($pdo, $raceId);
            usort($existing, fn($a,$b)=>((int)$a['position'])<=>((int)$b['position']));
            $defaultRows  = max(count($participants), count($existing));
            $mode = 'edit';
            require_once __DIR__ . "/../Views/admin/race-results-form.php";
            exit;
        }
        if (preg_match('#^/admin/races/(\d+)/results/update$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }
            $raceId = (int)$m[1];
            $race   = getRaceByIdWithJoins($pdo, $raceId);
            if (!$race) { flash_set('error', "Course introuvable."); header("Location: /dashboard-races"); exit; }

            $seasonId  = (int)$race['seasonId'];
            $positions = array_map('intval', (array)($_POST['position'] ?? []));
            $pilotIds  = array_map('intval', (array)($_POST['pilotId']  ?? []));
            $avgSpeeds = (array)($_POST['avg'] ?? []);
            $pointsArr = (array)($_POST['points'] ?? []);
            $laps      = (array)($_POST['laps'] ?? []);
            $gaps      = (array)($_POST['gap'] ?? []);
            $fastestPilotId   = isset($_POST['fastest_pilot']) ? (int)$_POST['fastest_pilot'] : 0;
            $fastestLapPoints = isset($_POST['fastest_points']) ? (float)str_replace(',', '.', (string)$_POST['fastest_points']) : 0.5;

            $entries = [];
            foreach ($positions as $idx => $pos) {
                $pilotId = (int)($pilotIds[$idx] ?? 0);
                if ($pilotId <= 0) continue;
                $avg = isset($avgSpeeds[$idx]) ? (float)$avgSpeeds[$idx] : null;
                $pts = isset($pointsArr[$idx]) ? (float)$pointsArr[$idx] : null;
                if ($pts === null || $pts == 0.0) $pts = max(22 - ((int)$pos - 1), 0);
                $rowLaps = [];
                if (!empty($laps[$pos]) && is_array($laps[$pos])) {
                    foreach ($laps[$pos] as $lapStr) { $lapStr = trim((string)$lapStr); if ($lapStr!=='') $rowLaps[] = (float)$lapStr; }
                }
                $gap = isset($gaps[$idx]) && $gaps[$idx] !== '' ? (float)$gaps[$idx] : 0.0;

                $entries[] = ['position'=>(int)$pos,'pilotId'=>$pilotId,'avg'=>$avg,'points'=>$pts,'laps'=>$rowLaps,'gap'=>$gap];
            }
            usort($entries, fn($a,$b)=>$a['position']<=>$b['position']);
            if ($fastestPilotId > 0 && $fastestLapPoints > 0) {
                foreach ($entries as &$e) { if ((int)$e['pilotId'] === $fastestPilotId) { $e['points'] += $fastestLapPoints; break; } }
                unset($e);
            }

            try {
                $pdo->beginTransaction();
                // si tu as une fonction "updateRaceResults", sinon delete+save
                if (function_exists('updateRaceResults')) {
                    updateRaceResults($pdo, $raceId, $seasonId, $entries, null, 0.0);
                } else {
                    if (function_exists('deleteRaceResults')) deleteRaceResults($pdo, $raceId);
                    saveRaceResults($pdo, $raceId, $seasonId, $entries, null, 0.0);
                }
                $pdo->prepare("UPDATE race SET fastDriver = :p WHERE raceId = :r")->execute([':p'=>$fastestPilotId ?: null, ':r'=>$raceId]);
                $pdo->commit();
                flash_set('success', "Résultats mis à jour.");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', "Erreur: ".$e->getMessage());
                header("Location: /admin/races/$raceId/results/edit"); exit;
            }
            header("Location: /admin/races/$raceId"); exit;
        }

        /* ---------- SAISONS (ranking, attach, etc.) ---------- */
        if (preg_match('#^/admin/seasons/(\d+)/edit$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $csrf = ensure_csrf();
            $seasonId = (int)$m[1];
            $season = getSeasonById($pdo, $seasonId);
            if (!$season) { flash_set('error', "Saison introuvable."); header("Location: /dashboard-races"); exit; }
            $mode='edit';
            require_once __DIR__ . "/../Views/admin/season-form.php";
            exit;
        }
        if (preg_match('#^/admin/seasons/(\d+)/update$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }
            $seasonId = (int)$m[1];
            try {
                $year = (int)($_POST['year'] ?? 0);
                if ($year < 1900 || $year > 2100) throw new RuntimeException("Année invalide.");
                updateSeasonYear($pdo, $seasonId, $year);
                flash_set('success', "Saison mise à jour.");
            } catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races"); exit;
        }
        if (preg_match('#^/admin/seasons/(\d+)/delete$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /dashboard-races"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }
            try { deleteSeason($pdo, (int)$m[1]); flash_set('success', "Saison supprimée."); }
            catch (Throwable $e) { flash_set('error', $e->getMessage()); }
            header("Location: /dashboard-races"); exit;
        }
        if (preg_match('#^/admin/seasons/(\d+)/ranking$#', $uri, $m)) {
            ensure_admin_or_redirect();
            $seasonId = (int)$m[1];
            $season   = getSeasonById($pdo, $seasonId);
            if (!$season) { flash_set('error', "Saison introuvable."); header("Location: /dashboard-races"); exit; }
            $ranking  = getSeasonRanking($pdo, $seasonId);
            $csrf     = ensure_csrf();
            require_once __DIR__ . "/../Views/admin/season-ranking.php";
            exit;
        }
        if (preg_match('#^/admin/seasons/(\d+)/ranking/save$#', $uri, $m)) {
            ensure_admin_or_redirect();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /admin/seasons/{$m[1]}/ranking"); exit; }
            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /admin/seasons/{$m[1]}/ranking"); exit;
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
            header("Location: /admin/seasons/$seasonId/ranking"); exit;
        }

        /* ---------- Attach drivers (déjà présent chez toi) ---------- */
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
                require_once __DIR__ . "/../Views/admin/season-attach-drivers.php";
                exit;
            }

            if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['_csrf'])) {
                flash_set('error', "Jeton CSRF invalide."); header("Location: /dashboard-races"); exit;
            }
            $participantIds = array_map('intval', (array)($_POST['driverIds'] ?? []));
            try {
                $pdo->beginTransaction();
                syncSeasonDrivers($pdo, $seasonId, $participantIds);
                $pdo->commit();
                flash_set('success', "Participants mis à jour.");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', "Erreur: ".$e->getMessage());
            }
            header("Location: /admin/seasons/$seasonId/attach-drivers"); exit;
        }

        /* ---------- rien trouvé : laisser autre contrôleur ---------- */
        break;
}
