<?php
require_once __DIR__ . '/../api/auth_guard.php';

$totalToday    = $pdo->query("SELECT COUNT(*) FROM certificate_requests WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalAll      = $pdo->query("SELECT COUNT(*) FROM certificate_requests")->fetchColumn();
$pendingScans  = $pdo->query("SELECT COUNT(*) FROM scans WHERE status = 'pending_review'")->fetchColumn();
$pendingOnline = $pdo->query("SELECT COUNT(*) FROM certificate_requests WHERE status IN ('pending','under_review') AND initiated_by = 'resident'")->fetchColumn();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
  <div>
    <p class="cs-eyebrow mb-1">Overview</p>
    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
    <p class="text-slate-500 text-sm mt-1">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>.</p>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
  <div class="cs-metric" style="--m-color:#D99A2B">
    <div class="flex items-center justify-between mb-3">
      <span class="text-xs font-medium text-[#B6BEC9]">Today</span>
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#D99A2B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3"/>
      </svg>
    </div>
    <p class="text-3xl font-bold text-[#F8FAFC]"><?= (int)$totalToday ?></p>
    <p class="text-xs text-[#B6BEC9] mt-1">Certificates issued</p>
  </div>

  <div class="cs-metric" style="--m-color:#C9A24F">
    <div class="flex items-center justify-between mb-3">
      <span class="text-xs font-medium text-[#B6BEC9]">All Time</span>
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#C9A24F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 v13a1 1 0 001.6.8L9 18l3 2 3-2 3.4 1.8A1 1 0 0020 19v-6z"/>
      </svg>
    </div>
    <p class="text-3xl font-bold text-[#F8FAFC]"><?= (int)$totalAll ?></p>
    <p class="text-xs text-[#B6BEC9] mt-1">Total on record</p>
  </div>

  <div class="cs-metric" style="--m-color:#E6B84A">
    <div class="flex items-center justify-between mb-3">
      <span class="text-xs font-medium text-[#B6BEC9]">Scans</span>
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#E6B84A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3"/>
      </svg>
    </div>
    <p class="text-3xl font-bold text-[#F8FAFC]"><?= (int)$pendingScans ?></p>
    <p class="text-xs text-[#B6BEC9] mt-1">Awaiting review</p>
  </div>

  <div class="cs-metric" style="--m-color:<?= $pendingOnline > 0 ? '#C0392B' : '#D99A2B' ?>">
    <div class="flex items-center justify-between mb-3">
      <span class="text-xs font-medium text-[#B6BEC9]">Online</span>
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:<?= $pendingOnline > 0 ? '#C0392B' : '#D99A2B' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
    </div>
    <p class="text-3xl font-bold text-[#F8FAFC]"><?= (int)$pendingOnline ?></p>
    <p class="text-xs text-[#B6BEC9] mt-1">Requests need action</p>
  </div>
</div>

<div class="cs-card cs-card-body">
  <p class="cs-eyebrow mb-3">Quick Actions</p>
  <div class="flex flex-wrap gap-3">
    <a href="scan.php" class="cs-btn-primary">+ Scan a new document</a>
    <a href="manage_requests.php" class="cs-btn-secondary">Manage requests</a>
    <a href="search_resident.php" class="cs-btn-secondary">Find resident</a>
    <a href="residents_directory.php" class="cs-btn-secondary">Residents</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
