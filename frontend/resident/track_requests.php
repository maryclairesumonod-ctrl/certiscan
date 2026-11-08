<?php
require_once __DIR__ . '/../../api/resident_auth_guard.php';
$pageTitle = 'My Requests';
require __DIR__ . '/includes/header.php';
?>
<p class="cs-eyebrow mb-1">Status Tracking</p>
<h1 class="text-2xl font-bold text-slate-900 mb-1">My requests</h1>
<p class="text-slate-500 text-sm mb-6">Track the status of every certificate you've requested. You can edit a request while it's still Pending.</p>

<div id="requestList" class="space-y-3">
  <p class="text-slate-400 text-sm">Loading...</p>
</div>

<!-- Edit modal -->
<div id="editModal" class="hidden fixed inset-0 bg-slate-900/40 flex items-center justify-center p-4 z-20">
  <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Edit request</h2>
      <button id="closeModalBtn" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <p id="editCertName" class="text-sm text-slate-500"></p>
    <div id="editFields" class="space-y-3"></div>
    <p id="editError" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
    <p id="editSuccess" class="text-sm text-green-600 hidden bg-green-50 border border-green-100 rounded-lg p-3"></p>
    <button id="saveEditBtn" class="w-full bg-primary text-white rounded-lg py-2.5 font-medium hover:bg-primary-dark transition disabled:opacity-50">
      Save Changes
    </button>
  </div>
</div>

<script>
const statusStyle = {
  pending: 'badge-pending',
  under_review: 'badge-under_review',
  approved: 'badge-approved',
  rejected: 'badge-rejected',
  ready_for_pickup: 'badge-ready_for_pickup',
  released: 'badge-released',
};
const statusLabel = {
  pending: 'Pending',
  under_review: 'Under Review',
  approved: 'Approved',
  rejected: 'Rejected',
  ready_for_pickup: 'Ready for Pickup',
  released: 'Released',
};

function renderRequestCard(r) {
  return `
    <div data-request-id="${r.request_id}" class="bg-[#252C3A] border border-[#353F50] rounded-xl p-4 flex items-center justify-between transition-all">
      <div>
        <p class="font-semibold text-[#F8FAFC]">${escapeHtml(r.certificate_type)}</p>
        <p class="text-xs text-[#9CA3AF] font-mono mt-0.5">${escapeHtml(r.cert_number)}</p>
        <div class="rejection-container mt-1">
          ${r.status === 'rejected' && r.rejection_reason ? `<p class="text-xs text-red-400">Reason: ${escapeHtml(r.rejection_reason)}</p>` : ''}
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span class="status-badge cs-badge px-3 py-1 rounded-full text-xs font-semibold ${statusStyle[r.status] || 'bg-[#353F50] text-[#F8FAFC]'}">
          ${statusLabel[r.status] || r.status}
        </span>
        <div class="edit-btn-container">
          ${r.status === 'pending' ? `<button data-id="${r.request_id}" class="editBtn bg-[#161B24] border border-[#353F50] text-[#D99A2B] hover:text-[#E6B84A] text-xs px-3 py-1.5 rounded-lg hover:bg-[#2A3242] transition font-medium">Edit</button>` : ''}
        </div>
      </div>
    </div>
  `;
}

function bindEditButtons() {
  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.onclick = () => openEditModal(btn.dataset.id);
  });
}

function updateRequestsUI(requests) {
  const container = document.getElementById('requestList');
  if (!container) return;

  if (!requests || requests.length === 0) {
    container.innerHTML = '<p class="text-[#9CA3AF] text-sm">You have not requested any certificates yet.</p>';
    return;
  }

  const existingCards = container.querySelectorAll('[data-request-id]');
  if (existingCards.length === 0) {
    container.innerHTML = requests.map(r => renderRequestCard(r)).join('');
    bindEditButtons();
    return;
  }

  requests.forEach(r => {
    let card = container.querySelector(`[data-request-id="${r.request_id}"]`);
    if (!card) {
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = renderRequestCard(r);
      container.prepend(tempDiv.firstElementChild);
      bindEditButtons();
    } else {
      const badge = card.querySelector('.status-badge');
      if (badge) {
        const expectedText = statusLabel[r.status] || r.status;
        if (badge.textContent.trim() !== expectedText) {
          badge.textContent = expectedText;
          badge.className = `status-badge cs-badge px-3 py-1 rounded-full text-xs font-semibold ${statusStyle[r.status] || 'bg-[#353F50] text-[#F8FAFC]'}`;
        }
      }
      const rejectionBox = card.querySelector('.rejection-container');
      if (rejectionBox) {
        const expectedReasonHtml = (r.status === 'rejected' && r.rejection_reason) ? `<p class="text-xs text-red-400">Reason: ${escapeHtml(r.rejection_reason)}</p>` : '';
        if (rejectionBox.innerHTML.trim() !== expectedReasonHtml.trim()) {
          rejectionBox.innerHTML = expectedReasonHtml;
        }
      }
      const editBox = card.querySelector('.edit-btn-container');
      if (editBox) {
        const expectedEditHtml = r.status === 'pending' ? `<button data-id="${r.request_id}" class="editBtn bg-[#161B24] border border-[#353F50] text-[#D99A2B] hover:text-[#E6B84A] text-xs px-3 py-1.5 rounded-lg hover:bg-[#2A3242] transition font-medium">Edit</button>` : '';
        if (editBox.innerHTML.trim() !== expectedEditHtml.trim()) {
          editBox.innerHTML = expectedEditHtml;
          bindEditButtons();
        }
      }
    }
  });
}

