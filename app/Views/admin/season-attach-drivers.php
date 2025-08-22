<?php
// Views/admin/season-attach-drivers.php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
function role_badge(int $role): string {
  return $role === 1
    ? '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Organisateur</span>'
    : '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">Pilote</span>';
}
$seasonYear = (int)($season['year'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Saison <?= e($seasonYear) ?> — Sélection des participants</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="bg-indigo-600 text-white">
    <div class="mx-auto max-w-5xl px-6 py-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold">Saison <?= e($seasonYear) ?> — Participants</h1>
      <a href="/dashboard-races" class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Retour</a>
    </div>
  </header>

  <main class="mx-auto max-w-5xl p-6 space-y-6">
    <?php if ($m = flash_take('error')): ?>
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_take('success')): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= e($m) ?></div>
    <?php endif; ?>

    <div class="rounded-xl border bg-white shadow-sm">
      <form method="post" action="/admin/seasons/<?= (int)$season['seasonId'] ?>/attach-drivers">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="p-4 border-b flex items-center gap-3">
          <div class="text-slate-700 font-medium">Sélectionner les participants (pilotes & organisateurs)</div>
          <div class="ml-auto">
            <input id="chkAll" type="checkbox" class="h-4 w-4 rounded border-slate-300">
            <label for="chkAll" class="ml-2 text-sm text-slate-600">Tout cocher / décocher</label>
          </div>
        </div>

        <div class="p-4">
          <div class="overflow-x-auto">
            <table class="min-w-full text-left">
              <thead class="bg-slate-100 text-slate-700 text-sm">
                <tr>
                  <th class="p-3 w-12"></th>
                  <th class="p-3">Nom</th>
                  <th class="p-3">E-mail</th>
                  <th class="p-3">Rôle</th>
                </tr>
              </thead>
              <tbody class="text-sm">
                <?php foreach (($participants ?? []) as $u): ?>
                  <?php $id = (int)$u['id']; $checked = in_array($id, $attachedIds ?? [], true); ?>
                  <tr class="border-t">
                    <td class="p-3">
                      <input type="checkbox" name="driverIds[]" value="<?= $id ?>" class="rowchk h-4 w-4 rounded border-slate-300"
                             <?= $checked ? 'checked' : '' ?>>
                    </td>
                    <td class="p-3 font-medium"><?= e(($u['lastName'] ?? '').' '.($u['firstName'] ?? '')) ?></td>
                    <td class="p-3"><?= e($u['email'] ?? '') ?></td>
                    <td class="p-3"><?= role_badge((int)($u['role'] ?? 0)) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($participants)): ?>
                  <tr><td colspan="4" class="p-6 text-center text-slate-500">Aucun organisateur/pilote disponible.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <a href="/dashboard-races" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Annuler</a>
            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Enregistrer</button>
          </div>
        </div>
      </form>
    </div>
  </main>

  <script>
    // Toggle all
    document.getElementById('chkAll')?.addEventListener('change', function() {
      document.querySelectorAll('.rowchk').forEach(cb => cb.checked = !!this.checked);
    });
  </script>
</body>
</html>
