<?php
require_once __DIR__ . '/../../api/config.php';
if (!empty($_SESSION['resident_id'])) { header('Location: dashboard.php'); exit; }
$pageTitle = 'Create Account';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-lg mx-auto">
  <div class="text-center mb-8">
    <img src="../img/logo.jpg" alt="CertiScan logo" class="w-16 h-16 mx-auto block object-cover rounded-full shadow-md ring-4 ring-blue-50 mb-4">
    <h1 class="text-2xl font-bold text-slate-900">Create your resident account</h1>
    <p class="text-slate-500 text-sm mt-1">Fill in your details, then verify with a valid ID.</p>
  </div>

  <div class="flex items-center justify-center gap-2 mb-6 text-xs font-medium">
    <span id="stepDot1" class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center">1</span>
    <span class="w-8 h-0.5 bg-blue-200"></span>
    <span id="stepDot2" class="w-7 h-7 rounded-full bg-blue-100 text-slate-400 flex items-center justify-center">2</span>
    <span class="w-8 h-0.5 bg-blue-200"></span>
    <span id="stepDot3" class="w-7 h-7 rounded-full bg-blue-100 text-slate-400 flex items-center justify-center">3</span>
  </div>

  <!-- Step 1: Basic info form -->
  <div id="step1" class="bg-white border border-blue-100 rounded-2xl shadow-sm shadow-blue-100/50 p-6 space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">First name</label>
        <input type="text" id="r_first_name" required class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Middle name</label>
        <input type="text" id="r_middle_name" class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Last name</label>
        <input type="text" id="r_last_name" required class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
      <input type="text" id="r_address" required class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Birthdate</label>
        <input type="date" id="r_birthdate" required class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Civil status</label>
        <select id="r_civil_status" class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="">Select</option>
          <option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option><option>Divorced</option>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Place of birth</label>
      <input type="text" id="r_place_of_birth" class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <hr class="border-blue-100">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
      <input type="text" id="r_username" required class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Email (optional)</label>
      <input type="email" id="r_email" class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Mobile number</label>
      <input type="tel" id="r_phone" required placeholder="09XXXXXXXXX" maxlength="11"
             autocomplete="tel"
             class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-slate-900 bg-white placeholder-slate-400"
             style="color: #0f172a !important; background-color: #ffffff !important; -webkit-text-security: none !important;">
      <p class="text-xs text-slate-400 mt-1">We'll text you when your certificate is ready for pickup.</p>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
      <input type="password" id="r_password" minlength="6" required class="w-full border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div class="flex items-start gap-2 pt-1">
      <input type="checkbox" id="agreeTerms" required
             class="mt-1 w-4 h-4 rounded border-blue-300 text-primary focus:ring-primary/40">
      <label for="agreeTerms" class="text-xs text-slate-500 leading-relaxed">
        I agree to the <a href="#" class="text-primary hover:underline">Terms of Use</a> and consent to the collection and processing of my personal data in accordance with the
        <a href="#" class="text-primary hover:underline">Data Privacy Act of 2012 (RA 10173)</a>.
      </label>
    </div>

    <p id="step1Error" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
    <button type="button" id="toStep2Btn" class="w-full bg-primary text-white rounded-lg py-3 font-medium hover:bg-primary-dark transition">
      Continue
    </button>
    <p class="text-center text-sm text-slate-500">
      Already have an account? <a href="login.php" class="text-primary font-medium hover:underline">Log in</a>
    </p>
  </div>

  <!-- Step 2: Summary -->
  <div id="step2" class="hidden bg-white border border-blue-100 rounded-2xl shadow-sm shadow-blue-100/50 p-6 space-y-4">
    <p class="text-sm text-slate-500 mb-2">Please review your details before continuing.</p>
    <div id="summaryBox" class="bg-blue-50 rounded-xl p-4 space-y-2 text-sm"></div>
    <div class="flex gap-2">
      <button type="button" id="backToStep1BtnFromStep2" class="flex-1 bg-white border border-blue-200 text-slate-700 rounded-lg py-2.5 font-medium hover:bg-blue-50 transition">
        &larr; Back
      </button>
      <button type="button" id="toStep3Btn" class="flex-1 bg-primary text-white rounded-lg py-2.5 font-medium hover:bg-primary-dark transition">
        Confirm &amp; Continue
      </button>
    </div>
  </div>

  <!-- Step 3: Upload/Capture ID for verification -->
  <div id="step3" class="hidden bg-white border border-blue-100 rounded-2xl shadow-sm shadow-blue-100/50 p-6 space-y-4">
    <p class="text-sm text-slate-500">Capture or upload a photo of a valid ID. We'll check that it matches what you entered.</p>

    <!-- Mode toggle: pill switch (Camera first, best on phones) -->
    <div class="bg-blue-50 rounded-xl p-1 flex gap-1">
      <button type="button" id="cameraModeBtn"
              class="flex-1 rounded-lg py-2.5 text-sm font-semibold transition bg-white text-primary shadow-sm">
        Use Camera
      </button>
      <button type="button" id="fileModeBtn"
              class="flex-1 rounded-lg py-2.5 text-sm font-semibold transition text-slate-500 hover:text-primary">
        Upload File
      </button>
    </div>

    <!-- Camera capture mode (default/first) -->
    <div id="cameraMode" class="space-y-3">
      <div class="relative border-2 border-blue-200 rounded-xl overflow-hidden bg-slate-900" style="aspect-ratio: 4/3;">
        <video id="cameraVideo" class="absolute inset-0 w-full h-full object-contain hidden" autoplay playsinline muted></video>
        <img id="capturedPreview" class="absolute inset-0 w-full h-full object-contain hidden">
        <div id="cameraStatus" class="absolute inset-0 flex items-center justify-center px-6 text-slate-300 text-sm text-center">
          Tap "Start Camera" below
        </div>
      </div>
      <canvas id="captureCanvas" class="hidden"></canvas>

      <div class="flex flex-col sm:flex-row gap-2">
        <button type="button" id="startCameraBtn"
                class="flex-1 bg-white border border-blue-200 text-slate-700 rounded-lg py-3 sm:py-2.5 text-sm font-medium hover:bg-blue-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
          Start Camera
        </button>
        <button type="button" id="captureBtn" disabled
                class="hidden flex-1 bg-primary text-white rounded-lg py-3 sm:py-2.5 text-sm font-medium hover:bg-primary-dark transition disabled:opacity-40 disabled:cursor-not-allowed">
          Capture Photo
        </button>
        <button type="button" id="retakeBtn" class="hidden flex-1 bg-white border border-blue-200 text-slate-700 rounded-lg py-3 sm:py-2.5 text-sm font-medium hover:bg-blue-50 transition">
          Retake
        </button>
      </div>
      <p id="cameraError" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
      <p id="cameraDiagnostic" class="text-xs text-slate-400"></p>
    </div>

    <!-- File upload mode (hidden by default) -->
    <div id="fileMode" class="hidden">
      <label for="id_image"
             class="flex flex-col items-center justify-center border-2 border-dashed border-blue-200 rounded-xl h-52 cursor-pointer hover:border-primary hover:bg-blue-50/50 transition overflow-hidden">
        <img id="preview" class="hidden w-full h-full object-contain">
        <div id="placeholderContent" class="flex flex-col items-center px-4 text-center">
          <span class="text-sm font-medium text-slate-500">Tap to choose an image of your ID</span>
          <span class="text-xs text-slate-400 mt-1">JPG or PNG, max 1MB</span>
        </div>
      </label>
      <input type="file" id="id_image" accept="image/*" class="hidden">
    </div>

    <div id="nameComparisonBox" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm"></div>
    <div id="addressComparisonBox" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm"></div>
    <div id="birthdateComparisonBox" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm"></div>

    <p id="verifyError" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
    <p id="registerError" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>

    <div class="flex flex-col sm:flex-row gap-2">
      <button type="button" id="backToStep2BtnFromStep3" class="flex-1 bg-white border border-blue-200 text-slate-700 rounded-lg py-3 font-medium hover:bg-blue-50 transition">
        &larr; Back
      </button>
      <button type="button" id="verifyBtn" class="flex-1 bg-primary text-white rounded-lg py-3 font-medium hover:bg-primary-dark transition disabled:opacity-50">
        Verify &amp; Create Account
      </button>
    </div>
  </div>

  <!-- Success -->
  <div id="stepSuccess" class="hidden bg-green-50 border border-green-200 rounded-2xl p-6 text-center space-y-3">
    <p class="text-green-700 font-medium">Account created and verified!</p>
    <p class="text-sm text-green-600">You can log in now and start requesting certificates right away.</p>
    <a href="login.php" class="inline-block bg-primary text-white rounded-lg px-5 py-2.5 font-medium hover:bg-primary-dark transition">Go to Login</a>
  </div>
