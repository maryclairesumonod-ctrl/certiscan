<?php
require_once __DIR__ . '/../api/auth_guard.php';
$pageTitle = 'Records';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-6">
  <p class="cs-eyebrow mb-1">Archive</p>
  <h1 class="text-2xl font-bold text-slate-900">Certificate records</h1>
  <p class="text-slate-500 text-sm mt-1">All certificates generated through CertiScan.</p>
</div>
<div class="cs-card overflow-hidden">
  <table class="w-full text-sm cs-table">
    <thead>
      <tr>
        <th>Cert No.</th>
        <th>Resident</th>
        <th>Certificate type</th>
        <th>Status</th>
        <th>Issued</th>
        <th>Handled by</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="tableBody">
      <tr><td class="px-4 py-6 text-slate-400" colspan="7">Loading records...</td></tr>
    </tbody>
  </table>
</div>
<script>
async function loadCertificates() {
  const res = await fetch('../api/list_certificates.php');
  const data = await res.json();
  const tbody = document.getElementById('tableBody');
  if (!data.success || data.certificates.length === 0) {
    tbody.innerHTML = '<tr><td class="px-4 py-6 text-slate-400" colspan="7">No certificates generated yet.</td></tr>';
    return;
  }
  const statusColor = {
    pending: 'badge-pending',
    under_review: 'badge-under_review',
    approved: 'badge-approved',
    rejected: 'badge-rejected',
    ready_for_pickup: 'badge-ready_for_pickup',
    released: 'badge-released',
  };
  const statusLabel = {
    pending: 'Pending', under_review: 'Under Review', approved: 'Approved',
    rejected: 'Rejected', ready_for_pickup: 'Ready for Pickup', released: 'Released',
  };
  tbody.innerHTML = data.certificates.map(c => `
    <tr>
      <td class="font-mono text-xs text-slate-500">${c.cert_number}</td>
      <td class="font-medium text-slate-800">${c.full_name}</td>
      <td>${c.certificate_type}</td>
      <td>
        <span class="cs-badge ${statusColor[c.status] || 'bg-slate-100 text-slate-600'}">
          ${statusLabel[c.status] || c.status}
        </span>
      </td>
      <td class="text-slate-500">${c.issued_at ?? '—'}</td>
      <td class="text-slate-500">${c.handled_by}</td>
      <td>
        ${c.pdf_path
          ? `<a href="print_certificate.php?request_id=${c.request_id}" class="text-primary text-xs font-semibold hover:underline">Print</a>`
          : `<span title="No document was ever generated for this record — likely no template was registered for this certificate type at the time." class="text-xs text-amber-600 cursor-help">No file</span>`
        }
      </td>
    </tr>
  `).join('');
}
loadCertificates();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>