async function loadRequests() {
  const res = await fetch('../../api/get_my_requests.php');
  const data = await res.json();
  if (data.requests) {
    updateRequestsUI(data.requests);
  }
}

window.addEventListener('certiscan:updatesReceived', (e) => {
  if (e.detail && e.detail.requests) {
    updateRequestsUI(e.detail.requests);
  }
});

// ---------- Edit modal ----------
const editModal = document.getElementById('editModal');
const editFields = document.getElementById('editFields');
const editCertName = document.getElementById('editCertName');
const editError = document.getElementById('editError');
const editSuccess = document.getElementById('editSuccess');
const saveEditBtn = document.getElementById('saveEditBtn');
let currentEditRequestId = null;

function formatLabel(key) {
  const cleanKey = String(key).replace(/[\$\{\}]/g, '').trim();
  return cleanKey.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

async function openEditModal(requestId) {
  editError.classList.add('hidden');
  editSuccess.classList.add('hidden');
  currentEditRequestId = requestId;

  const res = await fetch('../../api/get_request_detail.php?request_id=' + requestId);
  const data = await res.json();

  if (!data.success) {
    alert(data.message || 'Could not load this request.');
    return;
  }

  const request = data.request;
  if (request.status !== 'pending') {
    alert('This request can no longer be edited — it is already being processed.');
    return;
  }

  editCertName.textContent = request.certificate_type;

    const baseFields = [
      'full_name', 'resident_name', 'address', 'resident_address', 'birthdate',
      'civil_status', 'place_of_birth', 'cert_number', 'certificate_number',
      'cert_day', 'cert_month', 'cert_year', 'date', 'issued_date',
      'certificate_subtitle', 'amount_paid', 'age'
    ];
  const rawKeys = (request.extra_fields && request.extra_fields.length) ? request.extra_fields
    : Object.keys(request.final_data || {});
  const editableKeys = rawKeys.filter(k => {
    const clean = String(k).replace(/[\$\{\}]/g, '').trim().toLowerCase();
    return !baseFields.includes(clean) && !clean.startsWith('official_') && clean !== '';
  });

  editFields.innerHTML = editableKeys.length ? editableKeys.map(key => `
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">${formatLabel(key)}</label>
      <input type="text" data-edit-field="${key}" value="${(request.final_data && request.final_data[key]) || ''}"
             class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
  `).join('') : '<p class="text-sm text-slate-400">No certificate-specific fields to edit.</p>';

editModal.classList.remove('hidden');
}

document.getElementById('closeModalBtn').addEventListener('click', () => {
  editModal.classList.add('hidden');
});

saveEditBtn.addEventListener('click', async () => {
  editError.classList.add('hidden');
  editSuccess.classList.add('hidden');

  const extraValues = {};
  document.querySelectorAll('[data-edit-field]').forEach(input => {
    extraValues[input.dataset.editField] = input.value;
  });

  saveEditBtn.disabled = true;
  saveEditBtn.textContent = 'Saving...';

  const res = await fetch('../../api/edit_request.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ request_id: currentEditRequestId, extra_values: extraValues }),
  });
  const data = await res.json();

  if (data.success) {
    editSuccess.textContent = 'Saved!';
    editSuccess.classList.remove('hidden');
    setTimeout(() => {
      editModal.classList.add('hidden');
      loadRequests();
    }, 800);
  } else {
    editError.textContent = data.message || 'Something went wrong';
    editError.classList.remove('hidden');
  }
  saveEditBtn.disabled = false;
  saveEditBtn.textContent = 'Save Changes';
});

loadRequests();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
