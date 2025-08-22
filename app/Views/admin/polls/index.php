<?php if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); } } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Admin — Sondages</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">

<header class="bg-indigo-600 text-white">
  <div class="mx-auto max-w-7xl px-6 h-16 flex items-center justify-between">
    <h1 class="text-lg font-semibold">Sondages (admin)</h1>
    <a href="/admin/polls/new"
    class="inline-flex items-center rounded-lg bg-white text-indigo-700 px-4 py-2 hover:bg-slate-100">
    + Créer un sondage
</a>
<a href="/dashboard-administrator"
   class="inline-flex items-center rounded-lg bg-white text-indigo-700 px-4 py-2 hover:bg-slate-100">
  Retour
</a>
  </div>
</header>

<main class="mx-auto max-w-7xl px-6 py-6 space-y-4">
  <?php if ($m = (function_exists('flash_take')?flash_take('success'):null)): ?>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($m = (function_exists('flash_take')?flash_take('error'):null)): ?>
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
  <?php endif; ?>

  <div class="rounded-xl border bg-white overflow-x-auto">
    <table class="min-w-full text-left">
      <thead class="bg-slate-100 text-slate-700 text-sm">
        <tr>
          <th class="p-3">Titre</th>
          <th class="p-3">Type</th>
          <th class="p-3">Choix multiples</th>
          <th class="p-3">Vidéo</th>
          <th class="p-3">Créé le</th>
          <th class="p-3">Votes</th>
          <th class="p-3 w-72">Actions</th>
        </tr>
      </thead>
      <tbody class="text-sm">
      <?php foreach (($polls ?? []) as $p): ?>
        <tr class="border-t">
          <td class="p-3 font-medium"><?= e($p['titlePoll']) ?></td>
          <td class="p-3"><?= e($p['pollType']) ?></td>
          <td class="p-3"><?= !empty($p['isManyChoice']) ? 'Oui' : 'Non' ?></td>
          <td class="p-3"><?= !empty($p['video']) ? 'Oui' : '—' ?></td>
          <td class="p-3"><?= e($p['pollDate'] ? (new DateTime($p['pollDate']))->format('d/m/Y H:i') : '—') ?></td>
          <td class="p-3"><?= (int)($p['votes'] ?? 0) ?></td>
          <td class="p-3">
            <div class="flex flex-wrap gap-2">
              <a href="/admin/polls/<?= (int)$p['id'] ?>/voters" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Votants</a>
              <a href="/admin/polls/<?= (int)$p['id'] ?>/edit" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Modifier</a>
              <form method="post" action="/admin/polls/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm('Supprimer ce sondage ?');">
                <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
                <button class="px-3 py-1.5 rounded-lg border text-rose-700 hover:bg-rose-50">Supprimer</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($polls)): ?>
        <tr><td class="p-6 text-center text-slate-500" colspan="7">Aucun sondage.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
