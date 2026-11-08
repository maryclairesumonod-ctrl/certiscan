<?php
require_once __DIR__ . '/../../api/resident_auth_guard.php';
$pageTitle = 'Notifications';
require __DIR__ . '/includes/header.php';
?>
<div class="flex items-center justify-between mb-6">
  <div>
    <p class="cs-eyebrow mb-1">Updates</p>
    <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
  </div>
  <div class="flex items-center gap-2 text-xs text-slate-400">
    <span id="liveDot" class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
    <span>Checking for updates automatically</span>
  </div>
</div>

<div id="newBanner" class="hidden bg-blue-50 border border-blue-200 text-primary text-sm font-medium rounded-xl px-4 py-3 mb-4 cursor-pointer hover:bg-blue-100 transition">
  🔔 New notification received — click to view
</div>

<div id="notifList" class="space-y-3">
  <p class="text-slate-400 text-sm">Loading...</p>
</div>

<script>
let seenNotificationIds = new Set();
let isFirstLoad = true;
const POLL_INTERVAL_MS = 15000; // check for new notifications every 15 seconds

function timeAgo(dateString) {
  const then = new Date(dateString.replace(' ', 'T'));
  const seconds = Math.floor((new Date() - then) / 1000);
  if (seconds < 60) return 'Just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes} min${minutes === 1 ? '' : 's'} ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days} day${days === 1 ? '' : 's'} ago`;
  return dateString;
}

function renderNotifications(notifications, newIds) {
  const container = document.getElementById('notifList');

  if (!notifications || notifications.length === 0) {
    container.innerHTML = '<p class="text-slate-400 text-sm">No notifications yet.</p>';
    return;
  }

  container.innerHTML = notifications.map(n => {
    const isNew = newIds.has(n.notification_id);
    return `
      <div class="border border-[#353F50] ${isNew ? 'bg-[#2A3242] border-l-4 border-l-[#D99A2B]' : 'bg-[#252C3A]'} rounded-xl p-4 transition-all ${isNew ? 'animate-[pulseIn_0.6s_ease]' : ''}">
        <div class="flex items-start justify-between gap-3">
          <p class="text-sm text-[#F8FAFC]">${n.message}</p>
          ${isNew ? '<span class="shrink-0 bg-[#D99A2B] text-[#111827] text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full">New</span>' : ''}
        </div>
        <p class="text-xs text-[#B6BEC9] mt-1">${timeAgo(n.created_at)}</p>
      </div>
    `;
  }).join('');
}

async function loadNotifications({ silent = false } = {}) {
  const res = await fetch('../../api/get_notifications.php');
  const data = await res.json();
  const notifications = data.notifications || [];

  const currentIds = new Set(notifications.map(n => n.notification_id));
  const newIds = new Set();

  if (!isFirstLoad) {
    // Anything present now that wasn't in the previously seen set is new
    currentIds.forEach(id => {
      if (!seenNotificationIds.has(id)) newIds.add(id);
    });

    if (newIds.size > 0 && silent) {
      // A background poll found something new — show the banner instead
      // of silently swapping the list out from under the resident.
      const banner = document.getElementById('newBanner');
      banner.classList.remove('hidden');
      banner.onclick = () => {
        banner.classList.add('hidden');
        renderNotifications(notifications, newIds);
        seenNotificationIds = currentIds;
      };
      return; // don't re-render the list yet — wait for the resident to click the banner
    }
  }

  renderNotifications(notifications, newIds);
  seenNotificationIds = currentIds;
  isFirstLoad = false;
}

// Initial load shows everything without the "New" flash
loadNotifications();

// Background polling — checks quietly, surfaces a banner instead of
// yanking the list out from under someone who's mid-read.
setInterval(() => loadNotifications({ silent: true }), POLL_INTERVAL_MS);

// Also re-check immediately whenever the resident comes back to this
// tab (e.g. switched away and back), so it doesn't feel stale.
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    loadNotifications({ silent: true });
  }
});
</script>

<style>
  @keyframes pulseIn {
    0% { background-color: #eff6ff; }
    100% { background-color: #ffffff; }
  }
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
