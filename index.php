<?php
require_once __DIR__ . '/api/config.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: frontend/dashboard.php');
} else {
    header('Location: frontend/login.php');
}
exit;
