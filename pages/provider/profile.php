<?php
$pageTitle = 'My Profile | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('provider');

$stmt = $pdo->prepare('
    SELECT sp.*, u.first_name, u.last_name, u.email, u.phone, u.location
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    WHERE sp.user_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$provider = $stmt->fetch();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio              = trim($_POST['bio']);
    $services_offered = trim($_POST['services_offered']);
    $hourly_rate      = floatval($_POST['hourly_rate']);
    $experience_years = intval($_POST['experience_years']);
    $phone            = trim($_POST['phone']);
    $location         = trim($_POST['location']);

    // Update users table
    $pdo->prepare('
        UPDATE users SET phone = ?, location = ? WHERE user_id = ?
    ')->execute([$phone, $location, $_SESSION['user_id']]);

    // Update service_providers table
    $pdo->prepare('
        UPDATE service_providers
        SET bio = ?, services_offered = ?, hourly_rate = ?, experience_years = ?
        WHERE user_id = ?
    ')->execute([
        $bio,
        $services_offered,
        $hourly_rate,
        $experience_years,
        $_SESSION['user_id']
    ]);

    $success = 'Profile updated successfully!';

    // Refresh provider data
    $stmt = $pdo->prepare('
        SELECT sp.*, u.first_name, u.last_name, u.email, u.phone, u.location
        FROM service_providers sp
        JOIN users u ON sp.user_id = u.user_id
        WHERE sp.user_id = ?
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $provider = $stmt->fetch();
}
?>
<?php require_once '../../includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">My Profile</h3>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-2">Verification Status</h6>
                <?php if ($provider['verification_status'] === 'pending'): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-hourglass-split me-2"></i>
                        Your profile is pending verification by an admin.
                        You won't appear in search results until approved.
                    </div>
                <?php elseif ($provider['verification_status'] === 'approved'): ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-patch-check-fill me-2"></i>
                        Your profile is verified and visible in search results.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-x-circle me-2"></i>
                        Your verification was rejected. Please contact support.
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">First Name</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars($provider['first_name']) ?>"
                                disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars($provider['last_name']) ?>"
                                disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control"
                                value="<?= htmlspecialchars($provider['email']) ?>"
                                disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                value="<?= htmlspecialchars($provider['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location</label>
                            <input type="text" name="location" class="form-control"
                                placeholder="e.g. Soweto, Johannesburg"
                                value="<?= htmlspecialchars($provider['location'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Bio</label>
                            <textarea name="bio" class="form-control" rows="4"
                                placeholder="Tell clients about yourself and your experience..."><?= htmlspecialchars($provider['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Services Offered</label>
                            <input type="text" name="services_offered" class="form-control"
                                placeholder="e.g. Plumbing, General Repairs, Painting"
                                value="<?= htmlspecialchars($provider['services_offered'] ?? '') ?>">
                            <small class="text-muted">Separate services with commas</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Hourly Rate (R)</label>
                            <input type="number" name="hourly_rate" class="form-control"
                                min="0" step="0.01"
                                value="<?= $provider['hourly_rate'] ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Years of Experience</label>
                            <input type="number" name="experience_years" class="form-control"
                                min="0"
                                value="<?= $provider['experience_years'] ?? '' ?>">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>