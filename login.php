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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Movie Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                    }
                }
            }
        }
    </script>
    <script src="data.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%232563eb'/><text y='.9em' font-size='90'>🎬</text></svg>" type="image/svg+xml">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/50">
            <div class="text-center mb-8">
                <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-gray-900 to-slate-700 bg-clip-text text-transparent mb-2">MovieBooking</h1>
                <p class="text-gray-600">Welcome back</p>
            </div>
            
            <?php if ($login_error): ?>
            <div class="mb-6 p-4 rounded-xl border bg-red-50 border-red-200 text-red-800">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($login_message): ?>
            <div class="mb-6 p-4 rounded-xl border bg-green-50 border-green-200 text-green-800">
                <?php echo htmlspecialchars($login_message); ?>
            </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="login.php" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 bg-white/50 backdrop-blur-sm" placeholder="Enter your email">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" id="password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 bg-white/50 backdrop-blur-sm" placeholder="Enter your password">
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 text-lg">
                    Login
                </button>
            </form>
            <p class="text-center mt-6 text-gray-600">
                Don't have an account? 
                <a href="register.php" class="font-semibold text-primary hover:text-blue-700 transition-colors">Register</a>
            </p>
        </div>
    </div>

    <script>
        // Client-side validation fallback
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill all fields.');
            }
            // PHP handles rest
        });
    </script>
</body>
</html>
