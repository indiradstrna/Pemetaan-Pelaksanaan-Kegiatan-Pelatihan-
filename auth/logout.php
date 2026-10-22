<?php
session_start();
require_once __DIR__ . '/../config/constants.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/auth/login.php');
exit;