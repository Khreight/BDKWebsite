<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';


function mail_config(): array {
    return [
        'host'       => 'smtp.gmail.com',     // change si besoin
        'port'       => 587,
        'username'   => 'dropace.noreply@gmail.com',
        'password'   => 'imgr pqjm yylv ikhl', // mot de passe d'application Gmail
        'from'       => 'ton.email@gmail.com',
        'from_name'  => 'Karting EBISU',
        'secure'     => PHPMailer::ENCRYPTION_STARTTLS, // ou PHPMailer::ENCRYPTION_SMTPS
        'reply_to'   => null, // ex: 'contact@tondomaine.com'
        'base_url'   => 'http://localhost:8000',
    ];
}

/**
 * Envoi d'un e-mail HTML avec PHPMailer
 */
function send_mail(string $to, string $subject, string $htmlContent, string $altContent = ''): bool {
    $cfg  = mail_config();
    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = $cfg['secure'];
        $mail->Port       = $cfg['port'];

        // From / Reply-To
        $mail->setFrom($cfg['from'], $cfg['from_name']);
        if (!empty($cfg['reply_to'])) {
            $mail->addReplyTo($cfg['reply_to']);
        }

        // Destinataire
        $mail->addAddress($to);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;
        $mail->AltBody = $altContent ?: strip_tags($htmlContent);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Petit template HTML d'email (basique + responsive)
 */
function render_email_template(string $title, string $intro, string $ctaLabel, string $ctaHref, string $footer = ''): string {
    $logoUrl = '/Assets/Logo/logo.png'; // adapte si besoin (URL absolue de préférence)
    $ctaHrefEsc = htmlspecialchars($ctaHref, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>{$title}</title>
</head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="padding:24px 0;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;background:#ffffff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;">
          <tr>
            <td style="padding:24px 24px 0 24px;text-align:center;">
              <img src="https://www.dropbox.com/scl/fi/42y6si6e35jytgmvom75f/logo.png?rlkey=kt61jms8ccdhyxjdxie8tw881&st=4n6yp682&dl=1" alt="Karting EBISU" style="height:48px;">
            </td>
          </tr>
          <tr>
            <td style="padding:8px 24px 0 24px;text-align:center;">
              <h1 style="font-size:20px;margin:0;color:#1d4ed8;">{$title}</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px 0 24px;">
              <p style="font-size:14px;line-height:1.6;margin:0;color:#374151;">{$intro}</p>
            </td>
          </tr>
          <tr>
            <td style="padding:24px;text-align:center;">
              <a href="{$ctaHrefEsc}" style="display:inline-block;background:#1d4ed8;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold;">
                {$ctaLabel}
              </a>
              <p style="font-size:12px;color:#6b7280;margin-top:12px;word-break:break-all;">Si le bouton ne fonctionne pas, copie le lien ci-dessous dans ton navigateur :<br>{$ctaHrefEsc}</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px 24px 24px;">
              <p style="font-size:12px;color:#6b7280;margin:0;">{$footer}</p>
            </td>
          </tr>
        </table>
        <p style="font-size:12px;color:#9ca3af;margin:12px 0 0 0;">© Karting EBISU</p>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Envoi du mail de confirmation d'email
 */
function sendVerificationEmail(string $to, string $token): bool {
    $cfg   = mail_config();
    $link  = rtrim($cfg['base_url'], '/') . '/confirmation/' . $token;

    $html = render_email_template(
        'Confirme ton adresse e-mail',
        "Bonjour,<br>Merci pour ton inscription. Clique sur le bouton ci-dessous pour confirmer ton adresse e-mail (lien valide 15 minutes).",
        'Confirmer mon e-mail',
        $link,
        "Si tu n’es pas à l’origine de cette demande, ignore cet e-mail."
    );

    return send_mail($to, 'Confirme ton adresse e-mail', $html);
}

/**
 * Envoi du mail de réinitialisation de mot de passe
 */
function sendPasswordResetEmail(string $to, string $token): bool {
    $cfg   = mail_config();
    $link  = rtrim($cfg['base_url'], '/') . '/password-reset/' . $token;

    $html = render_email_template(
        'Réinitialisation de mot de passe',
        "Bonjour,<br>Tu as demandé la réinitialisation de ton mot de passe. Clique sur le bouton ci-dessous pour en choisir un nouveau (lien valide 30 minutes).",
        'Réinitialiser mon mot de passe',
        $link,
        "Si tu n’es pas à l’origine de cette demande, ignore cet e-mail."
    );

    return send_mail($to, 'Réinitialisation de ton mot de passe', $html);
}
