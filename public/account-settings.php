<?php
require_once __DIR__ . '/includes/functions.php';
bdr_start_session();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Profile";
$pageDescription = "Manage your account informations";
$role = $_SESSION['role'] ?? 'resident';
$name = htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8');
$status = ($_SESSION['resident_status'] ?? 'pending') === 'verified' ? 'Verified' : 'Pending Verification';
$basePath = bdr_base_path();
?>
<?php include __DIR__ . '/page-layout/header.php' ?>
<main class="account-settings-main flex-grow-1 overflow-auto p-3 p-lg-4">
    <div class="edit-panel m-3">
        <div class="main-card m-3 p-3 p-md-5 border border-2 rounded-3">
            <h3 class="fw-bold panel-title mb-4">Personal Information</h3>
            <div class="alert d-none" id="personalAlert" role="alert"></div>
            <form class="personinfo-form" id="personalForm" novalidate>
                <?php if ($role === 'resident'): ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="editFirstName">First Name</label>
                        <input type="text" class="form-control" id="editFirstName" autocomplete="given-name" value="" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editLastName">Last Name</label>
                        <input type="text" class="form-control" id="editLastName" autocomplete="family-name" value="" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="editContact">Contact Number</label>
                        <input type="tel" class="form-control" id="editContact" autocomplete="tel" placeholder="09171234567" value="" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editAddress">Address</label>
                        <input type="text" class="form-control" id="editAddress" autocomplete="street-address" value="" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="accountStatus">Account Status</label>
                        <input class="form-control text-secondary" id="accountStatus" value="<?= $status ?>" disabled>
                    </div>
                </div>
                <?php else: ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="editName">Full Name</label>
                        <input type="text" class="form-control" id="editName" autocomplete="name" value="<?= $name ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="accountRole">Role</label>
                        <input class="form-control text-secondary" id="accountRole" value="<?= $role === 'admin' ? 'Barangay Administrator' : 'Barangay Staff' ?>" disabled>
                    </div>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn continue-btn mt-4 text-light w-100 w-sm-auto" id="personalSubmit">Submit Changes</button>
            </form>
        </div>
        <div class="main-card m-3 p-3 p-md-5 border border-2 rounded-3">
            <h3 class="fw-bold panel-title mb-4">Sign in and Security</h3>
            <div class="alert d-none" id="securityAlert" role="alert"></div>
            <form class="personinfo-form" id="securityForm" novalidate>
                <div class="mb-3">
                    <label for="editEmail">Email Address</label>
                    <input type="email" class="form-control" id="editEmail" autocomplete="email" value="<?= $email ?>" required>
                </div>
                <p class="text-muted-soft small mb-2">Leave the password fields blank to keep your current password.</p>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="currentPass">Current Password</label>
                        <input type="password" class="form-control" id="currentPass" autocomplete="current-password" value="">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="editPass">New Password</label>
                        <input type="password" class="form-control" id="editPass" autocomplete="new-password" minlength="8" value="">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="confirmPass">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPass" autocomplete="new-password" minlength="8" value="">
                    </div>
                </div>
                <button type="submit" class="btn continue-btn mt-4 text-light w-100 w-sm-auto" id="securitySubmit">Submit Changes</button>
            </form>
        </div>
    </div>
</main>
<?php $pageScripts = ['assets/js/account-settings.js']; include __DIR__ . '/page-layout/footer.php' ?>
