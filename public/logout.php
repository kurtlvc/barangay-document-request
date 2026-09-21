<?php
require_once __DIR__ . '/includes/functions.php';
bdr_start_session();
bdr_end_session();
header("Location: login.php");
exit;
