<?php
$pageTitle = $pageTitle ?? 'CertiScan';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> · CertiScan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/tailwind.css">
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background-color: #1B1F2A;
    color: #F8FAFC;
  }

  .cs-shell {
    display: flex;
    min-height: 100vh;
    background-color: #1B1F2A;
  }

  .cs-sidebar {
    width: 264px;
    flex-shrink: 0;
    background: #161B24;
    color: #F8FAFC;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
    border-right: 1px solid #353F50;
  }

  .cs-nav-link {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.65rem 1.1rem;
    color: #B6BEC9;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0 8px 8px 0;
    margin: 0.1rem 0;
    border-left: 4px solid transparent;
    transition: all 0.15s ease;
  }

  .cs-nav-link svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    color: #9CA3AF;
    transition: color 0.15s ease;
  }

  .cs-nav-link:hover {
    background: #2A3242;
    color: #F8FAFC;
  }

  .cs-nav-link:hover svg {
    color: #F8FAFC;
  }

  .cs-nav-link.active {
    background: #232937;
    color: #F8FAFC;
    border-left-color: #D99A2B;
    font-weight: 600;
  }

  .cs-nav-link.active svg {
    color: #D99A2B;
  }

  .cs-main {
    flex: 1;
    min-width: 0;
    background: #1B1F2A;
    color: #F8FAFC;
  }

  .cs-topbar {
    background: #1B1F2A;
    border-bottom: 1px solid #353F50;
  }

  .cs-card {
    background: #252C3A;
    border: 1px solid #353F50;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.30);
    color: #F8FAFC;
  }

  .cs-card-header {
    border-bottom: 1px solid #353F50;
    padding: 1rem 1.5rem;
  }

  .cs-card-body {
    padding: 1.5rem;
  }

  .cs-eyebrow {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #D99A2B;
  }

  .cs-metric {
    position: relative;
    background: #252C3A;
    border: 1px solid #353F50;
    border-radius: 16px;
    padding: 1.25rem;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.30);
    color: #F8FAFC;
  }

  .cs-metric::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--m-color, #D99A2B);
  }

  .cs-btn-primary, .btn-primary {
    background: #D99A2B;
    color: #111827;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.65rem 1.25rem;
    transition: background 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
  }

  .cs-btn-primary:hover, .btn-primary:hover {
    background: #E6B84A;
    color: #111827;
  }

  .cs-btn-secondary, .btn-secondary {
    background: #232937;
    color: #D99A2B;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.65rem 1.25rem;
    border: 1px solid #D99A2B;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  .cs-btn-secondary:hover, .btn-secondary:hover {
    background: #2A3242;
    color: #E6B84A;
    border-color: #E6B84A;
  }

  .cs-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #353F50;
  }

  .cs-table thead {
    background: #232937;
  }

  .cs-table th {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #F8FAFC;
    text-align: left;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #353F50;
  }

  .cs-table td {
    padding: 0.9rem 1rem;
    border-top: 1px solid #353F50;
    background: #252C3A;
    color: #F8FAFC;
  }

  .cs-table tbody tr:hover td {
    background: #303A4D;
  }

  .cs-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.02em;
  }

  /* Status Badges */
  .badge-approved { background-color: #1F7A3D !important; color: #FFFFFF !important; }
  .badge-pending { background-color: #D99A2B !important; color: #111827 !important; }
  .badge-rejected { background-color: #C0392B !important; color: #FFFFFF !important; }
  .badge-released { background-color: #2563EB !important; color: #FFFFFF !important; }
  .badge-under_review, .badge-under-review { background-color: #7C3AED !important; color: #FFFFFF !important; }
  .badge-ready_for_pickup { background-color: #7C3AED !important; color: #FFFFFF !important; }

  /* Global Inputs Dark Theme */
  input[type="text"], input[type="password"], input[type="email"], input[type="date"], select, textarea {
    background-color: #232937 !important;
    color: #F8FAFC !important;
    border: 1px solid #353F50 !important;
    border-radius: 10px !important;
  }

  input::placeholder, textarea::placeholder {
    color: #9CA3AF !important;
  }

  input:focus, select:focus, textarea:focus {
    border-color: #D99A2B !important;
    outline: none !important;
    box-shadow: 0 0 0 2px rgba(217, 154, 43, 0.2) !important;
  }
</style>
</head>
<body class="bg-[#1B1F2A] text-[#F8FAFC]">

<?php if (!empty($_SESSION['staff_id'])):
  $navItems = [
    ['dashboard.php', 'Dashboard', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h4a1 1 0 001-1V10"/>'],
    ['scan.php', 'New Scan', '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3a1 1 0 011 1v11a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1z"/><circle cx="12" cy="13" r="3.5"/>'],
    ['search_resident.php', 'Find Resident', '<circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3"/>'],
    ['manage_requests.php', 'Manage Requests', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6M9 16h6"/>'],
    ['residents_directory.php', 'Residents', '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/>'],
    ['certificates.php', 'Records', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3M9 17l3 3m0 0l3-3m-3 3V9"/>'],
    ['manage_certificates.php', 'Certificate Types', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
    ['officials.php', 'Officials', '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'],
  ];
?>
<div class="cs-shell">
  <aside class="cs-sidebar">
    <div class="px-5 py-5 flex items-center gap-2.5">
      <img src="img/logo.jpg" alt="CertiScan logo" class="w-9 h-9 rounded-lg object-cover ring-1 ring-white/20">
      <div class="leading-tight">
        <p class="font-bold text-sm text-[#F8FAFC]">CertiScan</p>
        <p class="text-xs text-[#B6BEC9]">Brgy. Balongating</p>
      </div>
    </div>
    <nav class="flex-1 py-2 space-y-0.5">
      <?php foreach ($navItems as [$href, $label, $icon]): ?>
      <a href="<?= $href ?>" class="cs-nav-link <?= $currentPage === $href ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><?= $icon ?></svg>
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="px-5 py-4 border-t border-[#353F50] flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-full bg-[#D99A2B] text-[#111827] flex items-center justify-center text-xs font-bold"><?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?></div>
      <div class="leading-tight min-w-0">
        <p class="text-xs font-semibold text-[#F8FAFC] truncate"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
        <a href="../api/logout.php" class="text-xs text-[#B6BEC9] hover:text-[#F8FAFC] transition">Log out</a>
      </div>
    </div>
  </aside>

  <div class="cs-main">
    <div class="cs-topbar sticky top-0 z-10 px-6 py-3.5 flex items-center justify-between">
      <p class="text-sm font-medium text-[#B6BEC9]">Barangay Balongating &middot; Guipos, Zamboanga Del Sur</p>
      <span class="text-xs text-[#9CA3AF]">Republic of the Philippines</span>
    </div>
    <main class="max-w-6xl px-6 py-8">
<?php endif; ?>

