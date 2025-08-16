<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

function euro_price(?int $cents): string {
  if ($cents === null) return '—';
  return number_format($cents/100, 2, ',', ' ') . " €";
}
function human_date(?string $dt): string {
  if (!$dt) return '—';
  try { return (new DateTime($dt))->format('d/m/Y H:i'); } catch(Exception $e){ return e($dt); }
}
function race_phase(array $r, ?DateTime $now = null): array {
  $now = $now ?: new DateTime('now');
  $date  = !empty($r['date']) ? new DateTime($r['date']) : null;
  $open  = !empty($r['registration_open'])  ? new DateTime($r['registration_open'])  : null;
  $close = !empty($r['registration_close']) ? new DateTime($r['registration_close']) : null;

  if ($date && $now >= $date)       return ['label'=>'Course terminée','class'=>'bg-slate-100 text-slate-700 border-slate-200'];
  if ($open && $close && $now >= $open && $now <= $close)
                                    return ['label'=>'Inscriptions ouvertes','class'=>'bg-emerald-50 text-emerald-700 border-emerald-200'];
  if ($close && $now > $close && $date && $now < $date)
                                    return ['label'=>'Inscriptions clôturées','class'=>'bg-amber-50 text-amber-700 border-amber-200'];
  if ($open && $now < $open)        return ['label'=>'Bientôt','class'=>'bg-indigo-50 text-indigo-700 border-indigo-200'];
  return ['label'=>'À venir','class'=>'bg-indigo-50 text-indigo-700 border-indigo-200'];
}

