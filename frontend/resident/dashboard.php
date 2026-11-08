<?php
require_once __DIR__ . '/../../api/resident_auth_guard.php';
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="rounded-2xl p-8 mb-8 text-white shadow-lg relative overflow-hidden" style="background: #0f1220;">
  <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full opacity-20" style="background: radial-gradient(circle, #2563eb, transparent 70%);"></div>
  <div class="absolute -bottom-20 -left-10 w-48 h-48 rounded-full opacity-10" style="background: radial-gradient(circle, #2563eb, transparent 70%);"></div>
  <div class="relative">
    <p class="cs-eyebrow text-blue-300 mb-1">Resident Portal</p>
    <h1 class="text-2xl font-bold">Welcome, <?= htmlspecialchars($_SESSION['resident_name']) ?></h1>
    <p class="text-slate-300 text-sm mt-1">Request and track your barangay certificates from here.</p>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
  <a href="request_credential.php" class="cs-card p-5 hover:shadow-md transition block">
    <p class="cs-eyebrow mb-1">Start Here</p>
    <p class="font-semibold text-slate-900">Request a Certificate</p>
    <p class="text-sm text-slate-500 mt-1">Choose a certificate type and submit a request.</p>
  </a>
  <a href="track_requests.php" class="cs-card p-5 hover:shadow-md transition block">
    <p class="cs-eyebrow mb-1">Status</p>
    <p class="font-semibold text-slate-900">Track My Requests</p>
    <p class="text-sm text-slate-500 mt-1">See the status of certificates you've requested.</p>
  </a>
</div>

<div class="cs-card cs-card-body">
  <p class="cs-eyebrow mb-3">Recommended For You</p>
  <div id="recommendations" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <p class="text-slate-400 text-sm">Loading recommendations...</p>
  </div>
</div>

<script>
async function loadRecommendations() {
  const res = await fetch('../../api/get_recommendations.php');
  const data = await res.json();
  const container = document.getElementById('recommendations');
  if (!data.recommendations || data.recommendations.length === 0) {
    container.innerHTML = '<p class="text-slate-400 text-sm">No suggestions right now.</p>';
    return;
  }
  container.innerHTML = data.recommendations.map(r => `
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
      <p class="font-medium text-slate-900 text-sm">${r.name}</p>
      <p class="text-xs text-slate-500 mt-1">${r.description || 'You may be eligible for this certificate.'}</p>
    </div>
  `).join('');
}
loadRecommendations();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
