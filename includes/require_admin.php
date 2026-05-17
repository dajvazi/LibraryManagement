<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['auth_user']['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
} 
