<?php
/**
 * Authentication Controller
 * Handles user login, logout, and authentication
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/csrf.php';
require_once __DIR__ . '/../Helpers/validate.php';
require_once __DIR__ . '/../Helpers/response.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Show login form or handle login submission
     */
    public function login()
    {
        // If already authenticated, redirect to dashboard
        if (Auth::check()) {
            Response::redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleLogin();
        }

        return $this->showLoginForm();
    }

    /**
     * Show login form
     */
    private function showLoginForm($errors = [])
    {
        $data = [
            'title' => 'Login - Daily Statement App',
            'errors' => $errors,
            'csrf_token' => CSRF::getToken()
        ];

        include __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Handle login form submission
     */
    private function handleLogin()
    {
        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            return $this->showLoginForm();
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validate input
        $validator = Validate::make([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->showLoginForm($validator->errors());
        }

        // Attempt authentication
        if (Auth::attempt($email, $password)) {
            // Update last login
            $this->userModel->updateLastLogin(Auth::id());

            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberToken();
            }

            Flash::success('Welcome back, ' . Auth::user()['name'] . '!');
            
            // Redirect to intended page or dashboard
            $redirect = $_SESSION['intended_url'] ?? null;
            unset($_SESSION['intended_url']);
            
            if ($redirect) {
                // If intended URL is set, it's already a full path, redirect directly
                header("Location: {$redirect}");
                exit;
            } else {
                // Default redirect to dashboard
                Response::redirect('dashboard');
            }
        } else {
            Flash::error('Invalid email or password.');
            return $this->showLoginForm();
        }
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('dashboard');
        }

        // Initialize CSRF to ensure session is started
        CSRF::init();
        
        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            error_log('CSRF validation failed for logout. Token in POST: ' . ($_POST['_token'] ?? 'missing'));
            error_log('Session token: ' . ($_SESSION['csrf_token'] ?? 'missing'));
            error_log('Session ID: ' . session_id());
            
            // Generate a new token for next attempt
            CSRF::generateToken();
            
            Flash::error('Security token validation failed. Please try logging out again.');
            Response::redirect('dashboard');
        }

        // Clear remember token
        $this->clearRememberToken();

        Auth::logout();
        Flash::success('You have been logged out successfully.');
        Response::redirect('login');
    }

    /**
     * Handle GET logout (fallback for CSRF issues)
     */
    public function logoutGet()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::redirect('dashboard');
        }

        // Clear remember token
        $this->clearRememberToken();

        Auth::logout();
        Flash::success('You have been logged out successfully.');
        Response::redirect('login');
    }

    /**
     * Set remember me token
     */
    private function setRememberToken()
    {
        $token = Auth::generateToken();
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        setcookie('remember_token', $token, $expires, '/', '', false, true);
        
        // Store token hash in database (for future implementation)
        // This would require a remember_tokens table
    }

    /**
     * Clear remember me token
     */
    private function clearRememberToken()
    {
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }

    /**
     * Check remember me token (for future implementation)
     */
    public function checkRememberToken()
    {
        if (!Auth::check() && isset($_COOKIE['remember_token'])) {
            // Implementation would verify token against database
            // and automatically log in user if valid
        }
    }

    /**
     * Password reset request (for future implementation)
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            
            $validator = Validate::make(['email' => $email], ['email' => 'required|email']);
            
            if ($validator->passes()) {
                $user = $this->userModel->findByEmail($email);
                
                if ($user) {
                    // Generate reset token and send email
                    // This would require email functionality
                    Flash::success('Password reset instructions have been sent to your email.');
                } else {
                    Flash::error('No account found with that email address.');
                }
            } else {
                Flash::error('Please enter a valid email address.');
            }
        }

        // Show forgot password form
        include __DIR__ . '/../Views/auth/forgot-password.php';
    }

    /**
     * Password reset (for future implementation)
     */
    public function resetPassword()
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            Flash::error('Invalid reset token.');
            Response::redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle password reset form submission
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $validator = Validate::make([
                'password' => $password,
                'confirm_password' => $confirmPassword
            ], [
                'password' => 'required|min:8',
                'confirm_password' => 'required'
            ]);
            
            if ($password !== $confirmPassword) {
                $validator->addError('confirm_password', 'Passwords do not match.');
            }
            
            if ($validator->passes()) {
                // Verify token and update password
                Flash::success('Your password has been reset successfully.');
                Response::redirect('/login');
            }
        }

        // Show reset password form
        include __DIR__ . '/../Views/auth/reset-password.php';
    }

    /**
     * User registration (for future implementation)
     */
    public function register()
    {
        // Only allow registration if enabled in config
        $config = require __DIR__ . '/../../config/config.php';
        
        if (!($config['app']['allow_registration'] ?? false)) {
            Response::redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleRegistration();
        }

        // Show registration form
        include __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Handle user registration
     */
    private function handleRegistration()
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $validator = Validate::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $confirmPassword
        ], [
            'name' => 'required|length:2,120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'confirm_password' => 'required'
        ]);

        if ($password !== $confirmPassword) {
            $validator->addError('confirm_password', 'Passwords do not match.');
        }

        if ($validator->fails()) {
            Flash::error('Please correct the errors below.');
            return $this->showRegistrationForm($validator->errors());
        }

        try {
            $userId = $this->userModel->create([
                'name' => $name,
                'email' => $email,
                'password_hash' => Auth::hashPassword($password),
                'is_active' => 1
            ]);

            // Auto-login after registration
            Auth::attempt($email, $password);
            
            Flash::success('Account created successfully! Welcome to Daily Statement App.');
            Response::redirect('/dashboard');
            
        } catch (Exception $e) {
            Flash::error('Registration failed. Please try again.');
            return $this->showRegistrationForm();
        }
    }

    /**
     * Show registration form
     */
    private function showRegistrationForm($errors = [])
    {
        $data = [
            'title' => 'Register - Daily Statement App',
            'errors' => $errors,
            'csrf_token' => CSRF::getToken()
        ];

        include __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Check if user is authenticated (API endpoint)
     */
    public function checkAuth()
    {
        if (Response::expectsJson()) {
            Response::json([
                'authenticated' => Auth::check(),
                'user' => Auth::check() ? Auth::user() : null
            ]);
        }

        Response::redirect(Auth::check() ? '/dashboard' : '/login');
    }
}
