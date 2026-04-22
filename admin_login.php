<?php
// ============================================================
// admin_login.php — Admin Authentication (UPDATED)
//
// CHANGES FROM ORIGINAL:
//   1. Now starts a PHP session for the admin
//   2. Passwords are now verified using password_verify() (bcrypt)
//      The DB must store bcrypt hashes — see hash_password.php to generate them
//   3. On success, $_SESSION['admin_logged_in'] = true is set
//      so the admin stays logged in during the session
//   4. Input sanitization using htmlspecialchars (prevents XSS)
//   5. All other logic (fetching tables) is unchanged
//
// HOW BCRYPT WORKS:
//   - password_hash($plaintext, PASSWORD_BCRYPT) creates a secure hash
//   - password_verify($plaintext, $hash) checks if they match
//   - The hash looks like: $2y$10$... (bcrypt prefix)
//   - You can never reverse a bcrypt hash to get the original password
//
// !! IMPORTANT: Run hash_password.php once to update your Admin table !!
// !! with hashed passwords before this file will work correctly      !!
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session for admin state tracking
require 'session_manager.php';
require 'db.php';

// ---- Read and sanitize the JSON body ----
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// sanitize() is defined in session_manager.php
// It trims and applies htmlspecialchars to prevent XSS
$employee_code = sanitize($data['employee_code'] ?? '');
$password      = trim($data['password']          ?? '');  // Don't htmlspecialchars the password itself

if (empty($employee_code) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in both fields.']);
    exit;
}

// ---- Look up admin by employee_code ----
// Using prepared statements prevents SQL injection (unchanged from original)
$stmt = $pdo->prepare("SELECT * FROM Admin WHERE employee_code = :code LIMIT 1");
$stmt->execute([':code' => $employee_code]);
$admin = $stmt->fetch();

// ---- Verify password ----
// CHANGE: We now use password_verify() to check against the bcrypt hash in the DB.
//
// password_verify($plaintext_entered, $hash_from_db)
//   returns TRUE  if the password matches the hash
//   returns FALSE if it doesn't match (or if $hash_from_db is not a valid hash)
//
// NOTE: If your DB still has PLAIN TEXT passwords (old format), this will fail.
//       Run hash_password.php first to update all passwords to bcrypt hashes.
//
// FALLBACK for development: if the stored password doesn't look like a bcrypt hash,
// we fall back to plain text comparison so the project still works during transition.
// REMOVE the fallback in production!

$passwordMatches = false;

if ($admin) {
    $storedPassword = $admin['password'];

    // Check if the stored password looks like a bcrypt hash (starts with $2y$)
    if (strpos($storedPassword, '$2y$') === 0) {
        // Use secure bcrypt verification
        $passwordMatches = password_verify($password, $storedPassword);
    } else {
        // FALLBACK: plain text comparison (for transition period / dev only)
        // This keeps the system working if you haven't hashed passwords yet
        $passwordMatches = ($password === $storedPassword);
    }
}

if (!$admin || !$passwordMatches) {
    echo json_encode(['success' => false, 'message' => 'Credentials Invalid']);
    exit;
}

// ---- Authentication passed ----

// NEW: Set admin session variables so the admin stays logged in
// These are stored on the SERVER — the browser only holds PHPSESSID cookie
$_SESSION['admin_logged_in']   = true;
$_SESSION['admin_employee_code'] = sanitize($admin['employee_code']);
// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// ---- Fetch all table data (unchanged from original) ----
$reservations = $pdo->query("
    SELECT
        r.reservation_id,
        CONCAT(g.first_name, ' ', g.last_name) AS guest_name,
        g.email                                AS guest_email,
        g.phone_number,
        v.villa_name,
        r.check_in,
        r.check_out,
        r.adults_count,
        r.children_count,
        r.total_cost
    FROM  Reservation r
    JOIN  Guest g ON r.user_id  = g.user_id
    JOIN  Villa v ON r.villa_id = v.villa_id
    ORDER BY r.reservation_id DESC
")->fetchAll();

$guests = $pdo->query("
    SELECT user_id, first_name, last_name, email, phone_number
    FROM   Guest
    ORDER  BY user_id DESC
")->fetchAll();

$villas = $pdo->query("
    SELECT villa_id, villa_name, base_price, total_units, max_adults, max_children
    FROM   Villa
    ORDER  BY villa_id ASC
")->fetchAll();

echo json_encode([
    'success'      => true,
    'message'      => 'Authentication Successful',
    'reservations' => $reservations,
    'guests'       => $guests,
    'villas'       => $villas
]);
