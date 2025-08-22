<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$csrf = $_SESSION['csrf'] ?? '';
$mode = $mode ?? 'create';
$season = $season ?? [];
$title = $mode==='edit' ? "Modifier la saison" : "Créer une saison";
$action = $mode==='edit' ? "/admin/seasons/".(int)$season['seasonId']."/update" : "/admin/seasons/create";
$year   = (int)($season['year'] ?? '');
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?></title><script src="https://cdn.tailwindcss.com"></script>
</head><body class="min-h-screen bg-slate-50">
<header class="bg-indigo-600 text-white">
  <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
    <h1 class="text-2xl font-semibold"><?= e($title) ?></h1>
    <a href="/dashboard-races" class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Retour</a>
  </div>
</header>
<main class="max-w-3xl mx-auto p-6">
  <?php if ($m = flash_take('error')): ?><div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-700"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash_take('success')): ?><div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-emerald-700"><?= e($m) ?></div><?php endif; ?>

  <form method="post" action="<?= e($action) ?>" class="rounded-xl border bg-white p-5 shadow-sm grid gap-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <div>
      <label class="text-sm text-slate-600">Année</label>
      <input type="number" name="year" min="1900" max="2100" required
             class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $year ?: '' ?>">
    </div>
    <div class="flex gap-2">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"><?= $mode==='edit'?'Enregistrer':'Créer' ?></button>
      <a href="/dashboard-races" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Annuler</a>
    </div>
  </form>
</main>
</body></html>
