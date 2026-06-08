<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout duration: 10 minutes maximum (600 seconds)
$timeout_duration = 600;

if (isset($_SESSION['LAST_ACTIVITY'])) {
    $elapsed_time = time() - $_SESSION['LAST_ACTIVITY'];
    if ($elapsed_time > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: index.html?error=timeout");
        exit();
    }
}

// Keep tracking interactive session updates
$_SESSION['LAST_ACTIVITY'] = time();

// Enforce login validation checks
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
?>