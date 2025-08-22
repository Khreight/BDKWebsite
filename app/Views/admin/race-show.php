<?php
// Views/admin/race-show.php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$csrf = $_SESSION['csrf'] ?? '';

function race_status(array $r): array {
  $now      = new DateTimeImmutable('now');
  $raceDate = !empty($r['date']) ? new DateTimeImmutable($r['date']) : null;
  $open     = !empty($r['registration_open']) ? new DateTimeImmutable($r['registration_open']) : null;
  $close    = !empty($r['registration_close']) ? new DateTimeImmutable($r['registration_close']) : null;

  if ($raceDate && $now > $raceDate) return ['Fin de la course','bg-rose-50 text-rose-700 border border-rose-200'];
  if ($open && $now < $open)        return ['Phase d’attente','bg-slate-50 text-slate-700 border border-slate-200'];
  if ($open && $close && $now >= $open && $now <= $close)
                                    return ['Phase d’inscriptions','bg-emerald-50 text-emerald-700 border border-emerald-200'];
  if ($close && $now > $close && (!$raceDate || $now <= $raceDate))
                                    return ['Inscriptions clôturées','bg-amber-50 text-amber-700 border border-amber-200'];
  if ($raceDate && $now < $raceDate) return ['Programmée','bg-indigo-50 text-indigo-700 border border-indigo-200'];
  return ['—','bg-slate-50 text-slate-600 border border-slate-200'];
}

function fdate(?string $dt): string {
  if (!$dt) return '';
  try { return (new DateTimeImmutable($dt))->format('d/m/Y H:i'); }
  catch(Throwable) { return $dt; }
}

[$labelStatus, $clsStatus] = race_status($race);
$valid = (int)($regStats['valide'] ?? 0);
$wait  = (int)($regStats['waited'] ?? 0);
$noval = (int)($regStats['no-valide'] ?? 0);
$total = $valid + $wait + $noval;

// Séparation des listes (affichage)
$regsValid = array_values(array_filter($registrations, fn($r) => $r['status']==='valide'));
$regsWait  = array_values(array_filter($registrations, fn($r) => $r['status']==='waited'));
$regsNoVal = array_values(array_filter($registrations, fn($r) => $r['status']==='no-valide'));

$isAdmin     = !empty($_SESSION['user']) && (int)$_SESSION['user']['role'] === 1;
$isPast      = !empty($race['date']) && (new DateTime($race['date'])) < new DateTime('now');
$hasResults  = isset($raceHasResults) ? (bool)$raceHasResults : false;
$canEnterRes = $isAdmin && $isPast && !$hasResults;
$canEditRes  = $isAdmin && $isPast &&  $hasResults;
$raceId      = (int)$race['raceId'];

// Infos pratiques
$price = isset($race['price_cents']) && $race['price_cents'] !== null
  ? number_format(((int)$race['price_cents'])/100, 2, ',', ' ') . ' €'
  : '—';
