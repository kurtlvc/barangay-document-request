<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        if (!isset($pageTitle)) { $pageTitle = 'Document Request?'; }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Barangay Document Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg p-3 fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="#"> <img src="logo.png" alt="."> Request</a>
            <div class="nav-dropdown d-flex">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="navbar-menu-container justify-content-end collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav align-items-end">
                    <a href="#aboutpage" class="nav-item nav-link">About</a>
                    <a href="#howpage" class="nav-item nav-link">How it works</a>
                    <a href="#" class="nav-item nav-link"><i class="bi bi-moon-fill"></i></a>
                </div>
            </div>
        </div>
    </nav>
    <main class="landing-page row">
        <section class="intro-panel col-lg-6">
            <div class="intro-content container px-xl-5">
                <p class="service-label badge rounded-pill mt-5">ONLINE DOCUMENT REQUEST SERVICE OF BRGY. </p>
                <h1 class="intro fw-bold">Request barangay documents without <br>the long wait.</h1>
                <p class="sub-intro">Submit document requests online, follow their status, and know when your document is ready for release.</p>
                <div class="cta-group d-flex gap-3 mt-5">
                    <a class="btn btn-register" href="login.php#register-pane">Register as Resident</a>
                    <a class="btn login-btn px-4" href="login.php">Log In</a>
                </div>
            </div>
        </section>
        
        <section class="documents-panel col-lg-6">
            <div class="container pt-5 px-xl-5">
                <h4 class="fw-bold">Here are some example of documents you can request</h4>
                <p class=" mb-4">Choose from the barangay's most commonly requested documents.<br> Fees and processing times are shown so you know what to expect.</p>
                <div class="document-list p-4 mt-4">
                    <article class="document-item list-group-item d-flex gap-3">
                        <img src="" alt="Barangay Clearance" class="document-icon flex-shrink-0"></img>
                        <div class="flex-grow-1">
                            <h6>Barangay Clearance</h6>
                            <p>General-purpose clearance for employment, permits, or transactions</p>
                        </div>
                    </article>
                    <article class="document-item list-group-item d-flex gap-3">
                        <img src="" alt="Certificate of Recidency" class="document-icon flex-shrink-0"></img>
                        <div class="flex-grow-1">
                            <h6>Certificate of Residency</h6>
                            <p>Proof that you currently reside within the barangay</p>
                        </div>
                    </article>
                    <article class="document-item list-group-item d-flex gap-3">
                        <img src="" alt="Certificate of Indigency" class="document-icon flex-shrink-0"></img>
                        <div class="flex-grow-1">
                            <h6>Certificate of Indigency</h6>
                            <p>For residents availing a government assistance or subsidies</p>
                        </div>
                    </article>
                    <article class="document-item list-group-item d-flex gap-3">
                        <img src="" alt="Business Permit Endorsement" class="document-icon flex-shrink-0"></img>
                        <div class="">
                            <h6>Business Permit Endorsement</h6>
                            <p>Barangay endorsement required for business permit applications</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>
        
        <section class="about-page row pt-5" id="aboutpage">
            <div class=" about-img container col-md-4">
                <img src="https://fishry.com/wp-content/uploads/2024/05/fishry-banner-13-1024x829.png" alt="Brgy. System">
            </div>
            <div class="col-lg-6 m-5 pt-5 align-items-center">
                <h2 class="fw-bold">ABOUT THE SYSTEM</h2>
                <p class="sub-intro my-5">Making barangay document request service easier for everyone.</p>
                <p class="sub-intro bp-5 mb-5"> Our online document request system provides residents with a convenient way to request and track barangay documents.</p>
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
            <p class="sub-intro mt-4">Skip the uneccessary waiting.</p>
            <a href="login.php" class="btn btn-login px-5 my-4">Request a Document</a>
        </section>
    </main>
    <footer class="footer footer-expand-lg p-4">
        <div class="row container-fluid align-items-center">
            <section class="container col-lg-5 mb-4">
                <h3 class="fw-bold"><img src="logo.png" alt="."> Request</h3>
                <p class="sub-intro">The official online service for requesting and tracking barangay documents - no more long lines at the hall.</p>
            </section>
            <section class="container col-lg-4 mb-4">
                <h4>VISIT OR CONTACT US</h4>                    
                <p class="sub-intro"><i class="bi-geo-alt-fill"></i> Barangay Mamatid Hall, City of Cabuyao, Laguna</p>
                <p class="sub-intro"><i class="bi-clock-fill"></i> Mon-Fri, 8:00 AM - 5:00 PM</p>
                <p class="sub-intro"><i class="bi-telephone-fill"></i> (049) 123-4567</p>
                <p class="sub-intro"><i class="bi-envelope-fill"></i> mamatid.barangay@lgu.gov.ph</p>
            </section>
            <hr>
            <section class="copyright row px-5 ">
                <p class="col-lg-9 sub-intro"><i class="bi-c-circle"></i> 2026 Brgy. Mamatid - Local Government Unit. All rights reserved</p>
                <a href="privacy-policy" class="col sub-intro">Privacy Policy</a>
                <a href="terms-of-use" class="col sub-intro">Terms of Use</a>
            </section>
            
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('#navbarCollapse .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const navbar = document.getElementById('navbarCollapse');
        
                const collapse = bootstrap.Collapse.getInstance(navbar) || new bootstrap.Collapse(navbar, { toggle: false });
        
                collapse.hide();
            });
        });
    </script>
</body>
</html>