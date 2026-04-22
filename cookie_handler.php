<?php
// ============================================================
// cookie_handler.php — Cookie Management
//
// WHAT THIS FILE DOES:
//   - Reads the guest's cookie consent choice
//   - If consent is given, sets preference cookies (last dates, guest count)
//   - Provides helpers to read those cookies back (for pre-filling forms)
//
// COOKIE TYPES WE USE:
//   riviera_consent   → "yes" or "no" — did the guest accept cookies?
//   riviera_checkin   → last check-in date the guest searched
//   riviera_checkout  → last check-out date the guest searched
//   riviera_adults    → last adult count
//   riviera_children  → last children count
//
// IMPORTANT: We ONLY set preference cookies if the guest clicked "Accept".
// This follows GDPR best practice. The consent cookie itself is always set
// (it's a "strictly necessary" cookie — you need it to remember the choice).
//
// HOW TO USE:
//   require 'cookie_handler.php';
//   Then call getCookieConsent(), setPreferenceCookies(), getPreferenceCookies()
// ============================================================


// ---- How long preference cookies last (30 days in seconds) ----
define('COOKIE_LIFETIME', 30 * 24 * 60 * 60);  // 30 days


// ============================================================
// getCookieConsent()
//
// Returns the guest's cookie choice:
//   "yes"     → they accepted cookies
//   "no"      → they rejected cookies
//   "pending" → they haven't chosen yet (show the popup)
// ============================================================
function getCookieConsent() {
    if (isset($_COOKIE['riviera_consent'])) {
        // Sanitize the cookie value before using it
        $consent = htmlspecialchars($_COOKIE['riviera_consent'], ENT_QUOTES, 'UTF-8');
        return ($consent === 'yes' || $consent === 'no') ? $consent : 'pending';
    }
    return 'pending'; // Cookie doesn't exist yet
}


// ============================================================
// setConsentCookie($choice)
//
// Called when the guest clicks Accept or Reject on the popup.
// $choice = "yes" or "no"
//
// The consent cookie lasts 1 year — we remember their choice
// for a long time so the popup doesn't annoy them on every visit.
// ============================================================
function setConsentCookie($choice) {
    $choice = ($choice === 'yes') ? 'yes' : 'no';
    // setcookie(name, value, expires, path, domain, secure, httponly)
    //setcookie('riviera_consent', $choice, time() + (365 * 24 * 60 * 60), '/', '', false, true);
    setcookie('riviera_consent', $choice, 0, '/', '', false, true);
}


// ============================================================
// setPreferenceCookies($checkIn, $checkOut, $adults, $children)
//
// Saves the guest's last search as cookies so we can pre-fill
// the booking bar when they come back.
//
// Only call this if getCookieConsent() === "yes"
// ============================================================
function setPreferenceCookies($checkIn, $checkOut, $adults, $children) {
    $expires = time() + COOKIE_LIFETIME;

    // Only store if values are meaningful
    if ($checkIn)  setcookie('riviera_checkin',  htmlspecialchars($checkIn,  ENT_QUOTES, 'UTF-8'), $expires, '/', '', false, true);
    if ($checkOut) setcookie('riviera_checkout', htmlspecialchars($checkOut, ENT_QUOTES, 'UTF-8'), $expires, '/', '', false, true);

    setcookie('riviera_adults',   intval($adults),   $expires, '/', '', false, true);
    setcookie('riviera_children', intval($children), $expires, '/', '', false, true);
}


// ============================================================
// getPreferenceCookies()
//
// Returns the saved preference cookies as an associative array.
// Returns defaults if cookies don't exist.
// ============================================================
function getPreferenceCookies() {
    return [
        'checkIn'  => isset($_COOKIE['riviera_checkin'])  ? htmlspecialchars($_COOKIE['riviera_checkin'],  ENT_QUOTES, 'UTF-8') : '',
        'checkOut' => isset($_COOKIE['riviera_checkout']) ? htmlspecialchars($_COOKIE['riviera_checkout'], ENT_QUOTES, 'UTF-8') : '',
        'adults'   => isset($_COOKIE['riviera_adults'])   ? intval($_COOKIE['riviera_adults'])   : 2,
        'children' => isset($_COOKIE['riviera_children']) ? intval($_COOKIE['riviera_children']) : 0,
    ];
}


// ============================================================
// clearPreferenceCookies()
//
// Deletes all preference cookies (not the consent cookie).
// Called when consent is rejected.
// ============================================================
function clearPreferenceCookies() {
    // Setting expiry to the past deletes the cookie
    $past = time() - 3600;
    setcookie('riviera_checkin',  '', $past, '/');
    setcookie('riviera_checkout', '', $past, '/');
    setcookie('riviera_adults',   '', $past, '/');
    setcookie('riviera_children', '', $past, '/');
}


// ============================================================
// handleConsentAjax()
//
// Called when this file is hit directly via fetch() from the
// cookie popup buttons (Accept / Reject).
//
// JS sends: POST with JSON { "consent": "yes" } or { "consent": "no" }
// PHP responds: JSON { "success": true }
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {

    header('Content-Type: application/json');

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $choice = ($data['consent'] ?? 'no') === 'yes' ? 'yes' : 'no';

    setConsentCookie($choice);

    if ($choice === 'no') {
        clearPreferenceCookies();
    }

    echo json_encode(['success' => true, 'consent' => $choice]);
    exit;
}
