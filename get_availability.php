<?php
// ============================================================
// get_availability.php — Check Which Villas Are Available
//
// Called by: book.html (JavaScript fetch)
// Method:    GET
// Params:    check_in, check_out, adults, children (in URL query string)
//
// Returns: JSON array like:
// [
//   { "villa_id": 1, "base_price": 45000.00, "available": true  },
//   { "villa_id": 2, "base_price": 38000.00, "available": false },
//   ...
// ]
// ============================================================

// Tell the browser this response is JSON, not HTML
header('Content-Type: application/json');

// Allow requests from the same server (needed for local XAMPP testing)
header('Access-Control-Allow-Origin: *');

// Get the database connection ($pdo variable)
require 'db.php';

// ---- Step 1: Read the incoming parameters from the URL ----
// e.g. get_availability.php?check_in=2026-04-01&check_out=2026-04-05&adults=2&children=0

$check_in  = isset($_GET['check_in'])  ? trim($_GET['check_in'])  : '';
$check_out = isset($_GET['check_out']) ? trim($_GET['check_out']) : '';
$adults    = isset($_GET['adults'])    ? intval($_GET['adults'])   : 1;
$children  = isset($_GET['children'])  ? intval($_GET['children']) : 0;

// ---- Step 2: If no dates given, return all villas with prices ----
// (When user hasn't picked dates yet, show all rooms with no restriction)

if (empty($check_in) || empty($check_out)) {

    $stmt = $pdo->query("
        SELECT villa_id, base_price
        FROM Villa
        ORDER BY villa_id
    ");
    $villas = $stmt->fetchAll();

    $result = [];
    foreach ($villas as $villa) {
        $result[] = [
            'villa_id'   => intval($villa['villa_id']),
            'base_price' => floatval($villa['base_price']),
            'available'  => true   // no dates = show all as available
        ];
    }

    echo json_encode($result);
    exit;
}

// ---- Step 3: Check availability with a SQL JOIN ----
//
// HOW DATE OVERLAP WORKS:
// Two date ranges overlap if:
//   existing_check_in  < our_check_out   (existing booking starts before we leave)
//   AND
//   existing_check_out > our_check_in    (existing booking ends after we arrive)
//
// We LEFT JOIN so that villas with ZERO reservations still appear (with booked_units = 0).
// GROUP BY groups all the rows for each villa so COUNT() counts per villa.

$sql = "
    SELECT
        v.villa_id,
        v.base_price,
        v.total_units,
        v.max_adults,
        v.max_children,
        COUNT(r.reservation_id) AS booked_units
    FROM Villa v
    LEFT JOIN Reservation r
        ON  v.villa_id    = r.villa_id
        AND r.check_in    < :check_out
        AND r.check_out   > :check_in
    GROUP BY v.villa_id, v.base_price, v.total_units, v.max_adults, v.max_children
    ORDER BY v.villa_id
";

// Prepared statement: the :check_in and :check_out are placeholders.
// PDO replaces them safely — this prevents SQL injection.
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':check_in'  => $check_in,
    ':check_out' => $check_out
]);
$villas = $stmt->fetchAll();

// ---- Step 4: Build the result, deciding availability for each villa ----

$result = [];

foreach ($villas as $villa) {

    $booked_units = intval($villa['booked_units']);
    $total_units  = intval($villa['total_units']);
    $max_adults   = intval($villa['max_adults']);
    $max_children = intval($villa['max_children']);

    // A villa is available ONLY if all three conditions are true:
    // 1. At least one unit is still free
    $has_free_unit  = $booked_units < $total_units;

    // 2. It can fit the number of adults the user requested
    $fits_adults    = $max_adults >= $adults;

    // 3. It can fit the number of children the user requested
    $fits_children  = $max_children >= $children;

    $result[] = [
        'villa_id'   => intval($villa['villa_id']),
        'base_price' => floatval($villa['base_price']),
        'available'  => $has_free_unit && $fits_adults && $fits_children
    ];
}

// Return the JSON array to JavaScript
echo json_encode($result);
