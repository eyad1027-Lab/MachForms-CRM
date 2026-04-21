<?php
/**
 * Machform CRM - Authentication Class
 * Handle user authentication and authorization
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        // Check if user exists in machform users table
        // For now, we'll create a simple auth system
        // In production, integrate with existing user system
        
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $user = $this->db->fetchOne($sql, ['username' => $username]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'admin';
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current username
     */
    public function getUsername() {
        return $_SESSION['username'] ?? 'Guest';
    }
    
    /**
     * Get current user email
     */
    public function getEmail() {
        return $_SESSION['email'] ?? '';
    }
    
    /**
     * Get current user role
     */
    public function getRole() {
        return $_SESSION['role'] ?? 'user';
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Require login - redirect to login page if not logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /machform_crm/login.php');
            exit;
        }
    }
    
    /**
     * Require admin - redirect if not admin
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: /machform_crm/index.php?error=unauthorized');
            exit;
        }
    }
    
    /**
     * Register new user
     */
    public function register($username, $email, $password, $role = 'user') {
        // Check if username already exists
        $checkSql = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
        $existing = $this->db->fetchOne($checkSql, ['username' => $username, 'email' => $email]);
        
        if ($existing) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'created_at' => date(DATETIME_FORMAT)
        ];
        
        try {
            $userId = $this->db->insert('users', $data);
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        return $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            ['id' => $userId]
        );
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
