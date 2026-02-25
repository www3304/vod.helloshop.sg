<?php
session_start();

function require_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: /admin/login.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['user']['role'] !== $role) {
        die("Access denied");
    }
}
