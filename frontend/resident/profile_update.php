<?php
require_once __DIR__ . '/../../api/resident_auth_guard.php';
$pageTitle = 'Update Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="max-w-lg mx-auto">
  <p class="cs-eyebrow mb-1">Account</p>
  <h1 class="text-2xl font-bold text-slate-900 mb-1">Request a profile update</h1>
  <p class="text-slate-500 text-sm mb-6">Changes are reviewed by barangay staff before they take effect.</p>

  <form id="updateForm" class="bg-white border border-blue-100 rounded-2xl shadow-sm shadow-blue-100/50 p-6 space-y-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">New address</label>
      <input type="text" name="address" class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">New civil status</label>
      <select name="civil_status" class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">Select</option>
        <option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option><option>Divorced</option>
      </select>
    </div>
    <p id="successMsg" class="text-sm text-green-600 hidden bg-green-50 border border-green-100 rounded-lg p-3"></p>
    <button type="submit" class="w-full bg-primary text-white rounded-lg py-3 font-medium hover:bg-primary-dark transition">Submit for Review</button>
  </form>
</div>
<script>
document.getElementById('updateForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = Object.fromEntries(formData.entries());
  const res = await fetch('../../api/request_profile_update.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
  });
  const data = await res.json();
  const successMsg = document.getElementById('successMsg');
  successMsg.textContent = data.message;
  successMsg.classList.remove('hidden');
  e.target.reset();
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