</div>

<script>
function setStep(n) {
  ['step1','step2','step3'].forEach((id, i) => {
    document.getElementById(id).classList.toggle('hidden', i !== n - 1);
  });
  [1,2,3].forEach(i => {
    document.getElementById('stepDot' + i).className = i <= n
      ? 'w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center'
      : 'w-7 h-7 rounded-full bg-blue-100 text-slate-400 flex items-center justify-center';
  });
  if (n === 3) runCameraDiagnostic();
}

function getFullName() {
  const first = document.getElementById('r_first_name').value.trim();
  const middle = document.getElementById('r_middle_name').value.trim();
  const last = document.getElementById('r_last_name').value.trim();
  return [first, middle, last].filter(Boolean).join(' ');
}

document.getElementById('toStep2Btn').addEventListener('click', () => {
  const step1Error = document.getElementById('step1Error');
  step1Error.classList.add('hidden');

  const required = ['r_first_name','r_last_name','r_address','r_birthdate','r_username','r_phone','r_password'];
  for (const id of required) {
    if (!document.getElementById(id).value.trim()) {
      step1Error.textContent = 'Please fill in all required fields.';
      step1Error.classList.remove('hidden');
      return;
    }
  }
  if (document.getElementById('r_password').value.length < 6) {
    step1Error.textContent = 'Password must be at least 6 characters.';
    step1Error.classList.remove('hidden');
    return;
  }

  const phoneVal = document.getElementById('r_phone').value.trim();
  if (!/^09\d{9}$/.test(phoneVal)) {
    step1Error.textContent = 'Please enter a valid 11-digit Philippine mobile number starting with 09 (e.g., 09123456789).';
    step1Error.classList.remove('hidden');
    return;
  }

  if (!document.getElementById('agreeTerms').checked) {
    step1Error.textContent = 'Please agree to the Terms of Use and Data Privacy Act consent before continuing.';
    step1Error.classList.remove('hidden');
    return;
  }

  const summaryBox = document.getElementById('summaryBox');
  summaryBox.innerHTML = `
    <p><span class="text-slate-500">Full name:</span> <b>${getFullName()}</b></p>
    <p><span class="text-slate-500">Address:</span> <b>${document.getElementById('r_address').value}</b></p>
    <p><span class="text-slate-500">Birthdate:</span> <b>${document.getElementById('r_birthdate').value}</b></p>
    <p><span class="text-slate-500">Civil status:</span> <b>${document.getElementById('r_civil_status').value || '—'}</b></p>
    <p><span class="text-slate-500">Place of birth:</span> <b>${document.getElementById('r_place_of_birth').value || '—'}</b></p>
    <p><span class="text-slate-500">Username:</span> <b>${document.getElementById('r_username').value}</b></p>
    <p><span class="text-slate-500">Mobile number:</span> <b>${document.getElementById('r_phone').value}</b></p>
  `;
  setStep(2);
});

