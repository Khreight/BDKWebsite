  <!-- Hero -->
  <section id="top" class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-slate-50"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12 pb-10">
      <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-8 items-center">
        <div>
          <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
            <?= $userToSee['firstName'] ?><span class="text-indigo-600"> <?= $userToSee['lastName'] ?></span>
          </h1>
          <p class="mt-4 text-lg text-slate-600 max-w-2xl">
            <?php if ($userToSee['description']): ?>
              <?= htmlspecialchars($userToSee['description']) ?>
            <?php else: ?>
                Pas de description ajoutée par cet utilisteur.
            <?php endif; ?>
          </p>
          <div class="mt-6 flex flex-wrap items-center gap-3">
              <?php if ($userToSee['role'] === 1): ?>
                  <span class="inline-flex text-white items-center gap-2 rounded-full bg-red-500 border text-xs font-medium px-3 py-1.5">
                      Organisateur
                  </span>
              <?php elseif ($userToSee['id'] === 1): ?>
                  <span class="inline-flex items-center gap-2 rounded-full bg-red-500 text-white text-xs font-medium px-3 py-1.5">
                      Développeur
                  </span>
              <?php endif; ?>
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-600 text-white text-xs font-medium px-3 py-1.5">
                <span class="font-semibold"><?= ($userToSee['role'] <= 2  ? 'Pilote' : 'Visiteur') ?></span>
            </span>
          </div>

          <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-xl border bg-white p-4 text-center">
              <div class="text-2xl font-bold"><?= $userToSee['wins_count']?></div>
              <div class="text-xs text-slate-500">Victoires</div>
            </div>
            <div class="rounded-xl border bg-white p-4 text-center">
              <div class="text-2xl font-bold"><?= $userToSee['podiums_count']?></div>
              <div class="text-xs text-slate-500">Podiums</div>
            </div>
            <div class="rounded-xl border bg-white p-4 text-center">
              <div class="text-2xl font-bold"><?= $userToSee['fastest_laps_count']?></div>
              <div class="text-xs text-slate-500">Meilleurs tours</div>
            </div>
          </div>
        </div>

        <div class="relative">
            <div class="rounded-2xl border bg-white shadow-sm overflow-hidden">

                <div class="relative w-full" style="aspect-ratio: 4/3;">
                <img
                    src="/Assets/ProfilesPhoto/<?= $userToSee['picture'] ?>"
                    alt="Pilote de karting en piste"
                    class="absolute inset-0 h-full w-full object-cover object-center"
                    loading="lazy" decoding="async"
                />
                </div>
            <div class="p-4 sm:p-5">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm text-slate-500">Numéro</div>
                  <div class="text-2xl font-bold">#<?= ($userToSee['numero'] && $userToSee['numero'] != 0 ? $userToSee['numero'] : '0') ?></div>
                </div>
                <div class="text-right">
                <div class="text-sm text-slate-500">Âge</div>
                <div class="text-2xl font-bold">
                    <?= isset($userToSee['birthday']) && $userToSee['birthday']
                        ? (new DateTimeImmutable($userToSee['birthday']))->diff(new DateTimeImmutable('today'))->y
                        : '—' ?>
                </div>
                </div>

              </div>
              <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                <?php if($userToSee['taille']): ?>
                    <div class="rounded-lg bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Taille</div>
                    <div class="font-semibold"><?= $userToSee['taille'] ?>cm</div>
                    </div>
                <?php endif; ?>
                <?php if($userToSee['poids']): ?>
                    <div class="rounded-lg bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Poids</div>
                    <div class="font-semibold"><?= $userToSee['poids'] ?>cm</div>
                    </div>
                <?php endif; ?>
                <div class="rounded-lg bg-slate-50 p-3">
                  <div class="text-xs text-slate-500">Origine</div>
                  <div class="font-semibold"><?= $userToSee['country_name']?> (<?= $userToSee['city_name']?>)</div>
                </div>
              </div>
            </div>
          </div>
          <div class="absolute -right-2 -bottom-2 hidden md:block h-24 w-24 rounded-2xl bg-indigo-600/10 blur-2xl"></div>
        </div>
      </div>
    </div>
  </section>

