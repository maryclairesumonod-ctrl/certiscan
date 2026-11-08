<?php
require_once __DIR__ . '/../api/auth_guard.php';

$requestId = (int)($_GET['request_id'] ?? 0);
if (!$requestId) {
    header('Location: certificates.php');
    exit;
}

$stmt = $pdo->prepare('SELECT request_id, cert_number, pdf_path, status FROM certificate_requests WHERE request_id = ?');
$stmt->execute([$requestId]);
$cert = $stmt->fetch();

if (!$cert) {
    header('Location: certificates.php');
    exit;
}

$pageTitle = 'Print Certificate';
require __DIR__ . '/includes/header.php';
?>

<style>
  @media print {
    .cs-sidebar, .cs-topbar, footer, .no-print { display: none !important; }
  }
</style>

<div class="no-print flex items-center justify-between mb-4">
  <div class="flex items-center gap-3">
    <a href="javascript:history.back()"
       class="w-9 h-9 flex items-center justify-center rounded-lg border border-blue-200 text-slate-500 hover:bg-blue-50 hover:text-slate-700 transition shrink-0" title="Back">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
    </a>
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Print Certificate</h1>
      <p class="text-slate-500 text-sm">Cert No. <?= htmlspecialchars($cert['cert_number']) ?></p>
    </div>
  </div>
  <?php if ($cert['pdf_path']): ?>
  <div class="flex gap-3">
    <a href="../api/download_certificate.php?request_id=<?= $requestId ?>&t=<?= time() ?>"
       class="bg-white border border-blue-200 text-slate-700 px-4 py-2.5 rounded-lg font-medium hover:bg-blue-50 transition">
      Download .docx
    </a>
    <a href="../api/serve_certificate_pdf.php?request_id=<?= $requestId ?>&t=<?= time() ?>" target="_blank"
       class="bg-primary text-white px-5 py-2.5 rounded-lg font-medium hover:bg-primary-dark transition">
      Open PDF &amp; Print
    </a>
  </div>
  <?php endif; ?>
</div>

<?php if (!$cert['pdf_path']): ?>
  <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-6 text-center">
    <p class="font-medium">This certificate hasn't been generated yet.</p>
    <p class="text-sm mt-1">The document is created automatically once the request is approved in Manage Requests.</p>
  </div>
<?php else: ?>
  <p class="no-print text-xs text-slate-400 mb-3">
    Tip: click "Open PDF & Print" — it opens in a new tab where your browser's built-in PDF viewer gives you an exact print/save option.
  </p>
  <iframe
    src="../api/serve_certificate_pdf.php?request_id=<?= $requestId ?>&t=<?= time() ?>"
    class="w-full border border-blue-100 rounded-xl"
    style="height: 85vh;">
  </iframe>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>