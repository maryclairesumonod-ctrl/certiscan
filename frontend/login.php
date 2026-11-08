<?php
require_once __DIR__ . '/../api/config.php';
if (!empty($_SESSION['staff_id'])) {
    header('Location: dashboard.php');
    exit;
}
$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<style>
  .login-font { font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Inter', sans-serif; }
</style>
<div class="min-h-screen flex items-center justify-center p-4 login-font" style="background: #0000;">
  <div class="w-full max-w-md bg-white rounded-3xl shadow-xl shadow-blue-200/50 border border-blue-100/60 p-10">
    <div class="flex flex-col items-center mb-8">
      <img src="img/logo.jpg" alt="CertiScan logo" class="w-16 h-16 object-cover rounded-full shadow-md ring-4 ring-blue-50 mb-4">
      <p class="text-[11px] font-bold tracking-[0.15em] uppercase text-slate-400 text-center">
        Republic of the Philippines &middot; Barangay Balongating, Guipos, ZDS
      </p>
      <p class="text-[11px] font-bold tracking-[0.15em] uppercase text-primary mt-1">
        Secretary Login
      </p>
    </div>

    <form id="loginForm" class="space-y-5">
      <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5 ml-1">Username</label>
        <input
          type="text"
          name="username"
          required
          placeholder="Enter your username"
          class="w-full px-5 py-3.5 bg-slate-50 rounded-xl text-slate-800 text-[15px] placeholder:text-slate-400 placeholder:font-normal border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary focus:bg-white transition"
        >
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5 ml-1">Password</label>
        <input
          type="password"
          name="password"
          required
          placeholder="Enter your password"
          class="w-full px-5 py-3.5 bg-slate-50 rounded-xl text-slate-800 text-[15px] placeholder:text-slate-400 placeholder:font-normal border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary focus:bg-white transition"
        >
      </div>

      <p id="errorMsg" class="text-sm font-medium text-red-600 hidden text-center bg-red-50 border border-red-100 rounded-lg py-2"></p>

      <button
        type="submit"
        class="w-full py-3.5 bg-gradient-to-r from-primary to-primary-dark hover:from-primary-dark hover:to-primary text-white font-semibold text-[15px] tracking-wide rounded-xl shadow-lg shadow-primary/25 hover:shadow-primary/35 transition-all duration-200"
      >
        LOGIN
      </button>
    </form>

    <p class="text-center text-xs text-slate-400 font-medium mt-6">Authorized barangay personnel only.</p>
  </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const errorMsg = document.getElementById('errorMsg');
  errorMsg.classList.add('hidden');
  const res = await fetch('../api/login.php', {
    method: 'POST',
    body: new FormData(form),
  });
  const data = await res.json();
  if (data.success) {
    window.location.href = data.redirect;
  } else {
    errorMsg.textContent = data.message || 'Login failed';
    errorMsg.classList.remove('hidden');
  }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
