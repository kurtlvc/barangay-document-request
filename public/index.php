<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        require_once __DIR__ . '/config/database.php';
        $landingDocuments = $pdo->query('SELECT document_name, fee, processing_days FROM document_types WHERE is_active = 1 ORDER BY document_name LIMIT 4')->fetchAll();
        $basePath = $basePath ?? '.';
        if (!isset($pageTitle)) { $pageTitle = 'DokuBayan | Barangay Document Request'; }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — DokuBayan |  Barangay Document Requests</title>
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
            <div class="nav-dropdown d-flex">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="navbar-menu-container justify-content-end collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav align-items-end">
                    <a href="#aboutpage" class="nav-item nav-link">About</a>
                    <a href="#howpage" class="nav-item nav-link">How it works</a>
                    <button type="button" class="btn ms-lg-2" id="themeToggle" aria-label="Toggle theme" title="Toggle theme"><i class="bi bi-moon-fill"></i></button>
                </div>
                <div class="d-flex d-lg-none flex-column gap-2 mt-3 w-100">
                    <a class="btn btn-brand" href="login.php#register-pane">Register as Resident</a>
                    <a class="btn btn-outline-brand" href="login.php">Login</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="landing-page row">
        <section class="intro-panel col-lg-6">
            <div class="intro-content container px-xl-5 mx-auto">
                <p class="service-label badge rounded-pill mt-5">ONLINE DOCUMENT REQUEST SERVICE</p>
                <h1 class="intro fw-bold mb-4">Request barangay documents without <br>the long wait.</h1>
                <p class="sub-text">Submit document requests online, follow their status, and know when your document is ready for release.</p>
                <div class="cta-group d-flex gap-3 mt-5">
                    <a class="btn btn-register p-3" href="login.php#register-pane">Register as Resident</a>
                    <a class="btn login-btn p-3" href="login.php">Login</a>
                </div>
            </div>
        </section>
        
        <section class="documents-panel col-lg-6">
            <div class="container pt-5 px-xl-5">
                <h4 class="fw-bold">Here are some example of documents you can request</h4>
                <p class=" mb-4">Choose from the barangay's most commonly requested documents.<br> Fees and processing times are shown so you know what to expect.</p>
                <div class="document-list p-4 mt-4">
                    <?php foreach ($landingDocuments as $document): ?>
                    <article class="document-item list-group-item d-flex gap-3">
                        <div class="flex-grow-1">
                            <h6><?= htmlspecialchars($document['document_name']) ?></h6>
                            <p><?= number_format((float) $document['fee'], 2) === '0.00' ? 'No fee' : 'Fee: ₱' . number_format((float) $document['fee'], 2) ?> · <?= (int) $document['processing_days'] ?> processing day<?= (int) $document['processing_days'] === 1 ? '' : 's' ?></p>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    <?php if (!$landingDocuments): ?><p class="text-muted">No documents are currently available for request.</p><?php endif; ?>
                </div>
            </div>
        </section>
        
        <section class="about-page row pt-5" id="aboutpage">
            <div class="about-img container col-md-5">
                <img src="<?= htmlspecialchars($basePath) ?>/logo.png" alt="DokuBayan Logo" class="img-fluid pt-5 ps-5">
            </div>
            <div class="col-lg-6 p-4 p-lg-5 align-items-center">
                <h2 class="fw-bold">ABOUT THE SYSTEM</h2>
                <p class="description my-5">Making barangay document request service easier for everyone.</p>
                <p class="description bp-5 mb-5"> Our online document request system provides residents with a convenient way to request and track barangay documents.</p>
                <div class="advantages col align-items-center d-inline-flex gap-5">
                    <div class="row ">
                        <i class="check-icon bi-check-circle-fill col"></i>
                        <p class="col mt-2">Convenient</p>
                    </div>
                    <div class="row">
                        <i class="check-icon bi-check-circle-fill col"></i>
                        <p class="col mt-2">Transparent</p>
                    </div>
                    <div class="row">
                        <i class="check-icon bi-check-circle-fill col"></i>
                        <p class="col mt-2">Efficient</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="how-page py-5" id="howpage">
            <h1 class="fw-bold mt-5 text-center">HOW IT WORKS</h1>
            <div class="how-flow d-flex flex-column align-items-center my-5 px-4">
                <div class="first text-center">
                    <h2>1</h2>
                    <div class="d-flex justify-content-center">
                        <div class="how-steps"><i class="bi-file-earmark-text-fill" style="font-size: 3.3rem;"></i></div>
                    </div>
                    <h4>Choose a document</h4>
                </div>
                    <span class="how-connector m-3"><i class="bi bi-arrow-right" style="font-size: 3.5rem;"></i></span>
                <div class="second text-center">
                    <h2>2</h2>
                    <div class="d-flex justify-content-center">
                        <div class="how-steps align-items-center"><i class="bi-send-fill" style="font-size: 3.5rem;"></i></div>
                    </div>                        
                    <h4>Submit your request</h4>
                </div>
                    <span class="how-connector m-3"><i class="bi bi-arrow-right" style="font-size: 3.5rem;"></i></span>
                <div class="third text-center">
                    <h2>3</h2>
                    <div class="d-flex justify-content-center">
                        <div class="how-steps"><i class="bi-geo-alt-fill" style="font-size: 3.5rem;"></i></div>
                    </div>                        
                    <h4>Track request status</h4>
                </div>
                    <span class="how-connector m-3"><i class="bi bi-arrow-right" style="font-size: 3.5rem;"></i></span>
                <div class="fourth text-center">
                    <h2>4</h2>
                    <div class="d-flex justify-content-center">
                        <div class="how-steps"><i class="bi-save-fill" style="font-size: 3.3rem;"></i></div>
                    </div>                        
                    <h4>Claim your document</h4>
                </div>
            </div>
        </section>
        <section class="py-5 d-flex flex-column align-items-center">
            <h2 class="fw-bold m-3 text-center">READY TO REQUEST A DOCUMENT? </h2>
            <p class="description mt-4">Skip the uneccessary waiting.</p>
            <a href="login.php" class="btn btn-login p-3 my-4">Request a Document</a>
        </section>
    </main>
    <footer class="footer footer-expand-lg p-4">
        <div class="row container-fluid align-items-center">
            <section class="footer-img container col-lg-6 mb-4">
            <img src="<?= htmlspecialchars($basePath) ?>/logo.png" alt="DokuBayan Logo">
            </section>
            <section class="container col-lg-4 mb-4">
                <h4>VISIT OR CONTACT US</h4>                    
                <p class="sub-text"><i class="bi-geo-alt-fill"></i> Barangay Mamatid Hall, City of Cabuyao, Laguna</p>
                <p class="sub-text"><i class="bi-clock-fill"></i> Mon-Fri, 8:00 AM - 5:00 PM</p>
                <p class="sub-text"><i class="bi-telephone-fill"></i> (049) 123-4567</p>
                <p class="sub-text"><i class="bi-envelope-fill"></i> mamatid.barangay@lgu.gov.ph</p>
            </section>
            <p class="text-white text-center small">The official online service for requesting and tracking barangay documents removing long lines at the hall.</p>
            <hr>
            <section class="copyright row px-5 ">
                <p class="col-lg-9 sub-text"><i class="bi-c-circle"></i> 2026 Brgy. Mamatid - Local Government Unit. All rights reserved</p>
                <a href="privacy-policy.php" class="col sub-text">Privacy Policy</a>
                <a href="terms-of-use.php" class="col sub-text">Terms of Use</a>
            </section>
            
        </div>
    </footer>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/script.js" defer></script>
</body>
</html>
