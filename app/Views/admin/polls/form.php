<?php if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= ($mode==='edit'?'Éditer':'Créer') ?> un sondage</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
<header class="bg-indigo-600 text-white">
  <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
    <h1 class="text-2xl font-semibold"><?= ($mode==='edit'?'Éditer':'Créer') ?> un sondage</h1>
    <a href="/admin/polls" class="bg-white text-indigo-700 px-4 py-2 rounded-lg hover:bg-slate-100">Retour</a>
  </div>
</header>

<main class="max-w-7xl mx-auto p-6 space-y-6">
  <?php if ($m = (function_exists('flash_take') ? flash_take('success') : null)): ?>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($m = (function_exists('flash_take') ? flash_take('error') : null)): ?>
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
  <?php endif; ?>

  <form method="post"
        enctype="multipart/form-data"
        action="<?= ($mode==='edit' ? '/admin/polls/'.(int)$poll['id'].'/update' : '/admin/polls/create') ?>"
        class="space-y-6">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="rounded-xl bg-white border shadow p-5 space-y-4">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-slate-600 mb-1">Titre *</label>
          <input name="titlePoll" required
                 value="<?= e($poll['titlePoll'] ?? '') ?>"
                 class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1">Type du sondage *</label>
          <?php $pt = $poll['pollType'] ?? 'text'; ?>
          <select name="pollType" id="pollType"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2"
                  onchange="onPollTypeChange()">
            <option value="date"    <?= $pt==='date'?'selected':'' ?>>Date</option>
            <option value="circuit" <?= $pt==='circuit'?'selected':'' ?>>Circuit</option>
            <option value="text"    <?= $pt==='text'?'selected':'' ?>>Texte</option>
            <option value="picture" <?= $pt==='picture'?'selected':'' ?>>Image (URL)</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm text-slate-600 mb-1">Description</label>
        <textarea name="description" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= e($poll['description'] ?? '') ?></textarea>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm text-slate-600 mb-1">Début</label>
          <input type="datetime-local" name="startDate"
                 value="<?= !empty($poll['startDate']) ? e(date('Y-m-d\TH:i', strtotime($poll['startDate']))) : '' ?>"
                 class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1">Fin</label>
          <input type="datetime-local" name="endDate"
                 value="<?= !empty($poll['endDate']) ? e(date('Y-m-d\TH:i', strtotime($poll['endDate']))) : '' ?>"
                 class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div class="flex items-center gap-3 mt-7">
          <?php $mc = !empty($poll['isManyChoice']); ?>
          <input type="checkbox" name="isManyChoice" id="isManyChoice" class="rounded border-slate-300" <?= $mc?'checked':'' ?>>
          <label for="isManyChoice" class="text-sm text-slate-700">Autoriser le choix multiple</label>
        </div>
      </div>

      <div>
        <label class="block text-sm text-slate-600 mb-1">Vidéo (upload)</label>
        <input type="file" name="video" accept=".mp4,.webm,.mov,.qt"
               class="w-full rounded-lg border border-slate-300 px-3 py-2" />
        <?php if (!empty($poll['video'])): ?>
          <div class="mt-2 text-sm text-slate-600">Vidéo actuelle : <code><?= e($poll['video']) ?></code></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Options -->
    <div class="rounded-xl bg-white border shadow p-5">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Options</h2>
        <button type="button" class="px-3 py-2 rounded-lg border hover:bg-slate-50" onclick="addOptionRow()">+ Ajouter une option</button>
      </div>
      <div id="optionsWrap" class="mt-4 space-y-3">
        <?php
          $existing = $options ?? [];
          if (empty($existing)) $existing = [[]];
          $idx = 0;
          foreach ($existing as $opt):
            $optId   = isset($opt['id']) ? (int)$opt['id'] : null;
            $pt      = $poll['pollType'] ?? ($_POST['pollType'] ?? 'text');
            $valDate = $valText = $valPict = $valCirc = '';
            if ($mode==='edit' && isset($poll['pollType'])) {
              if ($pt==='date')    { $valDate = !empty($opt['proposedDate'])    ? date('Y-m-d\TH:i', strtotime($opt['proposedDate'])) : ''; }
              if ($pt==='text')    { $valText = $opt['proposedText']    ?? ''; }
              if ($pt==='picture') { $valPict = $opt['proposedPicture'] ?? ''; }
              if ($pt==='circuit') { $valCirc = isset($opt['proposedCircuit']) ? (int)$opt['proposedCircuit'] : ''; }
            } else {
              $valDate = !empty($opt['date']) ? e($opt['date']) : '';
              $valText = !empty($opt['text']) ? e($opt['text']) : '';
              $valPict = !empty($opt['picture']) ? e($opt['picture']) : '';
              $valCirc = !empty($opt['circuit']) ? (int)$opt['circuit'] : '';
            }
        ?>
        <div class="optionRow rounded-lg border p-3" data-idx="<?= $idx ?>">
          <?php if ($optId): ?><input type="hidden" name="options[<?= $idx ?>][id]" value="<?= $optId ?>"><?php endif; ?>
          <input type="hidden" name="options[<?= $idx ?>][type]" class="optType" value="<?= e($pt) ?>">
          <div class="grid md:grid-cols-3 gap-3 items-end">
            <div class="col-span-2">
              <div class="typeFields">
                <div data-for="date" class="<?= $pt==='date'?'':'hidden' ?>">
                  <label class="block text-sm text-slate-600 mb-1">Date/heure</label>
                  <input type="datetime-local" name="options[<?= $idx ?>][date]" value="<?= e($valDate) ?>"
                         class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
                <div data-for="circuit" class="<?= $pt==='circuit'?'':'hidden' ?>">
                  <label class="block text-sm text-slate-600 mb-1">Circuit</label>
                  <select name="options[<?= $idx ?>][circuit]" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">— Sélectionner —</option>
                    <?php foreach (($circuits ?? []) as $c): ?>
                      <option value="<?= (int)$c['id'] ?>" <?= ($valCirc!=='') && ((int)$valCirc === (int)$c['id']) ? 'selected':'' ?>>
                        <?= e($c['nameCircuit']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div data-for="text" class="<?= $pt==='text'?'':'hidden' ?>">
                  <label class="block text-sm text-slate-600 mb-1">Texte</label>
                  <input type="text" name="options[<?= $idx ?>][text]" value="<?= e($valText) ?>"
                         class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
                <div data-for="picture" class="<?= $pt==='picture'?'':'hidden' ?>">
                  <label class="block text-sm text-slate-600 mb-1">Image (URL)</label>
                  <input type="url" name="options[<?= $idx ?>][picture]" value="<?= e($valPict) ?>"
                         class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                  <?php if ($valPict): ?>
                    <img src="<?= e($valPict) ?>" alt="" class="mt-2 h-24 rounded border object-contain">
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="flex gap-2 justify-end">
              <button type="button" class="px-3 py-2 rounded-lg border hover:bg-slate-50" onclick="removeOptionRow(this)">Supprimer</button>
            </div>
          </div>
        </div>
        <?php $idx++; endforeach; ?>
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <a href="/admin/polls" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Annuler</a>
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
        <?= ($mode==='edit'?'Enregistrer':'Publier / Créer') ?>
      </button>
    </div>
  </form>
</main>

<script>
  function currentPollType() { return document.getElementById('pollType').value; }
  function onPollTypeChange() {
    const t = currentPollType();
    document.querySelectorAll('.optionRow').forEach(row => {
      row.querySelector('.optType').value = t;
      row.querySelectorAll('.typeFields > div').forEach(div => {
        div.classList.toggle('hidden', div.getAttribute('data-for') !== t);
      });
    });
  }
  function removeOptionRow(btn) { const row = btn.closest('.optionRow'); if (row) row.remove(); }
  function addOptionRow() {
    const wrap = document.getElementById('optionsWrap');
    const idx = wrap.querySelectorAll('.optionRow').length;
    const t = currentPollType();
    const html = `
      <div class="optionRow rounded-lg border p-3" data-idx="${idx}">
        <input type="hidden" name="options[${idx}][type]" class="optType" value="${t}">
        <div class="grid md:grid-cols-3 gap-3 items-end">
          <div class="col-span-2">
            <div class="typeFields">
              <div data-for="date" ${t==='date'?'':'class="hidden"'}>
                <label class="block text-sm text-slate-600 mb-1">Date/heure</label>
                <input type="datetime-local" name="options[${idx}][date]" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
              </div>
              <div data-for="circuit" ${t==='circuit'?'':'class="hidden"'}>
                <label class="block text-sm text-slate-600 mb-1">Circuit</label>
                <select name="options[${idx}][circuit]" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                  <option value="">— Sélectionner —</option>
                  <?php if (!empty($circuits)): foreach ($circuits as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['nameCircuit']) ?></option>
                  <?php endforeach; endif; ?>
                </select>
              </div>
              <div data-for="text" ${t==='text'?'':'class="hidden"'}>
                <label class="block text-sm text-slate-600 mb-1">Texte</label>
                <input type="text" name="options[${idx}][text]" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
              </div>
              <div data-for="picture" ${t==='picture'?'':'class="hidden"'}>
                <label class="block text-sm text-slate-600 mb-1">Image (URL)</label>
                <input type="url" name="options[${idx}][picture]" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
              </div>
            </div>
          </div>
          <div class="flex gap-2 justify-end">
            <button type="button" class="px-3 py-2 rounded-lg border hover:bg-slate-50" onclick="removeOptionRow(this)">Supprimer</button>
          </div>
        </div>
      </div>
    `;
    const div = document.createElement('div');
    div.innerHTML = html.trim();
    wrap.appendChild(div.firstChild);
  }
  onPollTypeChange();
</script>
</body>
</html>
