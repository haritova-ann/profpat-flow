<?php

session_start();

function requireAuth() {

    if (empty($_SESSION['user'])) {

        header('Location: /auth/login.php');
        exit;
    }
}

function requireRole(array $roles) {

    requireAuth();

    $userRole = $_SESSION['user']['role'];

    if (!in_array($userRole, $roles)) {

        http_response_code(403);

        die('Нет доступа');
    }
}

function hasRole(array $roles): bool {

    if (empty($_SESSION['user'])) {
        return false;
    }

    return in_array(
        $_SESSION['user']['role'],
        $roles
    );
}