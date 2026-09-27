<?php
require_once __DIR__ . '/includes/functions.php';
bdr_start_session();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pageTitle = 'Profile';
$pageDescription = 'Manage your account information';
$role = $_SESSION['role'] ?? 'resident';
$username = htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8');
$basePath = bdr_base_path();
?>
<?php include __DIR__ . '/page-layout/header.php' ?>
<main class="px-5">
    <div class="edit-panel m-3">
        <div class="main-card m-3 p-5 border border-2 rounded-3">
            <h3 class="fw-bold panel-title mb-4">Personal Information</h3>
            <form action="" class="personinfo-form ">
                <?php if ($role === 'resident'): ?>
                <div class="mb-3">
                    <label for="editName">Full Name </label>
                    <input type="text" class="form-control" id="editName" value="<?= $username?>">
                </div>
                <?php elseif ($role === 'staff'): ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="editName">Full Name </label>
                        <input type="text" class="form-control" id="editName" value="<?= $username?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editName">Role </label>
                        <input class="form-control text-secondary" value="Barangay Staff" disabled>
                    </div>
                </div>
                <?php else: ($role === 'admin') ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="editName">Full Name </label>
                        <input type="text" class="form-control" id="editName" value="<?= $username?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editName">Role </label>
                        <input class="form-control text-secondary" value="Barangay Administrator" disabled>
                    </div>
                </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="editContact" >Contact Number </label>
                        <input type="tel" class="form-control" id="editContact" value="0900 000 0000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editContact" >Address </label>
                        <input type="text" class="form-control" id="editContact" value="Full Address">
                    </div>
                </div>
                <button type="submit" class="btn continue-btn mt-4 text-light">Submit Changes</button>
            </form>
        </div>
        <div class="main-card m-3 p-5 border border-2 rounded-3 ">
            <h3 class="fw-bold panel-title mb-4">Sign in and Security</h3>
            <form action="" class="personinfo-form">
                <div class="mb-3">
                    <label for="editEmail">Email Address </label>
                    <input type="text" class="form-control" id="editEmail" value="email@email.com">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="currentPass" >Current Password </label>
                        <input type="password" class="form-control" id="currentPass" value="">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editPass" >New Password </label>
                        <input type="password" class="form-control" id="editPass" value="">
                    </div>
                </div>
                
                <button type="submit" class="btn continue-btn mt-4 text-light">Submit Changes</button>
            </form>
        </div>
    </div>
</main>
<?php include __DIR__ . '/page-layout/footer.php' ?>
