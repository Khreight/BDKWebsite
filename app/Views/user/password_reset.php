<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-blue-100 py-8">
  <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
    <div class="flex flex-col items-center mb-6">
      <img src="/Assets/Logo/logo.png" alt="Logo" class="h-14 mb-2">
      <h2 class="text-2xl font-extrabold text-blue-700 mb-1">Nouveau mot de passe</h2>
      <p class="text-gray-600 text-center text-sm">Choisis un nouveau mot de passe pour ton compte.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="mb-4 p-3 text-sm text-red-700 bg-red-100 border border-red-300 rounded-lg">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="mb-4 p-3 text-sm text-green-700 bg-green-100 border border-green-300 rounded-lg">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <form action="" method="post" class="space-y-4">
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
        <input type="password" id="password" name="password"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm"
               placeholder="Au moins 8 caractères, une lettre et un chiffre" minlength="8" required>
      </div>

      <div>
        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirme le mot de passe</label>
        <input type="password" id="password_confirm" name="password_confirm"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm"
               minlength="8" required>
      </div>

      <button type="submit" name="submitPasswordReset"
              class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 rounded-lg shadow transition text-base tracking-wide">
        🔒 Mettre à jour le mot de passe
      </button>
    </form>
  </div>
</div>
