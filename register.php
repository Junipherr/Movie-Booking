<?php
/**
 * Registration Page
 * 
 * New user registration page for the Movie Booking system.
 * Validates input, checks for existing email, and creates new user record.
 * 
 * @route: register.php
 * @method: POST (form submission), GET (display form)
 * @requires: includes/config.php, includes/public-header.php
 * 
 * @form-fields: name, email, password, confirmPassword
 * @validation: name (min 2 chars), email (valid format), password (min 6 chars), password match
 * @db-inserts: new user record with hashed password
 * @redirects: login.php (on success with 1.5s delay via JavaScript)
 * 
 * @see login.php (existing user login)
 */

$message = '';
$message_type = '';

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/config.php';
    
    // Get and sanitize form inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    
    $errors = [];
    
    // Validate all fields
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Check if email already exists in database
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email already registered.';
        }
        $stmt->close();
    }
    
    // Insert new user if all validations pass
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';
        $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $message = 'Registration successful! Redirecting to login...';
            $message_type = 'success';
            // JavaScript redirect after 1.5 seconds
            echo '<script>setTimeout(() => { window.location.href = "login.php"; }, 1500);</script>';
        } else {
            $errors[] = 'Registration failed. Try again.';
        }
        $stmt->close();
    }
    
    // Display validation errors
    if (!empty($errors)) {
        $message = implode(' ', $errors);
        $message_type = 'error';
    }
    
    $conn->close();
}

// Set page metadata and include header
$pageTitle = 'Register - Movie Booking';
$activePage = '';
include 'includes/public-header.php';
?>

    <div class="min-h-screen flex items-center justify-center p-4 pt-24">
        <div class="w-full max-w-md">
            <div class="bg-neutral-800 rounded-xl shadow-lg p-8 border border-neutral-700">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">Create Account</h1>
                    <p class="text-gray-400">Join CineMovie</p>
                </div>
                
                <?php if ($message): ?>
                <div class="mb-6 p-3 rounded-lg <?php echo $message_type === 'success' ? 'bg-emerald-900/50 border border-emerald-700 text-emerald-300' : 'bg-red-900/50 border border-red-700 text-red-300'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>
                
                <form id="registerForm" method="POST" action="register.php" class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                        <input type="text" name="name" id="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" class="w-full px-4 py-3 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all duration-200" placeholder="Enter your full name">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                        <input type="email" name="email" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full px-4 py-3 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all duration-200" placeholder="Enter your email">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                        <input type="password" name="password" id="password" required minlength="6" class="w-full px-4 py-3 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all duration-200" placeholder="Create password">
                    </div>
                    <div>
                        <label for="confirmPassword" class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
                        <input type="password" name="confirmPassword" id="confirmPassword" required minlength="6" class="w-full px-4 py-3 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all duration-200" placeholder="Confirm password">
                    </div>
                    <button type="submit" class="w-full bg-netflix-red hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200">
                        Sign Up
                    </button>
                </form>
                <p class="text-center mt-6 text-gray-400">
                    Already have an account? 
                    <a href="login.php" class="font-semibold text-netflix-red hover:text-red-400 transition-colors">Sign in</a>
                </p>
            </div>
        </div>
    </div>
