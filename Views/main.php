<?php
/* ---------- Helpers ---------- */
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('euro_price')) {
  function euro_price(?int $cents): string {
    if ($cents === null) return '—';
    return number_format($cents/100, 2, ',', ' ') . " €";
  }
}
if (!function_exists('human_date')) {
  function human_date(?string $dt): string {
    if (!$dt) return '—';
    try { return (new DateTime($dt))->format('d/m/Y H:i'); }
    catch (Throwable) { return '—'; }
  }
}
if (!function_exists('fdate_home')) {
  function fdate_home(?string $dt): string {
    if (!$dt) return '—';
    try { return (new DateTimeImmutable($dt))->format('d/m/Y H:i'); }
    catch (Throwable) { return '—'; }
  }
}
if (!function_exists('race_phase')) {
  function race_phase(array $r, ?DateTime $now = null): array {
    $now  = $now ?: new DateTime('now');
    $date = !empty($r['date']) ? new DateTime($r['date']) : null;
    $open = !empty($r['registration_open'])  ? new DateTime($r['registration_open'])  : null;
    $close= !empty($r['registration_close']) ? new DateTime($r['registration_close']) : null;

    if ($date && $now >= $date)       return ['label'=>'Course terminée','class'=>'bg-slate-100 text-slate-700 border-slate-200'];
    if ($open && $close && $now >= $open && $now <= $close)
                                      return ['label'=>'Inscriptions ouvertes','class'=>'bg-emerald-50 text-emerald-700 border-emerald-200'];
    if ($close && $now > $close && (!$date || $now < $date))
                                      return ['label'=>'Inscriptions clôturées','class'=>'bg-amber-50 text-amber-700 border-amber-200'];
    if ($open && $now < $open)        return ['label'=>'Bientôt','class'=>'bg-indigo-50 text-indigo-700 border-indigo-200'];
    return ['label'=>'À venir','class'=>'bg-indigo-50 text-indigo-700 border-indigo-200'];
  }
}

/* ---------- Données (défauts pour éviter les notices) ---------- */
$latestSeason = $latestSeason ?? [];
$standings    = $standings    ?? [];
$winners      = $winners      ?? [];
$upcoming     = $upcoming     ?? [];
$latestRaces  = $latestRaces  ?? [];
$featured     = $featured     ?? [];
$circuits     = $circuits     ?? [];
$allSeasons   = $allSeasons   ?? [];
$counters     = $counters     ?? ['drivers'=>0,'circuits'=>0,'races'=>0,'seasons'=>0];

