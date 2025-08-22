<?php
// Views/public/race-details.php

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$race          = $race          ?? [];
$results       = $results       ?? [];
$registrations = $registrations ?? [];
$csrf          = $csrf          ?? '';

$logged   = !empty($_SESSION['user']);
$userLast = $logged ? ($_SESSION['user']['lastName']  ?? '') : '';
$userFirst= $logged ? ($_SESSION['user']['firstName'] ?? '') : '';
$iban     = 'BE82 7310 4088 5168';

// Formats
function fdate(?string $dt): string {
  if (!$dt) return '—';
  try { return (new DateTimeImmutable($dt))->format('d/m/Y H:i'); }
  catch (Throwable) { return $dt; }
}
function fnum($n, int $dec = 3): string {
  if ($n === null || $n === '') return '—';
  return number_format((float)$n, $dec, ',', ' ');
}

function formatGap($gap, int $pos = null): string {
    if ($pos === 1) return 'LEADER';
    if ($gap === null || $gap === '') return '—';

    if (abs($gap - round($gap)) < 1e-9) {
        $laps = (int)round($gap);
        if($laps == 0) return 'LEADER';
        return '+' . $laps . ' tour' . ($laps > 1 ? 's' : '');
    }
    return '+' . fnum($gap, 3) . 's';
}



// Y a-t-il des résultats ?
$hasResults = !empty($results);

