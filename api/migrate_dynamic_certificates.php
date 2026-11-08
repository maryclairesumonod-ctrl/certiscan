<?php
// ============================================================
// Migration script for Dynamic Certificate Management in CertiScan
// Safe, idempotent migration to extend certificate_types table
// and link existing DOCX templates.
// ============================================================

if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/config.php';
    if (empty($_SESSION['staff_id']) || ($_SESSION['role'] !== 'secretary' && $_SESSION['role'] !== 'admin')) {
        http_response_code(403);
        die('Forbidden: Secretary or Admin privileges required.');
    }
} else {
    require_once __DIR__ . '/config.php';
}

echo "Starting CertiScan Dynamic Certificate Migration...\n";

try {
    // 1. Check existing columns in certificate_types
    $stmt = $pdo->query("SHOW COLUMNS FROM certificate_types");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $columnsToAdd = [
        'template_file' => "VARCHAR(255) NULL AFTER description",
        'is_active' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER template_file",
        'is_available_online' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active",
        'requirements' => "TEXT NULL AFTER is_available_online",
        'placeholder_mappings' => "LONGTEXT NULL AFTER requirements",
        'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER placeholder_mappings",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    foreach ($columnsToAdd as $col => $definition) {
        if (!in_array($col, $existingColumns, true)) {
            $pdo->exec("ALTER TABLE certificate_types ADD COLUMN {$col} {$definition}");
            echo "Added column: {$col}\n";
        } else {
            echo "Column already exists: {$col}\n";
        }
    }

    // 2. Map existing templates and default requirements
    $templates = [
        'Certificate of Cutting Trees' => [
            'file' => 'certificate_cutting_trees.docx',
            'requirements' => 'Proof of land ownership, DENR/CENRO permit if required, Valid ID',
            'desc' => 'Permission/Clearance to cut trees within the barangay'
        ],
        'Certificate of Indigency' => [
            'file' => 'certificate_indigency.docx',
            'requirements' => 'Valid ID, Proof of financial need or indigency status',
            'desc' => 'Issued to indigent residents for medical, educational, or legal aid'
        ],
        'Certificate of Concession' => [
            'file' => 'certificate_concession.docx',
            'requirements' => 'Valid ID, Proof of concession or business agreement',
            'desc' => 'Barangay certification for concessionaires'
        ],
        'Certificate of Cohabitation' => [
            'file' => 'certificate_cohabitation.docx',
            'requirements' => 'Valid IDs of both partners, Joint Affidavit with 2 witnesses',
            'desc' => 'For marriage/legal purposes certifying live-in relationship'
        ],
        'Certificate of Solo Parent' => [
            'file' => 'certificate_solo_parent.docx',
            'requirements' => 'Solo Parent ID or DSWD certification, Child birth certificates, Valid ID',
            'desc' => 'Certifies solo parent status under the Solo Parents Welfare Act'
        ],
        'Certificate of First Time Jobseekers Assistance' => [
            'file' => 'certificate_first_time_jobseekers.docx',
            'requirements' => 'Valid ID, Oath of Undertaking, Barangay residency proof',
            'desc' => 'Assistance for first time jobseekers under RA 11261'
        ],
        'Certificate of Electric Connection' => [
            'file' => 'certificate_electric_connection.docx',
            'requirements' => 'Proof of property ownership / consent, Electric coop application form, Valid ID',
            'desc' => 'Certification endorsing electric service connection'
        ],
        'Death Certificate' => [
            'file' => 'certificate_death.docx',
            'requirements' => 'Hospital/Doctor death certificate, Valid ID of informant/claimant',
            'desc' => 'Barangay certification of death for burial or official transactions'
        ],
        'Certificate of 4Ps' => [
            'file' => 'certificate_4ps.docx',
            'requirements' => 'Pantawid Pamilya ID or household number, Valid ID',
            'desc' => 'Certifies beneficiary status in the Pantawid Pamilyang Pilipino Program'
        ],
        'Certificate of Residency' => [
            'file' => 'certificate_residency.docx',
            'requirements' => 'Valid ID, Proof of residence in the barangay for at least 6 months',
            'desc' => 'General certification verifying resident identity and address'
        ],
        'Certificate of Agriculture (RSBSA)' => [
            'file' => 'certificate_rsbsa.docx',
            'requirements' => 'RSBSA registration reference / stub, Proof of farm location, Valid ID',
            'desc' => 'Certification for registered farmers under RSBSA'
        ],
        'Barangay Clearance' => [
            'file' => 'certificate_barangay_clearance.docx',
            'requirements' => 'Valid Government ID, Recent Cedula (Community Tax Certificate)',
            'desc' => 'Official clearance certifying good standing with no derogatory records'
        ],
        'Business Permit' => [
            'file' => 'certificate_business_permit.docx',
            'requirements' => 'DTI / SEC Registration, Contract of Lease or Lot Title, Valid ID',
            'desc' => 'Barangay business permit / clearance to operate a commercial establishment'
        ],
    ];

    $updateStmt = $pdo->prepare(
        "UPDATE certificate_types 
         SET template_file = ?, 
             requirements = COALESCE(requirements, ?),
             description = COALESCE(description, ?),
             is_active = 1,
             is_available_online = 1
         WHERE name = ?"
    );

    foreach ($templates as $name => $meta) {
        $updateStmt->execute([$meta['file'], $meta['requirements'], $meta['desc'], $name]);
        echo "Updated mapping for: {$name} -> {$meta['file']}\n";
    }

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
