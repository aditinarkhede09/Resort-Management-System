<?php
// ============================================================
// db.php — Database Connection File
//
// Every other PHP file does: require 'db.php';
// That gives them the $pdo variable to talk to the database.
//
// PDO (PHP Data Objects) is a safe, modern way to use MySQL.
// It protects against SQL injection when we use "prepared statements".
// ============================================================

$host     = 'localhost';   // XAMPP MySQL runs here
$dbname   = 'riviera_db';  // Our database name
$username = 'root';        // Default XAMPP username
$password = '';            // Default XAMPP password (empty string)

try {
    // Create the connection
    // The "charset=utf8" makes sure names/emails with special characters work
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    // Tell PDO to throw a proper error (exception) if a query fails.
    // Without this, failures happen silently which is hard to debug.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Return results as associative arrays like ['email' => 'a@b.com']
    // instead of arrays with both numbered and named keys
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If connection fails, send a JSON error back to the browser
    // and stop execution. This is cleaner than a PHP error page.
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection failed. Check XAMPP is running.'
    ]);
    exit;
}
