<?php
require_once __DIR__ . '/../includes/page-guard.php';
bdr_require_page_role(['resident']);
$pageTitle = "My Requests";
$pageDescription = "Track your submitted document requests here";
?> 

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <div class="flex-grow-1 overflow-auto p-3">
        
    </div>
<?php include __DIR__ . '/../page-layout/footer.php' ?>