$siteName = "BDK Karting";
$heroImg  = "/Assets/hero-kart.jpg"; // place un visuel ici
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
        <span class="inline-flex h-8 w-8 rounded-lg bg-indigo-600 text-white items-center justify-center">BK</span>
        <span><?= e($siteName) ?></span>
      </a>
      <nav class="hidden md:flex items-center gap-6 text-sm text-slate-700">
        <a href="/dashboard-races" class="hover:text-indigo-600">Admin</a>
        <a href="/login" class="hover:text-indigo-600">Connexion</a>
        <a href="/register" class="inline-flex rounded-lg bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700">Rejoindre</a>
      </nav>
    </div>
  </header>

  <!-- Hero -->
  <section class="relative isolate">
    <div class="absolute inset-0 -z-10">
      <img src="<?= e($heroImg) ?>" alt="" class="h-full w-full object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-black/20"></div>
    </div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 lg:py-28 text-white">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs">
          <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Saison <?= e(($latestSeason['year'] ?? date('Y'))) ?> en cours
        </div>
        <h1 class="mt-4 text-3xl sm:text-5xl font-extrabold leading-tight">La référence du karting compétition</h1>
        <p class="mt-4 text-slate-200 max-w-2xl">
          Inscriptions, classements, circuits, résultats et vidéos : tout le championnat, au même endroit.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
          <a href="/register" class="rounded-lg bg-indigo-600 px-5 py-3 font-medium hover:bg-indigo-700">Devenir pilote</a>
          <a href="#calendrier" class="rounded-lg border border-white/30 px-5 py-3 font-medium hover:bg-white/10">Voir le calendrier</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Counters -->
  <section class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid grid-cols-2 md:grid-cols-5 gap-4">
      <?php
        $c = $counters ?? ['drivers'=>0,'circuits'=>0,'races'=>0,'seasons'=>0,'pastRaces'=>0];
        $cards = [
          ['label'=>'Pilotes & Org.','value'=>$c['drivers']],
          ['label'=>'Circuits','value'=>$c['circuits']],
          ['label'=>'Courses totales','value'=>$c['races']],
          ['label'=>'Saisons','value'=>$c['seasons']],
          ['label'=>'Courses disputées','value'=>$c['pastRaces']],
        ];
      ?>
      <?php foreach ($cards as $it): ?>
        <div class="rounded-xl border bg-white p-5 text-center">
          <div class="text-3xl font-extrabold"><?= (int)$it['value'] ?></div>
          <div class="mt-1 text-sm text-slate-600"><?= e($it['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Upcoming races -->
  <section id="calendrier" class="py-12 border-y bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Prochaines courses</h2>
        <a href="/dashboard-races" class="text-sm text-indigo-600 hover:underline">Tout le calendrier</a>
      </div>
      <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach (($upcoming ?? []) as $r): ?>
          <?php $phase = race_phase($r); ?>
          <a href="/admin/races/<?= (int)$r['raceId'] ?>" class="group block rounded-xl border bg-white hover:shadow-md transition overflow-hidden">
            <div class="p-5">
              <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-slate-500"><?= e($r['seasonYear'] ?? '') ?></div>
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium <?= e($phase['class']) ?>">
                  <?= e($phase['label']) ?>
                </span>
              </div>
              <div class="mt-1 text-lg font-semibold"><?= e($r['circuitName'] ?? 'Circuit') ?></div>
              <div class="text-sm text-slate-600"><?= e(($r['cityName'] ?? '').($r['countryName'] ? ', '.$r['countryName'] : '')) ?></div>
              <div class="mt-3 flex items-center justify-between text-sm">
                <div class="text-slate-700">Course: <span class="font-medium"><?= human_date($r['date'] ?? null) ?></span></div>
                <div class="text-slate-700">Prix: <span class="font-medium"><?= euro_price($r['price_cents'] ?? null) ?></span></div>
              </div>
              <?php if (!empty($r['registration_open']) || !empty($r['registration_close'])): ?>
                <div class="mt-2 text-xs text-slate-500">
                  Inscriptions: <?= e(human_date($r['registration_open'] ?? null)) ?> → <?= e(human_date($r['registration_close'] ?? null)) ?>
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
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold">Classement — Saison <?= e($latestSeason['year'] ?? '—') ?></h2>
          <a href="/admin/seasons/<?= (int)($latestSeason['seasonId'] ?? 0) ?>/ranking" class="text-sm text-indigo-600 hover:underline">Voir le classement</a>
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
              <?php $i=1; foreach (($standings ?? []) as $row): ?>
                <tr class="border-t">
                  <td class="p-3 font-medium"><?= $i++; ?></td>
                  <td class="p-3">
                    <div class="flex items-center gap-3">
                      <img src="/Assets/ProfilesPhoto/<?= e($row['picture'] ?: 'default.png') ?>" class="h-8 w-8 rounded-full object-cover" alt="">
                      <div><?= e($row['firstName'].' '.$row['lastName']) ?></div>
                    </div>
                  </td>
                  <td class="p-3"><?= e($row['numero'] ?? '—') ?></td>
                  <td class="p-3 font-semibold"><?= (int)($row['point'] ?? 0) ?></td>
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
          <?php foreach (($winners ?? []) as $w): ?>
            <div class="rounded-xl border bg-white p-4">
              <div class="text-sm text-slate-500"><?= e($w['seasonYear'] ?? '—') ?></div>
              <div class="mt-1 font-semibold"><?= e($w['circuitName'] ?? '—') ?></div>
              <div class="text-sm text-slate-600"><?= human_date($w['date'] ?? null) ?></div>
              <div class="mt-2 text-sm">Vainqueur : <span class="font-medium"><?= e($w['winnerName'] ?? '—') ?></span></div>
              <div class="mt-3">
                <a href="/<?= (int)($w['winnerId'] ?? 0) ?>/see" class="text-indigo-700 text-sm hover:underline">Voir le profil →</a>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($winners)): ?>
            <div class="rounded-xl border p-6 text-center text-slate-500">Résultats à venir.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Pilotes à la une -->
  <section class="py-12 border-y bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Pilotes & Organisateurs à la une</h2>
        <a href="/dashboard-members" class="text-sm text-indigo-600 hover:underline">Voir tous les membres</a>
      </div>
      <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach (($featured ?? []) as $u): ?>
          <a href="/<?= (int)$u['userId'] ?>/see" class="group rounded-xl border bg-white hover:shadow-md transition overflow-hidden">
            <div class="p-5">
              <div class="flex items-center gap-3">
                <img src="/Assets/ProfilesPhoto/<?= e($u['picture'] ?: 'default.png') ?>" class="h-12 w-12 rounded-full object-cover" alt="">
                <div>
                  <div class="font-semibold"><?= e($u['firstName'].' '.$u['lastName']) ?></div>
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

  <!-- Vidéos récentes -->
  <section class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Vidéos récentes</h2>
      </div>
      <div class="mt-6 grid md:grid-cols-3 gap-6">
        <?php foreach (($videos ?? []) as $v): ?>
          <div class="rounded-xl border bg-white overflow-hidden">
            <div class="aspect-video bg-black">
              <?php
                $src = (string)($v['video'] ?? '');
                $isLocal = preg_match('/\.(mp4|webm|mov|qt)$/i', $src);
              ?>
              <?php if ($isLocal): ?>
                <video src="<?= e($src) ?>" controls class="w-full h-full"></video>
              <?php else: ?>
                <!-- lien externe (YouTube/Vimeo): simple iframe si tu stockes l'embed, sinon lien -->
                <a href="<?= e($src) ?>" target="_blank" class="flex items-center justify-center h-full text-white">Voir la vidéo</a>
              <?php endif; ?>
            </div>
            <div class="p-4">
              <div class="text-sm text-slate-500"><?= e($v['seasonYear'] ?? '—') ?></div>
              <div class="font-semibold"><?= e($v['circuitName'] ?? '—') ?></div>
              <div class="text-sm text-slate-600"><?= human_date($v['date'] ?? null) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($videos)): ?>
          <div class="col-span-full rounded-xl border p-8 text-center text-slate-500">Aucune vidéo disponible.</div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Circuits -->
  <section class="py-12 border-t bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Circuits</h2>
        <a href="/dashboard-races" class="text-sm text-indigo-600 hover:underline">Gérer</a>
      </div>
      <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach (($circuits ?? []) as $c): ?>
          <div class="rounded-xl border bg-white overflow-hidden">
            <div class="h-36 bg-slate-100">
              <img src="<?= e($c['picture'] ?: '/Assets/circuit-placeholder.jpg') ?>" alt="" class="w-full h-full object-cover">
            </div>
            <div class="p-4">
              <div class="font-semibold"><?= e($c['nameCircuit']) ?></div>
              <div class="text-sm text-slate-600"><?= e(($c['cityName'] ?? '').($c['countryName'] ? ', '.$c['countryName'] : '')) ?></div>
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
      <div class="flex items-center gap-4">
        <a href="/dashboard-races" class="hover:text-indigo-600">Administration</a>
        <a href="/privacy" class="hover:text-indigo-600">Confidentialité</a>
        <a href="/terms" class="hover:text-indigo-600">Conditions</a>
      </div>
    </div>
  </footer>

</body>
</html>