$capMin = $race['capacity_min'] !== null ? (int)$race['capacity_min'] : null;
$capMax = $race['capacity_max'] !== null ? (int)$race['capacity_max'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Course #<?= (int)$race['raceId'] ?> — <?= e($race['circuitName'] ?? $race['nameCircuit'] ?? '—') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="bg-indigo-600 text-white">
    <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
      <h1 class="text-2xl font-semibold">
        <?= e($race['circuitName'] ?? $race['nameCircuit'] ?? '—') ?>
      </h1>

      <div class="flex items-center gap-2">
        <?php if ($canEditRes): ?>
          <a href="/admin/races/<?= $raceId ?>/results/edit"
            class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">
            Modifier les résultats
          </a>
        <?php elseif ($canEnterRes): ?>
          <a href="/admin/races/<?= $raceId ?>/results/new"
            class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">
            Saisir les résultats
          </a>
        <?php endif; ?>

        <a href="/dashboard-races"
          class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">
          Retour
        </a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl p-6 space-y-8">
    <!-- Bandeau statut + infos -->
    <section class="rounded-xl border bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?= e($clsStatus) ?>">
            <?= e($labelStatus) ?>
          </span>
          <h2 class="text-lg font-semibold">Course #<?= (int)$race['raceId'] ?></h2>
        </div>

        <div class="text-sm text-slate-600">
          <span class="mr-4">Date course : <strong><?= e(fdate($race['date'] ?? null)) ?></strong></span>
          <span class="mr-4">Ouverture : <strong><?= e(fdate($race['registration_open'] ?? null)) ?></strong></span>
          <span class="mr-4">Clôture : <strong><?= e(fdate($race['registration_close'] ?? null)) ?></strong></span>
          <span>Prix : <strong><?= e($price) ?></strong></span>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div class="rounded-lg border p-4">
          <div class="text-slate-500">Capacité min.</div>
          <div class="text-lg font-semibold"><?= $capMin !== null ? (int)$capMin : '—' ?></div>
        </div>
        <div class="rounded-lg border p-4">
          <div class="text-slate-500">Capacité max.</div>
          <div class="text-lg font-semibold"><?= $capMax !== null ? (int)$capMax : '—' ?></div>
        </div>
        <div class="rounded-lg border p-4">
          <div class="text-slate-500">Inscriptions</div>
          <div class="text-lg font-semibold">
            <?= (int)($regStats['valid_count'] ?? 0) ?> validées / <?= (int)($regStats['waited_count'] ?? 0) ?> en attente (<?= (int)($regStats['total'] ?? 0) ?> total)
          </div>
        </div>
      </div>
    </section>

    <?php if (!empty($results)): ?>
    <!-- Résultats -->
    <section class="rounded-xl border bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Résultats</h2>
        <div class="flex gap-2">
          <?php if ($canEditRes): ?>
            <a href="/admin/races/<?= (int)$race['raceId'] ?>/results/edit" class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Modifier</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-left">
          <thead class="bg-slate-100 text-slate-700 text-sm">
            <tr>
              <th class="p-3 w-14">Pos.</th>
              <th class="p-3">Pilote</th>
              <th class="p-3 w-36">Vitesse moy. (km/h)</th>
              <th class="p-3 w-28">Écart (s)</th>
              <th class="p-3 w-24">Points</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <?php foreach ($results as $r): ?>
              <tr class="border-t">
                <td class="p-3 font-medium">#<?= (int)$r['position'] ?></td>
                <td class="p-3">
                  <?php
                    // Si ton modèle joint déjà les noms, affiche-les. Sinon fallback sur l'ID.
                    if (!empty($r['firstName']) || !empty($r['lastName'])) {
                      echo e(trim(($r['firstName'] ?? '').' '.($r['lastName'] ?? '')));
                    } else {
                      echo 'Pilote #'.(int)$r['pilotId'];
                    }
                  ?>
                </td>
                <td class="p-3"><?= $r['avg']!==null ? number_format((float)$r['avg'],2,',',' ') : '—' ?></td>
                <td class="p-3"><?= number_format((float)($r['gap'] ?? 0),3,',',' ') ?></td>
                <td class="p-3 font-semibold"><?= number_format((float)($r['points'] ?? 0),1,',',' ') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <!-- Inscriptions -->
    <section class="rounded-xl border bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Inscriptions</h2>
        <div class="text-sm text-slate-600">
          <span class="mr-3">✅ Validés: <strong><?= (int)($regStats['valid_count'] ?? 0) ?></strong></span>
          <span class="mr-3">⏳ Attente: <strong><?= (int)($regStats['waited_count'] ?? 0) ?></strong></span>
          <span>❌ Refusés: <strong><?= (int)($regStats['novalid_count'] ?? 0) ?></strong> — Total: <strong><?= (int)($regStats['total'] ?? 0) ?></strong></span>
        </div>
      </div>

      <div class="mt-4 grid md:grid-cols-2 gap-6">
        <!-- Validés -->
        <div>
  <h3 class="font-medium mb-2">✅ Validés</h3>
    <div class="rounded-lg border overflow-hidden">
      <table class="min-w-full text-left">
        <thead class="bg-emerald-100 text-emerald-800 text-sm">
          <tr>
            <th class="p-3">Pilote</th>
            <th class="p-3 w-48">Date</th>
            <th class="p-3 w-40 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="text-sm">
          <?php foreach ($regsValid as $r): ?>
            <tr class="border-t bg-emerald-50">
              <td class="p-3 font-medium text-emerald-900">
                <?= e(trim(($r['lastName']??'').' '.($r['firstName']??''))) ?>
              </td>
              <td class="p-3 text-emerald-900"><?= e(fdate($r['date'] ?? '')) ?></td>
              <td class="p-3">
                <div class="flex justify-end">
                  <form method="post" action="/admin/races/<?= $raceId ?>/registrations/<?= (int)$r['registrationId'] ?>/kick" class="inline">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button class="px-3 py-1.5 text-xs rounded bg-rose-600 text-white hover:bg-rose-700" title="Retirer ce participant">
                      Kick
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($regsValid)): ?>
            <tr><td class="p-4 text-slate-500" colspan="3">Aucun.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>


        <!-- En attente (avec actions) -->
        <div>
          <h3 class="font-medium mb-2">⏳ En attente</h3>
          <div class="rounded-lg border overflow-hidden">
            <table class="min-w-full text-left">
              <thead class="bg-slate-100 text-slate-700 text-sm">
                <tr>
                  <th class="p-3">Pilote</th>
                  <th class="p-3 w-40">Date</th>
                  <th class="p-3 w-56 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="text-sm">
                <?php foreach ($regsWait as $r): ?>
                  <tr class="border-t">
                    <td class="p-3"><?= e(trim(($r['lastName']??'').' '.($r['firstName']??''))) ?></td>
                    <td class="p-3"><?= e(fdate($r['date'] ?? '')) ?></td>
                    <td class="p-3">
                      <div class="flex justify-end gap-2">
                        <form method="post" action="/admin/races/<?= $raceId ?>/registrations/<?= (int)$r['registrationId'] ?>/approve" class="inline">
                          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                          <button class="px-3 py-1.5 text-xs rounded bg-emerald-600 text-white hover:bg-emerald-700" title="Valider cette inscription">
                            Valider
                          </button>
                        </form>
                        <form method="post" action="/admin/races/<?= $raceId ?>/registrations/<?= (int)$r['registrationId'] ?>/reject" class="inline">
                          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                          <button class="px-3 py-1.5 text-xs rounded bg-rose-600 text-white hover:bg-rose-700" title="Refuser cette inscription">
                            Refuser
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($regsWait)): ?>
                  <tr><td class="p-4 text-slate-500" colspan="3">Aucune demande en attente.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php if (!empty($regsNoVal)): ?>
      <div class="mt-6">
        <h3 class="font-medium mb-2">❌ Refusés</h3>
        <div class="rounded-lg border overflow-hidden">
          <table class="min-w-full text-left">
            <thead class="bg-slate-100 text-slate-700 text-sm">
              <tr>
                <th class="p-3">Pilote</th>
                <th class="p-3 w-48">Date</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($regsNoVal as $r): ?>
                <tr class="border-t">
                  <td class="p-3"><?= e(trim(($r['lastName']??'').' '.($r['firstName']??''))) ?></td>
                  <td class="p-3"><?= e(fdate($r['date'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($eligibleSeasonPilots)): ?>
        <section class="rounded-xl border bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Ajouter un participant (pilotes de la saison)</h2>
          </div>
          <form method="post" action="/admin/races/<?= $raceId ?>/registrations/add" class="mt-4 flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <select name="userId" class="border rounded-lg p-2 flex-1">
              <?php foreach ($eligibleSeasonPilots as $p): ?>
                <option value="<?= (int)$p['id'] ?>">
                  <?= e(trim(($p['lastName']??'').' '.($p['firstName']??''))) ?><?= $p['numero'] ? ' — #'.e($p['numero']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
              Valider directement
            </button>
          </form>
        </section>
        <?php else: ?>
        <section class="rounded-xl border bg-white p-5 shadow-sm">
          <h2 class="text-lg font-semibold">Ajouter un participant</h2>
          <p class="mt-2 text-sm text-slate-600">Aucun pilote de la saison à ajouter (soit tous sont déjà en attente/validés, soit la saison n’a pas de participants).</p>
        </section>
      <?php endif; ?>

    </section>
  </main>
</body>
</html>
