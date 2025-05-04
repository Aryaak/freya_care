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

    public static function administratorOnly() {
        if (isset($_COOKIE['user_role']) || $_COOKIE['user_role'] !== 'administrator') {
            $host = $_SERVER['HTTP_HOST'];
            header("Location: http://$host");
            exit;
        }
    }

    public static function customerOnly() {
        if (isset($_COOKIE['user_role']) && $_COOKIE['user_role'] !== 'customer') {
            $host = $_SERVER['HTTP_HOST'];
            header("Location: http://$host/dashboard");
            exit;
        }
    }
}
