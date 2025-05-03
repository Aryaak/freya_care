<?php
class Middleware {
    public static function requireAuth() {
        if (!isset($_COOKIE['user_id']) || !isset($_COOKIE['user_name']) || !isset($_COOKIE['user_role'])) {
            header("Location: login.php");
            exit;
        }
    }

    public static function guestOnly() {
        if (isset($_COOKIE['user_id'])) {
            header("Location: dashboard");
            exit;
        }
    }

    public static function requireAdmin() {
        if (!isset($_COOKIE['user_role']) || $_COOKIE['user_role'] !== 'administrator') {
            $host = $_SERVER['HTTP_HOST'];
            header("Location: http://$host");
            exit;
        }
    }
}
