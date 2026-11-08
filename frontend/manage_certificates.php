<?php
require_once __DIR__ . '/../api/auth_guard.php';

// Secretary / Admin authorization guard
if (empty($_SESSION['role']) || ($_SESSION['role'] !== 'secretary' && $_SESSION['role'] !== 'admin')) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Certificate Management';
require __DIR__ . '/includes/header.php';
?>

<style>
  /* ============================================================ */
  /* SOLID CERTIFICATE MODAL STYLES                               */
  /* ============================================================ */

  /* Edit/Add certificate modal backdrop overlay */
  .edit-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: clamp(16px, 3vh, 32px) 16px;
    background: rgba(5, 10, 20, 0.85);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }

  .edit-modal-backdrop.hidden {
    display: none !important;
  }

  /* Main edit/add modal container */
  .edit-modal {
    position: relative;
    width: min(100%, 880px);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    background: #111c2e !important;
    color: #f1f5f9;
    border: 1px solid #334155;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.65);
    overflow: hidden;
    margin: auto;
    opacity: 1 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
  }

  /* Modal heading (sticky top) */
  .edit-modal-header {
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 10;
    background: #111c2e !important;
    padding: 18px 24px;
    border-bottom: 1px solid #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  /* Scrollable form body content */
  .edit-modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 20px 24px;
    background: #111c2e !important;
  }

  /* Modal footer (sticky bottom) */
  .edit-modal-footer {
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    z-index: 10;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    padding: 14px 24px;
    background: #111c2e !important;
    border-top: 1px solid #334155;
  }

  /* Form section containers */
  .edit-modal-section {
    background: #172235;
    border: 1px solid #2d3b50;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 18px;
  }

  .edit-modal-section:last-child {
    margin-bottom: 0;
  }

  .edit-modal-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    margin-bottom: 14px;
    border-bottom: 1px solid #253347;
  }

  .edit-modal-badge-num {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: #f4b323;
    color: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .edit-modal-section-title {
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #ffffff;
  }

  .edit-modal-section-subtitle {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 1px;
  }

  /* Subcards inside sections */
  .edit-modal-subcard {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 10px;
    padding: 14px 16px;
  }

  /* Form controls */
  .edit-modal input[type="text"],
  .edit-modal input[type="number"],
  .edit-modal textarea,
  .edit-modal select {
    background: #1e293b !important;
    color: #f8fafc !important;
    border: 1px solid #475569 !important;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.875rem;
    width: 100%;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .edit-modal input::placeholder,
  .edit-modal textarea::placeholder {
    color: #94a3b8 !important;
    opacity: 1 !important;
  }

  .edit-modal input:focus,
  .edit-modal textarea:focus,
  .edit-modal select:focus,
  #searchInput:focus {
    border-color: #f4b323 !important;
    outline: none !important;
    box-shadow: 0 0 0 2px rgba(244, 179, 35, 0.3) !important;
  }

  /* Gold accent save button */
  .edit-modal button.primary,
  .edit-btn-save {
    background: #f4b323 !important;
    color: #111827 !important;
    font-weight: 700;
    font-size: 0.8125rem;
    padding: 9px 20px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.15s ease;
  }

  .edit-modal button.primary:hover,
  .edit-btn-save:hover {
    background: #f5bc3a !important;
  }

  .edit-modal button.primary:disabled,
  .edit-btn-save:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  /* Neutral gray cancel button */
  .edit-btn-cancel {
    background: #253347 !important;
    color: #e2e8f0 !important;
    font-weight: 600;
    font-size: 0.8125rem;
    padding: 9px 18px;
    border-radius: 10px;
    border: 1px solid #3d4f68 !important;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
  }

  .edit-btn-cancel:hover {
    background: #2e3e57 !important;
    color: #ffffff !important;
  }

  /* Close X button in header */
  .edit-modal-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    background: #1e293b;
    border: 1px solid #334155;
    cursor: pointer;
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1;
    transition: all 0.15s ease;
  }

  .edit-modal-close-btn:hover {
    color: #ffffff;
    background: #28374d;
    border-color: #475569;
  }

  /* Solid custom toggle switch */
  .cs-toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    vertical-align: middle;
  }
  .cs-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
  }
  .cs-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #1e293b;
    border: 1px solid #475569;
    transition: all 0.2s ease-in-out;
    border-radius: 9999px;
  }
  .cs-toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: #cbd5e1;
    transition: all 0.2s ease-in-out;
    border-radius: 50%;
  }
  .cs-toggle-switch input:checked + .cs-toggle-slider {
    background-color: #f4b323;
    border-color: #f5bc3a;
  }
  .cs-toggle-switch input:checked + .cs-toggle-slider:before {
    transform: translateX(20px);
    background-color: #111827;
  }
  .cs-toggle-switch input:focus + .cs-toggle-slider {
    box-shadow: 0 0 0 2px rgba(244, 179, 35, 0.4);
  }

  /* Solid modal scrollbar */
  .custom-modal-scroll::-webkit-scrollbar {
    width: 8px;
  }
  .custom-modal-scroll::-webkit-scrollbar-track {
    background: #111c2e;
  }
  .custom-modal-scroll::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
  }
  .custom-modal-scroll::-webkit-scrollbar-thumb:hover {
    background: #f4b323;
  }

  /* Standard checkbox accent */
  input[type="checkbox"] {
    accent-color: #f4b323;
  }
