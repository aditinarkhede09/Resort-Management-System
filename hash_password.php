<?php
// ============================================================
// hash_password.php — One-Time Password Hashing Utility
//
// PURPOSE:
//   Converts all plain text passwords in the Admin table to
//   secure bcrypt hashes. Run this ONCE after setting up.
//
// HOW TO USE:
//   1. Open your browser: http://localhost/riviera/hash_password.php
//   2. It will update all admin passwords to bcrypt hashes
//   3. Delete or rename this file after running it (security!)
//
// HOW BCRYPT WORKS:
//   - password_hash($password, PASSWORD_BCRYPT) generates a hash like:
//     $2y$10$abcdefghijklmnopqrstuuVWXYZ0123456789...
//   - This hash is stored in the DB instead of the plain text password
//   - Next time admin logs in, password_verify() checks the entered
//     password against the stored hash — they never need to match exactly
//
// COST FACTOR:
//   PASSWORD_BCRYPT uses cost 10 by default.
//   Higher cost = slower hashing = harder to brute force.
//   Cost 10-12 is recommended for most applications.
// ============================================================

// Only allow running from localhost (basic protection)
// Comment this out if your XAMPP isn't on localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('This script can only be run from localhost.');
}

require 'db.php';

echo "<h2>Admin Password Hashing Utility</h2>";
echo "<pre>";

// ---- Fetch all admins ----
$stmt = $pdo->query("SELECT user_id, employee_code, password FROM admin");
$admins = $stmt->fetchAll();

if (empty($admins)) {
    echo "No admins found in the database.\n";
    echo "</pre>";
    exit;
}

$updated = 0;
$skipped = 0;

foreach ($admins as $admin) {
    $currentPassword = $admin['password'];

    // Skip if already hashed (starts with $2y$ — bcrypt prefix)
    if (strpos($currentPassword, '$2y$') === 0) {
        echo "Admin [{$admin['employee_code']}]: Already hashed — SKIPPED\n";
        $skipped++;
        continue;
    }

    // Hash the plain text password using bcrypt
    $hashedPassword = password_hash($currentPassword, PASSWORD_BCRYPT);

    // Update the DB with the hashed password
    $updateStmt = $pdo->prepare("UPDATE Admin SET password = :hash WHERE user_id = :id");
    $updateStmt->execute([
        ':hash' => $hashedPassword,
        ':id'   => $admin['user_id']
    ]);

    echo "Admin [{$admin['employee_code']}]: Password hashed and updated ✓\n";
    $updated++;
}

echo "\n--- Done ---\n";
echo "Updated: $updated admin(s)\n";
echo "Skipped: $skipped admin(s) (already hashed)\n";
echo "\n!! IMPORTANT: Delete or rename this file now for security !!\n";
echo "</pre>";
