<?php
require_once __DIR__ . '/../../api/resident_auth_guard.php';
$pageTitle = 'Request Certificate';
require __DIR__ . '/includes/header.php';
?>
<div class="max-w-lg mx-auto">
  <p class="cs-eyebrow mb-1">New Request</p>
  <h1 class="text-2xl font-bold text-slate-900 mb-1">Request a certificate</h1>
  <p class="text-slate-500 text-sm mb-6">We'll check your eligibility and use your saved profile details automatically.</p>

  <form id="requestForm" class="bg-white border border-blue-100 rounded-2xl shadow-sm shadow-blue-100/50 p-6 space-y-4">
    <div>
      <label for="certType" class="block text-sm font-medium text-slate-700 mb-1">Certificate type</label>
      <select id="certType" required disabled class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">Loading certificate types...</option>
      </select>
    </div>

    <!-- Certificate details / requirements (dynamic) -->
    <div id="certInfoBox" class="hidden rounded-xl bg-blue-50 border border-blue-100 p-3.5 text-xs space-y-2">
      <p id="certDescription" class="text-slate-700 leading-relaxed hidden"></p>
      <div id="certRequirementsWrapper" class="text-[#D99A2B] font-medium flex items-start gap-1.5 pt-0.5 hidden">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <span class="font-semibold uppercase tracking-wider text-[11px] block text-slate-700">Requirements / Eligibility:</span>
          <span id="certRequirements" class="text-slate-600 font-normal"></span>
        </div>
      </div>
    </div>

    <div id="extraFields" class="space-y-4 hidden"></div>

    <p id="errorMsg" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
    <p id="successMsg" class="text-sm text-green-600 hidden bg-green-50 border border-green-100 rounded-lg p-3"></p>

    <button type="submit" id="submitBtn" disabled class="w-full bg-primary text-white rounded-lg py-3 font-medium hover:bg-primary-dark transition disabled:opacity-50 shadow-md shadow-primary/20">
      Submit Request
    </button>
  </form>
</div>

<script>
let certTypes = [];

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

async function loadCertTypes() {
  const select = document.getElementById('certType');
  const submitBtn = document.getElementById('submitBtn');
  const errorMsg = document.getElementById('errorMsg');

  select.disabled = true;
  submitBtn.disabled = true;
  select.innerHTML = '<option value="">Loading certificate types...</option>';
  errorMsg.classList.add('hidden');

  try {
    const res = await fetch('../../api/get_certificate_types.php', {
      headers: { 'Accept': 'application/json' },
      cache: 'no-store'
    });

    if (!res.ok) {
      throw new Error(`Server returned status ${res.status}`);
    }

    const data = await res.json();
    if (!data || !data.success) {
      throw new Error((data && data.message) || 'Unable to retrieve certificate types.');
    }

    certTypes = Array.isArray(data.types) ? data.types : [];

    if (certTypes.length === 0) {
      select.innerHTML = '<option value="">No certificate types available</option>';
      select.disabled = true;
      submitBtn.disabled = true;
      return;
    }

    select.innerHTML = '<option value="">Select a certificate type</option>' +
      certTypes.map(t => `<option value="${escapeHtml(t.certificate_type_id)}">${escapeHtml(t.name)}</option>`).join('');

    select.disabled = false;
    submitBtn.disabled = !select.value;

  } catch (err) {
    console.error('Error loading certificate types:', err);
    select.innerHTML = '<option value="">Unable to load certificate types</option>';
    select.disabled = true;
    submitBtn.disabled = true;
    errorMsg.innerHTML = '<span>Failed to load certificate types: ' + escapeHtml(err.message || 'Please try again.') + '</span> <button type="button" onclick="loadCertTypes()" class="ml-2 font-semibold underline hover:opacity-80">Retry</button>';
    errorMsg.classList.remove('hidden');
  }
}

