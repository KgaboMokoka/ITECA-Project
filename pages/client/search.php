<?php
$pageTitle = 'Find Services | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';

// Build query with filters
$where    = ['sp.verification_status = "approved"'];
$params   = [];

$service  = trim($_GET['service']  ?? '');
$location = trim($_GET['location'] ?? '');
$rating   = trim($_GET['rating']   ?? '');
$max_rate = trim($_GET['max_rate'] ?? '');

if ($service) {
    $where[]  = 'sp.services_offered LIKE ?';
    $params[] = "%$service%";
}
if ($location) {
    $where[]  = 'u.location LIKE ?';
    $params[] = "%$location%";
}
if ($rating) {
    $where[]  = 'sp.avg_rating >= ?';
    $params[] = $rating;
}
if ($max_rate) {
    $where[]  = 'sp.hourly_rate <= ?';
    $params[] = $max_rate;
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT sp.*, u.first_name, u.last_name, u.location, u.user_id
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    WHERE $whereSQL
    ORDER BY sp.avg_rating DESC
");
$stmt->execute($params);
$providers = $stmt->fetchAll();
?>
<?php require_once '../../includes/header.php'; ?>

<div class="container py-5">
    <h3 class="fw-bold mb-1">Find a Service</h3>
    <p class="text-muted mb-4">Browse verified service providers near you</p>

    <div class="card p-4 mb-5">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Service Type</label>
                <select name="service" class="form-select">
                    <option value="">All Services</option>
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
                        <option value="<?= $cat ?>"
                            <?= $service === $cat ? 'selected' : '' ?>>
                            <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Location</label>
                <input type="text" name="location" class="form-control"
                    placeholder="e.g. Soweto"
                    value="<?= htmlspecialchars($location) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Min Rating</label>
                <select name="rating" class="form-select">
                    <option value="">Any</option>
                    <option value="3" <?= $rating == '3' ? 'selected' : '' ?>>3★ & up</option>
                    <option value="4" <?= $rating == '4' ? 'selected' : '' ?>>4★ & up</option>
                    <option value="4.5" <?= $rating == '4.5' ? 'selected' : '' ?>>4.5★ & up</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Max Rate (R/hr)</label>
                <input type="number" name="max_rate" class="form-control"
                    placeholder="e.g. 500"
                    value="<?= htmlspecialchars($max_rate) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Search
                </button>
                <a href="search.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>

    <p class="text-muted mb-3">
        <?= count($providers) ?> provider<?= count($providers) !== 1 ? 's' : '' ?> found
    </p>

    <?php if (empty($providers)): ?>
        <div class="text-center py-5">
            <i class="bi bi-person-x fs-1 text-muted"></i>
            <p class="text-muted mt-3">No providers found matching your filters.</p>
            <a href="search.php" class="btn btn-outline-secondary">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($providers as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card provider-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-warning d-flex align-items-center
                                    justify-content-center me-3 fw-bold text-dark"
                                style="width:50px;height:50px;font-size:1.2rem;">
                                <?= strtoupper(substr($p['first_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">
                                    <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?= htmlspecialchars($p['location'] ?? 'Location not set') ?>
                                </small>
                            </div>
                            <span class="badge-verified ms-auto">
                                <i class="bi bi-patch-check-fill text-success"></i> Verified
                            </span>
                        </div>

                        <p class="text-muted small mb-2">
                            <?= htmlspecialchars(substr($p['bio'] ?? 'No bio provided.', 0, 100)) ?>...
                        </p>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="stars">
                                <?php
                                $rating = round($p['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating
                                        ? '<i class="bi bi-star-fill"></i>'
                                        : '<i class="bi bi-star"></i>';
                                }
                                ?>
                                <small class="text-muted ms-1">(<?= $p['jobs_completed'] ?> jobs)</small>
                            </div>
                            <span class="fw-bold text-warning">
                                R<?= number_format($p['hourly_rate'] ?? 0, 0) ?>/hr
                            </span>
                        </div>

                        <p class="small text-muted mb-3">
                            <i class="bi bi-tools me-1"></i>
                            <?= htmlspecialchars(substr($p['services_offered'] ?? '', 0, 60)) ?>
                        </p>

                        <a href="provider-profile.php?id=<?= $p['user_id'] ?>"
                            class="btn btn-primary w-100 mt-auto">
                            View Profile & Book
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>