</style>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
  <div>
    <p class="cs-eyebrow mb-1">Administrative Control</p>
    <h1 class="text-2xl font-bold text-white">Certificate Types Management</h1>
    <p class="text-[#94A3B8] text-sm mt-1">
      Add, edit, activate, deactivate certificate types and configure Word (.docx) templates without touching code.
    </p>
  </div>
  <div>
    <button id="openAddModalBtn" class="cs-btn-primary font-bold cursor-pointer transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
      </svg>
      Add Certificate Type
    </button>
  </div>
</div>

<!-- Metrics summary -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="cs-metric" style="--m-color: #D99A2B;">
    <p class="text-xs uppercase tracking-wider font-semibold text-[#94A3B8]">Total Types</p>
    <p id="metricTotal" class="text-2xl font-bold text-white mt-1">...</p>
    <p class="text-xs text-[#94A3B8] mt-1">Configured certificates</p>
  </div>
  <div class="cs-metric" style="--m-color: #10B981;">
    <p class="text-xs uppercase tracking-wider font-semibold text-[#94A3B8]">Active</p>
    <p id="metricActive" class="text-2xl font-bold text-[#34D399] mt-1">...</p>
    <p class="text-xs text-[#94A3B8] mt-1">Ready for issuance</p>
  </div>
  <div class="cs-metric" style="--m-color: #3B82F6;">
    <p class="text-xs uppercase tracking-wider font-semibold text-[#94A3B8]">Online Requests</p>
    <p id="metricOnline" class="text-2xl font-bold text-[#60A5FA] mt-1">...</p>
    <p class="text-xs text-[#94A3B8] mt-1">Available in portal</p>
  </div>
  <div class="cs-metric" style="--m-color: #8B5CF6;">
    <p class="text-xs uppercase tracking-wider font-semibold text-[#94A3B8]">Templates Ready</p>
    <p id="metricTemplates" class="text-2xl font-bold text-[#C084FC] mt-1">...</p>
    <p class="text-xs text-[#94A3B8] mt-1">.docx on server</p>
  </div>
</div>

<!-- Search & Filters -->
<div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between mb-4">
  <div class="flex gap-2 flex-wrap">
    <button data-filter="all" class="filter-tab active-filter px-3.5 py-1.5 rounded-lg text-xs font-semibold transition bg-[#252C3A] text-white border border-[#D99A2B]">
      All
    </button>
    <button data-filter="active" class="filter-tab px-3.5 py-1.5 rounded-lg text-xs font-semibold transition bg-[#1E2533] text-[#A0AEC0] border border-[#353F50] hover:text-white hover:bg-[#2C3547]">
      Active Only
    </button>
    <button data-filter="inactive" class="filter-tab px-3.5 py-1.5 rounded-lg text-xs font-semibold transition bg-[#1E2533] text-[#A0AEC0] border border-[#353F50] hover:text-white hover:bg-[#2C3547]">
      Inactive Only
    </button>
    <button data-filter="online" class="filter-tab px-3.5 py-1.5 rounded-lg text-xs font-semibold transition bg-[#1E2533] text-[#A0AEC0] border border-[#353F50] hover:text-white hover:bg-[#2C3547]">
      Available Online
    </button>
  </div>
  <div class="relative min-w-[260px]">
    <input type="text" id="searchInput" placeholder="Search certificates..."
           class="w-full pl-9 pr-4 py-2 text-xs bg-[#1E2533] border border-[#353F50] rounded-xl text-white placeholder-[#8F9BAE] focus:outline-none focus:border-[#D99A2B]">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#8F9BAE] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <circle cx="11" cy="11" r="7" stroke-width="2"/>
      <path stroke-linecap="round" d="M21 21l-4.3-4.3" stroke-width="2"/>
    </svg>
  </div>
