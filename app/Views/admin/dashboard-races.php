<?php
// Views/admin/dashboard-races.php

/* ---------- Helpers ---------- */
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fdate')) {
  function fdate(?string $dt): string {
    if (!$dt) return '';
    try { return (new DateTimeImmutable($dt))->format('d/m/Y H:i'); }
    catch(Throwable) { return (string)$dt; }
  }
}
if (!function_exists('race_status')) {
  function race_status(array $r): array {
    $now  = new DateTimeImmutable('now');
    $race = !empty($r['date']) ? new DateTimeImmutable($r['date']) : null;
    $open = !empty($r['registration_open']) ? new DateTimeImmutable($r['registration_open']) : null;
    $close= !empty($r['registration_close']) ? new DateTimeImmutable($r['registration_close']) : null;

    if ($race && $now > $race) return ['Fin de la course',        'bg-rose-50 text-rose-700 border border-rose-200'];
    if ($open && $now < $open) return ['Phase d’attente',         'bg-slate-50 text-slate-700 border border-slate-200'];
    if ($open && $close && $now >= $open && $now <= $close)
                               return ['Phase d’inscriptions',    'bg-emerald-50 text-emerald-700 border border-emerald-200'];
    if ($close && $now > $close && (!$race || $now <= $race))
                               return ['Inscriptions clôturées',  'bg-amber-50 text-amber-700 border border-amber-200'];
    if ($race && $now < $race) return ['Programmée',              'bg-indigo-50 text-indigo-700 border border-indigo-200'];
    return ['—', 'bg-slate-50 text-slate-600 border border-slate-200'];
  }
}

/* ---------- Data (safe defaults) ---------- */
$csrf     = $_SESSION['csrf'] ?? '';
$circuits = $circuits ?? [];
$seasons  = $seasons  ?? [];
$races    = $races    ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard — Courses / Saisons / Circuits</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="bg-indigo-600 text-white">
    <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Administration — Courses</h1>
      <a href="/dashboard-administrator" class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Retour</a>
    </div>
  </header>

  <main class="mx-auto max-w-7xl p-6 space-y-10">
    <!-- Flash -->
    <?php if ($m = flash_take('error')): ?>
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_take('success')): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
    <?php endif; ?>

    <!-- ==================== CIRCUITS ==================== -->
    <section id="circuits" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Circuits</h2>
        <!-- Navigation as <a> (no form) -->
        <a href="/admin/circuits/new" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">+ Créer un circuit</a>
      </div>

      <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full text-left">
          <thead class="bg-slate-100 text-slate-700 text-sm">
            <tr>
              <th class="p-3">Circuit</th>
              <th class="p-3">Ville</th>
              <th class="p-3">Pays</th>
              <th class="p-3">Adresse</th>
              <th class="p-3">Image</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <?php foreach ($circuits as $c): ?>
              <tr class="border-t">
                <td class="p-3 font-medium"><?= e($c['nameCircuit'] ?? '') ?></td>
                <td class="p-3"><?= e($c['city_name'] ?? '') ?></td>
                <td class="p-3"><?= e($c['country_name'] ?? '') ?></td>
                <td class="p-3">
                  <?php
                    $addr = '';
                    if (!empty($c['address_street'])) $addr .= $c['address_street'].' ';
                    if (!empty($c['address_number'])) $addr .= $c['address_number'];
                    echo e(trim($addr) ?: '—');
                  ?>
                </td>
                <td class="p-3">
                  <?php if (!empty($c['picture'])): ?>
                    <img src="<?= e($c['picture']) ?>" alt="" class="h-10 w-16 object-cover rounded border" />
                  <?php else: ?>
                    <span class="text-slate-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="p-3">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Links for navigation -->
                    <a href="/admin/circuits/<?= (int)$c['circuitId'] ?>/edit" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Modifier</a>
                    <!-- Delete uses a small, isolated form -->
                    <form method="post" action="/admin/circuits/<?= (int)$c['circuitId'] ?>/delete" onsubmit="return confirm('Supprimer ce circuit ?');">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                      <button type="submit" class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50">Supprimer</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($circuits)): ?>
              <tr><td colspan="6" class="p-6 text-center text-slate-500">Aucun circuit.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ==================== SAISONS ==================== -->
    <section id="seasons" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Saisons</h2>
        <a href="/admin/seasons/new" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">+ Créer une saison</a>
      </div>

      <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full text-left">
          <thead class="bg-slate-100 text-slate-700 text-sm">
            <tr>
              <th class="p-3">Année</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <?php foreach ($seasons as $s): ?>
              <tr class="border-t">
                <td class="p-3 font-medium"><?= (int)($s['year'] ?? 0) ?></td>
                <td class="p-3">
                  <div class="flex items-center justify-end gap-2">
                    <a href="/admin/seasons/<?= (int)$s['seasonId'] ?>/ranking" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Voir classement</a>
                    <a href="/admin/seasons/<?= (int)$s['seasonId'] ?>/attach-drivers"
                      class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">
                      Sélectionner participants
                    </a>
                    <a href="/admin/seasons/<?= (int)$s['seasonId'] ?>/edit" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Modifier</a>
                    <form method="post" action="/admin/seasons/<?= (int)$s['seasonId'] ?>/delete" onsubmit="return confirm('Supprimer cette saison ?');">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                      <button type="submit" class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50">Supprimer</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($seasons)): ?>
              <tr><td colspan="2" class="p-6 text-center text-slate-500">Aucune saison.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ===================== COURSES ==================== -->
    <section id="races" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Courses</h2>
        <a href="/admin/races/new" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">+ Créer une course</a>
      </div>

      <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full text-left">
          <thead class="bg-slate-100 text-slate-700 text-sm">
            <tr>
              <th class="p-3">Date</th>
              <th class="p-3">Circuit</th>
              <th class="p-3">Saison</th>
              <th class="p-3">Statut</th>
              <th class="p-3">Capacité</th>
              <th class="p-3">Prix</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <?php foreach ($races as $r): ?>
              <?php
                [$statusLabel, $statusCls] = race_status($r);
                $cap = '—';
                if (!is_null($r['capacity_min']) || !is_null($r['capacity_max'])) {
                  $cap = (string)($r['capacity_min'] ?? 0) . '–' . (string)($r['capacity_max'] ?? '∞');
                }
                $price = is_null($r['price_cents']) ? '—' : (number_format(((int)$r['price_cents'])/100, 2, ',', ' ').' €');
              ?>
              <tr class="border-t">
                <td class="p-3 font-medium"><?= e(fdate($r['date'] ?? null)) ?></td>
                <td class="p-3"><?= e($r['circuitName'] ?? '') ?></td>
                <td class="p-3"><?= e((string)($r['seasonYear'] ?? '')) ?></td>
                <td class="p-3">
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= e($statusCls) ?>">
                    <?= e($statusLabel) ?>
                  </span>
                </td>
                <td class="p-3"><?= e($cap) ?></td>
                <td class="p-3"><?= e($price) ?></td>
                <td class="p-3">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Voir / Modifier = liens -->
                    <a href="/admin/races/<?= (int)$r['raceId'] ?>" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Voir</a>
                    <a href="/admin/races/<?= (int)$r['raceId'] ?>/edit" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Modifier</a>
                    <!-- Supprimer = form dédié -->
                    <form method="post" action="/admin/races/<?= (int)$r['raceId'] ?>/delete" onsubmit="return confirm('Supprimer cette course ?');">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                      <button type="submit" class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50">Supprimer</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($races)): ?>
              <tr><td colspan="7" class="p-6 text-center text-slate-500">Aucune course.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
