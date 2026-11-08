<?php
require_once __DIR__ . '/../api/auth_guard.php';
$pageTitle = 'Manage Requests';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
  <p class="cs-eyebrow mb-1">Processing Queue</p>
  <h1 class="text-2xl font-bold text-slate-900">Manage requests</h1>
  <p class="text-slate-500 text-sm mt-1">
    Review and act on certificate requests submitted by residents.
  </p>
</div>

<div class="flex gap-2 mb-4 flex-wrap">

  <button
    data-status=""
    class="filterBtn bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium">
    All
  </button>

  <button
    data-status="pending"
    class="filterBtn bg-white border border-blue-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50">
    Pending
  </button>

  <button
    data-status="under_review"
    class="filterBtn bg-white border border-blue-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50">
    Under Review
  </button>

  <button
    data-status="approved"
    class="filterBtn bg-white border border-blue-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50">
    Approved
  </button>

</div>


<div id="requestList" class="space-y-3"></div>


<!-- EDIT MODAL -->

<div
  id="editModal"
  class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">

  <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">

    <div class="flex items-center justify-between">

      <h2 class="text-lg font-semibold text-slate-900">
        Edit request details
      </h2>

      <button
        id="closeModalBtn"
        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
        &times;
      </button>

    </div>

    <p id="editCertName" class="text-sm text-slate-500"></p>

    <div id="editFields" class="space-y-3"></div>

    <p
      id="editError"
      class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3">
    </p>

    <p
      id="editSuccess"
      class="text-sm text-green-600 hidden bg-green-50 border border-green-100 rounded-lg p-3">
    </p>

    <button
      id="saveEditBtn"
      class="w-full bg-primary text-white rounded-lg py-2.5 font-medium hover:bg-primary-dark transition disabled:opacity-50">
      Save Changes
    </button>

  </div>

</div>


<!-- REJECT MODAL -->

<div
  id="rejectModal"
  class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">

  <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">

    <div class="flex items-center justify-between">

      <div class="flex items-center gap-2.5">

        <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">

          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="text-red-500"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            style="width:18px;height:18px;">

            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v3.75m0 3.75h.007v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>

          </svg>

        </div>

        <h2 class="text-lg font-semibold text-slate-900">
          Reject request
        </h2>

      </div>

      <button
        id="closeRejectModalBtn"
        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
        &times;
      </button>

    </div>

    <p id="rejectCertName" class="text-sm text-slate-500"></p>

    <div>

      <label class="block text-sm font-medium text-slate-700 mb-1.5">
        Reason for rejection
        <span class="text-red-500">*</span>
      </label>

      <textarea
        id="rejectReasonInput"
        rows="3"
        placeholder="e.g. Submitted ID photo is unreadable, please resubmit."
        class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-transparent transition resize-none"></textarea>

      <p
        id="rejectReasonError"
        class="text-xs text-red-600 mt-1.5 hidden">
        Please enter a reason before rejecting.
      </p>

    </div>

    <div class="flex gap-2">

      <button
        id="cancelRejectBtn"
        class="flex-1 bg-white border border-slate-200 text-slate-700 rounded-lg py-2.5 text-sm font-medium hover:bg-slate-50 transition">
        Cancel
      </button>

      <button
        id="confirmRejectBtn"
        class="flex-1 bg-white-400 text-red-600 rounded-lg py-2.5 text-sm font-medium hover:bg-red-700 transition disabled:opacity-50">
        Confirm Rejection
      </button>

    </div>

  </div>

</div>


<style>

.request-card-row {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  width: 100%;
}

.request-info {
  min-width: 0;
  flex: 1 1 auto;
}

.request-title {
  display: block;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.request-actions {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
  white-space: nowrap;
}

.request-actions button,
.request-actions a,
.request-actions span {
  flex-shrink: 0;
  white-space: nowrap;
}

.status-dropdown-wrap {
  position: relative;
  display: inline-block;
  flex-shrink: 0;
}

.statusDropdownBtn {
  position: relative;
  min-width: 168px;
  height: 34px;
  justify-content: space-between;
  border: 1px solid transparent;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.statusDropdownBtn:hover {
  box-shadow: 0 3px 8px rgba(15, 23, 42, 0.12);
}

.statusDropdownBtn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15), 0 2px 6px rgba(15, 23, 42, 0.10);
}

