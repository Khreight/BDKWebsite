<?php

    require_once "Model/userModel.php";
    require_once "Model/countryModel.php";

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
                    $userId = createUser($pdo, $old);

                    $token  = addTokenEmail($pdo, (int)$userId);

                    sendVerificationEmail($old['email'], $token);

                    flash_set('success', "Un e-mail de confirmation a été envoyé. Il est valable 15 minutes.");
                    header("Location: /register");
                    exit;
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
                    exit;
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
                exit;
            } else {
                flash_set('error', "Lien invalide ou expiré.");
                header("Location: /login");
                exit;
            }
        break;



        case "/resend-confirmation":

            if (isset($_POST["submitResend"])) {
                $email = strtolower(trim($_POST['email'] ?? ''));

                if (!is_valid_email($email)) {
                    flash_set('error', "Adresse e-mail invalide.");
                    header("Location: /resend-confirmation");
                    exit;
                }

                $user = getUserByEmail($pdo, $email);
                if (!$user) {
                    flash_set('error', "Aucun compte trouvé avec cet e-mail.");
                    header("Location: /resend-confirmation");
                    exit;
                }

                if ((int)$user['emailVerified'] === 1) {
                    flash_set('error', "Cet e-mail est déjà confirmé.");
                    header("Location: /login");
                    exit;
                }

                disableOldTokens($pdo, (int)$user['id'], 'verificationEmail');

                $user = getUserByEmail($pdo, $email);

                $token = addTokenEmail($pdo, (int)$user['id']);
                
                sendVerificationEmail($user['email'], $token);

                flash_set('success', "Un nouvel e-mail de confirmation vient d'être envoyé. Il est valide pendant 15 minutes.");
                header("Location: /login");
                exit;
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
                    exit;
                }

                $user = getUserByEmail($pdo, $email);
                if (!$user) {
                    flash_set('success', "Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.");
                    header("Location: /login");
                    exit;
                }

                if ((int)$user['emailVerified'] !== 1) {
                    flash_set('error', "Ce compte n’est pas encore confirmé. Renvoyer la confirmation ?");
                    header("Location: /resend-confirmation");
                    exit;
                }

                disableOldTokens($pdo, (int)$user['id'], 'passwordForget');

                $token = addTokenPasswordForget($pdo, (int)$user['id']);
                sendPasswordResetEmail($user['email'], $token);

                flash_set('success', "Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.");
                header("Location: /login");
                exit;
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
                    exit;
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
                    exit;
                }

                if (!is_valid_password($pwd)) {
                    flash_set('error', "Mot de passe invalide (min 8, au moins une lettre et un chiffre).");
                    header("Location: /password-reset/$token");
                    exit;
                }
                if ($pwd !== $pwd2) {
                    flash_set('error', "La confirmation ne correspond pas.");
                    header("Location: /password-reset/$token");
                    exit;
                }

                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                updateUserPassword($pdo, (int)$row['userId'], $hash);
                changeStatusToken($pdo, (int)$row['tokenUserId']);          // status = 0 pour ce token
                changeStatusCookies($pdo, (int)$row['userId']);             // (optionnel) invalide remember me

                flash_set('success', "Ton mot de passe a été réinitialisé. Tu peux te connecter.");
                header("Location: /login");
                exit;
            }

            header("HTTP/1.1 405 Method Not Allowed");
            exit;











    }
