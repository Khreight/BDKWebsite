<?php if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$pollTitle = $poll['titlePoll'] ?? 'Sondage';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Votants — <?= e($pollTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
<header class="bg-indigo-600 text-white">
  <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Votants — <?= e($pollTitle) ?></h1>
    <a href="/admin/polls" class="bg-white text-indigo-700 px-4 py-2 rounded-lg hover:bg-slate-100">Retour</a>
  </div>
</header>

<main class="max-w-7xl mx-auto p-6 space-y-6">
  <?php if (empty($groups)): ?>
    <div class="rounded-lg border bg-white p-6 text-slate-600">Aucun vote pour le moment.</div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 gap-6">
      <?php foreach ($groups as $g):
        $o = $g['option'];
        $label = '—';
        if (!empty($o['proposedText']))    $label = $o['proposedText'];
        elseif (!empty($o['proposedDate']))    $label = date('d/m/Y H:i', strtotime($o['proposedDate']));
        elseif (!empty($o['proposedPicture'])) $label = 'Image: '.basename(parse_url($o['proposedPicture'], PHP_URL_PATH));
        elseif (!empty($o['proposedCircuit'])) $label = 'Circuit #'.$o['proposedCircuit'];
      ?>
      <div class="rounded-xl border bg-white shadow-sm">
        <div class="p-4 border-b">
          <div class="text-sm text-slate-500">Option #<?= (int)$o['optionId'] ?></div>
          <div class="font-semibold"><?= e($label) ?></div>
        </div>
        <div class="p-4">
          <?php if (empty($g['voters'])): ?>
            <div class="text-slate-500 text-sm">Aucun votant.</div>
          <?php else: ?>
            <ul class="space-y-2">
              <?php foreach ($g['voters'] as $u): ?>
                <li class="flex items-center justify-between rounded-lg border px-3 py-2">
                  <div>
                    <div class="font-medium"><?= e($u['lastName'].' '.$u['firstName']) ?> <?= $u['numero'] ? '#'.(int)$u['numero'] : '' ?></div>
                    <div class="text-xs text-slate-500"><?= ((int)$u['role']===1?'Organisateur':'Pilote/Utilisateur') ?></div>
                  </div>
                  <span class="text-xs text-slate-500">ID <?= (int)$u['id'] ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
