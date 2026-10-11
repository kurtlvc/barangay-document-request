<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
bdr_start_session();
bdr_require_csrf();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'csrf-token') {
    echo json_encode(['status' => 'ok', 'csrf_token' => $_SESSION['csrf_token']]);
    exit;
}

if ($action === 'me') {
    $user = bdr_require_auth();
    echo json_encode(['status' => 'ok', 'user' => $user]);
    exit;
}

if ($action === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
    }

    $_SESSION = [];
    session_destroy();
    bdr_start_session();
    echo json_encode(['status' => 'ok', 'message' => 'Logged out.', 'csrf_token' => $_SESSION['csrf_token']]);
    exit;
}

// Registration Flow
if ($action === 'register') {
    try {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');

        // Fallback if full name was submitted
        if ($firstName === '' && !empty($_POST['name'])) {
            $nameParts = explode(' ', trim($_POST['name']), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
        }

        $email = trim(strtolower($_POST['email'] ?? ''));
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $streetAddress = trim($_POST['street_address'] ?? '');
        $purok = trim($_POST['purok'] ?? '');

        $address = trim($streetAddress . ($streetAddress !== '' && $purok !== '' ? ', ' : '') . $purok);
        if ($address === '' && !empty($_POST['address'])) {
            $address = trim($_POST['address']);
        }

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $address === '' || $contactNumber === '') {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
            exit;
        }

        if (strlen($password) < 8) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
            exit;
        }

        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
            exit;
        }

        // Security questions for offline password reset
        $allowedQuestions = bdr_security_questions();
        $sq1 = $_POST['security_q1'] ?? '';
        $sq2 = $_POST['security_q2'] ?? '';
        $sa1 = trim($_POST['security_a1'] ?? '');
        $sa2 = trim($_POST['security_a2'] ?? '');
        if (!isset($allowedQuestions[$sq1]) || !isset($allowedQuestions[$sq2]) || $sq1 === $sq2) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Choose two different security questions.']);
            exit;
        }
        if ($sa1 === '' || $sa2 === '' || mb_strlen($sa1) > 100 || mb_strlen($sa2) > 100) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Answer both security questions (100 characters or fewer).']);
            exit;
        }

        // Check if email is already taken
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'An account with this email address already exists.']);
            exit;
        }

        $pdo->beginTransaction();

        $fullName = trim("$firstName $lastName");
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 1. Insert into users
        $stmtUser = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, security_question1, security_answer1_hash, security_question2, security_answer2_hash)
            VALUES (:name, :email, :password_hash, 'resident', :sq1, :sa1, :sq2, :sa2)
        ");
        $stmtUser->execute([
            ':name' => $fullName,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':sq1' => $sq1,
            ':sa1' => password_hash(bdr_normalize_answer($sa1), PASSWORD_DEFAULT),
            ':sq2' => $sq2,
            ':sa2' => password_hash(bdr_normalize_answer($sa2), PASSWORD_DEFAULT),
        ]);
        $newUserId = (int)$pdo->lastInsertId();

        // 2. Check if a pre-existing walk-in record matches this resident
        $stmtMatch = $pdo->prepare("
            SELECT resident_id, status FROM residents 
            WHERE LOWER(first_name) = LOWER(:first_name) 
              AND LOWER(last_name) = LOWER(:last_name) 
              AND user_id IS NULL 
            LIMIT 1
        ");
        $stmtMatch->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
        ]);
        $matchedResident = $stmtMatch->fetch();

        if ($matchedResident) {
            // Link existing resident record
            $residentId = (int)$matchedResident['resident_id'];
            $residentStatus = $matchedResident['status'] ?: 'verified';
            $stmtUpdateResident = $pdo->prepare("
                UPDATE residents 
                SET user_id = :user_id,
                    address = :address,
                    contact_number = :contact_number
                WHERE resident_id = :resident_id
            ");
            $stmtUpdateResident->execute([
                ':user_id' => $newUserId,
                ':address' => $address,
                ':contact_number' => $contactNumber,
                ':resident_id' => $residentId,
            ]);
        } else {
            // Create new resident profile
            $stmtInsertResident = $pdo->prepare("
                INSERT INTO residents (user_id, first_name, last_name, address, contact_number, status) 
                VALUES (:user_id, :first_name, :last_name, :address, :contact_number, 'pending')
            ");
            $stmtInsertResident->execute([
                ':user_id' => $newUserId,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':address' => $address,
                ':contact_number' => $contactNumber,
            ]);
            $residentId = (int)$pdo->lastInsertId();
            $residentStatus = 'pending';
        }

        $pdo->commit();

        // Set session for immediate login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['name'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'resident';
        $_SESSION['resident_id'] = $residentId;
        $_SESSION['resident_status'] = $residentStatus;

        echo json_encode([
            'status' => 'ok',
            'message' => 'Registration successful! Welcome to the portal.',
            'role' => 'resident',
            'resident_id' => $residentId,
            'resident_status' => $residentStatus
        ]);
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error during registration.']);
        exit;
    }
}

