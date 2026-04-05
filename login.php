<?php
// Login handler - authenticates against DB
$login_message = '';
$login_message_type = '';
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/config.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $userRole = $user ? $user['role'] : null;
        $stmt->close();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login success - set session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $userRole ?: (stripos($email, 'admin') !== false ? 'admin' : 'user');
            
            // Redirect based on role
            $redirectUrl = ($_SESSION['user_role'] === 'admin') ? 'admin-dashboard.php' : 'index.php';
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $login_error = 'Invalid email or password.';
        }
    } else {
        $login_error = 'Please fill all fields.';
    }
    
    $conn->close();
}
$pageTitle = 'Login - Movie Booking';
$activePage = '';
include 'includes/public-header.php';
?>

    <div class="min-h-screen flex items-center justify-center p-4 pt-24">
        <div class="w-full max-w-md">
            <div class="bg-neutral-800 rounded-xl shadow-lg p-8 border border-neutral-700">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">Sign In</h1>
                    <p class="text-gray-400">Welcome back to Netflix Movies</p>
                </div>
                
                <?php if ($login_error): ?>
                <div class="mb-6 p-3 rounded-lg bg-red-900/50 border border-red-700 text-red-300">
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" action="login.php" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                        <input type="email" name="email" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full px-4 py-3 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all duration-200" placeholder="Enter your email">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                        <input type="password" name="password" id="password" required class="w-full px-4 py-3 bg-neutral-900 border border-neutral-600 rounded-lg text-white focus:border-netflix-red focus:ring-1 focus:ring-netflix-red transition-all duration-200" placeholder="Enter your password">
                    </div>
                    <button type="submit" class="w-full bg-netflix-red hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200">
                        Sign In
                    </button>
                </form>
                <p class="text-center mt-6 text-gray-400">
                    Don't have an account? 
                    <a href="register.php" class="font-semibold text-netflix-red hover:text-red-400 transition-colors">Sign up</a>
                </p>
            </div>
        </div>
    </div>
