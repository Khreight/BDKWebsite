<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$raceDate   = !empty($race['date']) ? new DateTime($race['date']) : new DateTime('now');
$raceTitle  = trim(($race['circuitName'] ?? 'Course').' — '.(($race['seasonYear'] ?? '—').' '.$raceDate->format('d/m/Y H:i')));
$rowsCount  = max(1, (int)($defaultRows ?? 1));
$participants = $participants ?? [];

$mode        = $mode ?? 'create';
$raceId      = (int)($race['raceId'] ?? 0);
$formAction  = ($mode === 'edit')
  ? "/admin/races/{$raceId}/results/update"
  : "/admin/races/{$raceId}/results/save";

$existing    = $existing ?? [];       // lignes existantes (si edit)
$defaultRows = $defaultRows ?? 10;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Saisir les résultats — <?= e($raceTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="bg-indigo-600 text-white">
    <div class="mx-auto max-w-6xl px-6 py-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold">Saisir les résultats</h1>
      <a href="/admin/races<?= isset($race['raceId']) ? '/'.(int)$race['raceId'] : '' ?>" class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Retour course</a>
    </div>
  </header>

  <main class="mx-auto max-w-6xl p-6 space-y-6">
    <?php if ($m = flash_take('error')): ?>
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_take('success')): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
    <?php endif; ?>

    <div class="rounded-xl border bg-white shadow-sm overflow-hidden">
      <div class="p-5 border-b">
        <div class="text-sm text-slate-500"><?= e($race['seasonYear'] ?? '—') ?></div>
        <h2 class="text-lg font-semibold"><?= e($race['circuitName'] ?? '—') ?> — <?= e($raceDate->format('d/m/Y H:i')) ?></h2>
      </div>

      <form method="post" action="/admin/races/<?= (int)$race['raceId'] ?>/results/save" class="p-5 space-y-6" id="resultsForm">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="overflow-x-auto">
          <table class="min-w-full text-left">
            <thead class="bg-slate-100 text-slate-700 text-sm">
              <tr>
                <th class="p-3 w-14">Pos.</th>
                <th class="p-3">Participant</th>
                <th class="p-3 w-36">Vitesse moy. (km/h)</th>
                <th class="p-3 w-32">Écart (s)</th>
                <th class="p-3">Tours (s)</th>
                <th class="p-3 w-24">Points</th>
              </tr>
            </thead>
            <tbody class="text-sm" id="tbodyRows">
              <?php
                $rowsCount = max($rowsCount, count($existing ?? []));
                for ($i = 0; $i < $rowsCount; $i++):
                  $row     = $existing[$i] ?? [];
                  $pos     = isset($row['position']) ? (int)$row['position'] : ($i+1);
                  $pilotId = isset($row['pilotId'])  ? (int)$row['pilotId']  : 0;
                  $avg     = isset($row['avg'])      ? (float)$row['avg']    : null;
                  $gap     = isset($row['gap'])      ? (float)$row['gap']    : 0.0;
                  $pts     = isset($row['points'])   ? (float)$row['points'] : max(22-($pos-1),0);
                  $lapsArr = $row['laps'] ?? [];
              ?>
                <tr class="border-t align-top">
                  <td class="p-3">
                    <input type="hidden" name="position[]" value="<?= $pos ?>">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 font-medium"><?= $pos ?></span>
                  </td>

                  <td class="p-3">
                    <select name="pilotId[]" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                      <option value="">— Sélectionner —</option>
                      <?php foreach ($participants as $u): $uid=(int)$u['id']; ?>
                        <option value="<?= $uid ?>" <?= $uid===$pilotId?'selected':'' ?>>
                          <?php
                            $lbl = trim(($u['lastName']??'').' '.($u['firstName']??''));
                            if (!empty($u['numero'])) $lbl .= " (#".(int)$u['numero'].")";
                            echo e($lbl);
                          ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>

                  <td class="p-3">
                    <input type="number" step="0.01" name="avg[]" placeholder="ex: 87.42"
                          value="<?= $avg!==null? e(number_format($avg,2,'.','')):'' ?>"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2">
                  </td>

                  <td class="p-3">
                    <input type="number" step="0.001" name="gap[]" placeholder="ex: 1.234"
                          value="<?= e(number_format((float)$gap,3,'.','')) ?>"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <div class="text-[11px] text-slate-500 mt-1">Écart avec le leader.</div>
                  </td>

                  <td class="p-3">
                    <div class="space-y-2" data-laps-for="<?= $pos ?>">
                      <?php if (!empty($lapsArr)): $ln=1; foreach ($lapsArr as $lt): ?>
                        <div class="flex items-center gap-2">
                          <input type="number" step="0.001" name="laps[<?= $pos ?>][]" value="<?= e(number_format((float)$lt,3,'.','')) ?>"
                                class="w-36 rounded-lg border border-slate-300 px-2 py-1">
                          <span class="text-xs text-slate-500">Tour <?= $ln++ ?></span>
                          <button type="button" class="text-xs text-rose-600 hover:underline" onclick="this.parentNode.remove()">Supprimer</button>
                        </div>
                      <?php endforeach; endif; ?>
                    </div>
                    <button type="button" class="mt-2 rounded-lg border px-2 py-1 text-xs hover:bg-slate-50"
                            onclick="addLap(<?= $pos ?>)">+ Ajouter un tour</button>
                    <div class="text-xs text-slate-500 mt-1">Saisir en <strong>secondes</strong> (ex : 58.321)</div>
                  </td>

                  <td class="p-3">
                    <input type="number" name="points[]" step="0.5"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 pointsInput"
                          value="<?= e(number_format((float)$pts,1,'.','')) ?>">
                  </td>
                </tr>
              <?php endfor; ?>
              </tbody>


          </table>
        </div>

        <div class="rounded-lg border bg-slate-50 p-4">
          <div class="flex flex-wrap items-end gap-4">
            <div class="grow">
              <label class="block text-sm text-slate-600 mb-1">Meilleur tour (bonus)</label>
              <select name="fastest_pilot" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">— Aucun —</option>
                <?php foreach ($participants as $u): ?>
                  <option value="<?= (int)$u['id'] ?>">
                    <?= e(trim(($u['lastName'] ?? '').' '.($u['firstName'] ?? ''))) ?><?= $u['numero'] ? ' #'.(int)$u['numero'] : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm text-slate-600 mb-1">Points bonus</label>
              <input type="number" step="0.5" name="fastest_points" value="0.5"
                     class="w-28 rounded-lg border border-slate-300 px-3 py-2">
            </div>
          </div>
          <div class="mt-2 text-xs text-slate-500">Par défaut : +0,5 point.</div>
        </div>

        <div class="flex justify-end gap-2">
          <a href="/admin/races/<?= (int)$race['raceId'] ?>" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Annuler</a>
          <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Enregistrer</button>
        </div>
      </form>
    </div>
  </main>

  <script>
    function addLap(pos) {
      const box = document.querySelector('[data-laps-for="'+pos+'"]');
      if (!box) return;
      const idx = box.querySelectorAll('input').length + 1;
      const wrap = document.createElement('div');
      wrap.className = "flex items-center gap-2";
      wrap.innerHTML = `
        <input type="number" step="0.001" name="laps[${pos}][]" placeholder="ex: 58.321"
               class="w-36 rounded-lg border border-slate-300 px-2 py-1">
        <span class="text-xs text-slate-500">Tour ${idx}</span>
        <button type="button" class="text-xs text-rose-600 hover:underline" onclick="this.parentNode.remove()">Supprimer</button>
      `;
      box.appendChild(wrap);
    }
  </script>
</body>
</html>
