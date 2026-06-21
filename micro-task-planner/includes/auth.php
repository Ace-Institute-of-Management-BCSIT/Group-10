<?php
// includes/auth.php — Session management and auth guard

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require the user to be logged in.
 * Redirects to index.php if not authenticated.
 */
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../index.php?page=signin');
        exit;
    }
}

/**
 * Redirect logged-in users away from auth pages.
 */
function redirectIfLoggedIn(): void {
    if (!empty($_SESSION['user_id'])) {
        header('Location: pages/dashboard.php');
        exit;
    }
}

/**
 * Return current logged-in user id or null.
 */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Log a user in by setting session variables.
 */
function loginUser(array $user): void {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email']= $user['email'];
}

/**
 * Destroy the session and log the user out.
 */
function logoutUser(): void {
    $_SESSION = [];
    session_destroy();
}
