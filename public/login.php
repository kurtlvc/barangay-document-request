<?php
require './includes/functions.php';
bdr_start_session();

if (isset($_SESSION['user_id'])) {
    header('Location: ./dashboard.php');
    exit;
}
?>
<?php
    $pageTitle = "Login | Register"
?>
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
    <link rel="stylesheet" href="./assets/css/styles.css">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg p-3 fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="index.php"> <img src="logo.png" alt="."> Request</a>
        </div>
    </nav>
    <main class="form-page row">
        <section class="intro-panel col-lg-6">
            <div class="container px-xl-5 ">
                <p class="service-label badge rounded-pill">ONLINE DOCUMENT REQUEST SERVICE OF BRGY. </p>
                <h1 class="intro fw-bold">Request barangay documents without <br> the long wait.</h1>
                <p class="sub-intro">Submit document requests online, follow their status, and know when your document is ready for release.</p>
            </div>    
        </section>
        <section class="form-panel col-lg-6 px-xl-5 justify-content-center">
            <div class="form-container p-5 mt-5">
                <nav class="form-tabs p-1 bg-body-secondary border rounded-3">
                    <div class="nav nav-pills nav-fill gap-1" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="nav-login-tab" data-bs-toggle="tab" data-bs-target="#login-pane" type="button" role="tab" aria-controls="login-pane" aria-selected="true">Login</button>
                        <button class="nav-link" id="nav-register-tab" data-bs-toggle="tab" data-bs-target="#register-pane" type="button" role="tab" aria-controls="register-pane" aria-selected="false">Register</button>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="login-pane" role="tabpanel" aria-labelledby="nav-login-tab" tabindex="0">
                        <div class="form-heading mt-3 text-center">
                            <p class="sub-intro">New Resident? Click the register above to create your account.</p>
                            <h2 class="fw-bold m-4">Welcome Back!</h2>
                        </div>
                        <form id="loginForm" class="login-form m-3">
                            <div class="mb-3">
                                <label for="emailInput" class="form-label">Email address</label>
                                <input type="email" name="email" class="form-control" id="emailInput" placeholder="juandelacruz@email.com" autocomplete="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="passwordInput" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" id="passwordInput" placeholder="Enter your password" autocomplete="current-password" required>
                            </div>
                            <div class="mb-3 d-flex justify-content-end">
                                <a href="#" class="forgotpass">Forgot Password?</a>
                            </div>
                            <button type="submit" class="btn btn-login col-12">Login to Account</button>
                            <p id="loginMessage" class="visually-hidden" role="status" aria-live="polite"></p>
                        </form>
                    </div>
                    <div class="tab-pane" id="register-pane" role="tabpanel" aria-labelledby="nav-register-tab" tabindex="0">
                        <div class="form-heading mt-3 text-center">
                            <p class="sub-intro">Already have an account? Click the Login above to sign in.</p>
                            <h2 class="fw-bold m-4">Create Resident Account</h2>
                        </div>
                        <form action="" class="register-form m-3" id="registerForm">
                            <div class="d-flex mb-4" id="stepIndicators">
                                <div class="step-item active" id="s1">
                                    <div class="step-circle">1</div>
                                    <div class="small mt-1 fw-semibold">Personal Information</div>
                                </div>
                                <div class="step-item" id="s2">
                                    <div class="step-circle">2</div>
                                    <div class="small mt-1 fw-semibold">Address</div>
                                </div>
                                <div class="step-item" id="s3">
                                    <div class="step-circle">3</div>
                                    <div class="small mt-1 fw-semibold">Account Credentials</div>
                                </div>
                            </div>
                            <section class="form-step active" id="step1" >
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2">
                                        <label for="firstNameInput" class="form-label">First Name</label>
                                        <input type="text" name="first_name" class="form-control" id="firstNameInput" placeholder="Juan" required>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="lastNameInput" class="form-label">Last Name</label>
                                        <input type="text" name="last_name" class="form-control" id="lastNameInput" placeholder="Dela Cruz" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2">
                                        <label for="registerEmailInput" class="form-label">Email address</label>
                                        <input type="email" name="email" class="form-control" id="registerEmailInput" placeholder="jdelacruz@email.com" required>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="contactnumInput" class="form-label">Contact Number</label>
                                        <input type="tel" name="contact_number" class="form-control" id="contactnumInput" placeholder="09171234567" required>
                                    </div>
                                </div>
                            </section>
                            <section class="form-step" id="step2">
                                <div class="mb-3">
                                    <label for="streetaddInput" class="form-label">Street Address</label>
                                    <input type="text" name="street_address" class="form-control" id="streetaddInput" placeholder="Street Name, Building, House No." required>
                                </div>
                                <div class="mb-3">
                                    <label for="purokaddInput" class="form-label">Purok / Phase</label>
                                    <input type="text" name="purok" class="form-control" id="purokaddInput" placeholder="Phase 1 / Purok 2" required>
                                </div>
                            </section>
                            <section class="form-step" id="step3">
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="registerPasswordInput" class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" id="registerPasswordInput" placeholder="Min. 8 characters" minlength="8" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirmpassInput" class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" id="confirmpassInput" placeholder="Re-enter password" minlength="8" required>
                                    </div>
                                </div>
                            </section>
                        </form>
                        <div class="register-actions pt-4 d-flex justify-content-between">
                            <button type="button" class="btn back-btn" id="prevBtn" disabled>Back</button>
                            <button type="button" class="btn continue-btn" id="nextBtn">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="loginToast" class="toast align-items-center border-0" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="loginToastMessage"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="./assets/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/login.js" defer></script>
    <script src="./assets/js/script.js" defer></script>
</body>
</html>
