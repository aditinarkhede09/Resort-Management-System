<?php
// ============================================================
// details.php — Personal Details Form (was details.html)
//
// CHANGES FROM details.html:
//   1. Added require for session_manager.php
//   2. PHP reads saved session data (all booking + saved form fields)
//   3. PHP echoes saved form values so inputs are pre-filled on back-nav
//   4. JS calls save_session.php when user leaves a field (blur event)
//      and also on every keyup, so data is continuously saved
//   5. Input sanitization helpers used on the PHP side for restored data
// ============================================================

require 'session_manager.php';

// ---- Read all saved booking data from session ----
$savedCheckIn    = readBookingData('checkIn',  '');
$savedCheckOut   = readBookingData('checkOut', '');
$savedAdults     = readBookingData('adults',   2);
$savedChildren   = readBookingData('children', 0);
$savedRoomName   = readBookingData('selectedRoomName',  '');
$savedRoomPrice  = readBookingData('selectedRoomPrice', 0);
$savedAmenities  = readBookingData('selectedAmenities', '{}');

// ---- Read saved personal details from session ----
// These are pre-filled into the form when user comes back
$savedFirstName = readBookingData('guestFirstName', '');
$savedLastName  = readBookingData('guestLastName',  '');
$savedEmail     = readBookingData('guestEmail',     '');
$savedPhone     = readBookingData('guestPhone',     '');
$savedNation    = readBookingData('guestNation',    '');
$savedBed       = readBookingData('bedPref',        '');
$savedFloor     = readBookingData('floorPref',      '');

// Encode for JS injection
$jsCheckIn    = json_encode($savedCheckIn);
$jsCheckOut   = json_encode($savedCheckOut);
$jsAdults     = intval($savedAdults);
$jsChildren   = intval($savedChildren);
$jsRoomName   = json_encode($savedRoomName);
$jsRoomPrice  = intval($savedRoomPrice);
$jsAmenities  = $savedAmenities ?: '{}';