// Pré-calcul du meilleur tour global si résultats
$globalBest = null;
if ($hasResults) {
  foreach ($results as $row) {
    if (!empty($row['laps']) && is_array($row['laps'])) {
      foreach ($row['laps'] as $lt) {
        $t = (float)$lt;
        if ($globalBest === null || $t < $globalBest) $globalBest = $t;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title><?= e($race['circuitName'] ?? 'Course') ?> — Détails</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
  <header class="bg-white border-b">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <h1 class="text-lg font-semibold">
        <?= e($race['circuitName'] ?? 'Circuit') ?>
      </h1>
      <a href="/races" class="text-sm text-indigo-600 hover:underline">← Retour aux courses</a>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <?php if ($m = flash_take('error')): ?>
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_take('success')): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
    <?php endif; ?>

    <!-- Entête course -->
    <section class="rounded-xl border bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="space-y-1">
          <div class="text-slate-500 text-sm">Course</div>
          <div class="text-xl font-semibold">
            <?= e($race['circuitName'] ?? 'Circuit') ?>
            <?php if (!empty($race['cityName'])): ?>
              <span class="text-slate-400 font-normal">— <?= e($race['cityName']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="text-sm text-slate-600">
          <span class="mr-4">Date: <strong><?= e(fdate($race['date'] ?? null)) ?></strong></span>
          <span class="mr-4">Ouverture: <strong><?= e(fdate($race['registration_open'] ?? null)) ?></strong></span>
          <span>Clôture: <strong><?= e(fdate($race['registration_close'] ?? null)) ?></strong></span>
        </div>
      </div>
      <?php if (!empty($race['description'])): ?>
        <p class="mt-3 text-slate-700 whitespace-pre-line"><?= e($race['description']) ?></p>
      <?php endif; ?>
    </section>

    <?php if ($hasResults): ?>
      <!-- ===================== RÉSULTATS (PUBLIC) ===================== -->
      <section class="rounded-xl border bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Résultats</h2>
          <?php if ($globalBest !== null): ?>
            <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1">
              🏆 Meilleur tour global: <strong><?= fnum($globalBest, 3) ?> s</strong>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left">
            <thead class="bg-slate-100 text-slate-700 text-sm">
              <tr>
                <th class="p-3 w-14">Pos.</th>
                <th class="p-3">Pilote</th>
                <th class="p-3 w-36">Vitesse moy. (km/h)</th>
                <th class="p-3 w-28">Écart (s)</th>
                <th class="p-3 min-w-[260px]">Tours (s)</th>
                <th class="p-3 w-24">Points</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($results as $row): ?>
                <?php
                  $pos   = (int)($row['position'] ?? 0);
                  $avg   = $row['avg']   ?? null;
                  $gap   = $row['gap']   ?? 0;
                  $pts   = $row['points']?? 0;
                  $laps  = is_array($row['laps'] ?? null) ? $row['laps'] : [];

                  // Nom + numéro
                  $pilotName = 'Pilote #'.(int)($row['pilotId'] ?? 0);
                  if (!empty($row['firstName']) || !empty($row['lastName'])) {
                    $pilotName = trim(($row['firstName'] ?? '').' '.($row['lastName'] ?? ''));
                  }
                  $numero = $row['numero'] ?? null;
                  if ($numero !== null && $numero !== '' && $numero !== '0') {
                    $pilotName .= ' — #' . $numero;
                  }


                  // Meilleur tour du pilote
                  $pilotBest = null;
                  foreach ($laps as $lt) {
                    $t = (float)$lt;
                    if ($pilotBest === null || $t < $pilotBest) $pilotBest = $t;
                  }
                ?>
                <tr class="border-t align-top">
                  <td class="p-3 font-medium">#<?= $pos ?></td>
                  <td class="p-3"><?= e($pilotName) ?></td>
                  <td class="p-3"><?= $avg !== null ? fnum($avg, 2) : '—' ?></td>
                  <td class="p-3"><?= e(formatGap($gap)) ?></td>
                  <td class="p-3">
                    <?php if (empty($laps)): ?>
                      <span class="text-slate-500">—</span>
                    <?php else: ?>
                      <div class="flex flex-wrap gap-2">
                        <?php foreach ($laps as $lt): ?>
                          <?php
                            $t = (float)$lt;
                            $isPilotBest  = ($pilotBest !== null && abs($t - $pilotBest) < 1e-9);
                            $isGlobalBest = ($globalBest !== null && abs($t - $globalBest) < 1e-9);

                            // Styles
                            $cls = "text-xs px-2 py-1 rounded border";
                            if ($isGlobalBest) {
                              $cls .= " bg-yellow-100 text-yellow-900 border-yellow-300 ring-2 ring-yellow-400 font-semibold";
                              $badge = "🥇";
                            } elseif ($isPilotBest) {
                              $cls .= " bg-emerald-100 text-emerald-800 border-emerald-300 ring-1 ring-emerald-300";
                              $badge = "★";
                            } else {
                              $cls .= " bg-slate-50 text-slate-700 border-slate-200";
                              $badge = "";
                            }
                          ?>
                          <span class="<?= e($cls) ?>" title="<?= $isGlobalBest ? 'Meilleur tour global' : ($isPilotBest ? 'Meilleur tour du pilote' : '') ?>">
                            <?= $badge ? $badge.' ' : '' ?><?= fnum($t, 3) ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 font-semibold"><?= fnum($pts, 1) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <p class="mt-3 text-xs text-slate-500">
          Indications: <span class="px-1 rounded bg-yellow-100 text-yellow-900 border border-yellow-300">🥇</span> meilleur tour global,
          <span class="px-1 rounded bg-emerald-100 text-emerald-800 border border-emerald-300">★</span> meilleur tour du pilote.
        </p>
      </section>

    <?php else: ?>
      <!-- ===================== AUCUN RÉSULTAT → INSCRIPTIONS PUBLIQUES ===================== -->
      <?php
        // Variables exposées par le contrôleur (si dispo)
        $state        = $state        ?? 'open'; // early|open|closed
        $canRegister  = $canRegister  ?? false;
        $capMax       = isset($race['capacity_max']) && $race['capacity_max'] !== null ? (int)$race['capacity_max'] : null;
        $priceStr     = isset($race['price_cents']) && $race['price_cents'] !== null ? number_format(((int)$race['price_cents'])/100, 2, ',', ' ').' €' : '—';

        // Inscrits visibles côté public: on masque les refusés
        $publicRegs = array_values(array_filter($registrations, fn($r) => in_array(($r['status'] ?? ''), ['waited','valide'], true)));
      ?>

      <section class="rounded-xl border bg-white p-5 shadow-sm space-y-5">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Inscriptions</h2>
          <div class="text-sm text-slate-600">
            <span class="mr-4">Prix: <strong><?= e($priceStr) ?></strong></span>
            <?php if ($capMax !== null): ?>
              <span>Capacité max: <strong><?= (int)$capMax ?></strong></span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Bouton s'inscrire (public, si fenêtre ouverte et autorisé) -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div class="text-sm text-slate-600">
            <?php if ($state === 'early'): ?>
              Les inscriptions ne sont pas encore ouvertes.
            <?php elseif ($state === 'closed'): ?>
              Les inscriptions sont clôturées.
            <?php else: ?>
              Inscriptions ouvertes.
            <?php endif; ?>
          </div>
          <div>
            <?php if ($canRegister): ?>
              <button
                class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"
                data-race-id="<?= (int)($race['id'] ?? 0) ?>"
                data-race-date="<?= e(fdate($race['date'] ?? null)) ?>"
                data-race-price="<?= e($priceStr) ?>"
                data-user-last="<?= e($userLast) ?>"
                data-user-first="<?= e($userFirst) ?>"
                onclick="openRegisterModal(this)"
              >
                S'inscrire
              </button>
            <?php else: ?>
              <button class="px-4 py-2 rounded-lg bg-slate-400 text-white cursor-not-allowed" disabled>S'inscrire</button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Liste simple des inscrits (public, sans actions) -->
        <div class="rounded-lg border overflow-hidden">
          <table class="min-w-full text-left">
            <thead class="bg-slate-100 text-slate-700 text-sm">
              <tr>
                <th class="p-3">Inscrits</th>
                <th class="p-3 w-48">Date</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($publicRegs as $r): ?>
                <?php
                  $name = trim(($r['lastName'] ?? '').' '.($r['firstName'] ?? ''));
                  $name = $name !== '' ? $name : ('Pilote #'.(int)($r['userId'] ?? 0));
                  $created = $r['created_at'] ?? ($r['date'] ?? null);
                ?>
                <tr class="border-t">
                  <td class="p-3"><?= e($name) ?></td>
                  <td class="p-3"><?= e(fdate($created)) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($publicRegs)): ?>
                <tr><td class="p-4 text-slate-500" colspan="2">Aucun inscrit pour le moment.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <!-- ========= MODAL INSCRIPTION (public) : seulement utilisé si pas de résultats ========= -->
  <div id="registerModal" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 bg-black/50" onclick="closeRegisterModal()"></div>
    <div class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl">
      <div class="p-5 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold">Confirmer l’inscription</h3>
        <button class="text-slate-500 hover:text-slate-800" onclick="closeRegisterModal()">✕</button>
      </div>
      <div class="p-5 space-y-4 text-sm text-slate-700">
        <div class="space-y-1">
          <div><span class="text-slate-500">Date :</span> <span id="mDate">—</span></div>
          <div><span class="text-slate-500">Prix :</span> <span id="mPrice">—</span></div>
        </div>
        <div class="space-y-1">
          <div class="font-medium">Paiement</div>
          <div><span class="text-slate-500">Compte :</span> <span><?= e($iban) ?></span></div>
          <div><span class="text-slate-500">Communication :</span> <span id="mDesc">—</span></div>
          <p class="text-xs text-slate-500">Effectue le virement avec la communication ci-dessus.</p>
        </div>
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 p-3 text-xs">
          En cliquant sur “Je confirme mon inscription”, ta demande sera envoyée à l’organisateur pour validation.
        </div>
      </div>
      <div class="p-5 border-t flex items-center justify-end gap-2">
        <button class="px-4 py-2 rounded-lg border hover:bg-slate-50" onclick="closeRegisterModal()">Annuler</button>
        <form id="registerForm" method="post" action="/races/0/register">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Je confirme mon inscription</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function openRegisterModal(btn) {
      const raceId   = btn.getAttribute('data-race-id');
      const dateStr  = btn.getAttribute('data-race-date') || '—';
      const priceStr = btn.getAttribute('data-race-price') || '—';
      const last     = btn.getAttribute('data-user-last') || '';
      const first    = btn.getAttribute('data-user-first') || '';
      const desc     = (last + ' ' + first + ' Karting ' + dateStr).trim().replace(/\s+/g, ' ');

      document.getElementById('mDate').textContent  = dateStr;
      document.getElementById('mPrice').textContent = priceStr;
      document.getElementById('mDesc').textContent  = desc;

      const f = document.getElementById('registerForm');
      f.setAttribute('action', '/races/' + raceId + '/register');

      const m = document.getElementById('registerModal');
      m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeRegisterModal() {
      const m = document.getElementById('registerModal');
      m.classList.add('hidden'); m.classList.remove('flex');
    }
  </script>
</body>
</html>
