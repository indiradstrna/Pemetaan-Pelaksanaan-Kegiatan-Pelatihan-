<?php
/**
 * Entry point project.
 */

session_start();
require_once __DIR__ . '/config/constants.php';
if (isset($_SESSION['user_id'])) {
    header('Location: ' . urlBerandaUntukRole($_SESSION['role']));
} else {
    // Arahkan ke sekar.php
    header('Location: ' . BASE_URL . '/sekar.php');
}
exit;