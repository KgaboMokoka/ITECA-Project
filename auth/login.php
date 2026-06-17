<?php
$pageTitle = 'Login | Handyman Hub';
require_once '../config/db.php';
require_once '../includes/session.php';

if (isLoggedIn()) redirectByRole();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role']       = $user['role'];
            redirectByRole();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="auth-card card p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-tools fs-1" style="color: var(--primary);"></i>
            <h3 class="fw-bold mt-2">Welcome Back</h3>
            <p class="text-muted">Login to your Handyman Hub account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control form-control-lg"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    placeholder="you@example.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control form-control-lg"
                    placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center">
            <p class="text-muted small mb-2">Test accounts:</p>
            <code class="small">admin@handymanhub.co.za / password</code>
        </div>

        <p class="text-center text-muted mt-4 mb-0">
            Don't have an account?
            <a href="register.php" class="text-decoration-none fw-semibold">Register here</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>