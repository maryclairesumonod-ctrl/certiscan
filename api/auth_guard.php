<?php
// ============================================================
// Auth guard — include this at the very top of any protected
// frontend page. Redirects to login if no staff is logged in.
// ============================================================
require_once __DIR__ . '/config.php';

if (empty($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