document.getElementById('certType').addEventListener('change', (e) => {
  const selectedId = e.target.value;
  const submitBtn = document.getElementById('submitBtn');
  const errorMsg = document.getElementById('errorMsg');
  const successMsg = document.getElementById('successMsg');
  const container = document.getElementById('extraFields');
  const infoBox = document.getElementById('certInfoBox');
  const descEl = document.getElementById('certDescription');
  const reqWrapper = document.getElementById('certRequirementsWrapper');
  const reqEl = document.getElementById('certRequirements');

  errorMsg.classList.add('hidden');
  successMsg.classList.add('hidden');
  container.innerHTML = '';

  if (!selectedId) {
    submitBtn.disabled = true;
    if (infoBox) infoBox.classList.add('hidden');
    container.classList.add('hidden');
    return;
  }

  // A valid certificate type has been selected
  submitBtn.disabled = false;

  const type = certTypes.find(t => String(t.certificate_type_id) === String(selectedId));
  if (!type) {
    if (infoBox) infoBox.classList.add('hidden');
    container.classList.add('hidden');
    return;
  }

  // Display description and requirements if present
  let hasInfo = false;
  if (infoBox && descEl) {
    if (type.description && type.description.trim()) {
      descEl.textContent = type.description.trim();
      descEl.classList.remove('hidden');
      hasInfo = true;
    } else {
      descEl.classList.add('hidden');
    }
  }

  if (infoBox && reqWrapper && reqEl) {
    if (type.requirements && type.requirements.trim()) {
      reqEl.textContent = type.requirements.trim();
      reqWrapper.classList.remove('hidden');
      hasInfo = true;
    } else {
      reqWrapper.classList.add('hidden');
    }
  }

  if (infoBox) {
    if (hasInfo) {
      infoBox.classList.remove('hidden');
    } else {
      infoBox.classList.add('hidden');
    }
  }

  // Handle dynamic extra fields
  const systemKeys = [
    'cert_number', 'certificate_number', 'cert_day', 'cert_month', 'cert_year',
    'date', 'issued_date', 'certificate_subtitle', 'amount_paid',
    'full_name', 'resident_name', 'address', 'resident_address',
    'civil_status', 'birthdate', 'place_of_birth', 'age'
  ];

  const validExtraFields = (type.extra_fields || []).filter(fieldName => {
    const cleanKey = String(fieldName).replace(/[\$\{\}]/g, '').trim();
    return cleanKey !== '' && !cleanKey.toLowerCase().startsWith('official_') && !systemKeys.includes(cleanKey.toLowerCase());
  });

  if (validExtraFields.length === 0) {
    container.classList.add('hidden');
    return;
  }

  container.classList.remove('hidden');
  validExtraFields.forEach(fieldName => {
    const cleanKey = String(fieldName).replace(/[\$\{\}]/g, '').trim();
    const label = cleanKey.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    container.innerHTML += `
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">${escapeHtml(label)}</label>
        <input type="text" data-extra="${escapeHtml(fieldName)}" required class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
      </div>`;
  });
});

document.getElementById('requestForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const errorMsg = document.getElementById('errorMsg');
  const successMsg = document.getElementById('successMsg');
  const submitBtn = document.getElementById('submitBtn');
  const select = document.getElementById('certType');
  const infoBox = document.getElementById('certInfoBox');
  const container = document.getElementById('extraFields');

  errorMsg.classList.add('hidden');
  successMsg.classList.add('hidden');

  if (!select.value) {
    errorMsg.textContent = 'Please select a certificate type.';
    errorMsg.classList.remove('hidden');
    return;
  }

  const extraValues = {};
  document.querySelectorAll('[data-extra]').forEach(input => {
    extraValues[input.dataset.extra] = input.value.trim();
  });

  submitBtn.disabled = true;
  submitBtn.textContent = 'Submitting...';

  try {
    const res = await fetch('../../api/submit_request.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        certificate_type_id: select.value,
        extra_values: extraValues,
      }),
    });

    const data = await res.json();

    if (data.success) {
      successMsg.textContent = 'Request submitted! You can track its status under "My Requests."';
      successMsg.classList.remove('hidden');
      e.target.reset();
      container.innerHTML = '';
      container.classList.add('hidden');
      if (infoBox) infoBox.classList.add('hidden');
      submitBtn.disabled = true;
    } else {
      errorMsg.textContent = data.message || 'Something went wrong';
      errorMsg.classList.remove('hidden');
      submitBtn.disabled = false;
    }
  } catch (err) {
    console.error('Submission failed:', err);
    errorMsg.textContent = 'Network or server error while submitting request. Please try again.';
    errorMsg.classList.remove('hidden');
    submitBtn.disabled = false;
  } finally {
    submitBtn.textContent = 'Submit Request';
  }
});

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', loadCertTypes);
} else {
  loadCertTypes();
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>