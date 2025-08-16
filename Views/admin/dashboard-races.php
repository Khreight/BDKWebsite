<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Courses - Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="bg-indigo-600 text-white py-4 px-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Courses</h1>
    <a href="/dashboard-administrator" class="bg-white text-indigo-600 px-4 py-2 rounded-lg shadow hover:bg-slate-100 transition">Retour</a>
  </header>

  <main class="max-w-4xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Liste des courses</h2>
      <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">+ Ajouter</button>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-100">
          <tr>
            <th class="p-3">Nom</th>
            <th class="p-3">Date</th>
            <th class="p-3">Lieu</th>
            <th class="p-3">Statut</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-t">
            <td class="p-3">10 km de Namur</td>
            <td class="p-3">28/09/2025</td>
            <td class="p-3">Namur</td>
            <td class="p-3 text-green-600">Ouverte</td>
          </tr>
          <tr class="border-t">
            <td class="p-3">Marathon de Paris</td>
            <td class="p-3">05/04/2026</td>
            <td class="p-3">Paris</td>
            <td class="p-3 text-red-600">Annulée</td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
