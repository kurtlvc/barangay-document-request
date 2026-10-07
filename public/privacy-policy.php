<?php
    $basePath = $basePath ?? '.';
    if (!isset($pageTitle)) { $pageTitle = 'Privacy Policy'; }
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
            <h2 class="heading-green fw-bold mt-3">Privacy Policy</h2>
            <p class="text-muted-soft small">Last updated: October 2026</p>

            <h5 class="fw-bold mt-4">1. Introduction</h5>
            <p class="text-muted-soft">DokuBayan is the online document request service of Barangay Mamatid, City of Cabuyao, Laguna. This policy explains what personal information we collect, why we collect it, and how it is handled when you create an account and request barangay documents.</p>

            <h5 class="fw-bold mt-4">2. Information we collect</h5>
            <p class="text-muted-soft mb-1">When you register and use the service, we collect:</p>
            <ul class="text-muted-soft">
                <li>Account details: full name, email address, and password (stored as a one-way hash, never in plain text).</li>
                <li>Resident profile: contact number, street address, and purok/phase.</li>
                <li>Request records: document type requested, purpose, request dates, status changes, and remarks.</li>
            </ul>

            <h5 class="fw-bold mt-4">3. How we use your information</h5>
            <ul class="text-muted-soft">
                <li>To verify that you are a resident of the barangay.</li>
                <li>To process, track, and release your document requests.</li>
                <li>To contact you about your requests or your account if needed.</li>
            </ul>

            <h5 class="fw-bold mt-4">4. Who can see your information</h5>
            <p class="text-muted-soft">Your information is visible only to authorized barangay staff and administrators for the purposes above. It is not sold, shared with third parties for marketing, or published publicly.</p>

            <h5 class="fw-bold mt-4">5. How we protect your information</h5>
            <ul class="text-muted-soft">
                <li>Passwords are hashed and never stored in readable form.</li>
                <li>Account sessions expire and are validated on every page.</li>
                <li>Changes to account details are protected against forgery and require you to be signed in.</li>
            </ul>

            <h5 class="fw-bold mt-4">6. Your rights</h5>
            <p class="text-muted-soft">You may review and update your personal information at any time through Account Settings. Changing your name or address will require your profile to be verified again. If you want your account removed, visit the barangay hall or contact us below.</p>

            <h5 class="fw-bold mt-4">7. Contact</h5>
            <p class="text-muted-soft mb-0">Barangay Mamatid Hall, City of Cabuyao, Laguna · Mon–Fri, 8:00 AM – 5:00 PM · (049) 123-4567 · mamatid.barangay@lgu.gov.ph</p>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="<?= htmlspecialchars($basePath) ?>/index.php" class="btn btn-outline-brand">Back to Home</a>
                <a href="<?= htmlspecialchars($basePath) ?>/terms-of-use.php" class="btn btn-brand">Terms of Use</a>
            </div>
        </div>
    </main>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/script.js" defer></script>
</body>
</html>