// Offline password reset via security questions (no email/SMS).
// Step 1: look up the account's questions.
if ($action === 'request-reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $stmt = $pdo->prepare('SELECT security_question1, security_question2 FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    $allowedQuestions = bdr_security_questions();
    if (!$row || empty($row['security_question1']) || empty($row['security_question2'])
        || !isset($allowedQuestions[$row['security_question1']]) || !isset($allowedQuestions[$row['security_question2']])) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'No password reset is available for that email. Please visit the barangay hall or ask an administrator.']);
        exit;
    }
    echo json_encode(['status' => 'ok', 'questions' => [
        'q1' => $allowedQuestions[$row['security_question1']],
        'q2' => $allowedQuestions[$row['security_question2']],
    ]]);
    exit;
}

// Step 2: verify answers and set the new password. Throttled per email.
if ($action === 'confirm-reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $answer1 = $_POST['answer1'] ?? '';
    $answer2 = $_POST['answer2'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    $attempts = $_SESSION['pw_reset_attempts'][$email] ?? ['count' => 0, 'first' => time()];
    if ($attempts['count'] >= 5 && (time() - $attempts['first']) < 900) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Try again in 15 minutes or visit the barangay hall.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, security_answer1_hash, security_answer2_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    $valid = $row && !empty($row['security_answer1_hash']) && !empty($row['security_answer2_hash'])
        && password_verify(bdr_normalize_answer($answer1), $row['security_answer1_hash'])
        && password_verify(bdr_normalize_answer($answer2), $row['security_answer2_hash']);
    if (!$valid) {
        $attempts['count']++;
        $_SESSION['pw_reset_attempts'][$email] = $attempts;
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Security answers do not match.']);
        exit;
    }

    if (strlen($newPassword) < 8) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $row['id']]);
    unset($_SESSION['pw_reset_attempts'][$email]);
    echo json_encode(['status' => 'ok', 'message' => 'Password reset. You can now log in.']);
    exit;
}

// User is logging in
if (isset($_POST['email']) && isset($_POST['password'])) {
    try {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Query users with LEFT JOIN to residents
        $sql = "SELECT 
                    u.id AS user_id, 
                    u.name, 
                    u.email, 
                    u.password_hash, 
                    u.role, u.is_active,
                    r.resident_id,
                    r.status AS resident_status,
                    r.first_name,
                    r.last_name,
                    r.address,
                    r.contact_number
                FROM users u
                LEFT JOIN residents r ON r.user_id = u.id
                WHERE u.email = :email 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        // Check password
        if ($row && (int) $row['is_active'] === 1 && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$row['user_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['resident_id'] = $row['resident_id'] !== null ? (int)$row['resident_id'] : null;
            $_SESSION['resident_status'] = $row['resident_status'] ?? null;

            echo json_encode([
                'status' => 'ok',
                'message' => 'Login successful',
                'role' => $row['role'],
                'resident_id' => $_SESSION['resident_id'],
                'resident_status' => $_SESSION['resident_status']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while processing your request', 'debug' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}