// Encode personal details for safe HTML attribute use
// We use htmlspecialchars here for HTML attributes (value="...")
$htmlFirstName = htmlspecialchars($savedFirstName, ENT_QUOTES, 'UTF-8');
$htmlLastName  = htmlspecialchars($savedLastName,  ENT_QUOTES, 'UTF-8');
$htmlEmail     = htmlspecialchars($savedEmail,     ENT_QUOTES, 'UTF-8');
$htmlPhone     = htmlspecialchars($savedPhone,     ENT_QUOTES, 'UTF-8');
$htmlNation    = htmlspecialchars($savedNation,    ENT_QUOTES, 'UTF-8');
$htmlBed       = htmlspecialchars($savedBed,       ENT_QUOTES, 'UTF-8');
$htmlFloor     = htmlspecialchars($savedFloor,     ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Details | The Riviera</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- All styles unchanged from details.html -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; color: #444; background: #f5f5f5; }
        a { text-decoration: none; color: inherit; }
        .navbar { position: sticky; top: 0; display: flex; justify-content: space-between; align-items: center; padding: 18px 5%; background: #000; color: #fff; z-index: 100; }
        .logo { font-size: 1.5rem; font-weight: 700; letter-spacing: 3px; display: flex; align-items: center; gap: 15px;}
        .logo-img { height: 35px; width: auto; }
        .nav-links { display: flex; gap: 30px; list-style: none; }
        .nav-links a { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #fff; opacity: 0.8; transition: opacity 0.3s; }
        .nav-links a:hover { opacity: 1; }
        .page-header { background: #111; color: #fff; padding: 50px 5%; text-align: center; }
        .page-header h1 { font-size: 2.5rem; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 10px; }
        .page-header p { font-size: 0.95rem; opacity: 0.6; letter-spacing: 1px; }
        .progress-bar { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 18px 5%; display: flex; align-items: center; }
        .step { display: flex; align-items: center; gap: 10px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: #bbb; white-space: nowrap; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 700; color: #ccc; flex-shrink: 0; }
        .step.active .step-num { background: #000; color: #fff; border-color: #000; }
        .step.active { color: #000; }
        .step.done .step-num { background: #555; color: #fff; border-color: #555; }
        .step.done { color: #555; }
        .step-line { flex: 1; height: 1px; background: #ddd; margin: 0 15px; }
        .main-layout { display: flex; gap: 30px; padding: 40px 5%; max-width: 1400px; margin: 0 auto; align-items: flex-start; }
        .form-column { flex: 2; min-width: 0; }
        .section-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 3px; color: #999; margin-bottom: 5px; }
        .section-title { font-size: 1.8rem; color: #000; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px; }
        .section-subtitle { font-size: 0.9rem; color: #888; margin-bottom: 25px; }
        .form-card { background: #fff; border: 1px solid #e5e5e5; padding: 35px 40px; margin-bottom: 20px; }
        .form-section-heading { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 3px; color: #000; padding-bottom: 12px; border-bottom: 1px solid #eee; margin-bottom: 25px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; color: #888; margin-bottom: 8px; }
        .form-group input, .form-group select { width: 100%; border: 1px solid #ddd; padding: 12px 15px; font-family: inherit; font-size: 0.9rem; color: #333; background: #fff; outline: none; transition: border-color 0.2s; border-radius: 0; }
        .form-group input:focus, .form-group select:focus { border-color: #000; }
        .form-group input.invalid, .form-group select.invalid { border-color: #cc0000; }
        .field-error { font-size: 0.7rem; color: #cc0000; margin-top: 5px; display: none; }
        .radio-group { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 8px; }
        .radio-option { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; color: #333; }
        .radio-option input[type="radio"] { width: 16px; height: 16px; cursor: pointer; accent-color: #000; }
        .radio-error { font-size: 0.7rem; color: #cc0000; margin-top: 6px; display: none; }
        .back-btn { background: transparent; border: 1px solid #000; color: #000; padding: 12px 25px; font-family: inherit; font-size: 0.8rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: all 0.3s; margin-bottom: 20px; }
        .back-btn:hover { background: #000; color: #fff; }
        .bill-column { flex: 1; position: sticky; top: 90px; }
        .bill-box { background: #fff; border: 1px solid #e0e0e0; padding: 28px; }
        .bill-box h3 { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f0f0f0; }
        .bill-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.85rem; border-bottom: 1px solid #f8f8f8; }
        .bill-row label { color: #666; }
        .bill-row span { color: #000; font-weight: 600; }
        .bill-row.small { font-size: 0.78rem; }
        .bill-row.small label { color: #888; font-style: italic; padding-left: 10px; }
        .bill-divider { border: none; border-top: 1px solid #e8e8e8; margin: 8px 0; }
        .bill-total { margin-top: 16px; padding-top: 14px; border-top: 2px solid #000; display: flex; justify-content: space-between; align-items: center; }
        .bill-total label { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: #000; }
        .bill-total span { font-size: 1.4rem; font-weight: 700; color: #000; }
        .bill-note { font-size: 0.72rem; color: #aaa; margin-top: 10px; line-height: 1.5; }
        .proceed-btn { width: 100%; background: #000; color: #fff; border: none; padding: 15px; font-family: inherit; font-size: 0.88rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; margin-top: 18px; transition: background 0.3s; }
        .proceed-btn:hover { background: #333; }
        @media (max-width: 900px) { .main-layout { flex-direction: column; } .bill-column { position: static; } .form-row { flex-direction: column; gap: 0; } }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <img src="img/logo.png" alt="Riviera Logo" class="logo-img">
            THE RIVIERA
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Events</a></li>
                <li><a href="#">Dining</a></li>
                <li><a href="sustainability.html">Sustainability</a></li>
                <li><a href="login.html">Login</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-header">
        <h1>Your Details</h1>
        <p>A few personal details to complete your booking</p>
    </div>

    <div class="progress-bar">
        <div class="step done"><div class="step-num">&#10003;</div><span>Select Room</span></div>
        <div class="step-line"></div>
        <div class="step done"><div class="step-num">&#10003;</div><span>Amenities</span></div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-num">3</div><span>Your Details</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">4</div><span>Confirmation</span></div>
    </div>

    <div class="main-layout">
        <div class="form-column">
            <p class="section-label">Step 3 of 4</p>
            <h2 class="section-title">Personal Details</h2>
            <p class="section-subtitle">All fields marked * are required.</p>

            <button class="back-btn" onclick="window.location.href='amenities.php'">&#8592; Back to Amenities</button>

            <div class="form-card">
                <p class="form-section-heading">Guest Information</p>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <!--
                            PHP CHANGE: value="<?= $htmlFirstName ?>"
                            Pre-fills the field with the session-saved value when user comes back.
                            htmlspecialchars prevents XSS in the attribute.
                        -->
                        <input type="text" id="first-name" placeholder="e.g. Rahul" value="<?= $htmlFirstName ?>">
                        <p class="field-error" id="err-first-name">Only letters allowed, minimum 2 characters.</p>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" id="last-name" placeholder="e.g. Sharma" value="<?= $htmlLastName ?>">
                        <p class="field-error" id="err-last-name">Only letters allowed, minimum 2 characters.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="text" id="email" placeholder="e.g. rahul@email.com" value="<?= $htmlEmail ?>">
                        <p class="field-error" id="err-email">Must be a valid email with @ and a domain (e.g. .com).</p>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="text" id="phone" placeholder="e.g. 9876543210" value="<?= $htmlPhone ?>">
                        <p class="field-error" id="err-phone">Must be exactly 10 digits, no spaces or dashes.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nationality *</label>
                        <select id="nationality">
                            <option value="">— Select Nationality —</option>
                            <!-- PHP CHANGE: selected attribute is added if this matches saved value -->
                            <option value="Indian"    <?= $htmlNation === 'Indian'     ? 'selected' : '' ?>>Indian</option>
                            <option value="American"  <?= $htmlNation === 'American'   ? 'selected' : '' ?>>American</option>
                            <option value="British"   <?= $htmlNation === 'British'    ? 'selected' : '' ?>>British</option>
                            <option value="Australian"<?= $htmlNation === 'Australian' ? 'selected' : '' ?>>Australian</option>
                            <option value="Other"     <?= $htmlNation === 'Other'      ? 'selected' : '' ?>>Other</option>
                        </select>
                        <p class="field-error" id="err-nationality">Please select your nationality.</p>
                    </div>
                    <div class="form-group">
                        <label>Date of Arrival</label>
                        <input type="text" id="arrival-display" readonly style="background:#f9f9f9; color:#888; cursor:not-allowed;">
                    </div>
                </div>
            </div>

            <div class="form-card">
                <p class="form-section-heading">Stay Preferences</p>

                <div class="form-row">
                    <div class="form-group">
                        <label>Bed Preference *</label>
                        <div class="radio-group">
                            <!-- PHP CHANGE: checked added if this value matches saved bed preference -->
                            <label class="radio-option">
                                <input type="radio" name="bed" value="King"         <?= $htmlBed === 'King'         ? 'checked' : '' ?>> King Bed
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="bed" value="Twin"         <?= $htmlBed === 'Twin'         ? 'checked' : '' ?>> Twin Beds
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="bed" value="No Preference"<?= $htmlBed === 'No Preference'? 'checked' : '' ?>> No Preference
                            </label>
                        </div>
                        <p class="radio-error" id="err-bed">Please select a bed preference.</p>
                    </div>

                    <div class="form-group">
                        <label>Room Floor *</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="floor" value="Lower"        <?= $htmlFloor === 'Lower'        ? 'checked' : '' ?>> Lower Floor
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="floor" value="Upper"        <?= $htmlFloor === 'Upper'        ? 'checked' : '' ?>> Upper Floor
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="floor" value="No Preference"<?= $htmlFloor === 'No Preference'? 'checked' : '' ?>> No Preference
                            </label>
                        </div>
                        <p class="radio-error" id="err-floor">Please select a floor preference.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bill-column">
            <div class="bill-box">
                <h3>Final Bill</h3>
                <div class="bill-row"><label>Villa</label><span id="bill-room-name" style="font-size:0.78rem; text-align:right; max-width:60%;">—</span></div>
                <div class="bill-row"><label>Nights</label><span id="bill-nights">—</span></div>
                <div class="bill-row"><label>Room Subtotal</label><span id="bill-room-total">—</span></div>
                <hr class="bill-divider">
                <div class="bill-row"><label style="font-weight:600; color:#000;">Amenities</label></div>
                <div id="bill-amenity-lines"></div>
                <div class="bill-row"><label>Amenities Subtotal</label><span id="bill-amenity-total">—</span></div>
                <hr class="bill-divider">
                <div class="bill-row"><label>Subtotal</label><span id="bill-subtotal">—</span></div>
                <div class="bill-row"><label>GST & Taxes (18%)</label><span id="bill-tax">—</span></div>
                <div class="bill-total"><label>Total</label><span id="bill-grand-total">—</span></div>
                <p class="bill-note">* In Indian Rupees (&#8377;). Payment on the next step.</p>
                <button class="proceed-btn" onclick="goToPayment()">PROCEED TO PAYMENT &#8594;</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // details.php — JavaScript
        //
        // CHANGES FROM details.html:
        //   1. PHP injects restored session data as JS variables
        //   2. Arrival date is filled from session data
        //   3. NEW: saveDetailsToSession() sends form data to PHP session
        //      on blur (when user leaves a field) so back-nav restores it
        //   4. goToPayment() also saves before navigating (belt and suspenders)
        // ============================================================


        // ---- PHP INJECTED DATA ----
        var restoredCheckIn  = <?= $jsCheckIn  ?>;
        var restoredCheckOut = <?= $jsCheckOut ?>;
        var restoredAdults   = <?= $jsAdults   ?>;
        var restoredChildren = <?= $jsChildren ?>;
        var restoredRoomName = <?= $jsRoomName ?>;
        var restoredRoomPrice= <?= $jsRoomPrice?>;
        var restoredAmenities= <?= $jsAmenities?>;


        // ---- Read data (PHP session first, sessionStorage fallback) ----
        var checkInStr  = restoredCheckIn   || sessionStorage.getItem('checkIn');
        var checkOutStr = restoredCheckOut  || sessionStorage.getItem('checkOut');
        var adults      = restoredAdults    || parseInt(sessionStorage.getItem('adults'))   || 2;
        var children    = restoredChildren  || parseInt(sessionStorage.getItem('children')) || 0;
        var roomName    = restoredRoomName  || sessionStorage.getItem('selectedRoomName')   || 'Villa';
        var roomPrice   = restoredRoomPrice || parseInt(sessionStorage.getItem('selectedRoomPrice')) || 0;
        var selectedAmenities = (typeof restoredAmenities === 'object' && restoredAmenities !== null)
            ? restoredAmenities
            : (sessionStorage.getItem('selectedAmenities') ? JSON.parse(sessionStorage.getItem('selectedAmenities')) : {});

        // Write back to sessionStorage
        if (checkInStr)  sessionStorage.setItem('checkIn',  checkInStr);
        if (checkOutStr) sessionStorage.setItem('checkOut', checkOutStr);
        sessionStorage.setItem('adults',   adults);
        sessionStorage.setItem('children', children);
        sessionStorage.setItem('selectedRoomName',  roomName);
        sessionStorage.setItem('selectedRoomPrice', roomPrice);
        sessionStorage.setItem('selectedAmenities', JSON.stringify(selectedAmenities));

        var checkIn  = checkInStr  ? new Date(checkInStr)  : null;
        var checkOut = checkOutStr ? new Date(checkOutStr) : null;

        var nights = 0;
        if (checkIn && checkOut) nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        if (nights < 1) nights = 1;

        function formatDate(date) {
            if (!date) return 'Not set';
            var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return date.getDate() + ' ' + m[date.getMonth()] + ' ' + date.getFullYear();
        }

        function formatPrice(num) { return '&#8377;' + num.toLocaleString('en-IN'); }

        document.getElementById('arrival-display').value = formatDate(checkIn);

        var roomSubtotal = roomPrice * nights;
        var shortName    = roomName.length > 30 ? roomName.substring(0, 30) + '...' : roomName;

        document.getElementById('bill-room-name').innerHTML  = shortName;
        document.getElementById('bill-nights').innerHTML     = nights + (nights === 1 ? ' night' : ' nights');
        document.getElementById('bill-room-total').innerHTML = formatPrice(roomSubtotal);

        var amenityTotal = 0;
        var amenityHTML  = '';
        var amenityKeys  = Object.keys(selectedAmenities);

        if (amenityKeys.length === 0) {
            amenityHTML = '<div class="bill-row small"><label>None selected</label><span>&#8377;0</span></div>';
        } else {
            amenityKeys.forEach(function(id) {
                var item = selectedAmenities[id];
                amenityTotal += item.price;
                amenityHTML += '<div class="bill-row small"><label>' + item.name + '</label><span>' + formatPrice(item.price) + '</span></div>';
            });
        }

        document.getElementById('bill-amenity-lines').innerHTML = amenityHTML;
        document.getElementById('bill-amenity-total').innerHTML = formatPrice(amenityTotal);

        var grandSubtotal = roomSubtotal + amenityTotal;
        var tax = Math.round(grandSubtotal * 0.18);
        var grandTotal = grandSubtotal + tax;

        document.getElementById('bill-subtotal').innerHTML    = formatPrice(grandSubtotal);
        document.getElementById('bill-tax').innerHTML         = formatPrice(tax);
        document.getElementById('bill-grand-total').innerHTML = formatPrice(grandTotal);


        // ============================================================
        // VALIDATION (unchanged from details.html)
        // ============================================================
        function showError(inputId, errorId) { document.getElementById(inputId).classList.add('invalid'); document.getElementById(errorId).style.display = 'block'; }
        function clearError(inputId, errorId) { document.getElementById(inputId).classList.remove('invalid'); document.getElementById(errorId).style.display = 'none'; }

        function validateFirstName()  { var v = document.getElementById('first-name').value.trim(); if (/^[A-Za-z ]{2,}$/.test(v)) { clearError('first-name','err-first-name'); return true; } showError('first-name','err-first-name'); return false; }
        function validateLastName()   { var v = document.getElementById('last-name').value.trim();  if (/^[A-Za-z ]{2,}$/.test(v)) { clearError('last-name','err-last-name');   return true; } showError('last-name','err-last-name');   return false; }
        function validateEmail()      { var v = document.getElementById('email').value.trim();       if (/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { clearError('email','err-email'); return true; } showError('email','err-email'); return false; }
        function validatePhone()      { var v = document.getElementById('phone').value.trim();       if (/^\d{10}$/.test(v)) { clearError('phone','err-phone'); return true; } showError('phone','err-phone'); return false; }
        function validateNationality(){ var v = document.getElementById('nationality').value;        if (v !== '') { clearError('nationality','err-nationality'); return true; } showError('nationality','err-nationality'); return false; }
        function validateBed()        { if (document.querySelector('input[name="bed"]:checked'))   { document.getElementById('err-bed').style.display = 'none';   return true; } document.getElementById('err-bed').style.display   = 'block'; return false; }
        function validateFloor()      { if (document.querySelector('input[name="floor"]:checked')) { document.getElementById('err-floor').style.display = 'none'; return true; } document.getElementById('err-floor').style.display = 'block'; return false; }


        // ============================================================
        // NEW: saveDetailsToSession()
        //
        // Sends the current form values to save_session.php so that
        // when the user clicks Back from payment.php, all fields
        // are pre-filled by PHP on page load.
        // ============================================================
        function saveDetailsToSession() {
            var bedEl   = document.querySelector('input[name="bed"]:checked');
            var floorEl = document.querySelector('input[name="floor"]:checked');

            fetch('save_session.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    action:    'saveDetails',
                    firstName: document.getElementById('first-name').value.trim(),
                    lastName:  document.getElementById('last-name').value.trim(),
                    email:     document.getElementById('email').value.trim(),
                    phone:     document.getElementById('phone').value.trim(),
                    nation:    document.getElementById('nationality').value,
                    bedPref:   bedEl   ? bedEl.value   : '',
                    floorPref: floorEl ? floorEl.value : ''
                })
            });
            // Fire and forget — we don't wait for this
        }

        // ---- Save on blur (when user leaves a field) ----
        document.getElementById('first-name').addEventListener('blur', function() { validateFirstName(); saveDetailsToSession(); });
        document.getElementById('last-name').addEventListener('blur',  function() { validateLastName();  saveDetailsToSession(); });
        document.getElementById('email').addEventListener('blur',      function() { validateEmail();      saveDetailsToSession(); });
        document.getElementById('phone').addEventListener('blur',      function() { validatePhone();      saveDetailsToSession(); });
        document.getElementById('nationality').addEventListener('blur', function() { validateNationality(); saveDetailsToSession(); });
        document.getElementById('nationality').addEventListener('change', saveDetailsToSession);

        // Save when radio buttons change
        document.querySelectorAll('input[name="bed"]').forEach(function(r)   { r.addEventListener('change', saveDetailsToSession); });
        document.querySelectorAll('input[name="floor"]').forEach(function(r) { r.addEventListener('change', saveDetailsToSession); });


        // ---- PROCEED (unchanged logic, added session save before nav) ----
        function goToPayment() {
            var allOk = true;
            if (!validateFirstName())  allOk = false;
            if (!validateLastName())   allOk = false;
            if (!validateEmail())      allOk = false;
            if (!validatePhone())      allOk = false;
            if (!validateNationality()) allOk = false;
            if (!validateBed())        allOk = false;
            if (!validateFloor())      allOk = false;

            if (!allOk) { window.scrollTo({ top: 200, behavior: 'smooth' }); return; }

            // Save all fields to sessionStorage (for compat)
            sessionStorage.setItem('guestFirstName', document.getElementById('first-name').value.trim());
            sessionStorage.setItem('guestLastName',  document.getElementById('last-name').value.trim());
            sessionStorage.setItem('guestEmail',     document.getElementById('email').value.trim());
            sessionStorage.setItem('guestPhone',     document.getElementById('phone').value.trim());
            sessionStorage.setItem('guestNation',    document.getElementById('nationality').value);
            sessionStorage.setItem('bedPref',        document.querySelector('input[name="bed"]:checked').value);
            sessionStorage.setItem('floorPref',      document.querySelector('input[name="floor"]:checked').value);
            sessionStorage.setItem('grandTotal',     grandTotal);

            // Also save to PHP session (keepalive ensures request completes on navigation)
            fetch('save_session.php', {
                method:    'POST',
                headers:   { 'Content-Type': 'application/json' },
                keepalive: true,
                body: JSON.stringify({
                    action:    'saveDetails',
                    firstName: document.getElementById('first-name').value.trim(),
                    lastName:  document.getElementById('last-name').value.trim(),
                    email:     document.getElementById('email').value.trim(),
                    phone:     document.getElementById('phone').value.trim(),
                    nation:    document.getElementById('nationality').value,
                    bedPref:   document.querySelector('input[name="bed"]:checked').value,
                    floorPref: document.querySelector('input[name="floor"]:checked').value
                })
            }).then(function() {
                window.location.href = 'payment.php';
            }).catch(function() {
                window.location.href = 'payment.php';
            });
        }
    </script>

</body>
</html>