document.getElementById('backToStep1BtnFromStep2').addEventListener('click', () => setStep(1));

// ---------- Format mobile number on input (digits only, 09XXXXXXXXX format) ----------
const rPhoneInput = document.getElementById('r_phone');
if (rPhoneInput) {
  rPhoneInput.addEventListener('input', (e) => {
    let val = e.target.value.replace(/\D/g, '');
    if (val.startsWith('639')) {
      val = '09' + val.slice(3);
    } else if (val.startsWith('63')) {
      val = '0' + val.slice(2);
    }
    if (val.length > 11) {
      val = val.slice(0, 11);
    }
    e.target.value = val;
  });
}
document.getElementById('toStep3Btn').addEventListener('click', () => setStep(3));
document.getElementById('backToStep2BtnFromStep3').addEventListener('click', () => { stopCamera(); setStep(2); });

// ---------- Step 3 mode toggle (Camera / File) ----------
const fileModeBtn = document.getElementById('fileModeBtn');
const cameraModeBtn = document.getElementById('cameraModeBtn');
const fileMode = document.getElementById('fileMode');
const cameraMode = document.getElementById('cameraMode');
let activeMode = 'camera'; // camera is the default, best for phones

const activeTabClass = 'flex-1 rounded-lg py-2.5 text-sm font-semibold transition bg-white text-primary shadow-sm';
const inactiveTabClass = 'flex-1 rounded-lg py-2.5 text-sm font-semibold transition text-slate-500 hover:text-primary';

