<?php
$pageTitle = 'Handyman Hub | Find Trusted Local Services';
require_once 'includes/header.php';
?>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1>Find Trusted <span>Handymen</span> Near You</h1>
                <p class="lead mt-3 text-secondary">
                    Connecting skilled informal workers with clients across South Africa.
                    Verified profiles, transparent pricing, secure bookings.
                </p>
                <div class="d-flex gap-3 mt-4">
                    <a href="pages/client/search.php" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-search me-2"></i>Find a Service
                    </a>
                    <a href="auth/register.php" class="btn btn-outline-light btn-lg px-4">
                        Join as Provider
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="bi bi-tools" style="font-size: 10rem; color: var(--primary); opacity: 0.8;"></i>
            </div>
        </div>
    </div>
</section>

<!-- <section class="py-5 bg-warning">
    <div class="container">
        <div class="row text-center text-dark">
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <h2 class="fw-bold mb-0">1,200+</h2>
                <p class="mb-0 small">Skilled Workers</p>
            </div>
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <h2 class="fw-bold mb-0">8,500+</h2>
                <p class="mb-0 small">Jobs Completed</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="fw-bold mb-0">4.8★</h2>
                <p class="mb-0 small">Average Rating</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="fw-bold mb-0">9</h2>
                <p class="mb-0 small">Service Categories</p>
            </div>
        </div>
    </div>
</section> -->

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Our Service Categories</h2>
            <p class="text-muted">Browse by the type of service you need</p>
        </div>
        <div class="row g-4">
            <?php
            $services = [
                ['icon' => 'bi-droplet',        'name' => 'Plumbing',          'desc' => 'Pipes, leaks, installations'],
                ['icon' => 'bi-lightning',       'name' => 'Electrical',        'desc' => 'Wiring, repairs, fittings'],
                ['icon' => 'bi-brush',           'name' => 'Painting',          'desc' => 'Interior & exterior painting'],
                ['icon' => 'bi-hammer',          'name' => 'General Repairs',   'desc' => 'Carpentry, welding & more'],
                ['icon' => 'bi-house-gear',      'name' => 'Home Maintenance',  'desc' => 'Cleaning, gardening, upkeep'],
                ['icon' => 'bi-car-front',       'name' => 'Auto Mechanic',     'desc' => 'Vehicle repairs & servicing'],
                ['icon' => 'bi-phone',           'name' => 'Appliance Repair',  'desc' => 'Electronics & appliances'],
                ['icon' => 'bi-snow2',           'name' => 'HVAC',              'desc' => 'Aircon, heating & cooling'],
                ['icon' => 'bi-person-workspace', 'name' => 'Domestic Work',     'desc' => 'Housekeeping & childcare'],
            ];
            foreach ($services as $s): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="pages/client/search.php?service=<?= urlencode($s['name']) ?>"
                        class="text-decoration-none">
                        <div class="card p-4 text-center h-100">
                            <i class="bi <?= $s['icon'] ?> mb-3"
                                style="font-size: 2.5rem; color: var(--primary);"></i>
                            <h6 class="fw-bold mb-1"><?= $s['name'] ?></h6>
                            <p class="text-muted small mb-0"><?= $s['desc'] ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">How It Works</h2>
            <p class="text-muted">Get the help you need in 3 simple steps</p>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="card p-4 h-100">
                    <div class="mb-3">
                        <span class="badge bg-warning text-dark rounded-circle p-3 fs-5">1</span>
                    </div>
                    <h5 class="fw-bold">Search & Filter</h5>
                    <p class="text-muted">Browse verified service providers by category, location, rating, and price.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 h-100">
                    <div class="mb-3">
                        <span class="badge bg-warning text-dark rounded-circle p-3 fs-5">2</span>
                    </div>
                    <h5 class="fw-bold">Book & Chat</h5>
                    <p class="text-muted">Send a job request, negotiate terms, and communicate directly with your provider.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 h-100">
                    <div class="mb-3">
                        <span class="badge bg-warning text-dark rounded-circle p-3 fs-5">3</span>
                    </div>
                    <h5 class="fw-bold">Pay & Review</h5>
                    <p class="text-muted">Pay securely once the job is done and leave a review to help the community.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" style="background: linear-gradient(135deg, #1f2937, #374151);">
    <div class="container text-center text-white">
        <h2 class="fw-bold mb-3">Are You a Skilled Worker?</h2>
        <p class="lead text-secondary mb-4">
            Join Handyman Hub and grow your income. Get verified, build your profile,
            and reach clients beyond your neighbourhood.
        </p>
        <a href="auth/register.php" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-person-plus me-2"></i>Register as a Provider
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>