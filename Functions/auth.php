<?php

require_once "Model/userModel.php";

function verificationRegister($data, $pdo): array {
    $values = [
        'first_name'        => str_clean($data['first_name'] ?? ''),
        'last_name'         => str_clean($data['last_name'] ?? ''),
        'email'             => strtolower(str_clean($data['email'] ?? '')),
        'phone'             => str_clean($data['phone'] ?? ''),
        'birthdate'         => str_clean($data['birthdate'] ?? ''),
        'nationality'       => $data['nationality'] ?? null,
        'city'              => str_clean($data['city'] ?? ''),
        'password'          => str_clean($data['password'] ?? ''),
        'password_confirm'  => str_clean($data['password_confirm'] ?? '')
    ];

    $errors = [];

    // Prénom
    if ($values['first_name'] === '' || !is_valid_name($values['first_name'])) {
        $errors['first_name'] = "Prénom invalide (2–50 caractères, lettres/espaces/-/'/.)";
    }

    // Nom
    if ($values['last_name'] === '' || !is_valid_name($values['last_name'])) {
        $errors['last_name'] = "Nom invalide (2–50 caractères, lettres/espaces/-/'/.)";
    }

    // Email
    if ($values['email'] === '' || !is_valid_email($values['email'])) {
        $errors['email'] = "Adresse e-mail invalide.";
    }

    // Téléphone
    if ($values['phone'] === '' || !is_valid_phone($values['phone'])) {
        $errors['phone'] = "Téléphone invalide (8–15 chiffres, format international accepté, ex. +32470123456).";
    } else {
        $values['phone'] = phone_normalize($values['phone']);
    }

    // Date de naissance
    if ($values['birthdate'] === '' || !is_valid_birthdate($values['birthdate'])) {
        $errors['birthdate'] = "Date de naissance invalide (format AAAA-MM-JJ, pas dans le futur).";
    }

    // Nationalité (1..195)
    if (!is_valid_nationality($values['nationality'])) {
        $errors['nationality'] = "Sélectionne une nationalité valide.";
    } else {
        $values['nationality'] = (int)$values['nationality'];
    }

    // Ville
    if ($values['city'] === '' || !is_valid_city($values['city'])) {
        $errors['city'] = "Ville invalide (2–100 caractères, lettres/espaces/-/'/.)";
    }

    // Mot de passe
    if ($values['password'] === '' || !is_valid_password($values['password'])) {
        $errors['password'] = "Mot de passe invalide (min. 8 caractères, inclure au moins une lettre et un chiffre).";
    }
    if ($values['password_confirm'] === '' || $values['password_confirm'] !== $values['password']) {
        $errors['password_confirm'] = "La confirmation ne correspond pas.";
    }

    if (!isset($errors['email'])) {
        if (userEmailExists($pdo, $values['email'])) {
            $errors['email'] = "Cet e-mail est déjà utilisé.";
        }
    }

    if (!isset($errors['phone'])) {
        if (userPhoneExists($pdo, $values['phone'])) {
            $errors['phone'] = "Ce numéro de téléphone est déjà utilisé.";
        }
    }

    unset($values['password_confirm']);

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
        'values' => $values,
    ];
}

function verificationLogin(array $data, PDO $pdo): array {
    $values = [
        'email'    => strtolower(str_clean($data['email'] ?? '')),
        'password' => (string)($data['password'] ?? ''),
    ];
    $errors = [];
    $user   = null;

    if ($values['email'] === '' || !is_valid_email($values['email'])) {
        $errors['email'] = "Adresse e-mail invalide.";
    }

    if ($values['password'] === '') {
        $errors['password'] = "Mot de passe requis.";
    }

    if (!isset($errors['email']) && !isset($errors['password'])) {
        $user = getUserByEmail($pdo, $values['email']);
        if (!$user) {
            $errors['email'] = "Identifiants invalides.";
        } else {
            if (!password_verify($values['password'], $user['password'])) {
                $errors['email'] = "Identifiants invalides.";
            } else {
                if ((int)$user['emailVerified'] !== 1) {
                    $errors['email'] = "Ton e-mail n’est pas encore confirmé.";
                }
            }
        }
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
        'values' => $values,
        'user'   => $user,
    ];
}



function str_clean(?string $v): string {
    $v = trim((string)$v);
    return preg_replace('/\s+/u', ' ', $v);
}

function is_valid_name(string $v, int $min=2, int $max=50): bool {
    $v = str_clean($v);
    $len = mb_strlen($v);
    if ($len < $min || $len > $max) return false;
    return (bool)preg_match("/^[\p{L}\p{M}][\p{L}\p{M}\-\'\. ]+$/u", $v);
}

function is_valid_email(string $email): bool {
    if (mb_strlen($email) > 254) return false;
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function phone_normalize(string $phone): string {
    $phone = preg_replace('/[^\d+]/', '', $phone);
    $phone = preg_replace('/(?<=.)\+/', '', $phone);
    return $phone;
}

function is_valid_phone(string $phone): bool {
    $p = phone_normalize($phone);
    $digitsOnly = preg_replace('/\D/', '', $p);
    $len = strlen($digitsOnly);

    if ($len < 8 || $len > 15) return false;

    if ($p[0] === '+') {
        return (bool)preg_match('/^\+\d{8,15}$/', $p);
    }
    return (bool)preg_match('/^\d{8,15}$/', $p);
}

function is_valid_birthdate(string $date, int $minYear = 1900): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    $dtErrors = DateTime::getLastErrors();

    if (!$dt) return false;

    if (is_array($dtErrors) && (
        ($dtErrors['warning_count'] ?? 0) > 0 ||
        ($dtErrors['error_count'] ?? 0) > 0
    )) {
        return false;
    }

    $dt->setTime(0,0,0);
    $today = new DateTime('today');

    if ($dt > $today) return false;
    if ((int)$dt->format('Y') < $minYear) return false;

    return true;
}


