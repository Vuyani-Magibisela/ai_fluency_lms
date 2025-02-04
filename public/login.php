<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';

$page_title = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $auth = new Auth();
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if ($auth->login($email, $password)) {
            header('Location: /dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../includes/header.php';
?>

<main class="main-container">
    <div class="auth-container">
        <h1 class="auth-title">Login to AI Fluency LMS</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    class="form-control"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    class="form-control"
                    minlength="8"
                >
            </div>

            <div class="form-group form-check">
                <input type="checkbox" id="remember" name="remember" class="form-check-input">
                <label for="remember" class="form-check-label">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>

            <div class="auth-links">
                <a href="forgot-password.php" class="forgot-password">Forgot your password?</a>
                <p class="register-link">
                    Don't have an account? 
                    <a href="register.php">Register here</a>
                </p>
            </div>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>