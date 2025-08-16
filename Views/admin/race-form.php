<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$csrf = $_SESSION['csrf'] ?? '';
$mode = $mode ?? 'create';
$race = $race ?? [];
$circuits = $circuits ?? [];
$seasons  = $seasons ?? [];
$title  = $mode==='edit' ? "Modifier la course" : "Créer une course";
$action = $mode==='edit' ? "/admin/races/".(int)$race['raceId']."/update" : "/admin/races/create";
$val = function($k,$d=''){ return e($race[$k] ?? $d); };
$valDT = function($k){ if (empty($GLOBALS['race'][$k])) return ''; $s = $GLOBALS['race'][$k]; return e(str_replace(' ', 'T', substr($s,0,16))); };
$sel = fn($k) => (int)($race[$k] ?? 0);
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

  <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="rounded-xl border bg-white p-5 shadow-sm grid gap-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm text-slate-600">Circuit</label>
        <select name="circuitId" required class="mt-1 w-full rounded-lg border px-3 py-2">
          <option value="">— Sélectionner —</option>
          <?php foreach ($circuits as $c): ?>
            <option value="<?= (int)$c['circuitId'] ?>" <?= $sel('circuitId')===(int)$c['circuitId']?'selected':'' ?>>
              <?= e($c['nameCircuit'].' — '.$c['city_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm text-slate-600">Saison</label>
        <select name="seasonId" required class="mt-1 w-full rounded-lg border px-3 py-2">
          <option value="">— Sélectionner —</option>
          <?php foreach ($seasons as $s): ?>
            <option value="<?= (int)$s['seasonId'] ?>" <?= $sel('seasonId')===(int)$s['seasonId']?'selected':'' ?>>
              <?= (int)$s['year'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm text-slate-600">Date</label>
        <input type="datetime-local" name="date" required class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $valDT('date') ?>">
      </div>
      <div>
        <label class="text-sm text-slate-600">Vidéo (optionnel)</label>
        <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" class="mt-1 w-full rounded-lg border px-3 py-2">
        <?php if (!empty($race['video'])): ?>
          <div class="text-xs text-slate-500 mt-1">Actuelle : <?= e(basename($race['video'])) ?></div>
          <!-- <input type="hidden" name="video_current" value="<?= e($race['video']) ?>"> -->
        <?php endif; ?>
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-3">
      <div>
        <label class="text-sm text-slate-600">Prix (centimes)</label>
        <input type="number" name="price_cents" min="0" step="1" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $val('price_cents') ?>">
      </div>
      <div>
        <label class="text-sm text-slate-600">Capacité min</label>
        <input type="number" name="capacity_min" min="0" step="1" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $val('capacity_min') ?>">
      </div>
      <div>
        <label class="text-sm text-slate-600">Capacité max</label>
        <input type="number" name="capacity_max" min="0" step="1" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $val('capacity_max') ?>">
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm text-slate-600">Ouverture inscriptions</label>
        <input type="datetime-local" name="registration_open" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $valDT('registration_open') ?>">
      </div>
      <div>
        <label class="text-sm text-slate-600">Clôture inscriptions</label>
        <input type="datetime-local" name="registration_close" class="mt-1 w-full rounded-lg border px-3 py-2" value="<?= $valDT('registration_close') ?>">
      </div>
    </div>

    <div>
      <label class="text-sm text-slate-600">Description</label>
      <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2"><?= $race['description'] ?? '' ?></textarea>
    </div>

    <div class="flex gap-2">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"><?= $mode==='edit'?'Enregistrer':'Créer' ?></button>
      <a href="/dashboard-races" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Annuler</a>
    </div>
  </form>
</main>
</body></html>