function is_valid_nationality($id): bool {
    return filter_var($id, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 195]
    ]) !== false;
}

function is_valid_city(string $v, int $min=2, int $max=100): bool {
    $v = str_clean($v);
    $len = mb_strlen($v);
    if ($len < $min || $len > $max) return false;
    return (bool)preg_match("/^[\p{L}\p{M}\-\'\. ]+$/u", $v);
}

function is_valid_password(string $pwd): bool {
    if (strlen($pwd) < 8) return false;
    $hasLetter = preg_match('/[A-Za-z]/', $pwd);
    $hasDigit  = preg_match('/\d/', $pwd);
    return $hasLetter && $hasDigit;
}

function set_remember_cookie(string $token, int $days = 10): void {
    setcookie('remember', $token, [
        'expires'  => time() + (86400 * $days),
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clear_remember_cookie(): void {
    setcookie('remember', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function disableOldTokens(PDO $pdo, int $userId, string $typeToken): void {
    $stmt = $pdo->prepare("UPDATE tokenUser SET status = 0 WHERE user = ? AND typeToken = ?");
    $stmt->execute([$userId, $typeToken]);
}




function verificationUpdateProfile(array $data, array $files, PDO $pdo, int $userId): array {
    $values = [
        'first_name'  => str_clean($data['first_name'] ?? ''),
        'last_name'   => str_clean($data['last_name'] ?? ''),
        'phone'       => str_clean($data['phone'] ?? ''),
        'birthdate'   => str_clean($data['birthdate'] ?? ''),
        'numero'      => str_clean($data['numero'] ?? ''),
        'taille'      => str_clean($data['taille'] ?? ''),
        'poids'       => str_clean($data['poids'] ?? ''),
        'description' => str_clean($data['description'] ?? ''),
        'nationality' => $data['nationality'] ?? null,  // id
        'city'        => str_clean($data['city'] ?? ''),
    ];
    $errors = [];

    // Noms
    if ($values['first_name'] === '' || !is_valid_name($values['first_name'])) $errors['first_name'] = "Prénom invalide.";
    if ($values['last_name']  === '' || !is_valid_name($values['last_name']))  $errors['last_name']  = "Nom invalide.";

    // Téléphone (optionnel)
    if ($values['phone'] !== '') {
        if (!is_valid_phone($values['phone'])) {
            $errors['phone'] = "Téléphone invalide.";
        } elseif (userPhoneExistsForOther($pdo, phone_normalize($values['phone']), $userId)) {
            $errors['phone'] = "Ce numéro est déjà utilisé.";
        } else {
            $values['phone'] = phone_normalize($values['phone']);
        }
    }

    // Date de naissance (optionnelle)
    if ($values['birthdate'] !== '' && !is_valid_birthdate($values['birthdate'])) {
        $errors['birthdate'] = "Date de naissance invalide.";
    }

    // Numéro / taille / poids (optionnels)
    if ($values['numero'] !== '' && !preg_match('/^\d{0,5}$/', $values['numero'])) $errors['numero'] = "Numéro invalide.";
    if ($values['taille'] !== '' && (!ctype_digit($values['taille']) || (int)$values['taille'] > 250)) $errors['taille'] = "Taille invalide.";
    if ($values['poids']  !== '' && (!ctype_digit($values['poids'])  || (int)$values['poids']  > 300)) $errors['poids']  = "Poids invalide.";
    if (mb_strlen($values['description']) > 255) $errors['description'] = "Description trop longue (255 max).";

    // Nationalité / Ville (id ou vide)
    if ($values['nationality'] !== null && $values['nationality'] !== '' &&
        filter_var($values['nationality'], FILTER_VALIDATE_INT) === false) {
        $errors['nationality'] = "Pays invalide.";
    } else {
        $values['nationality'] = $values['nationality'] ? (int)$values['nationality'] : null;
    }

    // Ville (texte, optionnelle). Si fournie → format + pays requis
    if ($values['city'] !== '') {
        if (!is_valid_city($values['city'])) {
            $errors['city'] = "Ville invalide (2–100 caractères).";
        } elseif (empty($values['nationality'])) {
            $errors['city'] = "Sélectionne un pays avant la ville.";
        }
    }

    // Upload image (optionnel)
    if (!empty($files['picture']) && is_uploaded_file($files['picture']['tmp_name'])) {
        $f = $files['picture'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors['picture'] = "Upload échoué.";
        } elseif ($f['size'] > 4 * 1024 * 1024) {
            $errors['picture'] = "Fichier trop volumineux (≤ 4 Mo).";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($f['tmp_name']);
            $ext = match($mime) {
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default      => null
            };
            if (!$ext) $errors['picture'] = "Format image invalide (jpg, png, webp).";
            else $picture_ready_tmp = ['tmp' => $f['tmp_name'], 'ext' => $ext];
        }
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
        'values' => $values,
        'picture_ready_tmp' => $picture_ready_tmp ?? null,
    ];
}
