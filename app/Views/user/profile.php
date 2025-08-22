<?php
// Views/user/profile.php

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Sécuriser les variables attendues
$userToSee         = $userToSee         ?? [];
$stats             = $stats             ?? ['wins'=>0,'podiums'=>0,'fastlaps'=>0];
$bySeason          = $bySeason          ?? [];
$seasonSummaries   = $seasonSummaries   ?? [];

// Helpers simples
$firstName = e($userToSee['firstName'] ?? '');
$lastName  = e($userToSee['lastName']  ?? '');
$role      = (int)($userToSee['role']  ?? 0);
$numero    = (int)($userToSee['numero'] ?? 0);
$picture   = e($userToSee['picture'] ?? 'default.png');
$country   = e($userToSee['country_name'] ?? '—');
$city      = e($userToSee['city_name'] ?? '');
$desc      = trim((string)($userToSee['description'] ?? ''));

// Âge
$age = '—';
if (!empty($userToSee['birthday'])) {
  try {
    $age = (new DateTimeImmutable($userToSee['birthday']))->diff(new DateTimeImmutable('today'))->y;
  } catch (Throwable $e) { /* noop */ }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title><?= $firstName ?> <?= $lastName ?> — Profil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">

<div class="p-4">
  <a href="/" class="inline-flex items-center rounded-lg bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700">
    ← Accueil
  </a>
</div>


  <!-- Hero -->
  <section id="top" class="relative overflow-hidden">
    <!-- Fond doux / halos -->
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-indigo-50 via-white to-slate-50"></div>
    <div class="absolute -top-20 -left-16 -z-10 h-64 w-64 rounded-full bg-indigo-200/30 blur-3xl"></div>
    <div class="absolute -bottom-24 -right-20 -z-10 h-72 w-72 rounded-full bg-fuchsia-200/30 blur-3xl"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12 pb-10">
      <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-8 items-start lg:items-center">

        <!-- Colonne gauche -->
        <div class="lg:pt-6 xl:pt-10">
          <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">
            <?= $firstName ?>
            <span class="bg-gradient-to-r from-indigo-600 to-fuchsia-600 bg-clip-text text-transparent">
              <?= $lastName ?>
            </span>
          </h1>

          <p class="mt-4 text-lg leading-relaxed text-slate-600 max-w-2xl">
            <?php if ($desc !== ''): ?>
              <?= e($desc) ?>
            <?php else: ?>
              Pas de description ajoutée par cet utilisateur.
            <?php endif; ?>
          </p>

          <!-- Badges -->
          <div class="mt-6 flex flex-wrap items-center gap-3">
            <?php if ($role === 1): ?>
              <span class="inline-flex items-center gap-2 rounded-full bg-rose-600 text-white text-xs font-medium px-3 py-1.5 shadow-sm">
                Organisateur
              </span>
            <?php elseif ((int)($userToSee['id'] ?? 0) === 1): ?>
              <span class="inline-flex items-center gap-2 rounded-full bg-rose-600 text-white text-xs font-medium px-3 py-1.5 shadow-sm">
                Développeur
              </span>
            <?php endif; ?>

            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-600 text-white text-xs font-medium px-3 py-1.5 shadow-sm">
              <?= ($role <= 2) ? 'Pilote' : 'Visiteur' ?>
            </span>
          </div>

          <!-- KPI -->
          <div class="mt-10 grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 text-center shadow-sm hover:shadow-md transition">
              <div class="text-3xl font-extrabold tracking-tight"><?= (int)($stats['wins'] ?? 0) ?></div>
              <div class="mt-1 text-sm text-slate-600">Victoires</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 text-center shadow-sm hover:shadow-md transition">
              <div class="text-3xl font-extrabold tracking-tight"><?= (int)($stats['podiums'] ?? 0) ?></div>
              <div class="mt-1 text-sm text-slate-600">Podiums</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 text-center shadow-sm hover:shadow-md transition">
              <div class="text-3xl font-extrabold tracking-tight"><?= (int)($stats['fastlaps'] ?? 0) ?></div>
              <div class="mt-1 text-sm text-slate-600">Meilleurs tours</div>
            </div>
          </div>
        </div>

        <!-- Colonne droite : carte photo -->
        <div class="relative">
          <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="relative w-full" style="aspect-ratio: 4/3;">
              <img
                src="/Assets/ProfilesPhoto/<?= $picture ?>"
                alt="Photo du pilote"
                class="absolute inset-0 h-full w-full object-cover object-center"
                loading="lazy" decoding="async"
                onerror="this.src='/Assets/ProfilesPhoto/default.png'"
              />
            </div>

            <div class="p-5">
              <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-slate-50 p-4">
                  <div class="text-xs text-slate-500">Numéro</div>
                  <div class="text-2xl font-bold">#<?= $numero !== 0 ? $numero : '0' ?></div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4 text-right">
                  <div class="text-xs text-slate-500">Âge</div>
                  <div class="text-2xl font-bold"><?= e((string)$age) ?></div>
                </div>
              </div>

              <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                <?php if (!empty($userToSee['taille'])): ?>
                  <div class="rounded-lg bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Taille</div>
                    <div class="font-semibold"><?= (int)$userToSee['taille'] ?> cm</div>
                  </div>
                <?php endif; ?>

                <?php if (!empty($userToSee['poids'])): ?>
                  <div class="rounded-lg bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Poids</div>
                    <div class="font-semibold"><?= (int)$userToSee['poids'] ?> kg</div>
                  </div>
                <?php endif; ?>

                <div class="rounded-lg bg-slate-50 p-3">
                  <div class="text-xs text-slate-500">Origine</div>
                  <div class="font-semibold">
                    <?= $country ?>
                    <?= $city ? ' ('.$city.')' : '' ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- halo décoratif -->
          <div class="absolute -right-2 -bottom-2 hidden md:block h-24 w-24 rounded-2xl bg-indigo-600/10 blur-2xl"></div>
        </div>

      </div>
    </div>
  </section>

  <!-- Palmarès : Top 3 -->
<?php if (!empty($top3)): ?>
  <section id="palmares" class="py-12 bg-white border-y">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="text-xl font-semibold">Les 3 meilleurs classements</h2>
      <div class="mt-6 grid md:grid-cols-3 gap-6">
        <?php foreach ($top3 as $row): ?>
          <?php
            $place = (int)($row['position'] ?? 0);
            // PHP 8+ (match), sinon voir la version if/elseif plus bas
            $medal = match ($place) {
              1 => '🥇',
              2 => '🥈',
              3 => '🥉',
              default => '🏁',
            };
          ?>
          <div class="rounded-xl border p-5">
            <div class="text-sm text-slate-500">
              <?= (int)($row['seasonYear'] ?? $row['year'] ?? 0) ?>
            </div>
            <h3 class="font-semibold mt-1"><?= e($row['circuitName'] ?? '—') ?></h3>
            <div class="text-xs text-slate-500">
              <?= e(($row['cityName'] ?? '').(!empty($row['countryName']) ? ', '.$row['countryName'] : '')) ?>
            </div>
            <ul class="mt-3 space-y-1 text-sm text-slate-700">
              <li><?= $medal ?> Place #<?= $place ?: '—' ?></li>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>



  <!-- Résultats par saison -->
  <div class="mt-10 space-y-8 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <?php foreach ($bySeason as $grp): ?>
      <section class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b bg-slate-50 flex items-center justify-between">
          <h3 class="text-lg font-semibold">
            Saison <?= (int)($grp['seasonYear'] ?? 0) ?> — Résultats
          </h3>
          <?php
            $sid = (int)($grp['seasonId'] ?? 0);
            $sum = $seasonSummaries[$sid] ?? ['rank'=>null,'points'=>0];
          ?>
        </div>

        <div class="p-5 overflow-x-auto">
          <table class="min-w-full text-left border-separate" style="border-spacing:0">
            <thead class="text-sm">
              <tr class="bg-slate-100 text-slate-700">
                <th class="p-3 font-medium">Date</th>
                <th class="p-3 font-medium">Circuit</th>
                <th class="p-3 font-medium">Lieu</th>
                <th class="p-3 font-medium">Place</th>
                <th class="p-3 font-medium">Points</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach (($grp['results'] ?? []) as $r): ?>
                <?php
                  $place = (int)($r['position'] ?? 0);
                  $placeChip = 'bg-slate-100 text-slate-700';
                  if ($place === 1) $placeChip = 'bg-amber-100 text-amber-800';
                  elseif ($place === 2) $placeChip = 'bg-zinc-100 text-zinc-800';
                  elseif ($place === 3) $placeChip = 'bg-orange-100 text-orange-800';

                  $dateTxt = '—';
                  if (!empty($r['date'])) {
                    try { $dateTxt = (new DateTime($r['date']))->format('d/m/Y'); } catch(Throwable $e) {}
                  }
                  $circuitName = e($r['circuitName'] ?? '—');
                  $loc = trim((string)(($r['cityName'] ?? '') . (!empty($r['countryName']) ? ', '.$r['countryName'] : '')));
                  $loc = $loc !== '' ? e($loc) : '—';
                  $pts = number_format((float)($r['points'] ?? 0), 1, ',', ' ');
                ?>
                <tr class="border-t border-slate-200 hover:bg-slate-50">
                  <td class="p-3"><?= $dateTxt ?></td>
                  <td class="p-3"><?= $circuitName ?></td>
                  <td class="p-3"><?= $loc ?></td>
                  <td class="p-3">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $placeChip ?>">
                      P<?= $place ?>
                    </span>
                  </td>
                  <td class="p-3"><?= $pts ?></td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($grp['results'])): ?>
                <tr><td colspan="5" class="p-6 text-center text-slate-500">Aucun résultat.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>

          <p class="mt-4 text-sm text-slate-700">
            Classement saison :
            <?php if (!empty($sum['rank'])): ?>
              <strong><?= (int)$sum['rank'] ?><sup>e</sup></strong>
            <?php else: ?>
              <em>n.c.</em>
            <?php endif; ?>
            — Points : <strong><?= number_format((float)($sum['points'] ?? 0), 1, ',', ' ') ?></strong>
          </p>
        </div>
      </section>
    <?php endforeach; ?>

    <?php if (empty($bySeason)): ?>
      <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">Aucune saison disputée.</div>
    <?php endif; ?>
  </div>

  <div class="h-10"></div>
</body>
</html>
