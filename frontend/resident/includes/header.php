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
<link rel="stylesheet" href="../assets/css/tailwind.css">

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
    width: 250px;
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
    padding: 0.65rem 1rem;
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


  /* NOTIFICATION BELL */

  .notif-dot {
    position: absolute;
    top: -6px;
    right: -8px;
    min-width: 17px;
    height: 17px;
    padding: 0 4px;

    background: #ef4444;
    color: #fff;

    border-radius: 999px;
    border: 2px solid #fff;

    display: none;

    align-items: center;
    justify-content: center;

    font-size: 9px;
    font-weight: 700;
    line-height: 1;

    box-shadow:
      0 2px 5px rgba(239,68,68,0.25);

    z-index: 5;
  }

  .notif-dot.items-center {
    display: flex;
  }


  /* NOTIFICATION DROPDOWN */

  .notif-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;

    width: 360px;
    max-width: calc(100vw - 30px);

    background: #252C3A;

    border: 1px solid #353F50;
    border-radius: 16px;

    box-shadow:
      0 12px 32px rgba(0,0,0,0.40);

    overflow: hidden;

    z-index: 40;
    color: #F8FAFC;
  }


  /* NOTIFICATION SCROLL AREA */

  #notifList {
    max-height: 340px;
    overflow-y: scroll;
    overflow-x: hidden;

    scrollbar-width: thin;
    scrollbar-color: #353F50 #232937;
  }

  #notifList::-webkit-scrollbar {
    width: 7px;
  }

  #notifList::-webkit-scrollbar-track {
    background: #232937;
  }

  #notifList::-webkit-scrollbar-thumb {
    background: #353F50;
    border-radius: 999px;
    border: 1px solid #232937;
  }

  #notifList::-webkit-scrollbar-thumb:hover {
    background: #4B5563;
  }


  /* NOTIFICATION ITEM */

  .notif-item {
    padding: 0.85rem 1.1rem;

    border-bottom: 1px solid #353F50;

    cursor: pointer;

    background: transparent;

    transition:
      background 0.12s ease,
      border-color 0.12s ease;
  }

  .notif-item:hover {
    background: #2D3646;
  }

  .notif-item:last-child {
    border-bottom: none;
  }


  /* NEW UNREAD NOTIFICATION */

  .notif-item-new {
    background: #2A3242;
    border-left: 4px solid #D99A2B;
  }

  .notif-item-new:hover {
    background: #303A4D;
  }


  /* RED NEW DOT */

  .notif-new-dot {
    width: 9px;
    height: 9px;

    background: #ef4444;

    border-radius: 999px;

    display: block;

    flex-shrink: 0;

    box-shadow:
      0 0 0 2px rgba(239,68,68,0.08);
  }


  /* NEW BADGE */

  .notif-new-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    background: #fee2e2;
    color: #dc2626;

    font-size: 9px;
    font-weight: 800;

    letter-spacing: 0.04em;

    padding: 2px 6px;

    border-radius: 999px;

    white-space: nowrap;
  }


  /* NEW DOT ANIMATION */

  @keyframes notifPulse {

    0% {
      box-shadow:
        0 0 0 0 rgba(239,68,68,0.35);
    }

    70% {
      box-shadow:
        0 0 0 5px rgba(239,68,68,0);
    }

    100% {
      box-shadow:
        0 0 0 0 rgba(239,68,68,0);
    }

  }

  .notif-new-dot {
    animation:
      notifPulse 1.8s infinite;
  }


  /* BELL PULSE WHEN NEW */

  @keyframes bellNotificationPulse {

    0%,
    100% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.08);
    }

  }

  .notif-has-new {
    animation:
      bellNotificationPulse 1.8s ease-in-out infinite;
  }

</style>
</head>

<body class="text-slate-800">

