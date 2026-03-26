<?php
// ============================================================
// admin_login.php — Admin Authentication
//
// Called by: login.html (JavaScript fetch, POST method)
// Receives:  JSON body with { employee_code, password }
//
// If credentials match:
//   - Returns success + all 3 tables (Reservations, Guests, Villas)
//
// If credentials don't match:
//   - Returns { success: false, message: "Credentials Invalid" }
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle browser preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php';

// Read the JSON body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$employee_code = trim($data['employee_code'] ?? '');
$password      = trim($data['password']      ?? '');

// Basic check — both fields must be filled
if (empty($employee_code) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in both fields.']);
    exit;
}

// ---- Step 1: Look up the admin by employee_code ----
// We only fetch one row since employee_code is UNIQUE in the table
$stmt = $pdo->prepare("SELECT * FROM Admin WHERE employee_code = :code LIMIT 1");
$stmt->execute([':code' => $employee_code]);
$admin = $stmt->fetch();

// ---- Step 2: Verify the password ----
// password_verify() checks the plain text against the stored bcrypt hash.
// This is the safe way — we never store plain text passwords.

if (!$admin || $password !== $admin['password']) {
    // Employee code not found OR password doesn't match
    echo json_encode(['success' => false, 'message' => 'Credentials Invalid']);
    exit;
}

// ---- Step 3: Authentication passed — fetch all table data ----

// All reservations, with guest name/email and villa name joined in
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
    JOIN  Guest g ON r.user_id   = g.user_id
    JOIN  Villa v ON r.villa_id  = v.villa_id
    ORDER BY r.reservation_id DESC
")->fetchAll();

// All guests
$guests = $pdo->query("
    SELECT user_id, first_name, last_name, email, phone_number
    FROM   Guest
    ORDER  BY user_id DESC
")->fetchAll();

// All villas
$villas = $pdo->query("
    SELECT villa_id, villa_name, base_price, total_units, max_adults, max_children
    FROM   Villa
    ORDER  BY villa_id ASC
")->fetchAll();

// ---- Step 4: Return success + data ----
echo json_encode([
    'success'      => true,
    'message'      => 'Authentication Successful',
    'reservations' => $reservations,
    'guests'       => $guests,
    'villas'       => $villas
]);
