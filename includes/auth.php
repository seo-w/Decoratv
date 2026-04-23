<?php
/**
 * Authentication and Session Management for DecoraTV
 */

// Strict Session Security Defaults
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
// Ensure to uncomment this in production with HTTPS:
// ini_set('session.cookie_secure', 1);

session_start();

/**
 * Check if the current user is logged in.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login for a page. Redirects to login if not authenticated.
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Attempt to login with username and password.
 */
function attempt_login($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if account is active
            if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                return 'disabled'; // Special return to indicate account is inactive
            }

            // Regeneration for security
            session_regenerate_id();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
    }
    return false;
}

/**
 * Log the current user out.
 */
function logout() {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}
