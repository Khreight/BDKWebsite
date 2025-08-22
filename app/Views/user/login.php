<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($errors) || !is_array($errors)) $errors = [];
if (!isset($old) || !is_array($old))       $old    = [];

if (!function_exists('e')) {
  function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('has_error')) {
  function has_error($k, $errors) { return !empty($errors[$k]); }
}
if (!function_exists('err')) {
  function err($k, $errors) { return $errors[$k] ?? ''; }
}
if (!function_exists('input_classes')) {
  function input_classes($k, $errors) {
    $base = "w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 transition text-sm";
    return has_error($k, $errors) ? $base." border-red-500 focus:ring-red-400" : $base." border-gray-300 focus:ring-blue-400";
  }
}
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-blue-100 py-8">
  <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-6 md:p-8">
    <div class="text-center mb-6">
      <img src="Assets/Logo/logo.png" alt="BDK Karting Logo" class="h-14 mx-auto mb-2">
      <h1 class="text-2xl font-extrabold text-blue-700">Connexion</h1>
      <p class="text-gray-600 text-sm">Ravi de te revoir 👋</p>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3">
        <p class="text-sm text-green-700 font-medium"><?= e($success) ?></p>
      </div>
    <?php endif; ?>

    <?php if (has_error('email', $errors) && err('email', $errors) === 'Identifiants invalides.'): ?>
      <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3">
        <p class="text-sm text-red-700 font-medium">Identifiants invalides.</p>
      </div>
    <?php endif; ?>

    <?php if (has_error('email', $errors) && err('email', $errors) === "Ton e-mail n’est pas encore confirmé."): ?>
      <div class="mb-5 rounded-lg border border-yellow-200 bg-yellow-50 p-3">
        <p class="text-sm text-yellow-800 font-medium">
          Ton e-mail n’est pas encore confirmé.
          <a href="/resend-confirmation?email=<?= urlencode($old['email'] ?? '') ?>" class="underline text-yellow-800 font-semibold">Renvoyer le mail</a>
        </p>
      </div>
    <?php endif; ?>

    <form action="/login" method="post" novalidate>
      <div class="space-y-4">
        <div>
          <label for="email" class="block text-gray-700 font-semibold mb-1 text-sm">Adresse e-mail</label>
          <input
            type="email"
            id="email"
            name="email"
            value="<?= e($old['email'] ?? '') ?>"
            class="<?= input_classes('email', $errors) ?>"
            aria-invalid="<?= has_error('email', $errors) ? 'true' : 'false' ?>"
            aria-describedby="<?= has_error('email', $errors) ? 'email_error' : '' ?>"
            autocomplete="email"
            placeholder="ex: prenom.nom@example.com"
          >
          <?php if (has_error('email', $errors) && err('email', $errors) !== 'Identifiants invalides.' && err('email', $errors) !== "Ton e-mail n’est pas encore confirmé."): ?>
            <p id="email_error" class="mt-1 text-xs text-red-600"><?= e(err('email', $errors)) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="password" class="block text-gray-700 font-semibold mb-1 text-sm">Mot de passe</label>
          <div class="relative">
            <input
              type="password"
              id="password"
              name="password"
              class="<?= input_classes('password', $errors) ?> pr-10"
              aria-invalid="<?= has_error('password', $errors) ? 'true' : 'false' ?>"
              aria-describedby="<?= has_error('password', $errors) ? 'password_error' : '' ?>"
              autocomplete="current-password"
              placeholder="Ton mot de passe"
            >
            <button type="button" id="togglePwd" class="absolute inset-y-0 right-0 px-3 text-gray-500" aria-label="Afficher le mot de passe">👁️</button>
          </div>
          <?php if (has_error('password', $errors)): ?>
            <p id="password_error" class="mt-1 text-xs text-red-600"><?= e(err('password', $errors)) ?></p>
          <?php endif; ?>
        </div>

        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="remember" value="1" class="rounded border-gray-300">
          Se souvenir de moi
        </label>

        <div class="flex items-center justify-between text-sm">
          <a href="/password-forget" class="text-blue-700 hover:underline font-semibold">Mot de passe oublié ?</a>
        </div>

        <button name="submitLogin" value="submitLogin"
          class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-2.5 rounded-lg shadow transition text-base tracking-wide">
          Se connecter
        </button>
      </div>
    </form>

    <p class="text-center text-gray-500 text-xs mt-4">
      Pas encore de compte ?
      <a href="/register" class="text-blue-600 hover:underline font-semibold">Créer un compte</a>
    </p>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  function toggleVisibility(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.type = el.type === "password" ? "text" : "password";
  }
  document.getElementById("togglePwd")?.addEventListener("click", () => toggleVisibility("password"));

  const firstInvalid = document.querySelector('[aria-invalid="true"]');
  if (firstInvalid) firstInvalid.focus({ preventScroll: false });
});
</script>
