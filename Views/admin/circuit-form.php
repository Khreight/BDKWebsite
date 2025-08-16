<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$csrf = $_SESSION['csrf'] ?? '';
$mode = $mode ?? 'create';
$circuit = $circuit ?? [];
$countries = $countries ?? [];
$h = fn($k,$d='') => e($circuit[$k] ?? $d);
$selCountry = (int)($circuit['countryId'] ?? 0);
$cityName   = $circuit['city_name'] ?? '';
$title = $mode === 'edit' ? "Modifier le circuit" : "Créer un circuit";
$action = $mode === 'edit' ? "/admin/circuits/".(int)$circuit['circuitId']."/update" : "/admin/circuits/create";
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?></title><script src="https://cdn.tailwindcss.com"></script>
</head><body class="min-h-screen bg-slate-50">
<header class="bg-indigo-600 text-white">
  <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
    <h1 class="text-2xl font-semibold"><?= e($title) ?></h1>
    <a href="/dashboard-races" class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Retour</a>
  </div>
</header>
<main class="max-w-4xl mx-auto p-6">
  <?php if ($m = flash_take('error')): ?><div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-700"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash_take('success')): ?><div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-emerald-700"><?= e($m) ?></div><?php endif; ?>

  <form method="post" action="<?= e($action) ?>" class="rounded-xl border bg-white p-5 shadow-sm grid gap-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <div>
      <label class="text-sm text-slate-600">Nom du circuit</label>
      <input name="nameCircuit" required class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $h('nameCircuit') ?>">
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm text-slate-600">Pays</label>
        <select name="countryId" required class="mt-1 w-full rounded-lg border px-3 py-2">
          <option value="">— Sélectionner —</option>
          <?php foreach ($countries as $co): ?>
            <option value="<?= (int)$co['countryId'] ?>" <?= $selCountry===(int)$co['countryId']?'selected':'' ?>><?= e($co['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm text-slate-600">Ville</label>
        <input name="cityName" required class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= e($cityName) ?>">
      </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm text-slate-600">Rue</label>
        <input name="street" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $h('address_street') ?>">
      </div>
      <div>
        <label class="text-sm text-slate-600">Numéro</label>
        <input type="number" name="number" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $h('address_number') ?>">
      </div>
    </div>
    <div>
      <label class="text-sm text-slate-600">Image (URL)</label>
      <input type="url" name="picture" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $h('picture') ?>">
    </div>
    <div class="flex gap-2">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"><?= $mode==='edit'?'Enregistrer':'Créer' ?></button>
      <a href="/dashboard-races" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Annuler</a>
    </div>
  </form>
</main>
</body></html>