.statusDropdownBtn[aria-expanded="true"] {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15), 0 3px 8px rgba(15, 23, 42, 0.12);
}

.statusDropdownBtn[aria-expanded="true"] .chevron-icon {
  transform: rotate(180deg);
}

.chevron-icon {
  transition: transform 0.18s ease;
}

.status-dropdown-menu {
  position: absolute;
  top: calc(100% + 7px);
  right: 0;
  width: 230px;
  max-width: calc(100vw - 32px);
  background: #252C3A;
  border: 1px solid #353F50;
  border-radius: 14px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.40);
  z-index: 100;
  overflow: hidden;
  animation: dropdownIn 0.16s ease;
  transform-origin: top right;
  color: #F8FAFC;
}

.status-dropdown-menu::before {
  content: "";
  position: absolute;
  top: -5px;
  right: 20px;
  width: 10px;
  height: 10px;
  background: #252C3A;
  border-left: 1px solid #353F50;
  border-top: 1px solid #353F50;
  transform: rotate(45deg);
}

@keyframes dropdownIn {
  from { opacity: 0; transform: translateY(-6px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.status-dropdown-heading {
  position: relative;
  z-index: 1;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #B6BEC9;
  padding: 0.75rem 0.9rem 0.3rem;
  background: #252C3A;
}

.status-dropdown-subheading {
  position: relative;
  z-index: 1;
  font-size: 0.68rem;
  color: #B6BEC9;
  padding: 0 0.9rem 0.5rem;
  line-height: 1.4;
}

.status-dropdown-item {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  min-height: 43px;
  padding: 0.6rem 0.9rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: #F8FAFC;
  cursor: pointer;
  transition: background 0.12s ease, color 0.12s ease, padding-left 0.12s ease;
}

.status-dropdown-item:hover {
  background: #303A4D;
  color: #F8FAFC;
  padding-left: 1rem;
}

.status-dropdown-item.current {
  background: #232937;
  color: #D99A2B;
  font-weight: 600;
  cursor: default;
}

.status-dropdown-item.current:hover {
  background: #232937;
  color: #D99A2B;
  padding-left: 0.9rem;
}

.status-dropdown-item.locked {
  color: #4B5563;
  cursor: not-allowed;
  pointer-events: none;
}

.status-dropdown-item.locked .status-dot {
  opacity: 0.35;
}

.status-dropdown-item.next-available {
  color: #F8FAFC;
}

.status-dropdown-item.next-available::after {
  content: "Next";
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #D99A2B;
  background: #232937;
  padding: 0.15rem 0.4rem;
  border-radius: 999px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(53, 63, 80, 0.50);
}

.status-dot.under_review {
  background: #7C3AED;
}

.status-dot.approved {
  background: #1F7A3D;
}

.status-dot.ready_for_pickup {
  background: #7C3AED;
}

.status-dot.released {
  background: #2563EB;
}

@media (max-width: 900px) {
  .request-card-row { align-items: flex-start; }
  .request-info { min-width: 180px; }
  .request-actions { gap: 0.35rem; }
  .request-actions button,
  .request-actions a { padding-left: 0.55rem !important; padding-right: 0.55rem !important; }
  .statusDropdownBtn { min-width: 135px; }
}

@media (max-width: 700px) {
  .request-card-container { overflow-x: auto; }
  .request-card-row { min-width: 760px; }
}

@media (max-width: 640px) {
  .status-dropdown-menu {
    position: fixed;
    top: auto;
    right: 16px;
    left: 16px;
    width: auto;
    max-width: none;
    border-radius: 14px;
  }
  .status-dropdown-menu::before { display: none; }
}

</style>


<script>

const statusLabel = {
  pending: 'Pending',
  under_review: 'Under Review',
  approved: 'Approved',
  rejected: 'Rejected',
  ready_for_pickup: 'Ready for Pickup',
  released: 'Released'
};

const statusColor = {
  pending: 'badge-pending',
  under_review: 'badge-under_review',
  approved: 'badge-approved',
  rejected: 'badge-rejected',
  ready_for_pickup: 'badge-ready_for_pickup',
  released: 'badge-released'
};

const fullPipelineOrder = ['pending', 'under_review', 'approved', 'ready_for_pickup', 'released'];

const pipelineStages = [
  { value: 'under_review', label: 'Under Review' },
  { value: 'approved', label: 'Approved' },
  { value: 'ready_for_pickup', label: 'Ready for Pickup' },
  { value: 'released', label: 'Released' }
];

const queuePriority = {
  pending: 0,
  under_review: 1,
  approved: 2,
  ready_for_pickup: 3,
  released: 4,
  rejected: 5
};

const baseFieldsToSkip = [
  'full_name', 'resident_name', 'address', 'resident_address', 'birthdate',
  'civil_status', 'place_of_birth', 'cert_number', 'certificate_number',
  'cert_day', 'cert_month', 'cert_year', 'date', 'issued_date',
  'certificate_subtitle', 'amount_paid', 'age'
];

function shouldSkipDetailField(key) {
  const clean = String(key).replace(/[\$\{\}]/g, '').toLowerCase().trim();
  if (baseFieldsToSkip.includes(clean)) return true;
  if (clean.startsWith('official_')) return true;
  return false;
}

function formatFieldLabel(key) {
  const clean = String(key).replace(/[\$\{\}]/g, '').trim();
  return clean.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function getNextStage(currentStatus) {
  const index = fullPipelineOrder.indexOf(currentStatus);
  if (index === -1 || index >= fullPipelineOrder.length - 1) return null;
  return fullPipelineOrder[index + 1];
}

function renderDetails(finalData) {
  if (!finalData || typeof finalData !== 'object') {
    return `<p class="text-xs text-slate-400">No additional details submitted.</p>`;
  }

  const cleanVal = v => {
    if (v === null || v === undefined) return '—';
    const s = String(v).replace(/^\$\{([a-zA-Z0-9_]+)\}$/, '$1');
    return escapeHtml(s);
  };

  const rows = Object.entries(finalData)
    .filter(([key, value]) => !shouldSkipDetailField(key) && value !== '' && value !== null)
    .map(([key, value]) => `
      <div class="flex justify-between gap-4 py-1 border-b border-slate-100 last:border-0">
        <span class="text-slate-500">${formatFieldLabel(key)}</span>
        <span class="text-slate-800 font-medium text-right">${cleanVal(value)}</span>
      </div>
    `).join('');

  const baseRows = `
    <div class="flex justify-between gap-4 py-1 border-b border-slate-100">
      <span class="text-slate-500">Address on file</span>
      <span class="text-slate-800 font-medium text-right">${cleanVal(finalData.address)}</span>
    </div>
    <div class="flex justify-between gap-4 py-1 border-b border-slate-100">
      <span class="text-slate-500">Birthdate</span>
      <span class="text-slate-800 font-medium text-right">${cleanVal(finalData.birthdate)}</span>
    </div>
    <div class="flex justify-between gap-4 py-1 border-b border-slate-100">
      <span class="text-slate-500">Civil status</span>
      <span class="text-slate-800 font-medium text-right">${cleanVal(finalData.civil_status)}</span>
    </div>
  `;

  return baseRows + (rows || `<p class="text-xs text-slate-400 mt-2">No certificate-specific details submitted.</p>`);
}

let allRequests = [];
let requestInFlight = false;
let currentFilter = '';
let openDetailPanels = new Set(); // tracks open panels by request_id, survives re-renders

async function loadRequests(status = '') {
  try {
    const res = await fetch('../api/secretary_list_requests.php?status=' + encodeURIComponent(status));
    const data = await res.json();
    allRequests = data.requests || [];

    allRequests = allRequests
      .map((request, originalIndex) => ({ request, originalIndex }))
      .sort((a, b) => {
        const priorityA = queuePriority[a.request.status] ?? 99;
        const priorityB = queuePriority[b.request.status] ?? 99;
        if (priorityA !== priorityB) return priorityA - priorityB;
        return a.originalIndex - b.originalIndex;
      })
      .map(item => item.request);

    const container = document.getElementById('requestList');

    if (allRequests.length === 0) {
      container.innerHTML = `
        <div class="bg-white border border-blue-100 rounded-xl p-8 text-center">
          <p class="text-slate-400 text-sm">No requests found.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = allRequests.map((r, i) => {
      const nextStage = getNextStage(r.status);
      const isPanelOpen = openDetailPanels.has(String(r.request_id));

      return `
        <div class="request-card-container bg-white border border-blue-100 rounded-xl overflow-visible">
          <div class="request-card-row p-4">
            <div class="request-info">
              <p class="request-title font-medium text-slate-900" title="${r.full_name} — ${r.certificate_type}">
                ${r.full_name} — ${r.certificate_type}
              </p>
              <p class="text-xs text-slate-400 font-mono mt-0.5 truncate" title="${r.cert_number}">
                ${r.cert_number} · ${r.initiated_by === 'resident' ? 'Online request' : 'Walk-in'}
              </p>
              ${r.status === 'rejected' && r.rejection_reason ? `
                <p class="text-xs text-red-500 mt-1 truncate" title="Rejected: ${r.rejection_reason}">
                  Rejected: ${r.rejection_reason}
                </p>
              ` : ''}
            </div>

            <div class="request-actions">
              <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColor[r.status] || 'bg-slate-100 text-slate-600'}">
                ${statusLabel[r.status] || r.status}
              </span>

              <button data-toggle="${r.request_id}" class="toggleBtn bg-white border border-blue-200 text-slate-600 text-xs px-3 py-1.5 rounded-lg hover:bg-blue-50 transition">
                ${isPanelOpen ? 'Hide Details' : 'View Details'}
              </button>

              ${r.pdf_path
                ? `<a href="print_certificate.php?request_id=${r.request_id}" class="bg-white border border-green-200 text-green-700 text-xs px-3 py-1.5 rounded-lg hover:bg-green-50 transition">Print</a>`
                : (['approved','ready_for_pickup','released'].includes(r.status)
                    ? `<span title="No Word template is registered for this certificate type yet." class="text-[11px] text-amber-600 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg">No certificate file</span>`
                    : '')
              }

              ${r.status !== 'released' ? `
                <button data-index="${i}" class="editBtn bg-white border border-blue-200 text-primary text-xs px-3 py-1.5 rounded-lg hover:bg-blue-50 transition">
                  Edit
                </button>
              ` : ''}

              ${r.status !== 'rejected' ? `
                <div class="status-dropdown-wrap">
                  <button type="button" data-id="${r.request_id}" data-current="${r.status}" aria-expanded="false" aria-haspopup="true"
                    class="statusDropdownBtn bg-primary text-white text-xs px-3 py-1.5 rounded-lg hover:bg-primary-dark transition flex items-center gap-2">
                    <span class="flex items-center gap-1.5 min-w-0">
                      <span class="w-1.5 h-1.5 rounded-full bg-white/80 shrink-0"></span>
                      <span class="truncate">${statusLabel[r.status] === 'Pending' ? 'Set Status' : statusLabel[r.status]}</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 chevron-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>
                  <div class="status-dropdown-menu hidden" data-menu-for="${r.request_id}">
                    <p class="status-dropdown-heading">Move to stage</p>
                    <p class="status-dropdown-subheading">Requests move forward one step at a time.</p>
                    ${pipelineStages.map(stage => {
                      const isCurrent = stage.value === r.status;
                      const isNext = stage.value === nextStage;
                      const itemClass = isCurrent ? 'current' : (isNext ? 'next-available' : 'locked');
                      return `
                        <div role="button" tabindex="${isNext ? 0 : -1}" class="status-dropdown-item ${itemClass}"
                          ${isNext ? `data-set-status="${stage.value}" data-request-id="${r.request_id}"` : ''}>
                          <span class="flex items-center gap-3">
                            <span class="status-dot ${stage.value}"></span>
                            <span>${stage.label}</span>
                          </span>
                          ${isCurrent ? `
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                          ` : ''}
                        </div>
                      `;
                    }).join('')}
                  </div>
                </div>
              ` : ''}

              ${r.status === 'pending' || r.status === 'under_review' ? `
                <button data-id="${r.request_id}" data-cert="${(r.full_name + ' — ' + r.certificate_type).replace(/"/g, '&quot;')}"
                  class="rejectBtn bg-white border border-red-200 text-red-600 text-xs px-3 py-1.5 rounded-lg hover:bg-red-50 transition">
                  Reject
                </button>
              ` : ''}
            </div>
          </div>

          <div id="details-${r.request_id}" class="${isPanelOpen ? '' : 'hidden'} bg-slate-50 border-t border-blue-100 px-4 py-3 text-sm space-y-3">
            ${renderDetails(r.final_data)}
          
          </div>
        </div>
      `;
    }).join('');

    document.querySelectorAll('.toggleBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        const requestId = btn.dataset.toggle;
        const panel = document.getElementById('details-' + requestId);
        const isHidden = panel.classList.contains('hidden');
        panel.classList.toggle('hidden');
        btn.textContent = isHidden ? 'Hide Details' : 'View Details';

        if (isHidden) {
          openDetailPanels.add(requestId);
        } else {
          openDetailPanels.delete(requestId);
        }
      });
    });

    document.querySelectorAll('.statusDropdownBtn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        const menu = document.querySelector(`.status-dropdown-menu[data-menu-for="${btn.dataset.id}"]`);
        document.querySelectorAll('.status-dropdown-menu').forEach(otherMenu => {
          if (otherMenu !== menu) {
            otherMenu.classList.add('hidden');
            const otherButton = otherMenu.closest('.status-dropdown-wrap')?.querySelector('.statusDropdownBtn');
            if (otherButton) otherButton.setAttribute('aria-expanded', 'false');
          }
        });
        const willOpen = menu.classList.contains('hidden');
        menu.classList.toggle('hidden', !willOpen);
        btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });

    document.querySelectorAll('.status-dropdown-item[data-set-status]').forEach(item => {
      item.addEventListener('click', e => {
        e.stopPropagation();
        if (requestInFlight) return;
        const requestId = item.dataset.requestId;
        const newStatus = item.dataset.setStatus;
        const menu = item.closest('.status-dropdown-menu');
        if (menu) {
          menu.classList.add('hidden');
          const button = menu.closest('.status-dropdown-wrap')?.querySelector('.statusDropdownBtn');
          if (button) button.setAttribute('aria-expanded', 'false');
        }
        updateStatus(requestId, newStatus);
      });

      item.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          item.click();
        }
      });
    });

    document.querySelectorAll('.rejectBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        openRejectModal(btn.dataset.id, btn.dataset.cert);
      });
    });

    document.querySelectorAll('.editBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        openEditModal(allRequests[btn.dataset.index]);
      });
    });

  } catch (error) {
    console.error(error);
    document.getElementById('requestList').innerHTML = `
      <div class="bg-red-50 border border-red-100 rounded-xl p-4">
        <p class="text-sm text-red-600">Unable to load requests. Please try again.</p>
      </div>
    `;
  }
}

document.addEventListener('click', () => {
  document.querySelectorAll('.status-dropdown-menu').forEach(menu => menu.classList.add('hidden'));
  document.querySelectorAll('.statusDropdownBtn').forEach(button => button.setAttribute('aria-expanded', 'false'));
});

async function updateStatus(requestId, status, rejectionReason = '') {
  if (requestInFlight) return;
  requestInFlight = true;

  try {
    const response = await fetch('../api/secretary_update_request_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ request_id: requestId, status: status, rejection_reason: rejectionReason })
    });

    const data = await response.json();

    if (data.success === false) {
      alert(data.message || 'Unable to update request status.');
      return;
    }

    await loadRequests(currentFilter);
  } catch (error) {
    console.error(error);
    alert('Something went wrong while updating the request.');
  } finally {
    requestInFlight = false;
  }
}

const editModal = document.getElementById('editModal');
const editFields = document.getElementById('editFields');
const editCertName = document.getElementById('editCertName');
const editError = document.getElementById('editError');
const editSuccess = document.getElementById('editSuccess');
const saveEditBtn = document.getElementById('saveEditBtn');
let currentEditRequestId = null;

function openEditModal(request) {
  editError.classList.add('hidden');
  editSuccess.classList.add('hidden');
  currentEditRequestId = request.request_id;
  editCertName.textContent = `${request.full_name} — ${request.certificate_type}`;

  const finalData = request.final_data || {};
  const editableKeys = Object.keys(finalData).filter(k => !shouldSkipDetailField(k));

  editFields.innerHTML = editableKeys.length
    ? editableKeys.map(key => `
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">${formatFieldLabel(key)}</label>
          <input type="text" data-edit-field="${key}" value="${(finalData[key] || '').toString().replace(/"/g, '&quot;')}"
                 class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
      `).join('')
    : `<p class="text-sm text-slate-400">No certificate-specific fields to edit.</p>`;

  editModal.classList.remove('hidden');
}

document.getElementById('closeModalBtn').addEventListener('click', () => {
  editModal.classList.add('hidden');
});

editModal.addEventListener('click', e => {
  if (e.target === editModal) editModal.classList.add('hidden');
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

  try {
    const res = await fetch('../api/secretary_edit_request.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ request_id: currentEditRequestId, extra_values: extraValues })
    });
    const data = await res.json();

    if (data.success) {
      editSuccess.textContent = 'Changes saved successfully!';
      editSuccess.classList.remove('hidden');
      setTimeout(() => {
        editModal.classList.add('hidden');
        loadRequests(currentFilter);
      }, 800);
    } else {
      editError.textContent = data.message || 'Something went wrong.';
      editError.classList.remove('hidden');
    }
  } catch (error) {
    console.error(error);
    editError.textContent = 'Unable to save changes. Please try again.';
    editError.classList.remove('hidden');
  }

  saveEditBtn.disabled = false;
  saveEditBtn.textContent = 'Save Changes';
});

const rejectModal = document.getElementById('rejectModal');
const rejectCertName = document.getElementById('rejectCertName');
const rejectReasonInput = document.getElementById('rejectReasonInput');
const rejectReasonError = document.getElementById('rejectReasonError');
const confirmRejectBtn = document.getElementById('confirmRejectBtn');
let currentRejectRequestId = null;

function openRejectModal(requestId, certLabel) {
  currentRejectRequestId = requestId;
  rejectCertName.textContent = certLabel || '';
  rejectReasonInput.value = '';
  rejectReasonError.classList.add('hidden');
  confirmRejectBtn.disabled = false;
  confirmRejectBtn.textContent = 'Confirm Rejection';
  rejectModal.classList.remove('hidden');
  rejectReasonInput.focus();
}

function closeRejectModal() {
  rejectModal.classList.add('hidden');
  currentRejectRequestId = null;
}

document.getElementById('closeRejectModalBtn').addEventListener('click', closeRejectModal);
document.getElementById('cancelRejectBtn').addEventListener('click', closeRejectModal);

rejectModal.addEventListener('click', e => {
  if (e.target === rejectModal) closeRejectModal();
});

confirmRejectBtn.addEventListener('click', async () => {
  const reason = rejectReasonInput.value.trim();

  if (!reason) {
    rejectReasonError.classList.remove('hidden');
    rejectReasonInput.focus();
    return;
  }

  if (requestInFlight) return;

  confirmRejectBtn.disabled = true;
  confirmRejectBtn.textContent = 'Rejecting...';

  await updateStatus(currentRejectRequestId, 'rejected', reason);
  closeRejectModal();
});

document.querySelectorAll('.filterBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    currentFilter = btn.dataset.status;
    document.querySelectorAll('.filterBtn').forEach(b => {
      b.className = 'filterBtn bg-white border border-blue-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50';
    });
    btn.className = 'filterBtn bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium';
    loadRequests(currentFilter);
  });
});

// Auto-refresh to catch new incoming requests, without disrupting an
// in-progress action (an open dropdown, or either modal visible).
function isSecretaryMidAction() {
  const dropdownOpen = !!document.querySelector('.status-dropdown-menu:not(.hidden)');
  const editModalOpen = !editModal.classList.contains('hidden');
  const rejectModalOpen = !rejectModal.classList.contains('hidden');
  return dropdownOpen || editModalOpen || rejectModalOpen;
}

setInterval(() => {
  if (!isSecretaryMidAction()) {
    loadRequests(currentFilter);
  }
}, 5000);



loadRequests();

</script>

<?php require __DIR__ . '/includes/footer.php'; ?>