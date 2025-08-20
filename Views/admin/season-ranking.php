<?php
// Views/admin/season-ranking.php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$csrf      = $_SESSION['csrf'] ?? '';
$seasonId  = (int)($season['seasonId'] ?? 0);
$seasonYr  = (int)($season['year'] ?? 0);
$ranking   = $ranking ?? []; // [{rankingId, point, pilotId, firstName, lastName}]
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Classement — Saison <?= e($seasonYr) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="bg-indigo-600 text-white">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Saison <?= e($seasonYr) ?> — Classement</h1>
      <div class="flex items-center gap-2">
        <a href="/admin/seasons/<?= $seasonId ?>/attach-drivers"
           class="px-3 py-2 rounded-lg bg-indigo-500 hover:bg-indigo-400">Lier des pilotes</a>
        <a href="/dashboard-races"
           class="px-3 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Retour</a>
      </div>
    </div>
  </header>

  <main class="max-w-6xl mx-auto p-6">
    <div class="rounded-xl border bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h2 class="text-lg font-semibold">Classement actuel</h2>
          <p class="text-sm text-slate-500">L’attribution des points est manuelle.</p>
        </div>
        <div class="w-full sm:w-72">
          <input id="filter" type="text"
                 placeholder="Filtrer par nom…"
                 class="w-full rounded-lg border px-3 py-2 text-sm" />
        </div>
      </div>

      <form method="post" action="/admin/seasons/<?= $seasonId ?>/ranking/save" class="mt-4">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="overflow-x-auto rounded-lg border">
          <table class="min-w-full text-left">
            <thead class="bg-slate-100 text-slate-700 text-sm">
              <tr>
                <th class="p-3 w-16">Pos.</th>
                <th class="p-3">Pilote</th>
                <th class="p-3 w-40">Points</th>
              </tr>
            </thead>
            <tbody id="rankBody" class="text-sm">
              <?php if (!empty($ranking)): ?>
                <?php
                  // Rang "dense" calculé dans la boucle (1,1,3… en cas d’égalité)
                  $rank = 0; $prevPts = null; $i = 0;
                ?>
                <?php foreach ($ranking as $row): ?>
                  <?php
                    $i++;
                    $rid   = (int)($row['rankingId'] ?? 0);
                    $pid   = (int)($row['pilotId'] ?? 0);
                    $name  = trim(($row['lastName'] ?? '').' '.($row['firstName'] ?? ''));
                    $pts   = (float)($row['point'] ?? 0);

                    if ($prevPts === null || $pts < $prevPts) {
                      $rank = $i;
                      $prevPts = $pts;
                    }
                  ?>
                  <tr class="border-t rank-row">
                    <td class="p-3 font-medium text-slate-700">#<?= $rank ?></td>
                    <td class="p-3">
                      <div class="font-medium"><?= e($name ?: ('Pilote #'.$pid)) ?></div>
                      <div class="text-xs text-slate-500">ID pilote: <?= $pid ?> — ID ranking: <?= $rid ?></div>
                    </td>
                    <td class="p-3">
                      <input
                        type="number"
                        name="points[<?= $rid ?>]"
                        value="<?= e((string)$pts) ?>"
                        min="0"
                        step="0.1"
                        inputmode="decimal"
                        class="w-28 rounded-lg border px-3 py-2 text-sm">
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" class="p-6 text-center text-slate-500">
                    Aucun pilote lié à cette saison.<br>
                    <a class="text-indigo-600 hover:underline" href="/admin/seasons/<?= $seasonId ?>/attach-drivers">Lier des pilotes maintenant</a>.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if (!empty($ranking)): ?>
          <div class="mt-4 flex justify-end gap-2">
            <a href="/admin/seasons/<?= $seasonId ?>/attach-drivers"
               class="px-4 py-2 rounded-lg border hover:bg-slate-50">Gérer les pilotes</a>
            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
              Enregistrer les points
            </button>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </main>

  <script>
    // Filtre simple côté client
    const filter = document.getElementById('filter');
    const rows   = document.querySelectorAll('#rankBody .rank-row');
    filter?.addEventListener('input', () => {
      const q = filter.value.toLowerCase();
      rows.forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
