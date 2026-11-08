<?php
// ============================================================
// Resident auth guard — separate session namespace from staff,
// so a resident and a staff member can theoretically be logged
// in on the same browser without colliding.
// ============================================================
require_once __DIR__ . '/config.php';

if (empty($_SESSION['resident_id'])) {
    header('Location: login.php');
    exit;
}