</div>

<!-- Certificate List Table -->
<div class="cs-card overflow-hidden bg-[#252C3A] border border-[#353F50]">
  <div class="overflow-x-auto">
    <table class="cs-table w-full text-left text-sm">
      <thead>
        <tr>
          <th>Certificate Name</th>
          <th>Template File</th>
          <th>Status</th>
          <th>Online Access</th>
          <th>Extra Fields</th>
          <th>Eligibility</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody id="certTableBody">
        <tr>
          <td colspan="7" class="px-4 py-8 text-center text-[#94A3B8]">Loading certificate types...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================================ -->
<!-- ADD / EDIT CERTIFICATE MODAL                                 -->
<!-- ============================================================ -->
<div id="certModal" class="edit-modal-backdrop hidden">
  <form id="certForm" class="edit-modal" autocomplete="off">
    <input type="hidden" id="modalCertId" name="certificate_type_id" value="">

    <!-- Modal Header (Sticky) -->
    <div class="edit-modal-header">
      <div>
        <h2 id="modalTitle" class="text-lg font-bold text-white flex items-center gap-2">
          Add Certificate Type
        </h2>
        <p class="text-xs text-[#94A3B8] mt-0.5">Configure certificate details, placeholders, and Word template.</p>
      </div>
      <button type="button" id="closeModalBtn" class="edit-modal-close-btn" title="Close modal">
        &times;
      </button>
    </div>

    <!-- Scrollable Modal Body -->
    <div class="edit-modal-body custom-modal-scroll" id="modalBody">
      
      <!-- SECTION 1: Certificate Details -->
      <div class="edit-modal-section">
        <div class="edit-modal-section-header">
          <span class="edit-modal-badge-num">1</span>
          <div>
            <h3 class="edit-modal-section-title">Certificate Details</h3>
            <p class="edit-modal-section-subtitle">Primary certificate title and official records naming.</p>
          </div>
        </div>

        <div class="space-y-4">
          <!-- Certificate Name -->
          <div>
            <label for="modalName" class="block text-xs font-semibold text-white mb-1.5">
              Certificate Name <span class="text-[#EF4444]">*</span>
            </label>
            <input type="text" id="modalName" name="name" required placeholder="e.g. Certificate of Good Moral Character, Barangay Clearance">
            <p class="text-[11px] text-[#94A3B8] mt-1">The official certificate title displayed in portals and printed records.</p>
          </div>
        </div>
      </div>

      <!-- SECTION 2: Status and Online Requests -->
      <div class="edit-modal-section">
        <div class="edit-modal-section-header">
          <span class="edit-modal-badge-num">2</span>
          <div>
            <h3 class="edit-modal-section-title">Status and Online Requests</h3>
            <p class="edit-modal-section-subtitle">Configure availability for staff issuing and resident online requests.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <!-- Active Status -->
          <div class="edit-modal-subcard flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-semibold text-white">Active Status</p>
              <p class="text-[11px] text-[#94A3B8] mt-0.5">Allow staff to issue in office</p>
            </div>
            <label class="cs-toggle-switch shrink-0" title="Toggle active status">
              <input type="checkbox" id="modalIsActive" name="is_active" value="1" checked>
              <span class="cs-toggle-slider"></span>
            </label>
          </div>

          <!-- Online Requests -->
          <div class="edit-modal-subcard flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-semibold text-white">Online Requests</p>
              <p class="text-[11px] text-[#94A3B8] mt-0.5">Visible to residents in portal</p>
            </div>
            <label class="cs-toggle-switch shrink-0" title="Toggle online availability">
              <input type="checkbox" id="modalIsAvailableOnline" name="is_available_online" value="1" checked>
              <span class="cs-toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- SECTION 3: Eligibility Rules -->
      <div class="edit-modal-section">
        <div class="edit-modal-section-header">
          <span class="edit-modal-badge-num">3</span>
          <div>
            <h3 class="edit-modal-section-title">Eligibility Rules</h3>
            <p class="edit-modal-section-subtitle">Optional eligibility criteria and applicant age requirements.</p>
          </div>
        </div>

        <div class="edit-modal-subcard">
          <label for="modalMinAge" class="block text-xs font-semibold text-white mb-2">
            Minimum Age Requirement (Optional)
          </label>
          <div class="flex items-center gap-3">
            <input type="number" id="modalMinAge" name="min_age" min="0" max="120" placeholder="e.g. 18" style="width: 140px;">
            <span class="text-xs font-medium text-[#CBD5E1]">years old or above</span>
          </div>
          <p class="text-[11px] text-[#94A3B8] mt-2">Leave blank if this certificate is available to all age groups without minimum age restrictions.</p>
        </div>
      </div>

      <!-- SECTION 4: Word Template Upload -->
      <div class="edit-modal-section">
        <div class="edit-modal-section-header">
          <span class="edit-modal-badge-num">4</span>
          <div>
            <h3 class="edit-modal-section-title">Word Template Upload</h3>
            <p class="edit-modal-section-subtitle">Attach or update the Microsoft Word (.docx) document template.</p>
          </div>
        </div>

        <div class="space-y-3.5">
          <!-- Current template display -->
          <div id="currentTemplateDisplay" class="hidden text-xs edit-modal-subcard flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-7 h-7 rounded-lg bg-[#111c2e] border border-[#334155] flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#f4b323]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 4h7v5h5v11H6V4z"/>
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-[11px] text-[#94A3B8]">Active Template on Server:</p>
                <p id="currentTemplateName" class="font-mono text-xs text-white font-semibold truncate"></p>
              </div>
            </div>
            <span class="text-[11px] text-[#A0AEC0] bg-[#111c2e] border border-[#334155] px-2.5 py-1 rounded-md shrink-0">Keep or replace below</span>
          </div>

          <div>
            <label for="modalTemplateFile" class="block text-xs font-semibold text-white mb-1.5">
              Word Template File (.docx)
            </label>
            <input type="file" id="modalTemplateFile" name="template_file" accept=".docx"
                   class="w-full text-xs text-[#CBD5E1] bg-[#1e293b] border border-[#475569] rounded-xl p-2 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#f4b323] file:text-[#111827] hover:file:bg-[#f5bc3a] cursor-pointer focus:outline-none focus:border-[#f4b323]">
          </div>

          <div class="edit-modal-subcard text-[11px] text-[#94A3B8] space-y-1">
            <p class="text-xs font-semibold text-[#CBD5E1] flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#f4b323] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Automatic Template Integration
            </p>
            <p class="text-[11px] text-[#94A3B8]">
              Resident details, dates, certificate numbers, and barangay officials are populated automatically from system records during issuance.
            </p>
          </div>
        </div>
      </div>

      <!-- SECTION 5: Certificate-Specific Extra Fields -->
      <div class="edit-modal-section">
        <div class="edit-modal-section-header">
          <span class="edit-modal-badge-num">5</span>
          <div>
            <h3 class="edit-modal-section-title">Certificate-Specific Extra Fields</h3>
            <p class="edit-modal-section-subtitle">Custom fields required when issuing or requesting this specific certificate.</p>
          </div>
        </div>

        <div class="space-y-3.5">
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label for="modalExtraFields" class="text-xs font-semibold text-white">
                Extra Fields (Comma-Separated)
              </label>
              <span class="text-[11px] text-[#94A3B8]">Additional required fields</span>
            </div>
            <input type="text" id="modalExtraFields" name="extra_fields" placeholder="e.g. Purpose, Partner Name, Number of Trees, Business Nature">
            <p class="text-[11px] text-[#94A3B8] mt-1">These will dynamically generate input fields on the request and issuance forms.</p>
          </div>
        </div>
      </div>

      <!-- Alert Box -->
      <div id="modalAlert" class="hidden rounded-xl p-3 text-xs font-medium"></div>

    </div>

    <!-- Modal Footer (Sticky) -->
    <div class="edit-modal-footer">
      <button type="button" id="cancelModalBtn" class="edit-btn-cancel">
        Cancel
      </button>
      <button type="submit" id="saveCertBtn" class="edit-btn-save primary">
        Save Certificate Type
      </button>
    </div>

  </form>
</div>

<script>
let allCertificates = [];
let currentFilterTab = 'all';

// Load certificate types with full management metadata
async function loadCertificates() {
  try {
    const res = await fetch('../api/get_certificate_types.php?mode=manage');
    const data = await res.json();
    if (data.success) {
      allCertificates = data.types || [];
      updateMetrics();
      renderTable();
    } else {
      document.getElementById('certTableBody').innerHTML = `
        <tr><td colspan="7" class="px-4 py-8 text-center text-[#F87171]">Failed to load certificates: ${escapeHtml(data.message || 'Unknown error')}</td></tr>
      `;
    }
  } catch (err) {
    console.error(err);
    document.getElementById('certTableBody').innerHTML = `
      <tr><td colspan="7" class="px-4 py-8 text-center text-[#F87171]">Connection error loading certificates.</td></tr>
    `;
  }
}

function updateMetrics() {
  const total = allCertificates.length;
  const active = allCertificates.filter(c => c.is_active === 1).length;
  const online = allCertificates.filter(c => c.is_available_online === 1).length;
  const templates = allCertificates.filter(c => c.template_exists).length;

  document.getElementById('metricTotal').textContent = total;
  document.getElementById('metricActive').textContent = active;
  document.getElementById('metricOnline').textContent = online;
  document.getElementById('metricTemplates').textContent = templates;
}

function renderTable() {
  const tbody = document.getElementById('certTableBody');
  const search = document.getElementById('searchInput').value.toLowerCase().trim();

  let filtered = allCertificates.filter(c => {
    // Tab filter
    if (currentFilterTab === 'active' && c.is_active !== 1) return false;
    if (currentFilterTab === 'inactive' && c.is_active === 1) return false;
    if (currentFilterTab === 'online' && c.is_available_online !== 1) return false;

    // Search filter
    if (search !== '') {
      const matchName = c.name.toLowerCase().includes(search);
      const matchTmpl = (c.template_file || '').toLowerCase().includes(search);
      return matchName || matchTmpl;
    }
    return true;
  });

  if (filtered.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="7" class="px-4 py-8 text-center text-[#94A3B8]">No certificate types match the filter criteria.</td></tr>
    `;
    return;
  }

  tbody.innerHTML = filtered.map(c => {
    const extraCount = (c.extra_fields || []).length;
    const ruleCount = c.rule_count || 0;
    const templateName = c.template_file ? escapeHtml(c.template_file) : '<span class="text-[#D99A2B] italic font-normal">None</span>';

    return `
      <tr class="border-t border-[#353F50] hover:bg-[#2C3547] transition">
        <td class="py-3 px-4">
          <div class="font-semibold text-white">${escapeHtml(c.name)}</div>
        </td>

        <td class="py-3 px-4">
          <div class="flex items-center gap-1.5 font-mono text-xs">
            ${c.template_exists ? `
              <a href="../api/secretary_download_template.php?certificate_type_id=${c.certificate_type_id}"
                 title="Download .docx template"
                 class="inline-flex items-center gap-1 text-[#60A5FA] hover:text-[#93C5FD] hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-4-4m4 4l4-4"/>
                </svg>
                ${templateName}
              </a>
            ` : `
              <span class="text-[#D99A2B] flex items-center gap-1" title="Template file not found on disk">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                ${templateName}
              </span>
            `}
          </div>
        </td>

        <td class="py-3 px-4">
          <button onclick="toggleStatus(${c.certificate_type_id}, 'is_active', ${c.is_active ? 0 : 1})"
                  class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold transition ${c.is_active ? 'bg-[#123624] text-[#34D399] border border-[#1C5638] hover:bg-[#184830]' : 'bg-[#381A1D] text-[#F87171] border border-[#5C252B] hover:bg-[#482226]'}">
            <span class="w-1.5 h-1.5 rounded-full ${c.is_active ? 'bg-[#34D399]' : 'bg-[#F87171]'}"></span>
            ${c.is_active ? 'Active' : 'Inactive'}
          </button>
        </td>

        <td class="py-3 px-4">
          <button onclick="toggleStatus(${c.certificate_type_id}, 'is_available_online', ${c.is_available_online ? 0 : 1})"
                  class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold transition ${c.is_available_online ? 'bg-[#172E4C] text-[#60A5FA] border border-[#224874] hover:bg-[#1E3B61]' : 'bg-[#27303F] text-[#94A3B8] border border-[#384357] hover:bg-[#323D50]'}">
            <span class="w-1.5 h-1.5 rounded-full ${c.is_available_online ? 'bg-[#60A5FA]' : 'bg-[#94A3B8]'}"></span>
            ${c.is_available_online ? 'Portal Enabled' : 'Walk-in Only'}
          </button>
        </td>

        <td class="py-3 px-4 text-xs text-slate-300">
          ${extraCount > 0 
            ? `<span class="bg-[#161B24] border border-[#353F50] px-2.5 py-0.5 rounded text-[11px] font-medium text-[#D99A2B]" title="${(c.extra_fields || []).map(f => formatFieldLabel(f)).join(', ')}">${extraCount} field${extraCount > 1 ? 's' : ''}</span>`
            : '<span class="text-[#94A3B8] text-[11px]">None</span>'}
        </td>

        <td class="py-3 px-4 text-xs text-slate-300">
          ${ruleCount > 0 
            ? `<span class="bg-[#2A1E42] text-[#C084FC] border border-[#46316E] px-2.5 py-0.5 rounded text-[11px] font-medium">${ruleCount} rule${ruleCount > 1 ? 's' : ''}</span>` 
            : '<span class="text-[#94A3B8] text-[11px]">Standard</span>'}
        </td>

        <td class="py-3 px-4 text-right">
          <div class="inline-flex items-center gap-2">
            <button onclick="openEditModal(${c.certificate_type_id})"
                    class="px-3 py-1 bg-[#161B24] border border-[#353F50] text-[#D99A2B] hover:text-[#E6B84A] hover:bg-[#2C3547] hover:border-[#D99A2B] rounded-lg text-xs font-semibold transition cursor-pointer">
              Edit
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// Toggle status handler
async function toggleStatus(certId, field, newVal) {
  try {
    const res = await fetch('../api/secretary_toggle_certificate_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        certificate_type_id: certId,
        field: field,
        value: newVal,
      }),
    });
    const data = await res.json();
    if (data.success) {
      const item = allCertificates.find(c => c.certificate_type_id == certId);
      if (item) {
        item[field] = data.new_value;
        updateMetrics();
        renderTable();
      }
    } else {
      alert(data.message || 'Failed to update status.');
    }
  } catch (err) {
    console.error(err);
    alert('Network error updating status.');
  }
}

