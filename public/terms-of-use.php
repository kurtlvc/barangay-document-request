<?php
    $basePath = $basePath ?? '.';
    if (!isset($pageTitle)) { $pageTitle = 'Terms of Use'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — DokuBayan | Barangay Document Requests</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/bootstrap-icons/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/styles.css">
    <script>try{if(localStorage.getItem('dokubayan-theme')==='dark')document.documentElement.dataset.theme='dark';}catch(e){}</script>
</head>
<body>
    <script>if(document.documentElement.dataset.theme==='dark')document.body.dataset.theme='dark';</script>
    <nav class="navbar navbar-expand-lg p-3 fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="<?= htmlspecialchars($basePath) ?>/index.php"> <img src="<?= htmlspecialchars($basePath) ?>/logo.png" alt="DokuBayan"> DokuBayan</a>
            <button type="button" class="btn ms-auto" id="themeToggle" aria-label="Toggle theme" title="Toggle theme"><i class="bi bi-moon-fill"></i></button>
        </div>
    </nav>
    <main class="resident-content flex-grow-1 overflow-auto p-3 p-lg-4" style="padding-top: 6.5rem !important;">
        <div class="panel p-3 p-md-4 p-lg-5 mx-auto" style="max-width: 880px;">
            <span class="eyebrow-badge p-1 rounded">DOKUBAYAN</span>
            <h2 class="heading-green fw-bold mt-3">Terms of Use</h2>
            <p class="text-muted-soft small">Last updated: October 2026</p>

            <h5 class="fw-bold mt-4">1. Acceptance</h5>
            <p class="text-muted-soft">By creating an account or submitting a request on DokuBayan, you agree to these terms and to the Privacy Policy.</p>

            <h5 class="fw-bold mt-4">2. Eligibility and verification</h5>
            <ul class="text-muted-soft">
                <li>Accounts are for residents served by Barangay Mamatid.</li>
                <li>A barangay administrator must verify your resident profile before you can submit document requests.</li>
                <li>If you change your name or address later, your profile returns to pending status until it is verified again.</li>
            </ul>

            <h5 class="fw-bold mt-4">3. Your account</h5>
            <ul class="text-muted-soft">
                <li>Provide accurate personal information when registering.</li>
                <li>Keep your password private. You are responsible for activity under your account.</li>
                <li>One account per person. Duplicate or false accounts may be deactivated.</li>
            </ul>

            <h5 class="fw-bold mt-4">4. Document requests</h5>
            <ul class="text-muted-soft">
                <li>State the true purpose of each request.</li>
                <li>Bring the listed requirements when claiming your document at the barangay hall.</li>
                <li>Only pending requests can be cancelled. Approved, released, or rejected requests follow barangay procedure — ask the staff for assistance.</li>
                <li>Processing times shown are estimates; release depends on verification and availability of the signatory.</li>
            </ul>

            <h5 class="fw-bold mt-4">5. Acceptable use</h5>
            <p class="text-muted-soft">Do not misuse the service: no false information, no requests on behalf of another person without authorization, and no attempts to access other users' accounts or disrupt the system. Violations may lead to deactivation of your account.</p>

            <h5 class="fw-bold mt-4">6. Availability</h5>
            <p class="text-muted-soft">The service is provided on a best-effort basis. Scheduled maintenance or outages may temporarily prevent access; in that case, transact directly at the barangay hall.</p>

            <h5 class="fw-bold mt-4">7. Contact</h5>
            <p class="text-muted-soft mb-0">Barangay Mamatid Hall, City of Cabuyao, Laguna · Mon–Fri, 8:00 AM – 5:00 PM · (049) 123-4567 · mamatid.barangay@lgu.gov.ph</p>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="<?= htmlspecialchars($basePath) ?>/index.php" class="btn btn-outline-brand">Back to Home</a>
                <a href="<?= htmlspecialchars($basePath) ?>/privacy-policy.php" class="btn btn-brand">Privacy Policy</a>
            </div>
        </div>
    </main>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/script.js" defer></script>
</body>
</html>
