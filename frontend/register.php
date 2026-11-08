<?php
require_once __DIR__ . '/../api/config.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>

<div class="relative min-h-[75vh] flex items-center justify-center overflow-hidden py-10">
  <div class="absolute -top-24 -left-24 w-72 h-72 bg-blue-200/40 rounded-full blur-3xl"></div>
  <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-sky-200/50 rounded-full blur-3xl"></div>

  <div class="relative w-full max-w-md">
    <div class="text-center mb-8">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-primary to-accent text-white flex items-center justify-center font-bold text-xl mb-4 shadow-lg shadow-primary/25">CS</div>
      <h1 class="text-2xl font-bold text-slate-900">Create a staff account</h1>
      <p class="text-sm text-slate-500 mt-1">Barangay Balongating Staff Registration</p>
    </div>

    <div class="bg-white/90 backdrop-blur border border-blue-100 rounded-2xl shadow-xl shadow-blue-100/50 p-7 space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
        <input type="text" id="s_full_name" required
               class="w-full border border-blue-200 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
        <input type="text" id="s_username" required
               class="w-full border border-blue-200 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input type="password" id="s_password" required minlength="6"
               class="w-full border border-blue-200 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
        <input type="password" id="s_confirm_password" required minlength="6"
               class="w-full border border-blue-200 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
        <select id="s_role"
                class="w-full border border-blue-200 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
          <option value="secretary">Secretary</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
        <div class="flex items-start gap-2.5">
          <input type="checkbox" id="agreeTerms" required
                 class="mt-1 w-4 h-4 rounded border-blue-300 text-primary focus:ring-primary/40 flex-shrink-0 cursor-pointer">
          <label for="agreeTerms" class="text-xs text-slate-600 leading-relaxed cursor-pointer">
            I agree to the
            <button type="button" id="openTermsBtn" class="text-primary font-medium hover:underline">Terms of Use</button>
            and consent to the collection and processing of my personal information in accordance with the
            <button type="button" id="openPrivacyBtn" class="text-primary font-medium hover:underline">Data Privacy Act of 2012 (RA 10173)</button>.
          </label>
        </div>
      </div>

      <p id="formError" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
      <p id="successMsg" class="text-sm text-green-600 hidden bg-green-50 border border-green-100 rounded-lg p-3"></p>

      <button type="button" id="submitBtn"
              class="w-full bg-primary text-white rounded-lg py-2.5 font-semibold hover:bg-primary-dark transition shadow-md shadow-primary/25">
        Create Account
      </button>

      <p class="text-center text-sm text-slate-500">
        Already have an account? <a href="login.php" class="text-primary font-medium hover:underline">Log in</a>
      </p>
    </div>
  </div>
</div>

<!-- Terms of Use modal -->
<div id="termsModal" class="hidden fixed inset-0 bg-slate-900/40 flex items-center justify-center p-4 z-20">
  <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-3 max-h-[80vh] overflow-y-auto">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Terms of Use</h2>
      <button type="button" class="closeModalBtn text-slate-400 hover:text-slate-600 text-xl">&times;</button>
    </div>
    <p class="text-sm text-slate-600 leading-relaxed">
      This account is provided for authorized Barangay Balongating personnel only, for the purpose of processing
      resident credential requests through the CertiScan system. Access is restricted to legitimate barangay
      duties, and account credentials must not be shared with unauthorized individuals. All actions taken within
      the system, including scans, reviews, and certificate generation, are logged and attributable to the staff
      account that performed them.
    </p>
  </div>
</div>

<!-- Data Privacy modal -->
<div id="privacyModal" class="hidden fixed inset-0 bg-slate-900/40 flex items-center justify-center p-4 z-20">
  <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-3 max-h-[80vh] overflow-y-auto">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Data Privacy Notice</h2>
      <button type="button" class="closeModalBtn text-slate-400 hover:text-slate-600 text-xl">&times;</button>
    </div>
    <p class="text-sm text-slate-600 leading-relaxed">
      In accordance with Republic Act 10173, the Data Privacy Act of 2012, the personal information you provide
      when creating this account — including your name and login credentials — is collected solely to identify
      authorized staff within the CertiScan system. This information is stored in the barangay's local database
      and is not shared with third parties.
    </p>
  </div>
</div>

<script>
document.getElementById('openTermsBtn').addEventListener('click', () => document.getElementById('termsModal').classList.remove('hidden'));
document.getElementById('openPrivacyBtn').addEventListener('click', () => document.getElementById('privacyModal').classList.remove('hidden'));
document.querySelectorAll('.closeModalBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('termsModal').classList.add('hidden');
    document.getElementById('privacyModal').classList.add('hidden');
  });
});

document.getElementById('submitBtn').addEventListener('click', async () => {
  const formError = document.getElementById('formError');
  const successMsg = document.getElementById('successMsg');
  const submitBtn = document.getElementById('submitBtn');
  formError.classList.add('hidden');
  successMsg.classList.add('hidden');

  const required = ['s_full_name','s_username','s_password','s_confirm_password'];
  for (const id of required) {
    if (!document.getElementById(id).value.trim()) {
      formError.textContent = 'Please fill in all required fields.';
      formError.classList.remove('hidden');
      return;
    }
  }
  if (document.getElementById('s_password').value.length < 6) {
    formError.textContent = 'Password must be at least 6 characters.';
    formError.classList.remove('hidden');
    return;
  }
  if (document.getElementById('s_password').value !== document.getElementById('s_confirm_password').value) {
    formError.textContent = 'Passwords do not match.';
    formError.classList.remove('hidden');
    return;
  }
  if (!document.getElementById('agreeTerms').checked) {
    formError.textContent = 'Please agree to the Terms of Use and Data Privacy Act consent before continuing.';
    formError.classList.remove('hidden');
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Creating account...';

  const registerForm = new FormData();
  registerForm.append('full_name', document.getElementById('s_full_name').value);
  registerForm.append('username', document.getElementById('s_username').value);
  registerForm.append('password', document.getElementById('s_password').value);
  registerForm.append('confirm_password', document.getElementById('s_confirm_password').value);
  registerForm.append('role', document.getElementById('s_role').value);

  const registerRes = await fetch('../api/register.php', { method: 'POST', body: registerForm });
  const registerData = await registerRes.json();

  if (registerData.success) {
    successMsg.textContent = 'Account created — redirecting to login...';
    successMsg.classList.remove('hidden');
    setTimeout(() => window.location.href = 'login.php', 1200);
  } else {
    formError.textContent = registerData.message || 'Registration failed';
    formError.classList.remove('hidden');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Create Account';
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>