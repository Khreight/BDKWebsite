<?php
if (!isset($errors) || !is_array($errors)) $errors = [];
if (!isset($old) || !is_array($old))       $old    = [];

if (!function_exists('e')) {
  function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('has_error')) {
  function has_error($key, $errors) { return isset($errors[$key]) && $errors[$key] !== ''; }
}
if (!function_exists('err')) {
  function err($key, $errors) { return has_error($key, $errors) ? $errors[$key] : ''; }
}
if (!function_exists('input_classes')) {
  function input_classes($key, $errors) {
      $base = "w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 transition text-sm";
      return has_error($key, $errors)
        ? $base . " border-red-500 focus:ring-red-400"
        : $base . " border-gray-300 focus:ring-blue-400";
  }
}

$totalErrors = count($errors);
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-blue-100 py-8">
  <div class="w-full max-w-3xl bg-white rounded-xl shadow-lg p-6 md:p-8">
    <div class="flex flex-col items-center mb-6">
      <img src="Assets/Logo/logo.png" alt="BDK Karting Logo" class="h-14 mb-2">
      <h1 class="text-2xl md:text-3xl font-extrabold text-blue-700 text-center">Rejoins BDK Karting</h1>
      <p class="text-gray-600 text-center text-sm mt-1">Crée ton compte et commence à rouler avec nous !</p>
    </div>

    <?php if ($totalErrors > 0): ?>
      <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3">
        <p class="text-sm text-red-700 font-medium mb-1">Votre inscription contient <?= $totalErrors ?> erreur<?= $totalErrors>1?'s':'' ?>.</p>
      </div>

    <?php else: ?>
        <?php if (!empty($success)): ?>
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3">
            <p class="text-sm text-green-700 font-medium"><?= e($success) ?></p>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="/register" method="post" novalidate>
      <section class="mb-6">
        <h2 class="text-base font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-flex w-6 h-6 items-center justify-center rounded-full bg-blue-600 text-white text-xs">1</span>
          Informations de connexion
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label for="email" class="block text-gray-700 font-semibold mb-1 text-sm">Adresse mail</label>
            <input
              type="email"
              id="email"
              name="email"
              value="<?= e($old['email'] ?? '') ?>"
              class="<?= input_classes('email', $errors) ?>"
              aria-invalid="<?= has_error('email', $errors) ? 'true' : 'false' ?>"
              aria-describedby="<?= has_error('email', $errors) ? 'email_error' : '' ?>"
              autocomplete="email"
              placeholder="ex: exemple@gmail.com"
            >
            <?php if (has_error('email', $errors)): ?>
              <p id="email_error" class="mt-1 text-xs text-red-600"><?= e(err('email', $errors)) ?></p>
            <?php endif; ?>
          </div>

          <div>
            <label for="password" class="block text-gray-700 font-semibold mb-1 text-sm">Mot de passe</label>
            <div class="relative">
              <input
                type="password"
                id="password"
                name="password"
                class="<?= input_classes('password', $errors) ?> pr-10"
                aria-invalid="<?= has_error('password', $errors) ? 'true' : 'false' ?>"
                aria-describedby="<?= has_error('password', $errors) ? 'password_error' : 'password_hint' ?>"
                autocomplete="new-password"
                minlength="8"
                placeholder="Au moins 8 caractères"
              >
              <button type="button" id="togglePwd" class="absolute inset-y-0 right-0 px-3 text-gray-500" aria-label="Afficher le mot de passe">👁️</button>
            </div>
            <?php if (has_error('password', $errors)): ?>
              <p id="password_error" class="mt-1 text-xs text-red-600"><?= e(err('password', $errors)) ?></p>
            <?php else: ?>
              <p id="password_hint" class="mt-1 text-xs text-gray-500">Au moins une lettre et un chiffre.</p>
            <?php endif; ?>
          </div>

          <div>
            <label for="password_confirm" class="block text-gray-700 font-semibold mb-1 text-sm">Confirme le mot de passe</label>
            <div class="relative">
              <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                class="<?= input_classes('password_confirm', $errors) ?> pr-10"
                aria-invalid="<?= has_error('password_confirm', $errors) ? 'true' : 'false' ?>"
                aria-describedby="<?= has_error('password_confirm', $errors) ? 'password_confirm_error' : '' ?>"
                autocomplete="new-password"
                minlength="8"
                placeholder="Répète le mot de passe"
              >
              <button type="button" id="togglePwd2" class="absolute inset-y-0 right-0 px-3 text-gray-500" aria-label="Afficher le mot de passe">👁️</button>
            </div>
            <?php if (has_error('password_confirm', $errors)): ?>
              <p id="password_confirm_error" class="mt-1 text-xs text-red-600"><?= e(err('password_confirm', $errors)) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="mb-6">
        <h2 class="text-base font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-flex w-6 h-6 items-center justify-center rounded-full bg-blue-600 text-white text-xs">2</span>
          Ton identité
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="first_name" class="block text-gray-700 font-semibold mb-1 text-sm">Prénom</label>
            <input
              type="text"
              id="first_name"
              name="first_name"
              value="<?= e($old['first_name'] ?? '') ?>"
              class="<?= input_classes('first_name', $errors) ?>"
              aria-invalid="<?= has_error('first_name', $errors) ? 'true' : 'false' ?>"
              aria-describedby="<?= has_error('first_name', $errors) ? 'first_name_error' : '' ?>"
              autocomplete="given-name"
            >
            <?php if (has_error('first_name', $errors)): ?>
              <p id="first_name_error" class="mt-1 text-xs text-red-600"><?= e(err('first_name', $errors)) ?></p>
            <?php endif; ?>
          </div>

          <div>
            <label for="last_name" class="block text-gray-700 font-semibold mb-1 text-sm">Nom de famille</label>
            <input
              type="text"
              id="last_name"
              name="last_name"
              value="<?= e($old['last_name'] ?? '') ?>"
              class="<?= input_classes('last_name', $errors) ?>"
              aria-invalid="<?= has_error('last_name', $errors) ? 'true' : 'false' ?>"
              aria-describedby="<?= has_error('last_name', $errors) ? 'last_name_error' : '' ?>"
              autocomplete="family-name"
            >
            <?php if (has_error('last_name', $errors)): ?>
              <p id="last_name_error" class="mt-1 text-xs text-red-600"><?= e(err('last_name', $errors)) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="mb-6">
        <h2 class="text-base font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-flex w-6 h-6 items-center justify-center rounded-full bg-blue-600 text-white text-xs">3</span>
          Tes coordonnées
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="phone" class="block text-gray-700 font-semibold mb-1 text-sm">Téléphone</label>
            <input
              type="tel"
              id="phone"
              name="phone"
              value="<?= e($old['phone'] ?? '') ?>"
              class="<?= input_classes('phone', $errors) ?>"
              aria-invalid="<?= has_error('phone', $errors) ? 'true' : 'false' ?>"
              aria-describedby="<?= has_error('phone', $errors) ? 'phone_error' : '' ?>"
              placeholder="+32470123456"
              autocomplete="tel"
              inputmode="tel"
            >
            <?php if (has_error('phone', $errors)): ?>
              <p id="phone_error" class="mt-1 text-xs text-red-600"><?= e(err('phone', $errors)) ?></p>
            <?php endif; ?>
          </div>

          <div>
            <label for="city" class="block text-gray-700 font-semibold mb-1 text-sm">Ville</label>
            <input
              type="text"
              id="city"
              name="city"
              value="<?= e($old['city'] ?? '') ?>"
              class="<?= input_classes('city', $errors) ?>"
              aria-invalid="<?= has_error('city', $errors) ? 'true' : 'false' ?>"
              aria-describedby="<?= has_error('city', $errors) ? 'city_error' : '' ?>"
              autocomplete="address-level2"
              placeholder="ex: Bruxelles"
            >
            <?php if (has_error('city', $errors)): ?>
              <p id="city_error" class="mt-1 text-xs text-red-600"><?= e(err('city', $errors)) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="mb-6">
        <h2 class="text-base font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-flex w-6 h-6 items-center justify-center rounded-full bg-blue-600 text-white text-xs">4</span>
          Détails
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="birthdate" class="block text-gray-700 font-semibold mb-1 text-sm">Date de naissance</label>
            <input
              type="date"
              id="birthdate"
              name="birthdate"
              value="<?= e($old['birthdate'] ?? '') ?>"
              class="<?= input_classes('birthdate', $errors) ?>"
              aria-invalid="<?= has_error('birthdate', $errors) ? 'true' : 'false' ?>"
              aria-describedby="<?= has_error('birthdate', $errors) ? 'birthdate_error' : '' ?>"
              autocomplete="bday"
            >
            <?php if (has_error('birthdate', $errors)): ?>
              <p id="birthdate_error" class="mt-1 text-xs text-red-600"><?= e(err('birthdate', $errors)) ?></p>
            <?php endif; ?>
          </div>

          <div class="relative">
            <label class="block text-gray-700 font-semibold mb-1 text-sm">Nationalité</label>

            <div class="relative w-full" id="customNationalityDropdown">
              <button type="button" id="dropdownButton"
                class="w-full flex items-center justify-between px-3 py-2 border rounded-lg bg-white focus:outline-none transition <?= has_error('nationality', $errors) ? 'border-red-500 focus:ring-2 focus:ring-red-400' : 'border-gray-300 focus:ring-2 focus:ring-blue-400' ?>">
                <span class="flex items-center gap-2">
                  <img id="selectedFlag" src="" alt="" class="h-5 w-auto object-contain hidden">
                  <span id="selectedText" class="<?= empty($old['nationality'] ?? '') ? 'text-gray-500' : 'text-gray-800' ?> text-sm">
                    <?= empty($old['nationality'] ?? '') ? 'Sélectionne ta nationalité' : '—'  ?>
                  </span>
                </span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <ul id="dropdownList"
                class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow mt-1 max-h-60 overflow-y-auto hidden">
                <?php foreach($countries as $country): ?>
                  <li class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 cursor-pointer"
                    data-value="<?= e($country->countryId) ?>"
                    data-flag="<?= e($country->flag) ?>"
                    data-name="<?= e($country->name) ?>">
                    <img src="<?= e($country->flag) ?>" alt="" class="h-5 w-auto object-contain">
                    <span class="text-sm"><?= e($country->name) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>

              <input type="hidden" name="nationality" id="nationalityInput" value="<?= e($old['nationality'] ?? '') ?>">
            </div>

            <?php if (has_error('nationality', $errors)): ?>
              <p class="mt-1 text-xs text-red-600"><?= e(err('nationality', $errors)) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <div class="mt-6">
        <button value="submitRegister" name="submitRegister"
          class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-2.5 rounded-lg shadow transition text-base tracking-wide">
          Créer mon compte
        </button>
        <p class="text-center text-gray-500 text-xs mt-3">
          Déjà un compte ?
          <a href="/login" class="text-blue-600 hover:underline font-semibold">Se connecter</a>
        </p>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const wrapper = document.getElementById("customNationalityDropdown");
  const dropdownButton = document.getElementById("dropdownButton");
  const dropdownList = document.getElementById("dropdownList");
  const selectedFlag = document.getElementById("selectedFlag");
  const selectedText = document.getElementById("selectedText");
  const nationalityInput = document.getElementById("nationalityInput");

  function positionDropdown() {
    if (!dropdownList || dropdownList.classList.contains("hidden")) return;

    dropdownList.style.top = "";
    dropdownList.style.bottom = "";
    dropdownList.style.marginTop = "";
    dropdownList.style.marginBottom = "";

    const rect = dropdownButton.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const listHeight = Math.min(dropdownList.scrollHeight, 240);

    if (spaceBelow < listHeight && spaceAbove >= listHeight) {
      dropdownList.style.top = "auto";
      dropdownList.style.bottom = "100%";
      dropdownList.style.marginBottom = "0.25rem";
    } else {
      dropdownList.style.bottom = "auto";
      dropdownList.style.top = "100%";
      dropdownList.style.marginTop = "0.25rem";
    }
  }

  function openDropdown() {
    dropdownList.classList.remove("hidden");
    positionDropdown();
  }

  function closeDropdown() {
    dropdownList.classList.add("hidden");
  }

  function toggleDropdown() {
    dropdownList.classList.toggle("hidden");
    if (!dropdownList.classList.contains("hidden")) {
      positionDropdown();
    }
  }

  dropdownButton?.addEventListener("click", function () {
    toggleDropdown();
  });

  ["resize", "scroll"].forEach(evt => {
    window.addEventListener(evt, () => {
      positionDropdown();
    }, { passive: true });
  });

  dropdownList?.querySelectorAll("li").forEach(function (item) {
    item.addEventListener("click", function () {
      const flag = item.getAttribute("data-flag");
      const name = item.getAttribute("data-name");
      const value = item.getAttribute("data-value");

      selectedFlag.src = flag;
      selectedFlag.classList.remove("hidden");
      selectedText.textContent = name;
      selectedText.classList.remove("text-gray-500");
      selectedText.classList.add("text-gray-800");
      nationalityInput.value = value;

      closeDropdown();
    });
  });

  document.addEventListener("click", function (e) {
    if (wrapper && !wrapper.contains(e.target)) {
      closeDropdown();
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeDropdown();
      dropdownButton?.focus();
    }
  });

  const oldNationality = nationalityInput?.value;
  if (oldNationality && dropdownList) {
    const current = dropdownList.querySelector(`li[data-value="${CSS.escape(oldNationality)}"]`);
    if (current) {
      selectedFlag.src = current.getAttribute("data-flag");
      selectedFlag.classList.remove("hidden");
      selectedText.textContent = current.getAttribute("data-name");
      selectedText.classList.remove("text-gray-500");
      selectedText.classList.add("text-gray-800");
    }
  }

  function toggleVisibility(inputId) {
    const el = document.getElementById(inputId);
    if (!el) return;
    el.type = el.type === "password" ? "text" : "password";
  }
  document.getElementById("togglePwd")?.addEventListener("click", () => toggleVisibility("password"));
  document.getElementById("togglePwd2")?.addEventListener("click", () => toggleVisibility("password_confirm"));

  const firstInvalid = document.querySelector('[aria-invalid="true"]');
  if (firstInvalid) {
    firstInvalid.focus({ preventScroll: false });
  }
});
</script>
