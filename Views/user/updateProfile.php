<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$pictureUrl = !empty($userToSee['picture'])
  ? "/Assets/ProfilesPhoto/" . rawurlencode($userToSee['picture'])
  : "/Assets/ProfilesPhoto/default.png";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Modifier mon profil</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
<header class="bg-indigo-600 text-white py-4">
  <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">Modifier mon profil</h1>
    <a href="/<?= (int)$userToSee['id'] ?>/see" class="px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-slate-100">Voir mon profil</a>
  </div>
</header>

<main class="max-w-7xl mx-auto p-6">
  <?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 px-4 py-3">
      <div class="font-semibold mb-1">Corrige les champs suivants :</div>
      <ul class="list-disc pl-5 text-sm">
        <?php foreach ($errors as $v): ?><li><?= e($v) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($success = flash_take('success'))): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3"><?= e($success) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"/>
    <div class="lg:col-span-1 space-y-4">
      <div class="rounded-2xl border bg-white shadow-sm overflow-hidden">
        <div class="relative w-full aspect-[3/2] md:aspect-[4/3]">
          <img src="<?= e($pictureUrl) ?>" alt="Photo de profil" class="absolute inset-0 h-full w-full object-cover object-center"/>
        </div>
        <div class="p-4">
          <label class="block text-sm text-slate-600 mb-1">Photo (jpg / png / webp, ≤ 4 Mo)</label>
          <input type="file" name="picture" accept=".jpg,.jpeg,.png,.webp"
                 class="block w-full text-sm file:mr-3 file:rounded-lg file:border file:px-3 file:py-1.5 file:bg-slate-100 file:border-slate-300 file:hover:bg-slate-200"/>
        </div>
        <div class="px-4 pb-4 text-sm text-slate-600">
          <div class="flex items-center justify-between">
            <div>Âge</div>
            <div class="font-semibold">
              <?= isset($userToSee['birthday']) && $userToSee['birthday']
                   ? (new DateTimeImmutable($userToSee['birthday']))->diff(new DateTimeImmutable('today'))->y
                   : '—' ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="lg:col-span-2">
      <div class="rounded-xl border bg-white p-6 space-y-6">
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-slate-600">Prénom</label>
            <input name="first_name" value="<?= e($userToSee['firstName'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" required>
          </div>
          <div>
            <label class="text-sm text-slate-600">Nom</label>
            <input name="last_name" value="<?= e($userToSee['lastName'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" required>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-slate-600">Email</label>
            <input type="email" name="email"
                value="<?= e($userToSee['email'] ?? '') ?>"
                readonly
                class="mt-1 w-full rounded-lg border px-3 py-2 text-sm bg-slate-100 cursor-not-allowed">
            <div class="text-xs text-slate-500 mt-1">L’e-mail ne peut pas être modifié.</div>

          </div>
          <div>
            <label class="text-sm text-slate-600">Téléphone</label>
            <input name="phone" value="<?= e($userToSee['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
          </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
          <div>
            <label class="text-sm text-slate-600">Date de naissance</label>
            <input type="date" name="birthdate"
                   value="<?= !empty($userToSee['birthday']) ? e((new DateTimeImmutable($userToSee['birthday']))->format('Y-m-d')) : '' ?>"
                   class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
          </div>
          <div>
            <label class="text-sm text-slate-600">N° Pilote</label>
            <input name="numero" value="<?= e($userToSee['numero'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" placeholder="ex: 27">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm text-slate-600">Taille (cm)</label>
              <input name="taille" value="<?= e($userToSee['taille'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" placeholder="ex: 178">
            </div>
            <div>
              <label class="text-sm text-slate-600">Poids (kg)</label>
              <input name="poids" value="<?= e($userToSee['poids'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" placeholder="ex: 70">
            </div>
          </div>
        </div>

        <div>
          <label class="text-sm text-slate-600">Description</label>
          <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" placeholder="Brève bio / palmarès..."><?= e($userToSee['description'] ?? '') ?></textarea>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-slate-600">Nationalité</label>
            <select name="nationality" id="nationalitySelect" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
              <option value="">—</option>
              <?php foreach ($countries as $c): ?>
                <option value="<?= (int)$c['countryId'] ?>" <?= (!empty($userToSee['country_id']) && (int)$userToSee['country_id'] === (int)$c['countryId']) ? 'selected' : '' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
            <div>
            <label class="text-sm text-slate-600">Ville</label>
            <input
                type="text"
                name="city"
                id="cityInput"
                value="<?= e($userToSee['city_name'] ?? '') ?>"
                placeholder="Commence à taper…"
                list="cityList"
                class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
            >
            <datalist id="cityList"></datalist>
            <div class="text-xs text-slate-500 mt-1">
                Tu peux écrire une nouvelle ville : elle sera créée automatiquement pour le pays sélectionné.
            </div>
            </div>

        </div>

        <div class="flex justify-end gap-3 pt-2">
          <a href="/<?= (int)$userToSee['id'] ?>/see" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Annuler</a>
          <button name="submitUpdateProfile" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Enregistrer</button>
        </div>
      </div>
    </div>
  </form>
</main>

<script>
const nat  = document.getElementById('nationalitySelect');
const cityInput = document.getElementById('cityInput');
const cityList  = document.getElementById('cityList');

const ALL_CITIES = <?=
  json_encode($cities, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)
?>;

function rebuildCityDatalist() {
  const countryId = parseInt(nat?.value || 0, 10);
  cityList.innerHTML = '';
  ALL_CITIES.forEach(c => {
    if (!countryId || parseInt(c.country_id, 10) === countryId) {
      const opt = document.createElement('option');
      opt.value = c.name; 
      cityList.appendChild(opt);
    }
  });
}

rebuildCityDatalist();
if (nat) nat.addEventListener('change', rebuildCityDatalist);
</script>

</body>
</html>
