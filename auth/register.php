<?php
$pageTitle = 'Register | Handyman Hub';
require_once '../config/db.php';
require_once '../includes/session.php';

if (isLoggedIn()) redirectByRole();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];
    $phone      = trim($_POST['phone']);
    $location   = trim($_POST['location']);
    $role       = $_POST['role'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!in_array($role, ['client', 'provider'])) {
        $error = 'Invalid role selected.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('
                INSERT INTO users (first_name, last_name, email, password, phone, location, role)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$first_name, $last_name, $email, $hashed, $phone, $location, $role]);
            $user_id = $pdo->lastInsertId();

            if ($role === 'provider') {
                $stmt = $pdo->prepare('INSERT INTO service_providers (user_id) VALUES (?)');
                $stmt->execute([$user_id]);
            }
            $success = 'Account created! You can now log in.';
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="auth-card card p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-tools fs-1" style="color: var(--primary);"></i>
            <h3 class="fw-bold mt-2">Create an Account</h3>
            <p class="text-muted">Join Handyman Hub today</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control"
                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control"
                        value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone" class="form-control"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Location</label>
                    <input type="text" name="location" class="form-control"
                        placeholder="e.g. Soweto, Johannesburg"
                        value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control"
                        minlength="8" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">I want to <span class="text-danger">*</span></label>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role"
                                id="role_client" value="client" required
                                <?= (($_POST['role'] ?? '') === 'client') ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary w-100 py-3" for="role_client">
                                <i class="bi bi-person-circle d-block fs-3 mb-1"></i>
                                Hire a Worker
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role"
                                id="role_provider" value="provider"
                                <?= (($_POST['role'] ?? '') === 'provider') ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary w-100 py-3" for="role_provider">
                                <i class="bi bi-tools d-block fs-3 mb-1"></i>
                                Offer Services
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </div>
            </div>
        </form>

        <p class="text-center text-muted mt-4 mb-0">
            Already have an account?
            <a href="login.php" class="text-decoration-none fw-semibold">Login here</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>