// Filter tabs
document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.filter-tab').forEach(t => {
      t.className = 'filter-tab px-3.5 py-1.5 rounded-lg text-xs font-semibold transition bg-[#1E2533] text-[#A0AEC0] border border-[#353F50] hover:text-white hover:bg-[#2C3547]';
    });
    tab.className = 'filter-tab active-filter px-3.5 py-1.5 rounded-lg text-xs font-semibold transition bg-[#252C3A] text-white border border-[#D99A2B]';
    currentFilterTab = tab.dataset.filter;
    renderTable();
  });
});

// Search input
document.getElementById('searchInput').addEventListener('input', () => {
  renderTable();
});

// Modal Elements
const certModal = document.getElementById('certModal');
const certForm = document.getElementById('certForm');
const modalBody = document.getElementById('modalBody');
const modalTitle = document.getElementById('modalTitle');
const modalAlert = document.getElementById('modalAlert');
const saveCertBtn = document.getElementById('saveCertBtn');
const currentTemplateDisplay = document.getElementById('currentTemplateDisplay');
const currentTemplateName = document.getElementById('currentTemplateName');

function formatFieldLabel(str) {
  const clean = String(str || '').replace(/[\$\{\}]/g, '').trim();
  return clean.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function openAddModal() {
  certForm.reset();
  document.getElementById('modalCertId').value = '';
  document.getElementById('modalIsActive').checked = true;
  document.getElementById('modalIsAvailableOnline').checked = true;
  document.getElementById('modalMinAge').value = '';
  modalTitle.textContent = 'Add Certificate Type';
  currentTemplateDisplay.classList.add('hidden');
  modalAlert.className = 'hidden rounded-xl p-3 text-xs font-medium';
  modalAlert.textContent = '';
  
  certModal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';

  // Ensure scroll starts right at top
  if (modalBody) modalBody.scrollTop = 0;
  certModal.scrollTop = 0;
}

async function openEditModal(certId) {
  certForm.reset();
  modalAlert.className = 'hidden rounded-xl p-3 text-xs font-medium';
  modalAlert.textContent = '';
  modalTitle.textContent = 'Edit Certificate Type';

  try {
    const res = await fetch(`../api/secretary_get_certificate_type.php?certificate_type_id=${certId}`);
    const data = await res.json();
    if (!data.success) {
      alert(data.message || 'Could not load certificate type details.');
      return;
    }
    const c = data.certificate_type;

    document.getElementById('modalCertId').value = c.certificate_type_id;
    document.getElementById('modalName').value = c.name;
    document.getElementById('modalIsActive').checked = (c.is_active === 1);
    document.getElementById('modalIsAvailableOnline').checked = (c.is_available_online === 1);
    document.getElementById('modalMinAge').value = c.min_age !== null ? c.min_age : '';
    document.getElementById('modalExtraFields').value = (c.extra_fields || []).map(f => formatFieldLabel(f)).join(', ');

    if (c.template_file) {
      currentTemplateName.textContent = c.template_file;
      currentTemplateDisplay.classList.remove('hidden');
    } else {
      currentTemplateDisplay.classList.add('hidden');
    }

    certModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Ensure scroll starts right at top
    if (modalBody) modalBody.scrollTop = 0;
    certModal.scrollTop = 0;
  } catch (err) {
    console.error(err);
    alert('Error loading certificate details.');
  }
}

function addExtraField(fieldName) {
  const input = document.getElementById('modalExtraFields');
  const current = input.value.split(',').map(s => s.trim()).filter(Boolean);
  if (!current.includes(fieldName)) {
    current.push(fieldName);
    input.value = current.join(', ');
  }
}

function closeModal() {
  certModal.classList.add('hidden');
  document.body.style.overflow = '';
}

document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
document.getElementById('closeModalBtn').addEventListener('click', closeModal);
document.getElementById('cancelModalBtn').addEventListener('click', closeModal);

certModal.addEventListener('click', (e) => {
  if (e.target === certModal) closeModal();
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && !certModal.classList.contains('hidden')) {
    closeModal();
  }
});

