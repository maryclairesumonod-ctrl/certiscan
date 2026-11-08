<?php
require_once __DIR__ . '/config.php';
$_SESSION = [];
session_destroy();
header('Location: ../frontend/login.php');
exit;