function switchIdMode(mode) {
  if (mode === activeMode) return;
  activeMode = mode;
  if (mode === 'file') {
    fileMode.classList.remove('hidden');
    cameraMode.classList.add('hidden');
    fileModeBtn.className = activeTabClass;
    cameraModeBtn.className = inactiveTabClass;
    stopCamera();
  } else {
    fileMode.classList.add('hidden');
    cameraMode.classList.remove('hidden');
    cameraModeBtn.className = activeTabClass;
    fileModeBtn.className = inactiveTabClass;
    runCameraDiagnostic();
  }
}
fileModeBtn.addEventListener('click', () => switchIdMode('file'));
cameraModeBtn.addEventListener('click', () => switchIdMode('camera'));

// ---------- File upload preview ----------
const fileInput = document.getElementById('id_image');
const preview = document.getElementById('preview');
const placeholderContent = document.getElementById('placeholderContent');
fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (!file) return;
  preview.src = URL.createObjectURL(file);
  preview.classList.remove('hidden');
  placeholderContent.classList.add('hidden');
});

// ---------- Camera capture (rear camera by default, ideal for phones) ----------
const cameraVideo = document.getElementById('cameraVideo');
const capturedPreview = document.getElementById('capturedPreview');
const cameraStatus = document.getElementById('cameraStatus');
const captureCanvas = document.getElementById('captureCanvas');
const startCameraBtn = document.getElementById('startCameraBtn');
const captureBtn = document.getElementById('captureBtn');
const retakeBtn = document.getElementById('retakeBtn');
const cameraError = document.getElementById('cameraError');
const cameraDiagnostic = document.getElementById('cameraDiagnostic');

let cameraStream = null;
let cameraStarting = false;
let capturedBlob = null;

function setCameraView(view) {
  cameraStatus.classList.toggle('hidden', view !== 'status');
  cameraVideo.classList.toggle('hidden', view !== 'video');
  capturedPreview.classList.toggle('hidden', view !== 'captured');
}
function setCameraButtons(view) {
  startCameraBtn.classList.toggle('hidden', view !== 'idle');
  captureBtn.classList.toggle('hidden', view !== 'live');
  retakeBtn.classList.toggle('hidden', view !== 'captured');
  captureBtn.disabled = view !== 'live';
}

function runCameraDiagnostic() {
  if (activeMode !== 'camera') return;
  cameraDiagnostic.textContent = '';
  const hasMediaDevices = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  if (!hasMediaDevices) {
    cameraError.textContent = 'Camera access requires viewing this page via "http://localhost/..." or "https://". Please use "Upload File" instead.';
    cameraError.classList.remove('hidden');
    startCameraBtn.disabled = true;
  } else {
    cameraError.classList.add('hidden');
    startCameraBtn.disabled = false;
  }
}

async function startCamera() {
  if (cameraStream || cameraStarting) return;
  cameraStarting = true;
  cameraError.classList.add('hidden');
  startCameraBtn.disabled = true;

  try {
    // facingMode "environment" opens the rear camera by default on phones,
    // which is what you want for photographing an ID clearly.
    cameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
    });
    cameraVideo.srcObject = cameraStream;
    await cameraVideo.play();
    setCameraView('video');
    setCameraButtons('live');
  } catch (err) {
    let friendly = err.message;
    if (err.name === 'NotAllowedError') {
      friendly = 'Camera permission was denied. Allow camera access in your browser settings, then try again.';
    } else if (err.name === 'NotFoundError') {
      friendly = 'No camera was found on this device.';
    } else if (err.name === 'NotReadableError') {
      friendly = 'The camera is already in use by another app. Close it and try again.';
    }
    cameraError.textContent = friendly + ' You can still use "Upload File" instead.';
    cameraError.classList.remove('hidden');
    startCameraBtn.disabled = false;
  } finally {
    cameraStarting = false;
  }
}

function stopCamera() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
  setCameraView('status');
  setCameraButtons('idle');
  startCameraBtn.disabled = false;
}

startCameraBtn.addEventListener('click', startCamera);

