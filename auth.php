<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_admin_login() {
    if (!is_logged_in()) {
        header("Location: admin_login.php");
        exit();
    }
}

function login_admin($admin_id, $username) {
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_last_login'] = time();
}

function logout_admin() {
    session_unset();
    session_destroy();
}

function get_current_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}