<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/database.php';

start_secure_session();
$user = require_login();

// Example data — replace with real queries for your app.
json_response([
    'user' => $user,
    'stats' => [
        'member_since' => 'Fetch this from the users table if you need it.',
        'note' => 'Add whatever dashboard data your app needs here.',
    ],
]);