/* ---------- UI ---------- */
$siteName = "BDK Karting";
$heroImg  = "/Assets/Medias/background.png";
$isLoggedIn = !empty($_SESSION['user']);
$role       = (int)($_SESSION['user']['role'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= e($siteName) ?> — Accueil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">

<!-- Header -->
<header class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 font-bold text-lg">
      <img src="/Assets/Logo/logo.png" alt="<?= e($siteName) ?>" width="auto" height="50" class="h-10 w-35 object-cover" loading="eager" decoding="async" />
    </a>

    <nav class="hidden md:flex items-center gap-6 text-sm text-slate-700">
      <?php if (!$isLoggedIn): ?>
        <a href="/login" class="hover:text-indigo-600">Connexion</a>
        <a href="/register" class="inline-flex rounded-lg bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700">Rejoindre</a>
      <?php elseif ($role === 1): ?>
        <a href="/races" class="hover:text-indigo-600">Courses</a>
        <a href="/polls" class="hover:text-indigo-600">Sondages</a>
        <a href="/dashboard-administrator" class="hover:text-indigo-600">Admin</a>
        <a href="/update-profile" class="hover:text-indigo-600">Mon compte</a>
        <a href="/logout" class="inline-flex rounded-lg border px-4 py-2 hover:bg-slate-50">Se déconnecter</a>
      <?php elseif ($role === 2): ?>
        <a href="/races" class="hover:text-indigo-600">Courses</a>
        <a href="/polls" class="hover:text-indigo-600">Sondages</a>
        <a href="/update-profile" class="hover:text-indigo-600">Mon compte</a>
        <a href="/logout" class="inline-flex rounded-lg border px-4 py-2 hover:bg-slate-50">Se déconnecter</a>
      <?php else: ?>
        <a href="/become-driver" class="hover:text-indigo-600">Devenir Pilote</a>
        <a href="/update-profile" class="hover:text-indigo-600">Mon compte</a>
        <a href="/logout" class="inline-flex rounded-lg border px-4 py-2 hover:bg-slate-50">Se déconnecter</a>
      <?php endif; ?>
    </nav>

  </div>
</header>

<!-- Hero -->
<section class="relative isolate">
  <div class="absolute inset-0 -z-10">
    <img src="<?= e($heroImg) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
    <div class="absolute inset-0 bg-black/60"></div>
  </div>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 lg:py-28 text-white">
    <div class="max-w-3xl">
      <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Saison <?= e(($latestSeason['year'] ?? date('Y'))) ?> en cours
      </div>
      <h1 class="mt-4 text-3xl sm:text-5xl font-extrabold leading-tight">L'unique compétition sourde au Karting en Belgique</h1>
      <p class="mt-4 text-slate-200 max-w-2xl">Inscriptions, classements, circuits, résultats et sondages : TOUT le championnat, au même endroit.</p>
      <div class="mt-6 flex flex-wrap gap-3">
        <a href="/register" class="rounded-lg bg-indigo-600 px-5 py-3 font-medium hover:bg-indigo-700">Devenir pilote</a>
        <a href="#calendrier" class="rounded-lg border border-white/30 px-5 py-3 font-medium hover:bg-white/10">Voir le calendrier</a>
      </div>
    </div>
  </div>
</section>

<!-- Counters -->
<section class="py-10">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 justify-center">
      <?php
        $cards = [
          ['label'=>'Pilotes','value'=>$counters['drivers']],
          ['label'=>'Circuits','value'=>$counters['circuits']],
          ['label'=>'Courses totales','value'=>$counters['races']],
          ['label'=>'Saisons','value'=>$counters['seasons']],
        ];
      ?>
      <?php foreach ($cards as $it): ?>
        <div class="rounded-xl border bg-white p-5 text-center">
          <div class="text-3xl font-extrabold"><?= (int)$it['value'] ?></div>
          <div class="mt-1 text-sm text-slate-600"><?= e($it['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Upcoming races -->
<section id="calendrier" class="py-12 border-y bg-white">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-semibold">Prochaines courses</h2>
    </div>
    <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($upcoming as $r): ?>
        <?php $phase = race_phase($r); ?>
        <a href="/races/<?= (int)($r['raceId'] ?? $r['id'] ?? 0) ?>" class="group block rounded-xl border bg-white hover:shadow-md transition overflow-hidden">
          <div class="p-5">
            <div class="flex items-center justify-between gap-3">
              <div class="text-sm text-slate-500"><?= e($r['seasonYear'] ?? '') ?></div>
              <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium <?= e($phase['class']) ?>">
                <?= e($phase['label']) ?>
              </span>
            </div>
            <div class="mt-1 text-lg font-semibold"><?= e($r['circuitName'] ?? 'Circuit') ?></div>
            <div class="text-sm text-slate-600">
              <?= e(trim(($r['cityName'] ?? '').((($r['countryName'] ?? '') !== '') ? ', '.$r['countryName'] : ''))) ?>
            </div>
            <div class="mt-3 flex items-center justify-between text-sm">
              <div class="text-slate-700">Course: <span class="font-medium"><?= human_date($r['date'] ?? null) ?></span></div>
              <div class="text-slate-700">Prix: <span class="font-medium"><?= euro_price($r['price_cents'] ?? null) ?></span></div>
            </div>
            <?php if (!empty($r['registration_open']) || !empty($r['registration_close'])): ?>
              <div class="mt-2 text-xs text-slate-500">
                Inscriptions: <?= human_date($r['registration_open'] ?? null) ?> → <?= human_date($r['registration_close'] ?? null) ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="border-t bg-slate-50 px-5 py-3 text-sm text-indigo-700 group-hover:bg-indigo-50">Voir la course →</div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($upcoming)): ?>
        <div class="col-span-full rounded-xl border p-8 text-center text-slate-500">Aucune course planifiée pour le moment.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Standings + Winners -->
<section class="py-12">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-8">
    <!-- Classement -->
    <div class="lg:col-span-2">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold">Classement — Saison <?= e($latestSeason['year'] ?? '—') ?></h2>

        <div class="flex flex-wrap items-center gap-2">
          <?php $latestId = (int)($latestSeason['seasonId'] ?? 0); ?>
          <?php if ($latestId): ?>
            <a href="/seasons/<?= $latestId ?>/ranking"
               class="inline-flex items-center rounded-lg bg-indigo-600 text-white px-4 py-2 text-sm hover:bg-indigo-700">
              Voir les autres classements
            </a>
          <?php endif; ?>

          <?php foreach ($allSeasons as $s): ?>
            <?php $sid = (int)($s['seasonId'] ?? 0); if (!$sid || $sid === $latestId) continue; ?>
            <a href="/seasons/<?= $sid ?>/ranking"
               class="inline-flex items-center rounded-lg border px-3 py-1.5 text-sm bg-white hover:bg-slate-50">
              Saison <?= (int)($s['year'] ?? 0) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mt-4 rounded-xl border bg-white overflow-hidden">
        <table class="min-w-full text-left">
          <thead class="bg-slate-100 text-slate-700 text-sm">
            <tr>
              <th class="p-3">#</th>
              <th class="p-3">Pilote</th>
              <th class="p-3">Numéro</th>
              <th class="p-3">Points</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <?php
              $rank = 0; $prevPts = null; $i = 0;
              foreach ($standings as $row):
                $i++;
                $pts = (float)($row['point'] ?? 0);
                if ($prevPts === null || $pts < $prevPts) { $rank = $i; $prevPts = $pts; }
            ?>
              <tr class="border-t">
                <td class="p-3 font-medium"><?= $rank ?></td>
                <td class="p-3">
                  <div class="flex items-center gap-3">
                    <img src="/Assets/ProfilesPhoto/<?= e(($row['picture'] ?? '') ?: 'default.png') ?>" class="h-8 w-8 rounded-full object-cover" alt="" loading="lazy">
                    <div><?= e(trim(($row['firstName'] ?? '').' '.($row['lastName'] ?? ''))) ?></div>
                  </div>
                </td>
                <td class="p-3"><?= e($row['numero'] ?? '—') ?></td>
                <td class="p-3 font-semibold"><?= number_format((float)($row['point'] ?? 0), 1, ',', ' ') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($standings)): ?>
              <tr><td colspan="4" class="p-6 text-center text-slate-500">Pas de classement disponible.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Derniers vainqueurs -->
    <div>
      <h2 class="text-xl font-semibold">Derniers vainqueurs</h2>
      <div class="mt-4 grid gap-4">
        <?php foreach (array_slice($winners, 0, 3) as $w): ?>
          <div class="rounded-xl border bg-white p-4">
            <div class="text-sm text-slate-500"><?= e($w['seasonYear'] ?? '—') ?></div>
            <div class="mt-1 font-semibold"><?= e($w['circuitName'] ?? '—') ?></div>
            <div class="text-sm text-slate-600"><?= human_date($w['date'] ?? null) ?></div>
            <div class="mt-2 text-sm">Vainqueur : <span class="font-medium"><?= e($w['winnerName'] ?? '—') ?></span></div>
            <?php if (!empty($w['winnerId'])): ?>
              <div class="mt-3">
                <a href="/<?= (int)$w['winnerId'] ?>/see" class="text-indigo-700 text-sm hover:underline">Voir le profil →</a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (empty($winners)): ?>
          <div class="rounded-xl border p-6 text-center text-slate-500">Résultats à venir.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Dernières courses (centrées & horizontales) -->
<section class="py-12">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- En-tête centré -->
    <div class="text-center space-y-2">
      <h2 class="text-xl font-semibold">Dernières courses</h2>
      <a href="/races" class="inline-flex items-center rounded-lg border px-3 py-1.5 text-sm bg-white hover:bg-slate-50">
        Toutes les courses
      </a>
    </div>

    <?php
      // Tri du plus récent -> plus ancien + limite à 5
      $latest = array_values($latestRaces ?? []);
      usort($latest, fn($a,$b) => strcmp(($b['date'] ?? ''), ($a['date'] ?? '')));
      $latest = array_slice($latest, 0, 5);
    ?>

    <?php if (empty($latest)): ?>
      <div class="mt-6 rounded-xl border bg-white p-6 text-slate-600 text-center">
        Aucune course pour le moment.
      </div>
    <?php else: ?>
      <div class="mt-6 overflow-x-auto">
        <!-- rangée horizontale, centrée, avec padding pour éviter les bords collés -->
        <div class="grid grid-flow-col auto-cols-[18rem] gap-5 justify-center snap-x snap-mandatory px-2 pb-2">
          <?php foreach ($latest as $r): ?>
            <?php
              $id      = (int)($r['raceId'] ?? $r['id'] ?? 0);
              $dateStr = fdate_home($r['date'] ?? null);
              $title   = $r['circuitName'] ?? 'Circuit';
              $city    = $r['cityName'] ?? null;
            ?>
            <article class="snap-start rounded-xl border bg-white p-4 shadow-sm">
              <div class="text-sm text-slate-500"><?= e($dateStr) ?></div>
              <div class="mt-1 text-base font-semibold line-clamp-1"><?= e($title) ?></div>
              <?php if ($city): ?>
                <div class="text-sm text-slate-600"><?= e($city) ?></div>
              <?php endif; ?>
              <div class="mt-4">
                <a href="/races/<?= $id ?>" class="inline-block px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                  Voir la course
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>



<!-- Pilotes à la une -->
<section class="py-12 border-y bg-white">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-semibold">Pilotes à la une</h2>
    </div>
    <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($featured as $u): ?>
        <a href="/<?= (int)($u['userId'] ?? 0) ?>/see" class="group rounded-xl border bg-white hover:shadow-md transition overflow-hidden">
          <div class="p-5">
            <div class="flex items-center gap-3">
              <img src="/Assets/ProfilesPhoto/<?= e(($u['picture'] ?? '') ?: 'default.png') ?>" class="h-12 w-12 rounded-full object-cover" alt="" loading="lazy">
              <div>
                <div class="font-semibold"><?= e(trim(($u['firstName'] ?? '').' '.($u['lastName'] ?? ''))) ?></div>
                <div class="text-xs text-slate-500">#<?= e($u['numero'] ?? '—') ?></div>
              </div>
            </div>
          </div>
          <div class="border-t bg-slate-50 px-5 py-3 text-sm text-indigo-700 group-hover:bg-indigo-50">Profil →</div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($featured)): ?>
        <div class="col-span-full rounded-xl border p-8 text-center text-slate-500">Aucun profil en vedette.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Circuits -->
<section class="py-12 border-t bg-white">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-semibold">Circuits</h2>
    </div>
    <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($circuits as $c): ?>
        <div class="rounded-xl border bg-white overflow-hidden">
          <div class="h-36 bg-slate-100">
            <img src="<?= e(($c['picture'] ?? '') ?: '/Assets/circuit-placeholder.jpg') ?>" alt="" class="w-full h-full object-cover" loading="lazy">
          </div>
          <div class="p-4">
            <div class="font-semibold"><?= e($c['nameCircuit'] ?? '—') ?></div>
            <div class="text-sm text-slate-600">
              <?= e(trim(($c['cityName'] ?? '').((($c['countryName'] ?? '') !== '') ? ', '.$c['countryName'] : ''))) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($circuits)): ?>
        <div class="col-span-full rounded-xl border p-8 text-center text-slate-500">Aucun circuit pour l’instant.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-14">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-fuchsia-600 text-white p-8 md:p-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
      <div>
        <h3 class="text-2xl font-bold">Prêt à prendre le départ ?</h3>
        <p class="text-white/90 mt-1">Crée ton compte pilote et rejoins la prochaine manche du championnat.</p>
      </div>
      <div class="flex gap-3">
        <a href="/register" class="rounded-lg bg-white text-indigo-700 px-5 py-3 font-medium hover:bg-slate-100">Créer un compte</a>
        <a href="/login" class="rounded-lg border border-white/40 px-5 py-3 font-medium hover:bg-white/10">Se connecter</a>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="border-t py-8">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-sm text-slate-600 flex flex-col md:flex-row items-center justify-between gap-4">
    <div>© <?= date('Y') ?> <?= e($siteName) ?>. Tous droits réservés.</div>
    <div><a href="https://www.ebisu.brussels/nl/category/acti-fr" class="hover:text-indigo-600" target="_blank" rel="noopener">Le championnat sourd de Karting est organisé par l'ASBL EBISU</a></div>
    <div class="flex items-center gap-4">
      <a href="/dashboard-races" class="hover:text-indigo-600">Administration</a>
      <a href="/privacy" class="hover:text-indigo-600">Confidentialité</a>
      <a href="/terms" class="hover:text-indigo-600">Conditions</a>
    </div>
  </div>
</footer>

</body>
</html>
