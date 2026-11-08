<?php
require_once __DIR__ . '/../api/auth_guard.php';
$pageTitle = 'New Scan';
require __DIR__ . '/includes/header.php';
?>
<div class="max-w-xl mx-auto">
  <p class="cs-eyebrow mb-1">Document Intake</p>
  <h1 class="text-2xl font-bold text-slate-900 mb-1">Scan a resident's ID</h1>
  <p class="text-slate-500 text-sm mb-6">Capture or upload a clear photo of a valid ID or birth certificate. You'll review and correct the extracted details on the next screen.</p>

  <!-- Mode toggle: pill switch (Camera first) -->
  <div class="bg-blue-50 rounded-xl p-1 flex gap-1 mb-4">
    <button type="button" id="cameraModeBtn"
            class="flex-1 rounded-lg py-2.5 text-sm font-semibold transition bg-white text-primary shadow-sm">
      Use Camera
    </button>
    <button type="button" id="fileModeBtn"
            class="flex-1 rounded-lg py-2.5 text-sm font-semibold transition text-slate-500 hover:text-primary">
      Upload File
    </button>
  </div>

  <form id="scanForm" class="bg-white border border-blue-100 rounded-xl p-6 space-y-4">

    <!-- Camera capture mode (default/first) -->
    <div id="cameraMode" class="space-y-3">
      <div class="relative border-2 border-blue-200 rounded-xl overflow-hidden bg-slate-900" style="aspect-ratio: 4/3;">
        <video id="cameraVideo" class="absolute inset-0 w-full h-full object-contain hidden" autoplay playsinline muted></video>
        <img id="capturedPreview" class="absolute inset-0 w-full h-full object-contain hidden">
        <div id="cameraStatus" class="absolute inset-0 flex items-center justify-center px-6 text-slate-300 text-sm text-center">
          Click "Start Camera" below
        </div>
      </div>
      <canvas id="captureCanvas" class="hidden"></canvas>

      <div class="flex gap-2">
        <button type="button" id="startCameraBtn"
                class="flex-1 bg-white border border-blue-200 text-slate-700 rounded-lg py-2.5 text-sm font-medium hover:bg-blue-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
          Start Camera
        </button>
        <button type="button" id="captureBtn" disabled
                class="hidden flex-1 bg-primary text-white rounded-lg py-2.5 text-sm font-medium hover:bg-primary-dark transition disabled:opacity-40 disabled:cursor-not-allowed">
          Capture Photo
        </button>
        <button type="button" id="retakeBtn" class="hidden flex-1 bg-white border border-blue-200 text-slate-700 rounded-lg py-2.5 text-sm font-medium hover:bg-blue-50 transition">
          Retake
        </button>
      </div>
      <p id="cameraError" class="text-sm text-red-600 hidden bg-red-50 border border-red-100 rounded-lg p-3"></p>
      <p id="cameraDiagnostic" class="text-xs text-slate-400"></p>
    </div>

    <!-- File upload mode (hidden by default) -->
    <div id="fileMode" class="hidden">
      <label for="id_image"
             class="flex flex-col items-center justify-center border-2 border-dashed border-blue-200 rounded-lg h-56 cursor-pointer hover:border-primary transition overflow-hidden">
        <img id="preview" class="hidden w-full h-full object-contain">
        <span id="placeholderText" class="text-slate-400 text-sm">Click to choose an image (JPG, PNG, max 4MB)</span>
      </label>
      <input type="file" id="id_image" name="id_image" accept="image/*" class="hidden">
    </div>

    <p id="errorMsg" class="text-sm text-red-600 hidden"></p>
    <button type="submit" id="submitBtn"
            class="w-full bg-primary text-white rounded-lg py-2.5 font-medium hover:bg-primary-dark transition disabled:opacity-50">
      Upload &amp; extract data
    </button>
  </form>
</div>

<script>
// ---------- Mode toggle ----------
const fileModeBtn = document.getElementById('fileModeBtn');
const cameraModeBtn = document.getElementById('cameraModeBtn');
const fileMode = document.getElementById('fileMode');
const cameraMode = document.getElementById('cameraMode');
let activeMode = 'camera';

const activeTabClass = 'flex-1 rounded-lg py-2.5 text-sm font-semibold transition bg-white text-primary shadow-sm';
const inactiveTabClass = 'flex-1 rounded-lg py-2.5 text-sm font-semibold transition text-slate-500 hover:text-primary';

function switchMode(mode) {
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
fileModeBtn.addEventListener('click', () => switchMode('file'));
cameraModeBtn.addEventListener('click', () => switchMode('camera'));

// ---------- File upload preview ----------
const fileInput = document.getElementById('id_image');
const preview = document.getElementById('preview');
const placeholderText = document.getElementById('placeholderText');
fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (!file) return;
  preview.src = URL.createObjectURL(file);
  preview.classList.remove('hidden');
  placeholderText.classList.add('hidden');
});

// ---------- Camera capture ----------
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
      friendly = 'Camera permission was denied. Click the camera/lock icon in your browser\'s address bar to allow access, then try again.';
    } else if (err.name === 'NotFoundError') {
      friendly = 'No camera was found on this device.';
    } else if (err.name === 'NotReadableError') {
      friendly = 'The camera is already in use by another application. Close other apps using the camera and try again.';
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

// ---------- Submit ----------
document.getElementById('scanForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  const errorMsg = document.getElementById('errorMsg');
  errorMsg.classList.add('hidden');

  const formData = new FormData();

  if (activeMode === 'camera') {
    if (!capturedBlob) {
      errorMsg.textContent = 'Please capture a photo first.';
      errorMsg.classList.remove('hidden');
      return;
    }
    formData.append('id_image', capturedBlob, 'camera_capture.jpg');
  } else {
    if (!fileInput.files[0]) {
      errorMsg.textContent = 'Please choose an image file first.';
      errorMsg.classList.remove('hidden');
      return;
    }
    formData.append('id_image', fileInput.files[0]);
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Uploading...';

  try {
    const res = await fetch('../api/upload_scan.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      stopCamera();
      window.location.href = 'review.php?scan_id=' + data.scan_id;
    } else {
      errorMsg.textContent = data.message || 'Upload failed';
      errorMsg.classList.remove('hidden');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Upload & extract data';
    }
  } catch (err) {
    errorMsg.textContent = 'Something went wrong. Please try again.';
    errorMsg.classList.remove('hidden');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Upload & extract data';
  }
});

runCameraDiagnostic();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
