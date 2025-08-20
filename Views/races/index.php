<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$openRaces  = $openRaces  ?? [];
$otherRaces = $otherRaces ?? [];
$csrf       = $csrf       ?? '';

$logged     = !empty($_SESSION['user']);
$userFirst  = $logged ? ($_SESSION['user']['firstName'] ?? '') : '';
$userLast   = $logged ? ($_SESSION['user']['lastName']  ?? '') : '';
$iban       = 'BE82 7310 4088 5168';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Courses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

  <header class="bg-white border-b">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <h1 class="text-lg font-semibold">Courses</h1>
      <a href="/" class="text-sm text-indigo-600 hover:underline">Retour à l’accueil</a>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-10">

    <?php if ($m = flash_take('error')): ?>
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_take('success')): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
    <?php endif; ?>

    <!-- ======================== OUVERTES ======================== -->
    <section class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Inscriptions ouvertes</h2>
        <?php if (!$logged): ?>
          <a href="/login" class="text-sm text-indigo-600 hover:underline">Se connecter pour s’inscrire</a>
        <?php endif; ?>
      </div>

      <?php if (count($openRaces) === 0): ?>
        <div class="rounded-xl border bg-white p-6 text-slate-600">
          Aucune course n’est actuellement ouverte aux inscriptions.
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <?php foreach ($openRaces as $r): ?>
            <?php
              // Pastille état global
              $stateLbl = "Ouvert";
              $stateCls = "bg-emerald-100 text-emerald-800 border-emerald-200";

              // Mon statut d’inscription
              $status = $r['myRegStatus'] ?? null; // null|waited|valide|no-valide

              $isAccepted = ($status === 'valide');
              $cardBase   = 'rounded-xl border shadow-sm overflow-hidden flex flex-col';
              $cardCls    = $isAccepted ? $cardBase.' bg-emerald-50 border-emerald-300' : $cardBase.' bg-white';
              $headCls    = $isAccepted ? 'p-5 border-b border-emerald-200 bg-emerald-100' : 'p-5 border-b';
              $footCls    = $isAccepted ? 'p-5 border-t border-emerald-200 bg-emerald-50'  : 'p-5 border-t';

              $myLbl  = null; $myCls = '';
              if     ($status === 'waited')    { $myLbl='En attente'; $myCls='bg-amber-100 text-amber-800 border-amber-200'; }
              elseif ($status === 'valide')    { $myLbl='Accepté';    $myCls='bg-emerald-100 text-emerald-800 border-emerald-200'; }
              elseif ($status === 'no-valide') { $myLbl='Refusé';     $myCls='bg-rose-100 text-rose-800 border-rose-200'; }

              $capMax   = isset($r['capacity_max']) && $r['capacity_max'] !== null ? (int)$r['capacity_max'] : null;
              $regCount = isset($r['regCount']) ? (int)$r['regCount'] : 0;
              $full     = ($capMax !== null && $regCount >= $capMax);

              $already  = in_array($status, ['waited','valide'], true); // empêche de re-cliquer
              $can      = ($r['canRegister'] ?? false) && !$already && !$full && $logged;

              $priceStr = $r['_price'] ?? '—';
              $dateStr  = $r['_dateFr'] ?? '—';
            ?>
            <article class="<?= $cardCls ?>">
              <div class="<?= $headCls ?> flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-semibold"><?= e($r['circuitName'] ?? 'Circuit') ?></h3>
                  <span class="text-xs px-2 py-0.5 rounded-full border <?= e($stateCls) ?>"><?= e($stateLbl) ?></span>
                  <?php if ($myLbl): ?>
                    <span class="text-xs px-2 py-0.5 rounded-full border <?= e($myCls) ?>"><?= e($myLbl) ?></span>
                  <?php endif; ?>
                  <?php if ($full): ?>
                    <span class="text-xs px-2 py-0.5 rounded-full border bg-slate-100 text-slate-700 border-slate-300">Complet</span>
                  <?php endif; ?>
                </div>
                <div class="text-sm text-slate-600"><?= e($dateStr) ?></div>
              </div>

              <div class="p-5 space-y-2 text-sm text-slate-700 flex-1">
                <?php if (!empty($r['cityName'])): ?>
                  <div><span class="text-slate-500">Lieu :</span> <?= e($r['cityName']) ?></div>
                <?php endif; ?>
                <div><span class="text-slate-500">Prix :</span> <?= e($priceStr) ?></div>
                <?php if ($capMax !== null): ?>
                  <div><span class="text-slate-500">Inscriptions :</span> <?= (int)$regCount ?> / <?= (int)$capMax ?></div>
                <?php endif; ?>
                <?php if (!empty($r['description'])): ?>
                  <p class="pt-2 text-slate-600"><?= nl2br(e($r['description'])) ?></p>
                <?php endif; ?>
              </div>

              <div class="<?= $footCls ?> flex items-center justify-between gap-3">
                <a href="/races/<?= (int)$r['id'] ?>" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Voir la course</a>

                <?php if (!$logged): ?>
                  <a href="/login" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Se connecter</a>
                <?php else: ?>
                  <button
                    class="px-4 py-2 rounded-lg text-white <?= $can ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-400 cursor-not-allowed' ?>"
                    <?= $can ? '' : 'disabled' ?>
                    data-race-id="<?= (int)$r['id'] ?>"
                    data-race-date="<?= e($dateStr) ?>"
                    data-race-price="<?= e($priceStr) ?>"
                    data-user-last="<?= e($userLast) ?>"
                    data-user-first="<?= e($userFirst) ?>"
                    onclick="openRegisterModal(this)"
                  >
                    <?php
                      if ($already)      echo "Inscription envoyée";
                      elseif ($full)     echo "Complet";
                      else               echo "S'inscrire";
                    ?>
                  </button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- ======================== TOUTES LES COURSES ======================== -->
    <section class="space-y-4">
      <h2 class="text-xl font-semibold">Toutes les courses</h2>

      <?php if (count($otherRaces) === 0): ?>
        <div class="rounded-xl border bg-white p-6 text-slate-600">
          Aucune course à afficher.
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <?php foreach ($otherRaces as $r): ?>
            <?php
              // Pastille état global
              $state = $r['_state'] ?? 'open'; // early|open|closed
              if     ($state === 'early')  { $stateLbl='Inscriptions à venir'; $stateCls='bg-amber-100 text-amber-800 border-amber-200'; }
              elseif ($state === 'closed') { $stateLbl='Inscriptions clôturées'; $stateCls='bg-slate-200 text-slate-700 border-slate-300'; }
              else                         { $stateLbl='Ouvert'; $stateCls='bg-emerald-100 text-emerald-800 border-emerald-200'; }

              // Mon statut
              $status = $r['myRegStatus'] ?? null;

              $isAccepted = ($status === 'valide');
              $cardBase   = 'rounded-xl border shadow-sm overflow-hidden flex flex-col';
              $cardCls    = $isAccepted ? $cardBase.' bg-emerald-50 border-emerald-300' : $cardBase.' bg-white';
              $headCls    = $isAccepted ? 'p-5 border-b border-emerald-200 bg-emerald-100' : 'p-5 border-b';
              $footCls    = $isAccepted ? 'p-5 border-t border-emerald-200 bg-emerald-50'  : 'p-5 border-t';

              $myLbl  = null; $myCls='';
              if     ($status === 'waited')    { $myLbl='En attente'; $myCls='bg-amber-100 text-amber-800 border-amber-200'; }
              elseif ($status === 'valide')    { $myLbl='Accepté';    $myCls='bg-emerald-100 text-emerald-800 border-emerald-200'; }
              elseif ($status === 'no-valide') { $myLbl='Refusé';     $myCls='bg-rose-100 text-rose-800 border-rose-200'; }

              $capMax   = isset($r['capacity_max']) && $r['capacity_max'] !== null ? (int)$r['capacity_max'] : null;
              $regCount = isset($r['regCount']) ? (int)$r['regCount'] : 0;
              $priceStr = $r['_price'] ?? '—';
              $dateStr  = $r['_dateFr'] ?? '—';

              // Bouton s’inscrire ici en “autres courses” seulement si open (sécurité visuelle)
              $already  = in_array($status, ['waited','valide'], true);
              $full     = ($capMax !== null && $regCount >= $capMax);
              $can      = ($state === 'open') && !$already && !$full && $logged;
            ?>
            <article class="<?= $cardCls ?>">
              <div class="<?= $headCls ?> flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-semibold"><?= e($r['circuitName'] ?? 'Circuit') ?></h3>
                  <span class="text-xs px-2 py-0.5 rounded-full border <?= e($stateCls) ?>"><?= e($stateLbl) ?></span>
                  <?php if ($myLbl): ?>
                    <span class="text-xs px-2 py-0.5 rounded-full border <?= e($myCls) ?>"><?= e($myLbl) ?></span>
                  <?php endif; ?>
                  <?php if ($full): ?>
                    <span class="text-xs px-2 py-0.5 rounded-full border bg-slate-100 text-slate-700 border-slate-300">Complet</span>
                  <?php endif; ?>
                </div>
                <div class="text-sm text-slate-600"><?= e($dateStr) ?></div>
              </div>

              <div class="p-5 space-y-2 text-sm text-slate-700 flex-1">
                <?php if (!empty($r['cityName'])): ?>
                  <div><span class="text-slate-500">Lieu :</span> <?= e($r['cityName']) ?></div>
                <?php endif; ?>
                <div><span class="text-slate-500">Prix :</span> <?= e($priceStr) ?></div>
                <?php if ($capMax !== null): ?>
                  <div><span class="text-slate-500">Inscriptions :</span> <?= (int)$regCount ?> / <?= (int)$capMax ?></div>
                <?php endif; ?>
                <?php if (!empty($r['description'])): ?>
                  <p class="pt-2 text-slate-600"><?= nl2br(e($r['description'])) ?></p>
                <?php endif; ?>
              </div>

              <div class="<?= $footCls ?> flex items-center justify-between gap-3">
                <a href="/races/<?= (int)$r['id'] ?>" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Voir la course</a>
                <?php if ($state === 'open'): ?>
                  <?php if (!$logged): ?>
                    <a href="/login" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Se connecter</a>
                  <?php else: ?>
                    <button
                      class="px-4 py-2 rounded-lg text-white <?= $can ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-400 cursor-not-allowed' ?>"
                      <?= $can ? '' : 'disabled' ?>
                      data-race-id="<?= (int)$r['id'] ?>"
                      data-race-date="<?= e($dateStr) ?>"
                      data-race-price="<?= e($priceStr) ?>"
                      data-user-last="<?= e($userLast) ?>"
                      data-user-first="<?= e($userFirst) ?>"
                      onclick="openRegisterModal(this)"
                    >
                      <?php
                        if ($already)      echo "Inscription envoyée";
                        elseif ($full)     echo "Complet";
                        else               echo "S'inscrire";
                      ?>
                    </button>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <!-- ======================== MODAL ======================== -->
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

      // Description de paiement: Nom + Prénom + Karting + Date
      const desc = (last + ' ' + first + ' Karting ' + dateStr).trim().replace(/\s+/g, ' ');

      document.getElementById('mDate').textContent = dateStr;
      document.getElementById('mPrice').textContent = priceStr;
      document.getElementById('mDesc').textContent = desc;

      const f = document.getElementById('registerForm');
      f.setAttribute('action', '/races/' + raceId + '/register');

      document.getElementById('registerModal').classList.remove('hidden');
      document.getElementById('registerModal').classList.add('flex');
    }
    function closeRegisterModal() {
      const m = document.getElementById('registerModal');
      m.classList.add('hidden');
      m.classList.remove('flex');
    }
  </script>
</body>
</html>
