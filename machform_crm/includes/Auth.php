<?php
/**
 * Machform CRM - Authentication Class
 * Handle user authentication and authorization
 * Uses config-based credentials since Machform doesn't have a users table
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Login user with config-based credentials
     */
    public function login($username, $password) {
        // Get credentials from config file
        $configUsername = defined('CRM_USERNAME') ? CRM_USERNAME : 'admin';
        $configPassword = defined('CRM_PASSWORD') ? CRM_PASSWORD : 'admin123';
        
        if ($username === $configUsername && $password === $configPassword) {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = 'admin@localhost';
            $_SESSION['role'] = 'admin';
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
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Require admin - redirect if not admin
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: index.php?error=unauthorized');
            exit;
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
