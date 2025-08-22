<?php
// Views/public/season-ranking.php

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/* --- Données avec valeurs par défaut pour éviter notices --- */
$season        = $season        ?? [];
$standings     = $standings     ?? [];
$seasonRaces   = $seasonRaces   ?? [];   // <- utilisé plus bas
$allSeasons    = $allSeasons    ?? [];

$currentSeasonId = (int)($season['seasonId'] ?? 0);
$seasonYear      = (int)($season['year'] ?? 0);

/* --- Rang “dense” sur le classement (1,1,3…) --- */
$dense = []; $rank = 0; $prevPts = null; $i = 0;
foreach ($standings as $row) {
  $i++;
  $pts = isset($row['point']) ? (float)$row['point'] : 0.0;
  if ($prevPts === null || $pts < $prevPts) { $rank = $i; $prevPts = $pts; }
  $dense[(int)($row['pilotId'] ?? 0)] = $rank;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Classement — Saison <?= e($seasonYear) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">

  <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="/" class="font-bold">BDK Karting</a>
      <nav class="text-sm">
        <a href="/" class="text-indigo-600 hover:underline">Accueil</a>
      </nav>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <div class="text-sm text-slate-500">Classement</div>
        <h1 class="text-3xl sm:text-4xl font-extrabold">
          Saison <?= e($seasonYear) ?>
        </h1>
      </div>

      <!-- Sélecteur rapide d'autres saisons -->
      <div class="flex flex-wrap gap-2">
        <?php foreach ($allSeasons as $s): ?>
          <?php
            $sid  = (int)($s['seasonId'] ?? 0);
            $syr  = (int)($s['year'] ?? 0);
            $isOn = ($sid === $currentSeasonId);
          ?>
          <a href="/seasons/<?= $sid ?>/ranking"
             class="inline-block px-3 py-1.5 rounded border <?= $isOn ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-slate-50' ?>">
            Saison <?= e($syr) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Top 3 -->
    <section class="mt-8 grid sm:grid-cols-3 gap-4">
      <?php
        $top = array_slice($standings, 0, 3);
        $medals = ['🥇','🥈','🥉'];
      ?>
      <?php foreach ($top as $i=>$row): ?>
        <?php
          $name = trim(($row['firstName'] ?? '').' '.($row['lastName'] ?? ''));
          $pts  = number_format((float)($row['point'] ?? 0), 1, ',', ' ');
          $num  = (string)($row['numero'] ?? '—');
          $pic  = ($row['picture'] ?? 'default.png') ?: 'default.png';
        ?>
        <div class="rounded-2xl border bg-white p-5 shadow-sm hover:shadow-md transition">
          <div class="flex items-center gap-3">
            <img src="/Assets/ProfilesPhoto/<?= e($pic) ?>" class="h-12 w-12 rounded-full object-cover"
                 onerror="this.src='/Assets/ProfilesPhoto/default.png'" alt="">
            <div>
              <div class="text-2xl font-bold"><?= $medals[$i] ?> <?= e($name) ?></div>
              <div class="text-xs text-slate-500">#<?= e($num) ?> — <?= e($pts) ?> pts</div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($top)): ?>
        <div class="col-span-full rounded-xl border bg-white p-6 text-center text-slate-500">Aucun résultat.</div>
      <?php endif; ?>
    </section>

    <!-- Tableau complet -->
    <section class="mt-8 rounded-xl border bg-white shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b bg-slate-50">
        <h2 class="text-lg font-semibold">Classement complet</h2>
      </div>
      <div class="p-5 overflow-x-auto">
        <table class="min-w-full text-left">
          <thead class="bg-slate-100 text-slate-700 text-sm">
            <tr>
              <th class="p-3 w-16">Rang</th>
              <th class="p-3">Pilote</th>
              <th class="p-3 w-24">Numéro</th>
              <th class="p-3 w-28">Points</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <?php foreach ($standings as $row): ?>
              <?php
                $pid  = (int)($row['pilotId'] ?? 0);
                $rankShow = (int)($dense[$pid] ?? 0); // rang dense calculé plus haut
                $name = trim(($row['firstName'] ?? '').' '.($row['lastName'] ?? ''));
                $pts  = number_format((float)($row['point'] ?? 0), 1, ',', ' ');
                $num  = (string)($row['numero'] ?? '—');
                $pic  = ($row['picture'] ?? 'default.png') ?: 'default.png';
              ?>
              <tr class="border-t">
                <td class="p-3 font-semibold">#<?= $rankShow ?></td>
                <td class="p-3">
                  <div class="flex items-center gap-3">
                    <img src="/Assets/ProfilesPhoto/<?= e($pic) ?>" class="h-8 w-8 rounded-full object-cover"
                         onerror="this.src='/Assets/ProfilesPhoto/default.png'" alt="">
                    <a href="/<?= $pid ?>/see" class="hover:underline text-indigo-700"><?= e($name) ?></a>
                  </div>
                </td>
                <td class="p-3">#<?= e($num) ?></td>
                <td class="p-3"><?= e($pts) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($standings)): ?>
              <tr><td colspan="4" class="p-6 text-center text-slate-500">Pas encore de points pour cette saison.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Courses de la saison -->
    <section class="mt-8 rounded-xl border bg-white shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b bg-slate-50 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Courses de la saison <?= e($seasonYear) ?></h2>
        <span class="text-sm text-slate-600">
          <?= count($seasonRaces) ?> course(s)
        </span>
      </div>

      <div class="p-5">
        <?php if (!empty($seasonRaces)): ?>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($seasonRaces as $r): ?>
              <?php
                $rid   = (int)($r['raceId'] ?? 0);
                $date  = !empty($r['date']) ? (new DateTime($r['date']))->format('d/m/Y') : '—';
                $name  = $r['circuitName'] ?? 'Circuit';
                $city  = $r['cityName'] ?? '';
                $has   = !empty($r['hasResults']); // 0/1
                $href  = "/races/$rid";
              ?>
              <a
                <?php if ($has): ?>href="<?= e($href) ?>"<?php endif; ?>
                class="block rounded-lg border px-4 py-3 hover:bg-slate-50 <?php if(!$has): ?>opacity-70 cursor-not-allowed<?php endif; ?>">
                <div class="flex items-center justify-between gap-4">
                  <div>
                    <div class="text-sm text-slate-500"><?= e($date) ?></div>
                    <div class="font-medium"><?= e($name) ?></div>
                    <?php if ($city): ?>
                      <div class="text-xs text-slate-500"><?= e($city) ?></div>
                    <?php endif; ?>
                  </div>
                  <span class="text-xs rounded-full px-2 py-0.5 border
                               <?= $has ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-slate-50 text-slate-600 border-slate-200' ?>">
                    <?= $has ? 'Résultats' : 'Sans résultats' ?>
                  </span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-sm text-slate-500 text-center py-6">Aucune course associée à cette saison.</div>
        <?php endif; ?>
      </div>
    </section>

    <div class="mt-8">
      <a href="/" class="inline-flex items-center rounded-lg border px-4 py-2 hover:bg-slate-50">
        ← Retour à l’accueil
      </a>
    </div>
  </main>
</body>
</html>
