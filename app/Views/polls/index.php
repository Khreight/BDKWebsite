<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$polls = $polls ?? [];
$votersByOpt = $votersByOpt ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Sondages</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
<header class="bg-white border-b">
  <div class="mx-auto max-w-6xl px-6 h-16 flex items-center justify-between">
    <h1 class="text-lg font-semibold">Sondages</h1>
    <a href="/" class="text-sm text-indigo-600 hover:underline">Retour</a>
  </div>
</header>

<main class="mx-auto max-w-6xl px-6 py-6 space-y-6">
  <?php if ($m = flash_take('error')): ?>
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash_take('success')): ?>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
  <?php endif; ?>

  <?php if (empty($polls)): ?>
    <div class="rounded-lg border bg-white p-6 text-slate-600">Aucun sondage pour le moment.</div>
  <?php endif; ?>

  <?php foreach ($polls as $p): ?>
    <?php
      $canVote  = !empty($p['canVote']);
      $state    = $p['_state'] ?? 'open'; // early|open|closed
      $isMany   = !empty($p['isManyChoice']);
      $badgeCls = [
        'early'  => 'bg-amber-100 text-amber-800 border-amber-200',
        'open'   => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'closed' => 'bg-slate-200 text-slate-700 border-slate-300'
      ][$state];
      $badgeTxt = [
        'early'  => 'Pas encore ouvert',
        'open'   => 'Ouvert',
        'closed' => 'Clôturé'
      ][$state];
    ?>
    <div class="rounded-xl border bg-white shadow-sm overflow-hidden">
      <div class="p-5 border-b flex items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-base font-semibold"><?= e($p['titlePoll']) ?></h2>
            <span class="text-xs px-2 py-0.5 rounded-full border <?= e($badgeCls) ?>"><?= e($badgeTxt) ?></span>
          </div>
          <?php if (!empty($p['description'])): ?>
            <p class="mt-1 text-sm text-slate-600"><?= nl2br(e($p['description'])) ?></p>
          <?php endif; ?>
          <div class="mt-2 text-xs text-slate-500 flex flex-wrap items-center gap-3">
            <?php if (!empty($p['_startFr'])): ?>
              <span>Ouverture : <strong><?= e($p['_startFr']) ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($p['_endFr'])): ?>
              <span>Clôture : <strong><?= e($p['_endFr']) ?></strong></span>
            <?php endif; ?>
            <span>Mode : <strong><?= $isMany ? 'Choix multiples' : 'Choix unique' ?></strong></span>
            <span>Votes totaux : <strong><?= (int)($p['totalVotes'] ?? 0) ?></strong></span>
          </div>
        </div>
        <?php if (!empty($p['video'])): ?>
          <div class="w-64 shrink-0">
            <video src="<?= e($p['video']) ?>" class="w-full h-36 object-cover rounded border" controls preload="metadata"></video>
          </div>
        <?php endif; ?>
      </div>

      <form method="post" action="/polls/<?= (int)$p['id'] ?>/vote" class="p-5 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="space-y-3">
          <?php foreach ($p['options'] as $idx => $o): ?>
            <?php
              $oid = (int)$o['id'];
              // construire un label lisible selon le type
              $label = '—';
              if (!empty($o['proposedText'])) {
                  $label = $o['proposedText'];
              } elseif (!empty($o['proposedDate'])) {
                  $dt = new DateTime($o['proposedDate']);
                  $label = "🗓 " . $dt->format('d/m/Y H:i');
              } elseif (!empty($o['proposedCircuit'])) {
                  $label = "🏁 " . ($o['circuitName'] ?? ('Circuit #'.$o['proposedCircuit']));
              } elseif (!empty($o['proposedPicture'])) {
                  $label = "🖼 Image proposée";
              }
              $checked = !empty($o['checkedByUser']);
            ?>
            <label class="flex items-start gap-3 rounded-lg border px-3 py-2 hover:bg-slate-50">
              <input
                type="<?= $isMany ? 'checkbox' : 'radio' ?>"
                name="options[]"
                value="<?= $oid ?>"
                <?= $checked ? 'checked' : '' ?>
                <?= $canVote ? '' : 'disabled' ?>
                class="mt-1"
              >
              <div class="flex-1">
                <div class="text-sm font-medium"><?= e($label) ?></div>

                <?php if (!empty($o['proposedPicture'])): ?>
                  <div class="mt-2">
                    <img src="<?= e($o['proposedPicture']) ?>" alt="" class="max-h-32 rounded border">
                  </div>
                <?php endif; ?>

                <?php
                  $voters = $votersByOpt[$oid] ?? [];
                  $nbV = count($voters);
                ?>
                <div class="mt-1 text-xs text-slate-500">
                  <strong><?= $nbV ?></strong> vote<?= $nbV>1?'s':''; ?>
                  <?php if ($nbV): ?>
                    — votants :
                    <?php
                      $names = array_map(function($u){
                        $lbl = trim(($u['lastName'] ?? '').' '.($u['firstName'] ?? ''));
                        if (!empty($u['numero'])) $lbl .= " (#".$u['numero'].")";
                        return $lbl;
                      }, $voters);
                      echo e(implode(', ', $names));
                    ?>
                  <?php endif; ?>
                </div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="flex items-center justify-between pt-2">
          <?php if (!$canVote): ?>
            <div class="text-sm text-slate-500">
              <?php if ($state === 'early'): ?>
                Ouverture des votes <?php if (!empty($p['_startFr'])): ?>le <strong><?= e($p['_startFr']) ?></strong><?php endif; ?>.
              <?php elseif ($state === 'closed'): ?>
                Sondage clôturé <?php if (!empty($p['_endFr'])): ?>le <strong><?= e($p['_endFr']) ?></strong><?php endif; ?>.
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="text-sm text-slate-500">
              <?php if (!empty($p['_endFr'])): ?>Clôture le <strong><?= e($p['_endFr']) ?></strong><?php endif; ?>
            </div>
          <?php endif; ?>

          <button
            class="px-4 py-2 rounded-lg text-white <?= $canVote ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-400 cursor-not-allowed' ?>"
            <?= $canVote ? '' : 'disabled' ?>
          >Enregistrer mon vote</button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
</main>
</body>
</html>
