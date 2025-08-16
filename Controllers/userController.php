<?php

    require_once "Model/userModel.php";
    require_once "Model/countryModel.php";
    require_once "Model/cityModel.php";

    require_once "Functions/auth.php";
    require_once "Functions/mail.php";

    $uri = $_SERVER["REQUEST_URI"];

    if (empty($_SESSION['user']) && !empty($_COOKIE['remember'])) {
        $row = getCookieToken($pdo, $_COOKIE['remember']);
        if ($row) {
            $_SESSION['user'] = [
                'id'        => $row['userId'],
                'firstName' => $row['firstName'],
                'lastName'  => $row['lastName'],
                'email'     => $row['email'],
                'role'      => $row['role'],
                'picture'   => $row['picture'] ?? 'default.png'
            ];
        } else {
            clear_remember_cookie();
        }
    }


    switch($uri) {
        case "/register":
            if (isset($_POST["submitRegister"])) {
                $result = verificationRegister($_POST, $pdo);
                $errors = $result['errors'];
                $old    = $result['values'];

                if ($result['valid']) {
                    if(cityAlreadyExists($pdo, $old['city'], $old['nationality'])) {
                        $cityId = getCityId($pdo, $old['city'], $old['nationality']);
                    } else {
                        $cityId = createCity($pdo, $old['city'], $old['nationality']);
                    }

                    $userId = createUser($pdo, $old, $cityId);

                    $token  = addTokenEmail($pdo, (int)$userId);

                    sendVerificationEmail($old['email'], $token);

                    flash_set('success', "Un e-mail de confirmation a été envoyé. Il est valable 15 minutes.");
                    header("Location: /register");
                    break;
                }
            }

            $success   = flash_take('success');
            $countries = getAllCountries($pdo);
            if (!isset($errors)) $errors = [];
            if (!isset($old))    $old    = [];

            require_once "Views/user/register.php";
        break;


        case "/login":
            if (session_status() === PHP_SESSION_NONE) session_start();

            if (isset($_POST['submitLogin'])) {
                $result = verificationLogin($_POST, $pdo);
                $errors = $result['errors'];
                $old    = $result['values'];

                if ($result['valid']) {
                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id'        => (int)$result['user']['id'],
                        'firstName' => $result['user']['firstName'],
                        'lastName'  => $result['user']['lastName'],
                        'email'     => $result['user']['email'],
                        'role'      => (int)$result['user']['role'],
                        'picture'   => $result['user']['picture'] ?? 'default.png',
                    ];

                    if (!empty($_POST['remember'])) {
                        $token = bin2hex(random_bytes(32));
                        createCookieToken($pdo, (int)$result['user']['id'], $token);
                        set_remember_cookie($token, 10);
                    }

                    flash_set('success', "Content de te revoir, {$result['user']['firstName']} !");
                    header("Location: /");
                    break;
                }
            }
            $success = flash_take('success');
            if (!isset($errors)) $errors = [];
            if (!isset($old))    $old    = [];

            require_once "Views/user/login.php";
        break;


        case '/logout':
            $userId = $_SESSION['user']['id'] ?? null;

            clear_remember_cookie();

            if ($userId) {
                changeStatusCookies($pdo, $userId);
            }

            $_SESSION = [];
            session_destroy();

            header("Location: /");
        break;



        case (preg_match('#^/confirmation/([a-f0-9]{64})$#', $uri, $matches) ? true : false):
            $token = $matches[1];
            $row = getTokenEmail($pdo, $token);

            if ($row) {
                verifyEmailUser($pdo, $row['userId']);
                changeStatusToken($pdo, $row['tokenUserId']);
                flash_set('success', "Ton e-mail a été confirmé avec succès !");
                header("Location: /login");
                break;
            } else {
                flash_set('error', "Lien invalide ou expiré.");
                header("Location: /login");
                break;
            }
        break;



        case "/resend-confirmation":

            if (isset($_POST["submitResend"])) {
                $email = strtolower(trim($_POST['email'] ?? ''));

                if (!is_valid_email($email)) {
                    flash_set('error', "Adresse e-mail invalide.");
                    header("Location: /resend-confirmation");
                    break;
                }

                $user = getUserByEmail($pdo, $email);
                if (!$user) {
                    flash_set('error', "Aucun compte trouvé avec cet e-mail.");
                    header("Location: /resend-confirmation");
                    break;
                }

                if ((int)$user['emailVerified'] === 1) {
                    flash_set('error', "Cet e-mail est déjà confirmé.");
                    header("Location: /login");
                    break;
                }

                disableOldTokens($pdo, (int)$user['id'], 'verificationEmail');

                $user = getUserByEmail($pdo, $email);

                $token = addTokenEmail($pdo, (int)$user['id']);

                sendVerificationEmail($user['email'], $token);

                flash_set('success', "Un nouvel e-mail de confirmation vient d'être envoyé. Il est valide pendant 15 minutes.");
                header("Location: /login");
                break;
            }

            $error   = flash_take('error');
            $success = flash_take('success');

            require_once "Views/user/resend_confirmation.php";
        break;


        case "/password-forget":
            if (session_status() === PHP_SESSION_NONE) session_start();

            if (isset($_POST['submitPasswordForget'])) {
                $email = strtolower(trim($_POST['email'] ?? ''));

                if (!is_valid_email($email)) {
                    flash_set('error', "Adresse e-mail invalide.");
                    header("Location: /password-forget");
                    break;
                }

                $user = getUserByEmail($pdo, $email);
                if (!$user) {
                    flash_set('success', "Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.");
                    header("Location: /login");
                    break;
                }

                if ((int)$user['emailVerified'] !== 1) {
                    flash_set('error', "Ce compte n’est pas encore confirmé. Renvoyer la confirmation ?");
                    header("Location: /resend-confirmation");
                    break;
                }

                disableOldTokens($pdo, (int)$user['id'], 'passwordForget');

                $token = addTokenPasswordForget($pdo, (int)$user['id']);
                sendPasswordResetEmail($user['email'], $token);

                flash_set('success', "Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.");
                header("Location: /login");
                break;
            }

            $error   = flash_take('error');
            $success = flash_take('success');
            require_once "Views/user/password_forget.php";
        break;



        case (preg_match('#^/password-reset/([a-f0-9]{64})$#', $uri, $m) ? true : false):
            if (session_status() === PHP_SESSION_NONE) session_start();

            $token = $m[1];

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $row = getPasswordForgetToken($pdo, $token); // jointure token + user, valide, < durée
                if (!$row) {
                    flash_set('error', "Lien invalide ou expiré.");
                    header("Location: /password-forget");
                    break;
                }

                $error   = flash_take('error');
                $success = flash_take('success');
                require_once "Views/user/password_reset.php";
                break;
            }

            if (isset($_POST['submitPasswordReset'])) {
                $pwd  = (string)($_POST['password'] ?? '');
                $pwd2 = (string)($_POST['password_confirm'] ?? '');

                $row = getPasswordForgetToken($pdo, $token);
                if (!$row) {
                    flash_set('error', "Lien invalide ou expiré.");
                    header("Location: /password-forget");
                    break;
                }

                if (!is_valid_password($pwd)) {
                    flash_set('error', "Mot de passe invalide (min 8, au moins une lettre et un chiffre).");
                    header("Location: /password-reset/$token");
                    break;
                }
                if ($pwd !== $pwd2) {
                    flash_set('error', "La confirmation ne correspond pas.");
                    header("Location: /password-reset/$token");
                    break;
                }

                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                updateUserPassword($pdo, (int)$row['userId'], $hash);
                changeStatusToken($pdo, (int)$row['tokenUserId']);
                changeStatusCookies($pdo, (int)$row['userId']);

                flash_set('success', "Ton mot de passe a été réinitialisé. Tu peux te connecter.");
                header("Location: /login");
                break;
            }

            header("HTTP/1.1 405 Method Not Allowed");
        break;

        case "/dashboard-administrator":
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 1) {
                header("Location: /");
                break;
            }
            require_once "Views/admin/dashboard.php";
        break;



        case "/dashboard-members":
            if (empty($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
                header("Location: /");
                break;
            }

            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 20;
            $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;

            $offset = ($page - 1) * $perPage;
            $users = getUsersWithJoins($pdo, $perPage, $offset, $q);

            require_once "Views/admin/dashboard-member.php";
        break;



        case "/dashboard-races":
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 1) {
                header("Location: /");
                break;
            }
            require_once "Views/admin/dashboard-races.php";
        break;


        case "/dashboard-poll":
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 1) {
                header("Location: /");
                break;
            }
            require_once "Views/admin/dashboard-poll.php";
        break;



        case (preg_match('#^/(\d+)/(upgrade-admin|upgrade-driver|downgrade-driver|delete-account|see)$#', $uri, $m) ? true : false):
            $targetUserId = (int)$m[1];
            $action       = $m[2];

            if ($action !== 'see') {
                if (empty($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
                    header("Location: /");
                    break;
                }
            }

            switch ($action) {

                case 'see':
                    $userToSee = getUserByIdWithJoins($pdo, $targetUserId);
                    if (!$userToSee) {
                        header("Location: /");
                        break;
                    }

                    $pilotId = (int)$userToSee['id'];
                    $seasons = getPilotSeasonsWithRaces($pdo, $pilotId);
                    require_once "Views/user/profile.php";
                break;

                case 'upgrade-admin':
                    promoteUserToAdmin($pdo, $targetUserId);
                    header("Location: /dashboard-members");
                break;

                case 'upgrade-driver':
                    promoteUserToDriver($pdo, $targetUserId);
                    header("Location: /dashboard-members");
                break;

                case 'downgrade-driver':
                    downgradeUserFromDriver($pdo, $targetUserId);
                    flash_set('success', "Pilote retiré pour l'utilisateur #{$targetUserId}.");
                    header("Location: /dashboard-members");
                break;

                case 'delete-account':
                    if (!empty($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $targetUserId) {
                        header("Location: /dashboard-members");
                        break;
                    }
                    deleteUserAccount($pdo, $targetUserId);
                    header("Location: /dashboard-members");
                break;
            }
        break;



        case "/update-profile":
            if (empty($_SESSION['user'])) { header("Location: /login"); break; }

            if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $csrf = $_SESSION['csrf'];

            $authId    = (int)$_SESSION['user']['id'];
            $userToSee = getUserByIdWithJoins($pdo, $authId);
            if (!$userToSee) { header("Location: /"); break; }

            $countries = getAllCountries($pdo); // countryId, name, flag
            $cities    = getAllCities($pdo);    // id, name, country_id

            if (isset($_POST['submitUpdateProfile'])) {
                // CSRF check
                if (!isset($_POST['_csrf']) || !hash_equals($csrf, (string)$_POST['_csrf'])) {
                    $errors = ['csrf' => "Jeton CSRF invalide, réessaie."];
                } else {
                    $result = verificationUpdateProfile($_POST, $_FILES, $pdo, $authId);
                    $errors = $result['errors'];
                    $vals   = $result['values'];

                    // Upload image (si fourni et pas d'erreurs de saisie)
                    $newPictureFile = null;
                    if (!$errors && !empty($result['picture_ready_tmp'])) {
                        $tmp   = $result['picture_ready_tmp']['tmp'];
                        $ext   = $result['picture_ready_tmp']['ext'];
                        $fname = "u{$authId}_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

                        $targetDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . "/Assets/ProfilesPhoto";
                        if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);
                        $targetFs  = $targetDir . "/" . $fname;

                        if (!@move_uploaded_file($tmp, $targetFs)) {
                            $errors['picture'] = "Impossible d'enregistrer la photo.";
                        } else {
                            $newPictureFile = $fname;
                        }
                    }

                    if (!$errors) {
                        // 🔎 Résolution ville (nom -> id) + création auto si nécessaire (sans getCityId)
                        $cityId    = null;
                        $cityName  = trim($vals['city'] ?? '');            // NOM saisi dans l'input
                        $countryId = $vals['nationality'] ?: null;         // id pays

                        if ($cityName !== '' && $countryId) {
                            if (cityAlreadyExists($pdo, $cityName, (int)$countryId)) {
                                // On récupère l'id directement (pas besoin de getCityId)
                                $st = $pdo->prepare("SELECT cityId FROM city WHERE name = :n AND country = :c LIMIT 1");
                                $st->execute([':n' => $cityName, ':c' => (int)$countryId]);
                                $cid = $st->fetchColumn();
                                $cityId = $cid ? (int)$cid : null; // sécurité si conflit de casse/espaces
                            } else {
                                $cityId = createCity($pdo, $cityName, (int)$countryId);
                            }
                        }

                        // Construit les champs à mettre à jour (⚠️ on NE touche PAS à l'email)
                        $fields = [
                            'firstName'   => $vals['first_name'],
                            'lastName'    => $vals['last_name'],
                            'phone'       => $vals['phone'] ?: null,
                            'birthday'    => $vals['birthdate'] ?: null,
                            'numero'      => $vals['numero'] !== '' ? (int)$vals['numero'] : null,
                            'taille'      => $vals['taille'] !== '' ? (int)$vals['taille'] : null,
                            'poids'       => $vals['poids']  !== '' ? (int)$vals['poids']  : null,
                            'description' => $vals['description'] ?: null,
                            'nationality' => $vals['nationality'] ?: null,
                            'city'        => $cityId, // ⬅️ id résolu (ou null)
                        ];
                        if ($newPictureFile) $fields['picture'] = $newPictureFile;

                        updateUserProfile($pdo, $authId, $fields);

                        // Nettoyage ancienne photo si remplacée
                        if ($newPictureFile && !empty($userToSee['picture']) && $userToSee['picture'] !== 'default.png') {
                            $oldFs = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . "/Assets/ProfilesPhoto/" . $userToSee['picture'];
                            if (is_file($oldFs)) @unlink($oldFs);
                        }

                        // Refresh données + session
                        $userToSee = getUserByIdWithJoins($pdo, $authId);
                        $_SESSION['user']['firstName'] = $userToSee['firstName'];
                        $_SESSION['user']['lastName']  = $userToSee['lastName'];
                        $_SESSION['user']['email']     = $userToSee['email'];
                        if (!empty($userToSee['picture'])) {
                            $_SESSION['user']['picture'] = $userToSee['picture'];
                        }

                        flash_set('success', "Profil mis à jour avec succès.");
                        header("Location: /update-profile");
                        break;
                    }
                }
            }

            // Messages flash + fallback erreurs
            if (!isset($errors)) $errors = [];
            $success = flash_take('success');

            require_once "Views/user/updateProfile.php";
        break;


















    }
