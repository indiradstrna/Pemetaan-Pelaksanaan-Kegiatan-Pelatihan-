<?php
/**
 * Guard: wajib login (role apapun)
 * Include file ini di baris paling atas setiap halaman
 * yang butuh proteksi login, sebelum ada output HTML apapun.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}