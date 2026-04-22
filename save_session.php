<?php
// ============================================================
// save_session.php — Save Booking State to PHP Session
//
// Called by: index.php, book.php, amenities.php, details.php, payment.php
// Method:    POST with JSON body
// Purpose:   Stores booking progress in $_SESSION so the user can
//            navigate back and forth without losing their data.
//
// This is the KEY file for bi-directional navigation.
// Every page saves its state here when the user moves forward OR backward.
//
// Actions:
//   "saveBooking"   → saves dates, adults, children (from index.php)
//   "saveRoom"      → saves selected villa (from book.php)
//   "saveAmenities" → saves selected amenities (from amenities.php)
//   "saveDetails"   → saves personal details form (from details.php)
//   "savePayment"   → saves payment state (from payment.php)
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require 'session_manager.php';  // starts session + provides helpers

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'No action specified.']);
    exit;
}

$action = $data['action'];

// ---- Route to correct save function based on action ----

if ($action === 'saveBooking') {
    // Saves: check-in date, check-out date, adults count, children count
    // Called from index.php when user clicks "BOOK A STAY"
    saveBookingData('checkIn',  sanitize($data['checkIn']  ?? ''));
    saveBookingData('checkOut', sanitize($data['checkOut'] ?? ''));
    saveBookingData('adults',   intval($data['adults']     ?? 2));
    saveBookingData('children', intval($data['children']   ?? 0));

    // Also save to preference cookies if consent was given
    require 'cookie_handler.php';
    if (getCookieConsent() === 'yes') {
        setPreferenceCookies(
            $data['checkIn']  ?? '',
            $data['checkOut'] ?? '',
            intval($data['adults']   ?? 2),
            intval($data['children'] ?? 0)
        );
    }

    echo json_encode(['success' => true]);

} elseif ($action === 'saveRoom') {
    // Saves: selected villa id, name, price per night
    // Called from book.php when user selects a villa
    saveBookingData('selectedRoomId',    intval($data['roomId']    ?? 0));
    saveBookingData('selectedRoomName',  sanitize($data['roomName']  ?? ''));
    saveBookingData('selectedRoomPrice', intval($data['roomPrice'] ?? 0));

    echo json_encode(['success' => true]);

} elseif ($action === 'saveAmenities') {
    // Saves: the selected amenities object (already validated JS-side)
    // We store it as a JSON string (it's an object, not a simple value)
    $amenities = $data['amenities'] ?? [];
    // Basic safety: only allow string keys and numeric values
    $cleanAmenities = [];
    foreach ($amenities as $id => $item) {
        $cleanId = sanitize($id);
        $cleanAmenities[$cleanId] = [
            'name'  => sanitize($item['name']  ?? ''),
            'price' => intval($item['price']   ?? 0)
        ];
    }
    saveBookingData('selectedAmenities', json_encode($cleanAmenities));

    echo json_encode(['success' => true]);

} elseif ($action === 'saveDetails') {
    // Saves: personal details form fields
    // Called from details.php when user fills in their info
    saveBookingData('guestFirstName', sanitize($data['firstName'] ?? ''));
    saveBookingData('guestLastName',  sanitize($data['lastName']  ?? ''));
    saveBookingData('guestEmail',     sanitize($data['email']     ?? ''));
    saveBookingData('guestPhone',     sanitize($data['phone']     ?? ''));
    saveBookingData('guestNation',    sanitize($data['nation']    ?? ''));
    saveBookingData('bedPref',        sanitize($data['bedPref']   ?? ''));
    saveBookingData('floorPref',      sanitize($data['floorPref'] ?? ''));

    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