<?php if (!empty($_SESSION['resident_id'])):

  $navItems = [

    [
      'dashboard.php',
      'Dashboard',
      '<rect x="3" y="3" width="7" height="7" rx="1.5"/>
       <rect x="14" y="3" width="7" height="7" rx="1.5"/>
       <rect x="3" y="14" width="7" height="7" rx="1.5"/>
       <rect x="14" y="14" width="7" height="7" rx="1.5"/>'
    ],

    [
      'request_credential.php',
      'Request',
      '<path stroke-linecap="round" stroke-linejoin="round"
       d="M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3M9 17l3 3m0 0l3-3m-3 3V9"/>'
    ],

    [
      'track_requests.php',
      'My Requests',
      '<circle cx="12" cy="12" r="9"/>
       <path stroke-linecap="round" stroke-linejoin="round"
       d="M12 7v5l3 3"/>'
    ],

  ];

?>

<div class="cs-shell">

  <!-- SIDEBAR -->

  <aside class="cs-sidebar">

    <div class="px-5 py-5 flex items-center gap-2.5">

      <img
        src="../img/logo.jpg"
        alt="CertiScan logo"
        class="w-9 h-9 rounded-lg object-cover ring-1 ring-white/20"
      >

      <div class="leading-tight">

        <p class="font-bold text-sm">
          Brgy. Balongating
        </p>

        <p class="text-xs text-gray-400">
          Resident Portal
        </p>

      </div>

    </div>


    <!-- NAVIGATION -->

    <nav class="flex-1 py-2 space-y-0.5">

      <?php foreach ($navItems as [$href, $label, $icon]): ?>

        <a
          href="<?= $href ?>"
          class="cs-nav-link <?= $currentPage === $href ? 'active' : '' ?>"
        >

          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <?= $icon ?>
          </svg>

          <?= $label ?>

        </a>

      <?php endforeach; ?>

    </nav>


    <!-- RESIDENT ACCOUNT -->

    <div
      class="px-5 py-4
             border-t border-white/10
             flex items-center gap-2.5"
    >

      <div
        class="w-8 h-8
               rounded-full
               bg-blue-600
               flex items-center
               justify-center
               text-xs
               font-bold
               shrink-0"
      >

        <?= strtoupper(
          substr(
            $_SESSION['resident_name'],
            0,
            1
          )
        ) ?>

      </div>


      <div
        class="leading-tight
               min-w-0
               flex-1"
      >

        <p
          class="text-xs
                 font-semibold
                 truncate"
        >

          <?= htmlspecialchars(
            $_SESSION['resident_name']
          ) ?>

        </p>

        <p
          class="text-xs
                 text-gray-400"
        >
          Resident
        </p>

      </div>


      <!-- LOGOUT -->

      <a
        href="../../api/resident_logout.php"
        class="text-gray-400
               hover:text-white
               transition
               shrink-0"
        title="Log out"
      >

        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-4 h-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
          />

        </svg>

      </a>

    </div>

  </aside>


  <!-- MAIN -->

  <div class="cs-main">


    <!-- TOPBAR -->

    <div
      class="cs-topbar
             sticky
             top-0
             z-30
             px-6
             py-3.5
             flex
             items-center
             justify-between"
    >


      <!-- LEFT -->

      <div class="flex items-center gap-3">

        <button
          class="text-slate-500
                 hover:text-slate-700"
        >

          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >

            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4 6h16M4 12h16M4 18h16"
            />

          </svg>

        </button>


        <p
          class="text-sm
                 font-medium
                 text-primary"
        >

          Barangay Balongating
          Self-Service Portal

        </p>

      </div>


      <!-- NOTIFICATION BELL -->

      <div
        class="relative"
        id="notificationWrapper"
      >

        <button
          id="notifBellBtn"
          type="button"
          class="relative
                 text-slate-500
                 hover:text-slate-700
                 transition
                 p-1"
          aria-label="Notifications"
        >

          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >

            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />

          </svg>


          <!-- RED NUMBER -->

          <span
            id="notifDot"
            class="notif-dot hidden"
          >
            0
          </span>

        </button>


        <!-- NOTIFICATION DROPDOWN -->

        <div
          id="notifDropdown"
          class="notif-dropdown hidden"
        >

          <!-- HEADER -->

          <div
            class="px-4
                   py-3
                   border-b
                   border-[#353F50]
                   flex
                   items-center
                   justify-between"
          >

            <div class="flex items-center gap-2">

              <p
                class="text-sm
                       font-semibold
                       text-[#F8FAFC]"
              >
                Notifications
              </p>

              <span
                id="notifCount"
                class="text-xs text-[#B6BEC9]"
              ></span>

            </div>


            <button
              id="markAllReadBtn"
              type="button"
              class="text-xs text-[#D99A2B] hover:underline font-medium hidden"
            >
              Mark all as read
            </button>

          </div>



          <!-- SCROLLABLE LIST -->

          <div
            id="notifList"
            class="overflow-y-auto"
            style="max-height:340px;"
          >

            <p
              class="text-sm
                     text-slate-400
                     text-center
                     py-8"
            >
              Loading...
            </p>

          </div>

        </div>

      </div>

    </div>


    <!-- MAIN CONTENT -->

    <main
      class="max-w-6xl
             px-6
             py-8"
    >

<?php endif; ?>


<!-- NOTIFICATION JAVASCRIPT -->

<script>
let headerNotifications = [];
let isPollingUpdates = false;

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function headerTimeAgo(dateString) {
  if (!dateString) return '';
  const then = new Date(dateString.replace(' ', 'T'));
  const seconds = Math.floor((new Date() - then) / 1000);
  if (isNaN(seconds) || seconds < 0) return dateString;
  if (seconds < 60) return 'Just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes} min${minutes === 1 ? '' : 's'} ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days} day${days === 1 ? '' : 's'} ago`;
  return dateString;
}

function updateHeaderNotificationCount(unreadCount) {
  const dot = document.getElementById('notifDot');
  const countText = document.getElementById('notifCount');
  const bell = document.getElementById('notifBellBtn');
  const markAllBtn = document.getElementById('markAllReadBtn');

  if (!dot) return;

  const count = Number(unreadCount) || 0;

  if (count > 0) {
    dot.textContent = count > 99 ? '99+' : count;
    dot.classList.remove('hidden');
    dot.classList.add('items-center');

    if (bell) bell.classList.add('notif-has-new');

    if (countText) {
      countText.textContent = count === 1 ? '1 unread' : `${count} unread`;
      countText.classList.remove('text-[#B6BEC9]');
      countText.classList.add('text-red-400', 'font-semibold');
    }

    if (markAllBtn) markAllBtn.classList.remove('hidden');
  } else {
    dot.classList.add('hidden');
    dot.classList.remove('items-center');

    if (bell) bell.classList.remove('notif-has-new');

    if (countText) {
      countText.textContent = '';
      countText.classList.remove('text-red-400', 'font-semibold');
      countText.classList.add('text-[#B6BEC9]');
    }

    if (markAllBtn) markAllBtn.classList.add('hidden');
  }
}

function renderHeaderNotifications(notifications) {
  const list = document.getElementById('notifList');
  if (!list) return;

  if (!notifications || notifications.length === 0) {
    list.innerHTML = `
      <div class="text-center py-8 px-4">
        <div class="mx-auto mb-3 w-10 h-10 rounded-full bg-[#232937] flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#9CA3AF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </div>
        <p class="text-sm text-[#9CA3AF]">No notifications yet.</p>
      </div>
    `;
    return;
  }

  list.innerHTML = notifications.map(n => {
    const isUnread = Number(n.is_read) === 0;
    return `
      <div
        class="notif-item ${isUnread ? 'notif-item-new bg-[#2A3242] border-l-4 border-l-[#D99A2B]' : 'bg-[#252C3A] border-l-4 border-l-transparent'} p-3.5 border-b border-[#353F50] cursor-pointer hover:bg-[#303A4D] transition-colors"
        data-notification-id="${n.notification_id}"
      >
        <div class="flex items-start gap-3">
          <div class="w-3 shrink-0 pt-1 flex justify-center">
            ${isUnread ? '<span class="notif-new-dot" title="Unread"></span>' : '<span class="w-[9px] h-[9px] block"></span>'}
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
              <p class="text-sm leading-5 ${isUnread ? 'font-semibold text-[#F8FAFC]' : 'font-normal text-[#94A3B8]'}">
                ${escapeHtml(n.message)}
              </p>
              ${isUnread ? '<span class="notif-new-badge shrink-0 bg-[#D99A2B] text-[#111827] text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded-full">NEW</span>' : ''}
            </div>
            <p class="text-[11px] text-[#64748B] mt-1">
              ${headerTimeAgo(n.created_at)}
            </p>
          </div>
        </div>
      </div>
    `;
  }).join('');

  list.querySelectorAll('.notif-item').forEach(item => {
    item.addEventListener('click', async (e) => {
      e.stopPropagation();
      const id = item.dataset.notificationId;
      const notif = headerNotifications.find(n => String(n.notification_id) === String(id));
      if (notif && Number(notif.is_read) === 0) {
        await markSingleNotificationRead(id);
      }
    });
  });
}

async function markSingleNotificationRead(notificationId) {
  const notif = headerNotifications.find(n => String(n.notification_id) === String(notificationId));
  if (notif) {
    notif.is_read = 1;
  }
  const currentUnread = headerNotifications.filter(n => Number(n.is_read) === 0).length;
  updateHeaderNotificationCount(currentUnread);
  renderHeaderNotifications(headerNotifications);

  try {
    const res = await fetch('../../api/mark_notification_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ notification_id: notificationId })
    });
    const data = await res.json();
    if (data.success && typeof data.unread_count !== 'undefined') {
      updateHeaderNotificationCount(data.unread_count);
    }
  } catch (err) {
    console.error('Failed marking notification read:', err);
  }
}

async function markAllNotificationsRead() {
  headerNotifications.forEach(n => { n.is_read = 1; });
  updateHeaderNotificationCount(0);
  renderHeaderNotifications(headerNotifications);

  try {
    const res = await fetch('../../api/mark_all_notifications_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    if (data.success) {
      updateHeaderNotificationCount(0);
    }
  } catch (err) {
    console.error('Failed marking all notifications read:', err);
  }
}

async function pollRequestUpdates() {
  if (isPollingUpdates) return;
  isPollingUpdates = true;

  try {
    const res = await fetch('../../api/get_request_updates.php', { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();

    if (data.success) {
      if (data.notifications) {
        headerNotifications = data.notifications;
        updateHeaderNotificationCount(data.unread_count);
        renderHeaderNotifications(headerNotifications);
      }
      window.dispatchEvent(new CustomEvent('certiscan:updatesReceived', { detail: data }));
    }
  } catch (err) {
    console.warn('Background update check failed:', err);
  } finally {
    isPollingUpdates = false;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const bell = document.getElementById('notifBellBtn');
  const dropdown = document.getElementById('notifDropdown');
  const wrapper = document.getElementById('notificationWrapper');
  const markAllBtn = document.getElementById('markAllReadBtn');

  if (!bell || !dropdown) return;

  if (markAllBtn) {
    markAllBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      markAllNotificationsRead();
    });
  }

  bell.addEventListener('click', function (event) {
    event.stopPropagation();
    dropdown.classList.toggle('hidden');
  });

  dropdown.addEventListener('click', function (event) {
    event.stopPropagation();
  });

  document.addEventListener('click', function (event) {
    if (wrapper && !wrapper.contains(event.target)) {
      dropdown.classList.add('hidden');
    }
  });

  pollRequestUpdates();
  setInterval(pollRequestUpdates, 3000);

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      pollRequestUpdates();
    }
  });
});
</script>

