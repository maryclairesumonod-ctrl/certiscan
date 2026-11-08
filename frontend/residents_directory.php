<?php
require_once __DIR__ . '/../api/auth_guard.php';
$pageTitle = 'Residents';
require __DIR__ . '/includes/header.php';
?>
<p class="cs-eyebrow mb-1">Directory</p>
<h1 class="text-2xl font-bold text-slate-900 mb-1">Residents</h1>
<p class="text-slate-500 text-sm mb-6">Click a resident to see every certificate they've requested and what's already been released.</p>

<input type="text" id="searchInput" placeholder="Search by name..."
       class="w-full max-w-md border border-blue-200 rounded-lg px-4 py-2.5 mb-6 focus:outline-none focus:ring-2 focus:ring-primary">

<div id="residentList" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8"></div>

<!-- History modal -->
<div id="historyModal" class="hidden fixed inset-0 bg-slate-900/40 flex items-center justify-center p-4 z-20">
  <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4 max-h-[85vh] overflow-y-auto">
    <div class="flex items-center justify-between">
      <div>
        <h2 id="historyName" class="text-lg font-semibold text-slate-900"></h2>
        <p id="historyAddress" class="text-sm text-slate-500"></p>
      </div>
      <button id="closeHistoryBtn" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
    </div>
    <div id="historyList" class="space-y-2"></div>
  </div>
</div>

<script>
const statusLabel = {
  pending: 'Pending', under_review: 'Under Review', approved: 'Approved',
  rejected: 'Rejected', ready_for_pickup: 'Ready for Pickup', released: 'Released',
};
const statusColor = {
  pending: 'badge-pending', under_review: 'badge-under_review',
  approved: 'badge-approved', rejected: 'badge-rejected',
  ready_for_pickup: 'badge-ready_for_pickup', released: 'badge-released',
};

let debounceTimer;

async function loadResidents(query = '') {
  const res = await fetch('../api/secretary_list_all_residents.php?q=' + encodeURIComponent(query));
  const data = await res.json();
  const container = document.getElementById('residentList');

  if (!data.residents || data.residents.length === 0) {
    container.innerHTML = '<p class="text-slate-400 text-sm">No residents found.</p>';
    return;
  }

  container.innerHTML = data.residents.map(r => `
    <button data-id="${r.resident_id}" class="residentCard text-left bg-white border border-blue-100 rounded-xl p-4 hover:border-primary hover:shadow-sm transition">
      <p class="font-medium text-slate-900">${r.full_name}</p>
      <p class="text-xs text-slate-500">${r.address || 'No address on file'}</p>
      <p class="text-xs text-slate-400 mt-1">${r.released_count} released · ${r.total_count} total requests</p>
    </button>
  `).join('');

  document.querySelectorAll('.residentCard').forEach(card => {
    card.addEventListener('click', () => openHistory(card.dataset.id));
  });
}

async function openHistory(residentId) {
  const res = await fetch('../api/secretary_resident_certificate_history.php?resident_id=' + residentId);
  const data = await res.json();

  if (!data.success) {
    alert(data.message || 'Could not load history.');
    return;
  }

  document.getElementById('historyName').textContent = data.resident.full_name;
  document.getElementById('historyAddress').textContent = data.resident.address || '';

  const historyList = document.getElementById('historyList');
  if (data.certificates.length === 0) {
    historyList.innerHTML = '<p class="text-slate-400 text-sm">No certificate requests yet.</p>';
  } else {
    historyList.innerHTML = data.certificates.map(c => `
      <div class="border border-blue-100 rounded-lg p-3 flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-slate-900">${c.certificate_type}</p>
          <p class="text-xs text-slate-400 font-mono">${c.cert_number}</p>
          <p class="text-xs text-slate-400">Issued: ${c.issued_at || '—'}${c.released_at ? ' · Released: ' + c.released_at : ''}</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full text-xs font-medium ${statusColor[c.status] || 'bg-slate-100'}">${statusLabel[c.status] || c.status}</span>
          ${c.pdf_path ? `<a href="print_certificate.php?request_id=${c.request_id}" target="_blank" class="text-primary text-xs font-medium hover:underline">Print</a>` : ''}
        </div>
      </div>
    `).join('');
  }

  document.getElementById('historyModal').classList.remove('hidden');
}

document.getElementById('closeHistoryBtn').addEventListener('click', () => {
  document.getElementById('historyModal').classList.add('hidden');
});

document.getElementById('searchInput').addEventListener('input', (e) => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadResidents(e.target.value), 300);
});

loadResidents();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
