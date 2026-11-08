<?php
require_once __DIR__ . '/../api/auth_guard.php';

$scanId     = (int)($_GET['scan_id'] ?? 0);
$residentId = (int)($_GET['resident_id'] ?? 0);

if (!$scanId && !$residentId) {
    header('Location: search_resident.php');
    exit;
}

if ($scanId) {
    $stmt = $pdo->prepare('SELECT * FROM scans WHERE scan_id = ?');
    $stmt->execute([$scanId]);
    $scan = $stmt->fetch();

    if (!$scan) {
        header('Location: scan.php');
        exit;
    }
    $hasImage = true;
    // A scan may already be linked to a resident (e.g. re-scanning an
    // existing person) — check for an account the same way the
    // resident_id branch below does.
    $existingUsername = null;
    if (!empty($scan['resident_id'])) {
        $stmt = $pdo->prepare('SELECT username FROM residents WHERE resident_id = ?');
        $stmt->execute([$scan['resident_id']]);
        $existingUsername = $stmt->fetchColumn() ?: null;
    }
} else {
    $stmt = $pdo->prepare('SELECT * FROM residents WHERE resident_id = ?');
    $stmt->execute([$residentId]);
    $resident = $stmt->fetch();

    if (!$resident) {
        header('Location: search_resident.php');
        exit;
    }

    $scan = [
        'extracted_name'           => $resident['full_name'],
        'extracted_address'        => $resident['address'],
        'extracted_birthdate'      => $resident['birthdate'],
        'extracted_civil_status'   => $resident['civil_status'],
        'extracted_place_of_birth' => $resident['place_of_birth'],
        'raw_ocr_text'             => '',
    ];
    $hasImage = false;
    // This resident may already have registered their own online
    // account (e.g. self-registered, then later came in for a
    // walk-in) — if so, don't ask staff to set new credentials.
    $existingUsername = $resident['username'] ?? null;
}

$pageTitle = 'Review Resident Data';
require __DIR__ . '/includes/header.php';
?>

<p class="cs-eyebrow mb-1">Data Verification</p>
<h1 class="text-2xl font-bold text-slate-900 mb-1">Review resident data</h1>
<p class="text-slate-500 text-sm mb-6">Check the details below against the scanned document. Correct anything that looks off, then save.</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

  <div>
    <?php if ($hasImage): ?>
    <p class="text-sm font-medium text-slate-700 mb-2">Scanned document</p>
    <img src="../api/serve_image.php?scan_id=<?= $scanId ?>"
         class="w-full rounded-xl border border-blue-100 object-contain max-h-[500px]">

    <div class="mt-4 bg-slate-50 border border-slate-200 rounded-lg p-4 max-h-64 overflow-y-auto">
      <p class="text-xs font-medium text-slate-500 mb-2">Raw OCR text (for reference while correcting fields)</p>
      <pre class="text-xs text-slate-600 whitespace-pre-wrap"><?= htmlspecialchars($scan['raw_ocr_text']) ?></pre>
    </div>
    <?php else: ?>
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-6 flex items-center justify-center text-center h-full">
      <p class="text-slate-500 text-sm">Existing resident record — no scanned image for this request.</p>
    </div>
    <?php endif; ?>
  </div>

  <form id="reviewForm" class="space-y-4">
    <input type="hidden" name="scan_id" value="<?= $scanId ?: '' ?>">
    <input type="hidden" name="resident_id" value="<?= $residentId ?: '' ?>">

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($scan['extracted_name']) ?>" required
             class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
      <input type="text" name="address" value="<?= htmlspecialchars($scan['extracted_address']) ?>" required
             class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Birthdate</label>
        <input type="date" name="birthdate" value="<?= htmlspecialchars($scan['extracted_birthdate'] ?? '') ?>"
               class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Civil status</label>
        <select name="civil_status"
                class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="">Select</option>
          <option <?= $scan['extracted_civil_status'] === 'Single' ? 'selected' : '' ?>>Single</option>
          <option <?= $scan['extracted_civil_status'] === 'Married' ? 'selected' : '' ?>>Married</option>
          <option <?= $scan['extracted_civil_status'] === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
          <option <?= $scan['extracted_civil_status'] === 'Separated' ? 'selected' : '' ?>>Separated</option>
          <option <?= $scan['extracted_civil_status'] === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Place of birth</label>
      <input type="text" name="place_of_birth" value="<?= htmlspecialchars($scan['extracted_place_of_birth']) ?>"
             class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <?php if ($existingUsername): ?>
      <!-- Resident already has an online account — nothing to set up -->
      <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex items-center gap-2.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-slate-600">This resident already has an online account (<span class="font-medium"><?= htmlspecialchars($existingUsername) ?></span>).</p>
      </div>
      <input type="hidden" name="has_existing_account" value="1">
    <?php else: ?>
      <!-- No account yet — staff can optionally set one up now -->
      <div class="border-t border-slate-200 pt-4">
        <p class="text-sm font-medium text-slate-700 mb-1">Set up online account <span class="text-slate-400 font-normal">(optional)</span></p>
        <p class="text-xs text-slate-500 mb-3">Give this resident a username and password so they can log in and track future requests online. Leave blank to skip.</p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Username</label>
            <input type="text" name="username" id="usernameInput"
                   class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            <p id="usernameCheckMsg" class="text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Password</label>
            <input type="password" name="password" id="passwordInput" minlength="6"
                   class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
        </div>
      </div>
    <?php endif; ?>

    <p id="errorMsg" class="text-sm text-red-600 hidden"></p>
    <p id="successMsg" class="text-sm text-green-600 hidden"></p>

    <button type="submit" id="submitBtn"
            class="w-full bg-primary text-white rounded-lg py-2.5 font-medium hover:bg-primary-dark transition disabled:opacity-50">
      Save
    </button>
  </form>
