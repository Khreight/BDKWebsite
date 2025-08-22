<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-blue-100 py-8">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">

        <div class="flex flex-col items-center mb-6">
            <img src="/Assets/Logo/logo.png" alt="Logo" class="h-14 mb-2">
            <h2 class="text-2xl font-extrabold text-blue-700 mb-1">Renvoyer la confirmation</h2>
            <p class="text-gray-600 text-center text-sm">
                Indique ton adresse e-mail pour recevoir un nouveau lien de confirmation.
            </p>
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

        <form action="/resend-confirmation" method="post" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Adresse e-mail
                </label>
                <input type="email" name="email" id="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400
                              text-sm"
                       placeholder="exemple@mail.com" required>
            </div>

            <button type="submit" name="submitResend"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 rounded-lg shadow
                           transition text-base tracking-wide">
                📧 Renvoyer l'e-mail
            </button>
        </form>

        <p class="text-center text-gray-500 text-xs mt-6">
            Tu as déjà confirmé ton e-mail ?
            <a href="/login" class="text-blue-600 hover:underline font-semibold">Se connecter</a>
        </p>
    </div>
</div>
