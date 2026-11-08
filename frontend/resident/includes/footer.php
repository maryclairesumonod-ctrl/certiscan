    </main>
  </div>
</div>

<!-- Notification detail modal — referenced by openHeaderNotifModal() in header.php -->
<div id="notifModal" class="hidden fixed inset-0 bg-slate-900/40 flex items-center justify-center p-4 z-50">
  <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-4">
    <div class="flex items-center justify-between">
      <p class="cs-eyebrow">Notification</p>
      <button id="closeNotifModalBtn" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
    </div>
    <p id="notifModalMessage" class="text-sm text-slate-700 leading-relaxed"></p>
    <p id="notifModalTime" class="text-xs text-slate-400"></p>
  </div>
</div>

<footer class="text-center text-xs text-slate-400 py-6">
  CertiScan &middot; Barangay Balongating, Guipos, Zamboanga Del Sur
</footer>

<script>
  document.getElementById('closeNotifModalBtn')?.addEventListener('click', () => {
    document.getElementById('notifModal').classList.add('hidden');
  });
  document.getElementById('notifModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'notifModal') e.target.classList.add('hidden');
  });
</script>

</body>
</html>
