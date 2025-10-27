<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/boarding%20house%20finder/design/';
}

// ===== SESSION FUNCTIONS - ADD THESE =====
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isLandlord() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'landlord';
}

function isBoarder() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'boarder';
}

function redirectIfNotLoggedIn($redirectTo = 'login.html') {
    if (!isLoggedIn()) {
        header("Location: " . $redirectTo);
        exit();
    }
}

function redirectIfNotLandlord($redirectTo = 'home.html') {
    if (!isLandlord()) {
        header("Location: " . $redirectTo);
        exit();
    }
}
?>