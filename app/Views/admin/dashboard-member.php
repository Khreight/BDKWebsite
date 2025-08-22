<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Membres - Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">

  <?php
  if (!function_exists('flagEmojiFromIso2')) {
    function flagEmojiFromIso2(?string $iso2): string {
      if (!$iso2 || strlen($iso2) !== 2) return '';
      $iso2 = strtoupper($iso2);
      $cp1 = 0x1F1E6 + (ord($iso2[0]) - ord('A'));
      $cp2 = 0x1F1E6 + (ord($iso2[1]) - ord('A'));
      return mb_convert_encoding('&#' . $cp1 . ';', 'UTF-8', 'HTML-ENTITIES')
           . mb_convert_encoding('&#' . $cp2 . ';', 'UTF-8', 'HTML-ENTITIES');
    }
  }

  $ROLE_NAMES = [
    1 => 'Organisateur',
    2 => 'Pilote',
    3 => 'Demandeur',
    4 => 'Visiteur',
  ];
  ?>

  <header class="bg-indigo-600 text-white py-4 px-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Membres</h1>
    <a href="/dashboard-administrator" class="bg-white text-indigo-600 px-4 py-2 rounded-lg shadow hover:bg-slate-100 transition">Retour</a>
  </header>

  <main class="max-w-screen-2xl mx-auto w-full p-6 md:p-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <h2 class="text-xl font-semibold">Liste des membres</h2>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left border-collapse text-base md:text-[17px] leading-6">
          <thead class="bg-slate-100 text-slate-700 text-sm">
            <tr>
              <th class="p-4">Prénom</th>
              <th class="p-4">Nom</th>
              <th class="p-4">Naissance</th>
              <th class="p-4">Âge</th>
              <th class="p-4">Email</th>
              <th class="p-4">Téléphone</th>
              <th class="p-4">Ville</th>
              <th class="p-4">Nationalité</th>
              <th class="p-4">Rôle</th>
              <th class="p-4">Créé le</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <?php foreach ($users as $user): ?>
              <?php
                $first = htmlspecialchars($user['firstName'] ?? '');
                $last  = htmlspecialchars($user['lastName'] ?? '');
                $birth = $user['birthday'] ?? null;

                $age = '';
                if ($birth) {
                  try {
                    $dBirth = new DateTimeImmutable($birth);
                    $age = $dBirth->diff(new DateTimeImmutable('today'))->y;
                  } catch (Exception $e) { $age = ''; }
                }

                $birthFmt = $birth ? (new DateTimeImmutable($birth))->format('d/m/Y') : '';
                $email = htmlspecialchars($user['email'] ?? '');
                $phone = htmlspecialchars($user['phone'] ?? '');

                $cityIdField = isset($user['city_id']) ? 'city_id' : (isset($user['city']) ? 'city' : null);
                $cityName = '';
                if (!empty($user['city_name'])) {
                  $cityName = $user['city_name'];
                } elseif ($cityIdField) {
                  $cid = (int)$user[$cityIdField];
                  $cityName = $cid ? ('#'.$cid) : '';
                }

                $countryName = $user['country_name'] ?? '';
                $countryIso2 = $user['country_iso2'] ?? null; // si tu es passé aux ISO2
                $flag = $countryIso2 ? flagEmojiFromIso2($countryIso2) : '';

                $roleId = (int)($user['role'] ?? 0);
                $roleLabel = $user['role_name'] ?? ($ROLE_NAMES[$roleId] ?? 'Membre');

                $dateRequest = !empty($user['dateRequestMember']) ? (new DateTimeImmutable($user['dateRequestMember']))->format('d/m/Y H:i') : '';

                $created = !empty($user['created_at']) ? (new DateTimeImmutable($user['created_at']))->format('d/m/Y H:i') : '';

                $uid = (string)($user['id'] ?? ($user['userId'] ?? ''));
              ?>
              <tr class="border-t">
                <td class="p-4"><?= $first ?></td>
                <td class="p-4"><?= $last ?></td>
                <td class="p-4"><?= htmlspecialchars($birthFmt) ?></td>
                <td class="p-4"><?= htmlspecialchars((string)$age) ?></td>
                <td class="p-4"><?= $email ?></td>
                <td class="p-4"><?= $phone ?></td>
                <td class="p-4"><?= htmlspecialchars($cityName) ?></td>
                <td class="p-4">
                  <?php if ($countryName || $flag): ?>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1">
                      <?php if ($flag): ?><span class="text-base leading-none"><?= $flag ?></span><?php endif; ?>
                      <span class="text-slate-700"><?= htmlspecialchars($countryName) ?></span>
                    </span>
                  <?php else: ?>
                    <span class="text-slate-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="p-4">
                  <?php
                    $roleId    = (int)($user['role'] ?? 0);
                    $roleLabel = $user['role_name'] ?? ($ROLE_NAMES[$roleId] ?? 'Membre');

                    $dateRequestText = '';
                    if ($roleId === 3 && !empty($user['dateRequestMember'])) {
                      try {
                        $dtReq = new DateTimeImmutable($user['dateRequestMember']);
                        $dateRequestText = 'Demande envoyée le ' . $dtReq->format('d/m/Y') . ' à ' . $dtReq->format('H:i');
                      } catch (Exception $e) {
                      }
                    }
                  ?>
                  <div class="space-y-1">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                      <?= $roleLabel === 'Organisateur'
                            ? 'bg-indigo-50 text-indigo-700 border border-indigo-200'
                            : ($roleLabel === 'Pilote'
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                : ($roleLabel === 'Demandeur'
                                    ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                    : 'bg-slate-50 text-slate-700 border border-slate-200')) ?>">
                      <?= htmlspecialchars($roleLabel) ?>
                    </span>

                    <?php if ($dateRequestText): ?>
                      <div class="text-xs text-slate-500">
                        <?= htmlspecialchars($dateRequestText) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="p-4"><?= htmlspecialchars($created) ?></td>

                <td class="p-4 text-right">
                  <?php if ($uid !== ''): ?>
                    <div class="inline-flex flex-wrap justify-end gap-2">

                      <?php if ($roleId !== 1): ?>
                        <a href="/<?= urlencode($uid) ?>/upgrade-admin"
                           class="px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100">
                          Promouvoir
                        </a>
                      <?php endif; ?>

                      <?php if ($roleId === 3): ?>
                        <a href="/<?= urlencode($uid) ?>/upgrade-driver"
                           class="px-3 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
                          Nommer Pilote
                        </a>
                      <?php endif; ?>

                      <?php if ($roleId === 2): ?>
                        <a href="/<?= urlencode($uid) ?>/downgrade-driver"
                           class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
                          Retirer Pilote
                        </a>
                      <?php endif; ?>

                      <a href="/<?= urlencode($uid) ?>/see"
                          class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
                        Voir profil
                      </a>

                      <form method="post" action="/<?= urlencode($uid) ?>/delete-account"
                            onsubmit="return confirm('Supprimer ce membre ?');">
                        <button class="px-3 py-1.5 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100">
                          Supprimer
                        </button>
                      </form>
                    </div>
                  <?php else: ?>
                    <span class="text-slate-400 italic">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($users)): ?>
              <tr><td colspan="11" class="p-6 text-center text-slate-500">Aucun utilisateur trouvé.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (isset($page, $totalPages) && $totalPages > 1): ?>
        <div class="mt-4 flex items-center justify-between">
          <div class="text-sm text-slate-600">Page <?= (int)$page ?> / <?= (int)$totalPages ?></div>
          <div class="flex gap-2">
            <?php if ($page > 1): ?>
              <a class="px-3 py-1.5 rounded-lg border hover:bg-slate-50"
                 href="/dashboard-members?page=<?= $page-1 ?>&q=<?= urlencode($q ?? '') ?>">Précédent</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
              <a class="px-3 py-1.5 rounded-lg border hover:bg-slate-50"
                 href="/dashboard-members?page=<?= $page+1 ?>&q=<?= urlencode($q ?? '') ?>">Suivant</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
