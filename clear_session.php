<?php
// ============================================================
// clear_session.php — Clear Booking Data from PHP Session
//
// Called by: payment.php (JavaScript fetch, POST, after success)
// Purpose:   Clears only the booking sub-array from $_SESSION.
//            The admin session is NOT touched.
//
// This is called AFTER a successful booking so that if the
// user navigates back, they start fresh (not with old data).
// ============================================================

header('Content-Type: application/json');

require 'session_manager.php';  // starts session

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (isset($data['action']) && $data['action'] === 'clearBooking') {
    clearBookingData();  // defined in session_manager.php
    echo json_encode(['success' => true, 'message' => 'Booking session cleared.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