</div>

<script>
<?php if (!$existingUsername): ?>
// Live username-availability check, debounced, only shown when the
// staff member is actually typing a username to set up.
const usernameInput = document.getElementById('usernameInput');
const usernameCheckMsg = document.getElementById('usernameCheckMsg');
let usernameCheckTimer = null;

usernameInput.addEventListener('input', () => {
  clearTimeout(usernameCheckTimer);
  const value = usernameInput.value.trim();
  if (!value) {
    usernameCheckMsg.classList.add('hidden');
    return;
  }
  usernameCheckTimer = setTimeout(async () => {
    const res = await fetch('../api/check_username.php?username=' + encodeURIComponent(value));
    const data = await res.json();
    usernameCheckMsg.textContent = data.available ? 'Username is available' : 'Username is already taken';
    usernameCheckMsg.className = 'text-xs mt-1 ' + (data.available ? 'text-green-600' : 'text-red-600');
    usernameCheckMsg.classList.remove('hidden');
  }, 400);
});
<?php endif; ?>

document.getElementById('reviewForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const errorMsg = document.getElementById('errorMsg');
  const successMsg = document.getElementById('successMsg');
  const submitBtn = document.getElementById('submitBtn');
  errorMsg.classList.add('hidden');
  successMsg.classList.add('hidden');

  const username = form.username ? form.username.value.trim() : '';
  const password = form.password ? form.password.value : '';

  // Optional account setup: if either field is filled, both must be,
  // and the password needs a sane minimum length.
  if ((username && !password) || (!username && password)) {
    errorMsg.textContent = 'To set up an online account, fill in both username and password (or leave both blank to skip).';
    errorMsg.classList.remove('hidden');
    return;
  }
  if (password && password.length < 6) {
    errorMsg.textContent = 'Password must be at least 6 characters.';
    errorMsg.classList.remove('hidden');
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Saving...';

  const payload = {
    scan_id: form.scan_id.value || null,
    resident_id: form.resident_id.value || null,
    full_name: form.full_name.value,
    address: form.address.value,
    birthdate: form.birthdate.value || null,
    civil_status: form.civil_status.value || null,
    place_of_birth: form.place_of_birth.value,
    username: username || null,
    password: password || null,
  };

  const res = await fetch('../api/save_resident.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await res.json();

  if (data.success) {
    successMsg.textContent = 'Saved — redirecting...';
    successMsg.classList.remove('hidden');
    setTimeout(() => window.location.href = 'select_certificate.php?resident_id=' + data.resident_id, 800);
  } else {
    errorMsg.textContent = data.message || 'Something went wrong';
    errorMsg.classList.remove('hidden');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Save';
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>