captureBtn.addEventListener('click', () => {
  const ctx = captureCanvas.getContext('2d');
  captureCanvas.width = cameraVideo.videoWidth;
  captureCanvas.height = cameraVideo.videoHeight;
  ctx.drawImage(cameraVideo, 0, 0);

  captureCanvas.toBlob((blob) => {
    capturedBlob = blob;
    capturedPreview.src = URL.createObjectURL(blob);
    setCameraView('captured');
    setCameraButtons('captured');
    if (cameraStream) {
      cameraStream.getTracks().forEach(track => track.stop());
      cameraStream = null;
    }
  }, 'image/jpeg', 0.92);
});

retakeBtn.addEventListener('click', () => {
  capturedBlob = null;
  startCamera();
});

window.addEventListener('beforeunload', stopCamera);

function renderComparisonChips(containerId, title, wordComparison) {
  const box = document.getElementById(containerId);
  if (!wordComparison || wordComparison.length === 0) {
    box.classList.add('hidden');
    return;
  }
  const chips = wordComparison.map(w => `
    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium mr-1 mb-1 ${w.matched ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
      ${w.matched ? '✓' : '✗'} ${w.word}
    </span>
  `).join('');
  box.innerHTML = `<p class="text-xs font-medium text-slate-500 mb-2">${title}</p>${chips}`;
  box.classList.remove('hidden');
}

document.getElementById('verifyBtn').addEventListener('click', async () => {
  const verifyError = document.getElementById('verifyError');
  const registerError = document.getElementById('registerError');
  const verifyBtn = document.getElementById('verifyBtn');
  verifyError.classList.add('hidden');
  registerError.classList.add('hidden');

  const formData = new FormData();

  if (activeMode === 'camera') {
    if (!capturedBlob) {
      verifyError.textContent = 'Please capture a photo of your ID first.';
      verifyError.classList.remove('hidden');
      return;
    }
    formData.append('id_image', capturedBlob, 'id_capture.jpg');
  } else {
    if (!fileInput.files[0]) {
      verifyError.textContent = 'Please choose an ID image first.';
      verifyError.classList.remove('hidden');
      return;
    }
    formData.append('id_image', fileInput.files[0]);
  }

  verifyBtn.disabled = true;
  verifyBtn.textContent = 'Verifying...';

  formData.append('full_name', getFullName());
  formData.append('address', document.getElementById('r_address').value);
  formData.append('birthdate', document.getElementById('r_birthdate').value);
  formData.append('civil_status', document.getElementById('r_civil_status').value);

  const verifyRes = await fetch('../../api/resident_verify_id.php', { method: 'POST', body: formData });
  const verifyData = await verifyRes.json();

  renderComparisonChips('nameComparisonBox', 'Name comparison (word-by-word against your ID)', verifyData.nameComparison);
  renderComparisonChips('addressComparisonBox', 'Address comparison (word-by-word against your ID)', verifyData.addressComparison);
  renderComparisonChips('birthdateComparisonBox', 'Birthdate comparison (against your ID)', verifyData.birthdateComparison);

  if (!verifyData.success) {
    const details = (verifyData.mismatches || [])
      .filter(m => !['name', 'address', 'birthdate'].includes(m.field))
      .map(m => `<div class="mt-1"><b>${m.label}</b> — you entered "${m.entered || '(blank)'}", but your ID shows "${m.found}"</div>`)
      .join('');
    verifyError.innerHTML = (verifyData.message || 'Verification failed.') + details;
    verifyError.classList.remove('hidden');
    verifyBtn.disabled = false;
    verifyBtn.textContent = 'Verify & Create Account';
    return;
  }

  verifyBtn.textContent = 'Creating account...';
  const payload = {
    scan_id: verifyData.scan_id,
    full_name: getFullName(),
    address: document.getElementById('r_address').value,
    birthdate: document.getElementById('r_birthdate').value,
    civil_status: document.getElementById('r_civil_status').value,
    place_of_birth: document.getElementById('r_place_of_birth').value,
    username: document.getElementById('r_username').value,
    email: document.getElementById('r_email').value,
    phone_number: document.getElementById('r_phone').value,
    password: document.getElementById('r_password').value,
    id_verified: true,
  };

  const registerRes = await fetch('../../api/resident_register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const registerData = await registerRes.json();

  if (registerData.success) {
    stopCamera();
    document.getElementById('step3').classList.add('hidden');
    document.getElementById('stepSuccess').classList.remove('hidden');
  } else {
    registerError.textContent = registerData.message || 'Registration failed';
    registerError.classList.remove('hidden');
    verifyBtn.disabled = false;
    verifyBtn.textContent = 'Verify & Create Account';
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
