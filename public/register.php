<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';

$page_title = 'Register';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $auth = new Auth();
        
        // Sanitize inputs
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Additional validation
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if ($auth->register($username, $email, $password)) {
            // Auto login after registration
            $auth->login($email, $password);
            header('Location: ../public/dashboard.php');
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
        <h1 class="auth-title">Create an Account</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    class="form-control"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    pattern="[a-zA-Z0-9_-]{3,20}"
                    title="Username must be between 3 and 20 characters and may only contain letters, numbers, underscores, and hyphens"
                >
            </div>

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
                <small class="form-text text-muted">
                    Password must be at least 8 characters long
                </small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required 
                    class="form-control"
                    minlength="8"
                >
            </div>

            <div class="form-group form-check">
                <input type="checkbox" id="terms" name="terms" required class="form-check-input">
                <label for="terms" class="form-check-label">
                    I agree to the <a href="terms.php">Terms of Service</a> and 
                    <a href="privacy.php">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>

            <p class="login-link">
                Already have an account? 
                <a href="login.php">Login here</a>
            </p>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>