<?php if($userToSee['BestOf3Part1_year']): ?>
  <section id="palmares" class="py-12 bg-white border-y">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="text-xl font-semibold">Les 3 meilleurs classements</h2>
      <div class="mt-6 grid md:grid-cols-3 gap-6">
        <div class="rounded-xl border p-5">
          <div class="text-sm text-slate-500"><?= $userToSee['BestOf3Part1_year'] ?>
            </div>
            <h3 class="font-semibold mt-1"><?= $userToSee['BestOf3Part1_nameCircuit']?></h3>
            <ul class="mt-3 space-y-1 text-sm text-slate-700">
                <li>
                    <?php if($userToSee['BestOf3Part1_place'] == 1): ?>
                        🥇
                    <?php elseif($userToSee['BestOf3Part1_place'] == 2): ?>
                        🥈
                    <?php elseif($userToSee['BestOf3Part1_place'] == 3):  ?>
                        🥉
                    <?php else: ?>
                        🏁
                    <?php endif; ?>
                    Place #<?= $userToSee['BestOf3Part1_place'] ?>
                </li>
            </ul>
        </div>

        <?php if($userToSee['BestOf3Part2_year']): ?>
            <div class="rounded-xl border p-5">
            <div class="text-sm text-slate-500"><?= $userToSee['BestOf3Part2_year'] ?></div>
            <h3 class="font-semibold mt-1"><?= $userToSee['BestOf3Part2_nameCircuit']?></h3>
            <ul class="mt-3 space-y-1 text-sm text-slate-700">
                <li>
                    <?php if($userToSee['BestOf3Part2_place'] == 1): ?>
                        🥇
                    <?php elseif($userToSee['BestOf3Part2_place'] == 2): ?>
                        🥈
                    <?php elseif($userToSee['BestOf3Part2_place'] == 3):  ?>
                        🥉
                    <?php else: ?>
                        🏁
                    <?php endif; ?>
                    Place #<?= $userToSee['BestOf3Part2_place'] ?>
                </li>
            </ul>
            </div>
        <?php endif; ?>

        <?php if($userToSee['BestOf3Part3_year']): ?>
            <div class="rounded-xl border p-5">
            <div class="text-sm text-slate-500"><?= $userToSee['BestOf3Part3_year'] ?></div>
            <h3 class="font-semibold mt-1"><?= $userToSee['BestOf3Part3_nameCircuit']?></h3>
            <ul class="mt-3 space-y-1 text-sm text-slate-700">
                <li>
                    <?php if($userToSee['BestOf3Part3_place'] == 1): ?>
                        🥇
                    <?php elseif($userToSee['BestOf3Part3_place'] == 2): ?>
                        🥈
                    <?php elseif($userToSee['BestOf3Part3_place'] == 3):  ?>
                        🥉
                    <?php else: ?>
                        🏁
                    <?php endif; ?>
                    Place #<?= $userToSee['BestOf3Part3_place'] ?>
                </li>
            </ul>
            </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>



<?php foreach ($seasons as $season): ?>
  <section id="saison" class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Saison <?= (int)$season['year'] ?> — Résultats</h2>
      </div>

      <div class="mt-6 grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-xl border bg-white">
          <div class="overflow-x-auto">
            <table class="min-w-full text-left">
              <thead class="bg-slate-100 text-slate-700 text-sm">
                <tr>
                  <th class="p-4">Date</th>
                  <th class="p-4">Circuit</th>
                  <th class="p-4">Lieu</th>
                  <th class="p-4">Objectif</th>
                  <th class="p-4">Points gagnés</th>
                </tr>
              </thead>
              <tbody class="text-sm">
                <?php foreach (($season['races'] ?? []) as $race): ?>
                  <tr class="border-t">
                    <td class="p-4"><?= htmlspecialchars($race['date']) ?></td>
                    <td class="p-4"><?= htmlspecialchars($race['circuitName']) ?></td>
                    <td class="p-4"><?= htmlspecialchars($race['circuitCity']) ?></td>
                    <td class="p-4">Place #<?= htmlspecialchars((string)($race['place'] ?? '—')) ?></td>
                    <td class="p-4"><?= htmlspecialchars((string)($race['pointWin'] ?? '—')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="text-sm text-slate-500">
        Position dans le classement: #<?= htmlspecialchars((string)($season['rank'] ?? '—')) ?>
      </div>
    </div>
  </section>
<?php endforeach; ?>
