<?php
require_once '../config/config.php';
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: " . BASE_URL . "/pages/login.php");
exit();
?>