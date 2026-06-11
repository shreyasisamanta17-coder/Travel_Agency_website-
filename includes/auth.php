<?php
// Secure Authentication and Session Manager
require_once __DIR__ . '/db.php';

function checkSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Set secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// Ensure session starts on all pages using auth
checkSession();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getLoggedInUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getLoggedInUserName() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
}

function getLoggedInUserEmail() {
    return isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
}

/**
 * Handle user registration
 */
function registerUser($name, $email, $password) {
    global $pdo;
    
    // Clean input data
    $name = trim(htmlspecialchars($name));
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    
    if (empty($name) || empty($email) || empty($password)) {
        return "All fields are required.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email address format.";
    }
    
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long.";
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return "An account with this email address already exists.";
        }
        
        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $insertStmt->execute([$name, $email, $hashedPassword]);
        
        return true; // Registration successful
    } catch (\PDOException $e) {
        return "Registration failed: " . $e->getMessage();
    }
}

/**
 * Handle user login
 */
function loginUser($email, $password) {
    global $pdo;
    
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    
    if (empty($email) || empty($password)) {
        return "Email and password are required.";
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session id to protect against session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            return true;
        } else {
            return "Invalid email address or password.";
        }
    } catch (\PDOException $e) {
        return "Login error: " . $e->getMessage();
    }
}

/**
 * Terminate user session
 */
function logoutUser() {
    checkSession();
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Generate CSRF Token for Forms
 */
function generateCsrfToken() {
    checkSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken($token) {
    checkSession();
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
