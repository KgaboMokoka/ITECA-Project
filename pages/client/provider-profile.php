<?php
$pageTitle = 'Provider Profile | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: search.php');
    exit();
}

// Fetch provider details
$stmt = $pdo->prepare('
    SELECT sp.*, u.first_name, u.last_name, u.location, u.phone, u.email, u.user_id
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    WHERE u.user_id = ? AND sp.verification_status = "approved"
');
$stmt->execute([$user_id]);
$provider = $stmt->fetch();

if (!$provider) {
    header('Location: search.php');
    exit();
}

// Fetch portfolio images
$stmt = $pdo->prepare('
    SELECT * FROM portfolio_images
    WHERE provider_id = ?
    ORDER BY uploaded_at DESC
');
$stmt->execute([$provider['provider_id']]);
$portfolio = $stmt->fetchAll();

// Fetch reviews
$stmt = $pdo->prepare('
    SELECT r.*, u.first_name, u.last_name
    FROM reviews r
    JOIN users u ON r.client_id = u.user_id
    WHERE r.provider_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
');
$stmt->execute([$provider['provider_id']]);
$reviews = $stmt->fetchAll();

// Handle job request submission
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && $_SESSION['role'] === 'client') {
    $service_type = trim($_POST['service_type']);
    $description  = trim($_POST['description']);
    $location     = trim($_POST['location']);
    $budget       = floatval($_POST['budget']);

    if (empty($service_type) || empty($description) || empty($location) || $budget <= 0) {
        $error = 'Please fill in all fields with valid values.';
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO job_requests (client_id, provider_id, service_type, description, location, budget)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $_SESSION['user_id'],
            $provider['user_id'],
            $service_type,
            $description,
            $location,
            $budget
        ]);
        $success = 'Job request sent successfully! The provider will respond shortly.';
    }
}
?>
<?php require_once '../../includes/header.php'; ?>

<div class="container py-5">
    <div class="row g-5">

        <div class="col-lg-4">
            <div class="card p-4 text-center mb-4">
                <div class="rounded-circle bg-warning d-flex align-items-center
                            justify-content-center mx-auto mb-3 fw-bold text-dark"
                    style="width:80px;height:80px;font-size:2rem;">
                    <?= strtoupper(substr($provider['first_name'], 0, 1)) ?>
                </div>
                <h4 class="fw-bold mb-1">
                    <?= htmlspecialchars($provider['first_name'] . ' ' . $provider['last_name']) ?>
                </h4>
                <p class="text-muted mb-2">
                    <i class="bi bi-geo-alt me-1"></i>
                    <?= htmlspecialchars($provider['location'] ?? 'Not specified') ?>
                </p>
                <span class="badge bg-success mb-3">
                    <i class="bi bi-patch-check-fill me-1"></i>Verified Provider
                </span>

                <div class="d-flex justify-content-center mb-3 stars">
                    <?php
                    $r = round($provider['avg_rating']);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $r
                            ? '<i class="bi bi-star-fill"></i>'
                            : '<i class="bi bi-star"></i>';
                    }
                    ?>
                    <span class="ms-2 text-muted small">
                        <?= number_format($provider['avg_rating'], 1) ?>
                        (<?= $provider['jobs_completed'] ?> jobs)
                    </span>
                </div>

                <hr>

                <ul class="list-unstyled text-start small">
                    <li class="mb-2">
                        <i class="bi bi-tools me-2 text-warning"></i>
                        <?= htmlspecialchars($provider['services_offered'] ?? 'Not specified') ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock me-2 text-warning"></i>
                        <?= $provider['experience_years'] ?? 0 ?> years experience
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-currency-dollar me-2 text-warning"></i>
                        R<?= number_format($provider['hourly_rate'] ?? 0, 0) ?>/hr
                    </li>
                    <?php if ($provider['phone']): ?>
                        <li class="mb-2">
                            <i class="bi bi-telephone me-2 text-warning"></i>
                            <?= htmlspecialchars($provider['phone']) ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($provider['bio']): ?>
                <div class="card p-4 mb-4">
                    <h6 class="fw-bold mb-2">About</h6>
                    <p class="text-muted small mb-0">
                        <?= nl2br(htmlspecialchars($provider['bio'])) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">

            <?php if (!empty($portfolio)): ?>
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3">Portfolio</h5>
                    <div class="row g-3">
                        <?php foreach ($portfolio as $img): ?>
                            <div class="col-6 col-md-4">
                                <div class="card p-2 text-center">
                                    <i class="bi bi-image text-muted" style="font-size:3rem;"></i>
                                    <p class="small text-muted mt-1 mb-0">
                                        <?= htmlspecialchars($img['description'] ?? '') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card p-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-calendar-plus me-2 text-warning"></i>Send a Job Request
                </h5>

                <?php if (!isLoggedIn()): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-lock me-2"></i>
                        Please <a href="/handyman-hub/auth/login.php">login</a> to send a job request.
                    </div>
                <?php elseif ($_SESSION['role'] !== 'client'): ?>
                    <div class="alert alert-info">
                        Only clients can send job requests.
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service Needed</label>
                                <select name="service_type" class="form-select" required>
                                    <option value="">-- Select Service --</option>
                                    <?php
                                    $cats = [
                                        'Plumbing',
                                        'Electrical',
                                        'Painting',
                                        'General Repairs',
                                        'Home Maintenance',
                                        'Auto Mechanic',
                                        'Appliance Repair',
                                        'HVAC',
                                        'Domestic Work'
                                    ];
                                    foreach ($cats as $cat): ?>
                                        <option value="<?= $cat ?>"><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Your Location</label>
                                <input type="text" name="location" class="form-control"
                                    placeholder="e.g. Sandton, Johannesburg" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Budget (R)</label>
                                <input type="number" name="budget" class="form-control"
                                    placeholder="e.g. 500" min="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Job Description</label>
                                <textarea name="description" class="form-control" rows="4"
                                    placeholder="Describe the work you need done..."
                                    required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="bi bi-send me-2"></i>Send Request
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card p-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-star me-2 text-warning"></i>
                    Reviews (<?= count($reviews) ?>)
                </h5>
                <?php if (empty($reviews)): ?>
                    <p class="text-muted">No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small">
                                    <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                </strong>
                                <small class="text-muted">
                                    <?= date('d M Y', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                            <div class="stars mb-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="small text-muted mb-0">
                                <?= htmlspecialchars($review['review_text']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>