<?php
require_once __DIR__ . '/config.php';
unset($_SESSION['resident_id'], $_SESSION['resident_name'], $_SESSION['resident_account_status']);
header('Location: ../frontend/resident/login.php');
exit;
