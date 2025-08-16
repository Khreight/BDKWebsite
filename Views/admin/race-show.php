<?php
// Views/admin/race-show.php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$csrf = $_SESSION['csrf'] ?? '';

function race_status(array $r): array {
  $now = new DateTimeImmutable('now');
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
$valid = (int)($regStats['valid_count'] ?? 0);
$wait  = (int)($regStats['waited_count'] ?? 0);
$noval = (int)($regStats['novalid_count'] ?? 0);
$total = (int)($regStats['total'] ?? 0);

// Séparation des listes (affichage)
$regsValid = array_values(array_filter($registrations, fn($r) => $r['status']==='valide'));
$regsWait  = array_values(array_filter($registrations, fn($r) => $r['status']==='waited'));
$regsNoVal = array_values(array_filter($registrations, fn($r) => $r['status']==='no-valide'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Course #<?= (int)$race['raceId'] ?> — <?= e($race['circuitName']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="bg-indigo-600 text-white">
    <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
      <h1 class="text-2xl font-semibold">
        <?= htmlspecialchars($race['circuitName'] ?? $race['nameCircuit'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
      </h1>
      <a href="/dashboard-races" class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Retour</a>
    </div>
  </header>

  <main class="mx-auto max-w-7xl p-6 space-y-8">
    <!-- En-tête + statut -->
    <section class="rounded-xl border bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <div class="text-sm text-slate-500">Date de la course</div>
          <div class="text-xl font-semibold"><?= e(fdate($race['date'])) ?></div>
        </div>
        <div>
          <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium <?= $clsStatus ?>">
            <?= e($labelStatus) ?>
          </span>
        </div>
      </div>

      <div class="mt-4 grid md:grid-cols-3 gap-4">
        <div class="rounded-lg border p-4">
          <div class="text-sm text-slate-500">Inscriptions</div>
          <?php if (!empty($race['registration_open']) || !empty($race['registration_close'])): ?>
            <div class="font-medium"><?= e(fdate($race['registration_open'])) ?> → <?= e(fdate($race['registration_close'])) ?></div>
          <?php else: ?>
            <div class="text-slate-400">Non défini</div>
          <?php endif; ?>
        </div>
        <div class="rounded-lg border p-4">
          <div class="text-sm text-slate-500">Capacité</div>
          <div class="font-medium">
            <?php
              $cap = (is_null($race['capacity_min']) && is_null($race['capacity_max'])) ? '—'
                    : (($race['capacity_min'] ?? 0).'–'.($race['capacity_max'] ?? '∞'));
              echo e($cap);
            ?>
          </div>
        </div>
        <div class="rounded-lg border p-4">
          <div class="text-sm text-slate-500">Prix</div>
          <div class="font-medium">
            <?= is_null($race['price_cents']) ? '—' : e(number_format($race['price_cents']/100, 2, ',', ' ').' €') ?>
          </div>
        </div>
      </div>

      <?php if (!empty($race['description'])): ?>
      <div class="mt-4">
        <div class="text-sm text-slate-500 mb-1">Description</div>
        <p class="text-slate-700"><?= nl2br(e($race['description'])) ?></p>
      </div>
      <?php endif; ?>

      <div class="mt-4 flex flex-wrap gap-2">
        <a href="/admin/races/<?= (int)$race['raceId'] ?>/edit"
           onclick="event.preventDefault(); document.getElementById('editForm').submit();"
           class="px-3 py-1.5 rounded-lg border hover:bg-slate-50">Modifier</a>
        <form id="editForm" method="post" action="/admin/races/<?= (int)$race['raceId'] ?>/edit" class="hidden">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <!-- tu peux pré-remplir via le dashboard, ici on poste juste pour router -->
          <input type="hidden" name="circuitId" value="<?= (int)$race['circuitId'] ?>">
          <input type="hidden" name="seasonId" value="<?= (int)$race['seasonId'] ?>">
          <input type="hidden" name="date" value="<?= e(str_replace(' ', 'T', substr($race['date'],0,16))) ?>">
          <input type="hidden" name="description" value="<?= e($race['description'] ?? '') ?>">
        </form>

        <form method="post" action="/admin/races/<?= (int)$race['raceId'] ?>/delete"
              onsubmit="return confirm('Supprimer cette course ?');">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50">Supprimer</button>
        </form>
      </div>
    </section>

    <!-- Vidéo (optionnel) -->
    <?php if (!empty($race['video'])): ?>
    <section class="rounded-xl border bg-white p-5 shadow-sm">
      <h2 class="text-lg font-semibold mb-3">Vidéo</h2>
      <video controls class="w-full max-h-[480px] rounded-lg">
        <source src="<?= e($race['video']) ?>">
        Votre navigateur ne supporte pas la vidéo HTML5.
      </video>
    </section>
    <?php endif; ?>

    <!-- Inscriptions -->
    <section class="rounded-xl border bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Inscriptions</h2>
        <div class="text-sm text-slate-600">
          <span class="mr-3">✅ Validés: <strong><?= $valid ?></strong></span>
          <span class="mr-3">⏳ Attente: <strong><?= $wait ?></strong></span>
          <span>❌ Refusés: <strong><?= $noval ?></strong> — Total: <strong><?= $total ?></strong></span>
        </div>
      </div>

      <div class="mt-4 grid md:grid-cols-2 gap-6">
        <div>
          <h3 class="font-medium mb-2">✅ Validés</h3>
          <div class="rounded-lg border overflow-hidden">
            <table class="min-w-full text-left">
              <thead class="bg-slate-100 text-slate-700 text-sm">
                <tr><th class="p-3">Pilote</th><th class="p-3 w-48">Date</th></tr>
              </thead>
              <tbody class="text-sm">
                <?php foreach ($regsValid as $r): ?>
                  <tr class="border-t">
                    <td class="p-3"><?= e(($r['lastName']??'').' '.($r['firstName']??'')) ?></td>
                    <td class="p-3"><?= e(fdate($r['date'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($regsValid)): ?>
                  <tr><td class="p-4 text-slate-500" colspan="2">Aucun.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <h3 class="font-medium mb-2">⏳ En attente</h3>
          <div class="rounded-lg border overflow-hidden">
            <table class="min-w-full text-left">
              <thead class="bg-slate-100 text-slate-700 text-sm">
                <tr><th class="p-3">Pilote</th><th class="p-3 w-48">Date</th></tr>
              </thead>
              <tbody class="text-sm">
                <?php foreach ($regsWait as $r): ?>
                  <tr class="border-t">
                    <td class="p-3"><?= e(($r['lastName']??'').' '.($r['firstName']??'')) ?></td>
                    <td class="p-3"><?= e(fdate($r['date'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($regsWait)): ?>
                  <tr><td class="p-4 text-slate-500" colspan="2">Aucun.</td></tr>
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
              <tr><th class="p-3">Pilote</th><th class="p-3 w-48">Date</th></tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($regsNoVal as $r): ?>
                <tr class="border-t">
                  <td class="p-3"><?= e(($r['lastName']??'').' '.($r['firstName']??'')) ?></td>
                  <td class="p-3"><?= e(fdate($r['date'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
