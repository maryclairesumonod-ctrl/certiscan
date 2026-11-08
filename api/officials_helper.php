<?php
// ============================================================
// Barangay officials helper — loads the current officials from
// the database and fills them into a PHPWord template, so the
// names printed on certificates never have to be edited by hand
// in the Word files again.
//
// Failure-safe by design: if the officials table is missing or
// anything here goes wrong, certificate generation still works
// and the template simply keeps its own printed names.
// ============================================================

function get_officials(PDO $pdo): array {
    $stmt = $pdo->query(
        'SELECT position_key, position_label, full_name
         FROM barangay_officials
         ORDER BY display_order'
    );
    return $stmt->fetchAll();
}

/**
 * For every row in barangay_officials, fills ${official_<key>}.
 * Every row except the term also gets an uppercase version,
 * ${official_<key>_upper}, used for the signature line.
 * setValue() silently does nothing if a template doesn't contain
 * the tag, so templates you haven't converted yet are unaffected.
 */
function apply_officials(\PhpOffice\PhpWord\TemplateProcessor $processor, PDO $pdo): void {
    foreach (get_officials($pdo) as $official) {
        $key  = 'official_' . $official['position_key'];
        $name = $official['full_name'];

        $processor->setValue($key, htmlspecialchars($name));

        if ($official['position_key'] !== 'term') {
            $processor->setValue($key . '_upper', htmlspecialchars(mb_strtoupper($name, 'UTF-8')));
        }
    }
}

/** Wrapper used by the certificate generators — never throws. */
function apply_officials_if_available(\PhpOffice\PhpWord\TemplateProcessor $processor, PDO $pdo): void {
    try {
        apply_officials($processor, $pdo);
    } catch (\Throwable $e) {
        error_log('Officials not applied: ' . $e->getMessage());
    }
}