// Submit Add / Edit Form
certForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  modalAlert.className = 'hidden rounded-xl p-3 text-xs font-medium';
  saveCertBtn.disabled = true;
  saveCertBtn.innerHTML = `
    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-[#111827] inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
    </svg>
    Saving...
  `;

  const formData = new FormData(certForm);
  // Guarantee checkboxes submit 0 if unchecked
  if (!document.getElementById('modalIsActive').checked) {
    formData.set('is_active', '0');
  }
  if (!document.getElementById('modalIsAvailableOnline').checked) {
    formData.set('is_available_online', '0');
  }

  try {
    const res = await fetch('../api/secretary_save_certificate_type.php', {
      method: 'POST',
      body: formData,
    });
    const data = await res.json();

    if (data.success) {
      modalAlert.className = 'rounded-xl p-3 text-xs font-medium bg-[#123624] border border-[#1C5638] text-[#34D399]';
      modalAlert.textContent = data.message || 'Saved successfully!';
      setTimeout(() => {
        closeModal();
        loadCertificates();
      }, 700);
    } else {
      modalAlert.className = 'rounded-xl p-3 text-xs font-medium bg-[#381A1D] border border-[#5C252B] text-[#F87171]';
      modalAlert.textContent = data.message || 'Failed to save certificate.';
    }
  } catch (err) {
    console.error(err);
    modalAlert.className = 'rounded-xl p-3 text-xs font-medium bg-[#381A1D] border border-[#5C252B] text-[#F87171]';
    modalAlert.textContent = 'Connection error. Please try again.';
  } finally {
    saveCertBtn.disabled = false;
    saveCertBtn.textContent = 'Save Certificate Type';
  }
});

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

loadCertificates();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
