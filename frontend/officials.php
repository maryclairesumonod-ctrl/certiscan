<?php
require_once __DIR__ . '/../api/auth_guard.php';
require_once __DIR__ . '/../api/officials_helper.php';

$pageTitle = 'Barangay Officials';
$officials = get_officials($pdo);
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
  <p class="cs-eyebrow mb-1">Settings</p>
  <h1 class="text-2xl font-bold text-slate-900">Barangay officials</h1>
  <p class="text-slate-500 text-sm mt-1">
    These names appear on every certificate the system generates. Update them here after an election or any change in officials.
  </p>
</div>

<div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 text-sm text-slate-600">
  Changes apply dynamically across all certificates. Certificate previews, generated PDFs, and downloaded documents automatically update with the latest official names.
</div>

<form id="officialsForm" class="cs-card max-w-2xl">
  <div class="cs-card-body space-y-4">

    <?php foreach ($officials as $o): ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars($o['position_label']) ?></label>
      <input type="text"
             data-key="<?= htmlspecialchars($o['position_key']) ?>"
             value="<?= htmlspecialchars($o['full_name']) ?>"
             required maxlength="150"
             class="officialInput w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <?php endforeach; ?>

    <p id="errorMsg" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
    <p id="successMsg" class="text-sm text-green-600 hidden bg-green-50 border border-green-100 rounded-lg p-3"></p>

    <button type="submit" id="saveBtn"
            class="w-full bg-primary text-white rounded-lg py-2.5 font-medium hover:bg-primary-dark transition disabled:opacity-50">
      Save Changes
    </button>
  </div>
</form>

<script>
document.getElementById('officialsForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const errorMsg = document.getElementById('errorMsg');
  const successMsg = document.getElementById('successMsg');
  const saveBtn = document.getElementById('saveBtn');
  errorMsg.classList.add('hidden');
  successMsg.classList.add('hidden');

  const officials = {};
  document.querySelectorAll('.officialInput').forEach(input => {
    officials[input.dataset.key] = input.value;
  });

  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving...';

  try {
    const res = await fetch('../api/save_officials.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ officials })
    });
    const data = await res.json();

    if (data.success) {
      successMsg.textContent = 'Officials updated. New certificates will use these names.';
      successMsg.classList.remove('hidden');
    } else {
      errorMsg.textContent = data.message || 'Something went wrong.';
      errorMsg.classList.remove('hidden');
    }
  } catch (err) {
    errorMsg.textContent = 'Unable to save. Please try again.';
    errorMsg.classList.remove('hidden');
  }

  saveBtn.disabled = false;
  saveBtn.textContent = 'Save Changes';
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>