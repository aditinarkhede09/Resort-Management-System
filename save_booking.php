<?php
// ============================================================
// save_booking.php — Save Guest & Reservation to Database
//
// Called by: payment.html (JavaScript fetch, POST method)
// Receives:  JSON body with all booking details
//
// Steps:
//   1. Validate all incoming data
//   2. Check if guest email already exists
//      - If YES: use existing user_id
//      - If NO:  insert new guest, get new user_id
//   3. Re-check villa is still available (prevents double booking)
//   4. Insert the reservation row
//   5. Return JSON with reservation_id on success
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Browsers send an OPTIONS "preflight" request before POST.
// We just respond OK to that and stop.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed.']);
    exit;
}

require 'db.php';

// ---- Step 1: Read the JSON body that JavaScript sent ----
// JavaScript uses fetch() with JSON.stringify() to send the data.
// php://input is how PHP reads a raw POST body.
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true); // true = decode as array

// If JSON was malformed or missing
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid or missing data.']);
    exit;
}

// ---- Step 2: Extract each field from the decoded array ----
$first_name  = trim($data['first_name']  ?? '');
$last_name   = trim($data['last_name']   ?? '');
$email       = trim($data['email']       ?? '');
$phone       = trim($data['phone']       ?? '');
$villa_id    = intval($data['villa_id']  ?? 0);
$check_in    = trim($data['check_in']    ?? '');   // ISO format: "2026-04-01T00:00:00.000Z"
$check_out   = trim($data['check_out']   ?? '');
$adults      = intval($data['adults']    ?? 1);
$children    = intval($data['children']  ?? 0);
$total_cost  = floatval($data['total_cost'] ?? 0);

// ---- Step 3: Server-side validation ----
// (We always validate on the server too — browser validation can be bypassed)

if (!$first_name || !$last_name || !$email || !$phone || !$villa_id || !$check_in || !$check_out) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
    exit;
}

// Convert the ISO date strings from JS to MySQL DATE format (YYYY-MM-DD)
// e.g. "2026-04-01T00:00:00.000Z" → "2026-04-01"
// strtotime() understands many date formats. date('Y-m-d') formats to MySQL.
$check_in_date  = date('Y-m-d', strtotime($check_in));
$check_out_date = date('Y-m-d', strtotime($check_out));

// Sanity check on dates
if ($check_in_date === '1970-01-01' || $check_out_date === '1970-01-01') {
    echo json_encode(['success' => false, 'error' => 'Invalid date format received.']);
    exit;
}

if ($check_in_date >= $check_out_date) {
    echo json_encode(['success' => false, 'error' => 'Check-out must be after check-in.']);
    exit;
}

// ---- Steps 4–6: Database operations wrapped in a try-catch ----
// If anything goes wrong, the catch block returns a clear error.

try {

    // ---- Step 4: Find or create the guest ----

    // Check if this email is already in the Guest table
    $stmt = $pdo->prepare("SELECT user_id FROM Guest WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $existing_guest = $stmt->fetch();

    if ($existing_guest) {
        // Guest already exists — just use their user_id
        $user_id = $existing_guest['user_id'];

    } else {
        // New guest — insert into Guest table
        $stmt = $pdo->prepare("
            INSERT INTO Guest (first_name, last_name, email, phone_number)
            VALUES (:first_name, :last_name, :email, :phone)
        ");
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name'  => $last_name,
            ':email'      => $email,
            ':phone'      => $phone
        ]);

        // lastInsertId() gives us the auto-generated user_id
        $user_id = $pdo->lastInsertId();
    }

    // ---- Step 5: Re-check villa availability ----
    // The user might have taken 10 minutes to fill in card details.
    // Someone else could have booked the same villa in that time.
    // We check again now to prevent double-booking.

    $stmt = $pdo->prepare("
        SELECT v.total_units, COUNT(r.reservation_id) AS booked_units
        FROM Villa v
        LEFT JOIN Reservation r
            ON  v.villa_id    = r.villa_id
            AND r.check_in    < :check_out
            AND r.check_out   > :check_in
        WHERE v.villa_id = :villa_id
        GROUP BY v.villa_id, v.total_units
    ");
    $stmt->execute([
        ':villa_id'  => $villa_id,
        ':check_in'  => $check_in_date,
        ':check_out' => $check_out_date
    ]);
    $availability = $stmt->fetch();

    // If no villa found, or all units are booked, reject the booking
    if (!$availability) {
        echo json_encode(['success' => false, 'error' => 'Villa not found.']);
        exit;
    }

    if (intval($availability['booked_units']) >= intval($availability['total_units'])) {
        echo json_encode([
            'success' => false,
            'error'   => 'Sorry, this villa just became fully booked. Please go back and choose another.'
        ]);
        exit;
    }

    // ---- Step 6: Insert the reservation ----

    $stmt = $pdo->prepare("
        INSERT INTO Reservation
            (user_id, villa_id, check_in, check_out, adults_count, children_count, total_cost)
        VALUES
            (:user_id, :villa_id, :check_in, :check_out, :adults, :children, :total_cost)
    ");

    $stmt->execute([
        ':user_id'    => $user_id,
        ':villa_id'   => $villa_id,
        ':check_in'   => $check_in_date,
        ':check_out'  => $check_out_date,
        ':adults'     => $adults,
        ':children'   => $children,
        ':total_cost' => $total_cost
    ]);

    $reservation_id = $pdo->lastInsertId();

    // ---- Step 7: Send success response back to JavaScript ----
    echo json_encode([
        'success'        => true,
        'reservation_id' => intval($reservation_id),
        'message'        => 'Booking confirmed!'
    ]);

} catch (PDOException $e) {
    // Something unexpected happened with the database
    echo json_encode([
        'success' => false,
        'error'   => 'A database error occurred: ' . $e->getMessage()
    ]);
}
