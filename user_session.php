<?php
// Force login for dashboard access
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>