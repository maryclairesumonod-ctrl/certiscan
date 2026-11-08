<?php
require_once __DIR__ . '/../api/auth_guard.php';

$residentId = (int)($_GET['resident_id'] ?? 0);
if (!$residentId) {
    header('Location: search_resident.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM residents WHERE resident_id = ?');
$stmt->execute([$residentId]);
$resident = $stmt->fetch();

if (!$resident) {
    header('Location: search_resident.php');
    exit;
}

$pageTitle = 'Choose Certificate';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-xl mx-auto">
  <p class="cs-eyebrow mb-1">Certificate Issuance</p>
  <h1 class="text-2xl font-bold text-slate-900 mb-1">Choose a certificate</h1>

  <div class="bg-white border border-blue-100 rounded-xl p-4 mb-6">
    <p class="font-medium text-slate-900"><?= htmlspecialchars($resident['full_name']) ?></p>
    <p class="text-sm text-slate-500"><?= htmlspecialchars($resident['address']) ?></p>
    <a href="review.php?resident_id=<?= $residentId ?>" class="text-xs text-primary hover:underline">Edit resident details</a>
  </div>

  <form id="certForm" class="bg-white border border-blue-100 rounded-xl p-6 space-y-4">
    <input type="hidden" name="resident_id" value="<?= $residentId ?>">

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Certificate type</label>
      <select id="certType" name="certificate_type_id" required
              class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">Loading certificate types...</option>
      </select>
    </div>

    <div id="certWarning" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800">
      <p class="font-semibold">Notice: No valid Word template file found for this certificate.</p>
      <p class="mt-0.5">Please upload an approved .docx template under Certificate Management before generating.</p>
    </div>

    <div id="certInfoBox" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-3.5 space-y-1 text-xs">
      <p id="certDescription" class="text-slate-700 font-medium"></p>
      <div id="certRequirementsWrapper" class="hidden pt-1.5 border-t border-blue-200">
        <span class="font-semibold text-primary uppercase tracking-wide">Requirements: </span>
        <span id="certRequirements" class="text-slate-600"></span>
      </div>
    </div>

    <div id="extraFields" class="space-y-4"></div>

    <p id="errorMsg" class="text-sm text-red-600 hidden"></p>
    <div id="successBox" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 space-y-2">
      <p id="successMsg" class="text-sm text-green-700"></p>
      <a id="downloadLink" href="#" class="inline-block bg-green-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-green-700 transition">
        Download certificate (.docx)
      </a>
    </div>

    <button type="submit" id="submitBtn"
            class="w-full bg-primary text-white rounded-lg py-2.5 font-medium hover:bg-primary-dark transition disabled:opacity-50">
      Generate certificate
    </button>
  </form>
</div>

<script>
let certTypes = [];

async function loadCertTypes() {
  const res = await fetch('../api/get_certificate_types.php');
  const data = await res.json();
  certTypes = data.types || [];

  const select = document.getElementById('certType');
  select.innerHTML = '<option value="">Select a certificate type</option>' +
    certTypes.map(t => `<option value="${t.certificate_type_id}">${t.name}</option>`).join('');
}

document.getElementById('certType').addEventListener('change', (e) => {
  const type = certTypes.find(t => t.certificate_type_id == e.target.value);
  const container = document.getElementById('extraFields');
  const warningEl = document.getElementById('certWarning');
  const infoBox = document.getElementById('certInfoBox');
  const descEl = document.getElementById('certDescription');
  const reqWrapper = document.getElementById('certRequirementsWrapper');
  const reqEl = document.getElementById('certRequirements');
  const submitBtn = document.getElementById('submitBtn');

  container.innerHTML = '';

  if (!type) {
    warningEl.classList.add('hidden');
    infoBox.classList.add('hidden');
    submitBtn.disabled = false;
    return;
  }

  // Check template existence
  if (!type.template_exists) {
    warningEl.classList.remove('hidden');
    submitBtn.disabled = true;
  } else {
    warningEl.classList.add('hidden');
    submitBtn.disabled = false;
  }

  // Information and requirements
  let hasInfo = false;
  if (type.description && type.description.trim()) {
    descEl.textContent = type.description;
    descEl.classList.remove('hidden');
    hasInfo = true;
  } else {
    descEl.classList.add('hidden');
  }

  if (type.requirements && type.requirements.trim()) {
    reqEl.textContent = type.requirements;
    reqWrapper.classList.remove('hidden');
    hasInfo = true;
  } else {
    reqWrapper.classList.add('hidden');
  }

  if (hasInfo) {
    infoBox.classList.remove('hidden');
  } else {
    infoBox.classList.add('hidden');
  }

    if (!type.extra_fields) return;

  const systemKeys = [
    'cert_number', 'certificate_number', 'cert_day', 'cert_month', 'cert_year',
    'date', 'issued_date', 'certificate_subtitle', 'amount_paid',
    'full_name', 'resident_name', 'address', 'resident_address',
    'civil_status', 'birthdate', 'place_of_birth', 'age'
  ];
  type.extra_fields.forEach(fieldName => {
    const cleanKey = String(fieldName).replace(/[\$\{\}]/g, '').trim();
    if (cleanKey === '' || cleanKey.toLowerCase().startsWith('official_') || systemKeys.includes(cleanKey.toLowerCase())) {
      return;
    }
    const label = cleanKey.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    container.innerHTML += `
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">${label}</label>
        <input type="text" data-extra="${fieldName}" required
               class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
      </div>`;
  });
});

document.getElementById('certForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const errorMsg = document.getElementById('errorMsg');
  const successBox = document.getElementById('successBox');
  const successMsg = document.getElementById('successMsg');
  const downloadLink = document.getElementById('downloadLink');
  const submitBtn = document.getElementById('submitBtn');
  errorMsg.classList.add('hidden');
  successBox.classList.add('hidden');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Generating...';

  const extraValues = {};
  document.querySelectorAll('[data-extra]').forEach(input => {
    extraValues[input.dataset.extra] = input.value;
  });

  const payload = {
    resident_id: form.resident_id.value,
    certificate_type_id: form.certificate_type_id.value,
    extra_values: extraValues,
  };

  const res = await fetch('../api/generate_certificate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await res.json();

  if (data.success) {
    window.location.href = 'print_certificate.php?request_id=' + data.request_id;
  } else {
    errorMsg.textContent = data.message || 'Something went wrong';
    errorMsg.classList.remove('hidden');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Generate certificate';
  }
});

loadCertTypes();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
