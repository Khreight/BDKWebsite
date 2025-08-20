<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$tel = "+32 471 77 29 16";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Devenir pilote</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <!-- Bandeau -->
  <header class="bg-gradient-to-r from-indigo-600 to-violet-600 text-white">
    <div class="mx-auto max-w-5xl px-6 py-8">
      <h1 class="text-3xl font-bold tracking-tight">Devenir pilote</h1>
      <p class="mt-2 text-white/90">Fais une demande pour rejoindre la compétition officielle.</p>
    </div>
  </header>

  <main class="mx-auto max-w-5xl p-6 space-y-6">
    <?php if (!empty($error)): ?>
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Colonne 1: Infos -->
      <section class="lg:col-span-2 rounded-2xl border bg-white shadow-sm overflow-hidden">
        <div class="p-6 border-b">
          <h2 class="text-xl font-semibold">Comment ça marche ?</h2>
        </div>
        <div class="p-6 space-y-4 text-slate-700">
          <p>
            Pour participer aux courses, il faut d’abord soumettre une <strong>demande d’intégration</strong> en tant que pilote.
            Une fois envoyée, <strong>les organisateurs valident</strong> les demandes.
          </p>
          <ul class="list-disc pl-6 space-y-2">
            <li>Ta demande passe en statut <em>« en attente »</em> le temps de la validation.</li>
            <li>Les nouveaux pilotes sont <strong>généralement acceptés lors du passage à une nouvelle saison</strong>.</li>
            <li>Tu peux demander aussi en <strong>envoyant un message</strong> à l’organisation.</li>
          </ul>
          <div class="rounded-xl bg-slate-50 border p-4">
            <div class="font-medium text-slate-800 flex items-center gap-2">
              📱 Contacter l’organisation
            </div>
            <div class="mt-1 text-slate-700">
              Par message au <a class="text-indigo-600 hover:underline" href="tel:<?= e($tel) ?>"><?= e($tel) ?></a>.
            </div>
            <div class="mt-2 text-xs text-slate-500">
              Astuce&nbsp;: mentionne ton nom, prénom et ta motivation. 🙂
            </div>
          </div>
        </div>
      </section>

      <!-- Colonne 2: Carte action -->
      <aside class="rounded-2xl border bg-white shadow-sm overflow-hidden">
        <div class="p-6 border-b">
          <h3 class="text-lg font-semibold">Ta candidature</h3>
        </div>
        <div class="p-6 space-y-4">
          <?php if (!empty($isPending) && $isPending): ?>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
              Demande envoyée — en attente de validation.
              <?php if (!empty($requestedAt)): ?>
                <div class="text-xs text-amber-700/80 mt-1">
                  Depuis le <?= e((new DateTime($requestedAt))->format('d/m/Y H:i')) ?>.
                </div>
              <?php endif; ?>
            </div>
            <p class="text-sm text-slate-600">
              Tu peux contacter l’organisation si besoin : <a class="text-indigo-600 hover:underline" href="tel:<?= e($tel) ?>"><?= e($tel) ?></a>.
            </p>
            <a href="/" class="inline-flex items-center justify-center w-full rounded-lg border px-4 py-2 hover:bg-slate-50">
              Retour à l’accueil
            </a>
          <?php elseif (!empty($canRequest) && $canRequest): ?>
            <form method="post" action="/become-driver" class="space-y-3">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <p class="text-sm text-slate-600">
                En cliquant ci-dessous, ta demande sera transmise aux organisateurs. Ton statut passera en <strong>« en attente »</strong>.
              </p>
              <button class="w-full rounded-lg bg-indigo-600 text-white px-4 py-3 font-medium hover:bg-indigo-700" name="submitRequest" value="1">
                Demander à devenir pilote
              </button>
              <p class="text-xs text-slate-500">
                Conseil : envoie aussi un message au <a class="text-indigo-600 hover:underline" href="tel:<?= e($tel) ?>"><?= e($tel) ?></a> pour prévenir.
              </p>
            </form>
          <?php else: ?>
            <div class="text-sm text-slate-600">
              Cette page est réservée aux membres non pilotes.
            </div>
            <a href="/" class="inline-flex items-center justify-center w-full rounded-lg border px-4 py-2 hover:bg-slate-50">
              Retour à l’accueil
            </a>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </main>
</body>
</html>
