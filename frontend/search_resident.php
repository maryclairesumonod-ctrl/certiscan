<?php
require_once __DIR__ . '/../api/auth_guard.php';
$pageTitle = 'Find Resident';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-xl mx-auto">
  <p class="cs-eyebrow mb-1">Lookup</p>
  <h1 class="text-2xl font-bold text-slate-900 mb-1">Find a resident</h1>
  <p class="text-slate-500 text-sm mb-6">Search for a resident who already has a record. If they're not found, you'll need to scan a new document instead.</p>

  <div class="relative mb-6">
    <input type="text" id="searchInput" placeholder="Type a name..."
           class="w-full border border-blue-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary">
  </div>

  <div id="results" class="space-y-2"></div>

  <p id="emptyState" class="text-center text-slate-400 text-sm py-8 hidden">
    No matching resident found.
    <a href="scan.php" class="text-primary font-medium hover:underline">Scan a new document instead</a>
  </p>

  <div class="text-center mt-6">
    <a href="scan.php" class="text-sm text-slate-500 hover:text-primary transition">
      Or scan a new document →
    </a>
  </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const resultsDiv = document.getElementById('results');
const emptyState = document.getElementById('emptyState');
let debounceTimer;

searchInput.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  const query = searchInput.value.trim();

  if (query.length < 2) {
    resultsDiv.innerHTML = '';
    emptyState.classList.add('hidden');
    return;
  }

  debounceTimer = setTimeout(() => runSearch(query), 300);
});

async function runSearch(query) {
  const res = await fetch('../api/search_residents.php?q=' + encodeURIComponent(query));
  const data = await res.json();
  const residents = data.residents || [];

  if (residents.length === 0) {
    resultsDiv.innerHTML = '';
    emptyState.classList.remove('hidden');
    return;
  }

  emptyState.classList.add('hidden');
  resultsDiv.innerHTML = residents.map(r => `
    <button data-id="${r.resident_id}"
            class="resident-card w-full text-left bg-white border border-blue-100 rounded-lg p-4 hover:border-primary hover:shadow-sm transition">
      <p class="font-medium text-slate-900">${r.full_name}</p>
      <p class="text-sm text-slate-500">${r.address || 'No address on file'}</p>
    </button>
  `).join('');

  document.querySelectorAll('.resident-card').forEach(card => {
    card.addEventListener('click', () => {
      window.location.href = 'select_certificate.php?resident_id=' + card.dataset.id;
    });
  });
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
