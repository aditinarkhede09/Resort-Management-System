<?php
// ============================================================
// payment.php — Secure Payment Page (was payment.html)
//
// CHANGES FROM payment.html:
//   1. Added require for session_manager.php
//   2. PHP reads ALL saved booking data from session
//   3. Passes them as JS variables to pre-fill the order summary
//   4. After successful booking, clearBookingData() is called
//      via a separate endpoint (clear_booking_session.php)
// ============================================================

require 'session_manager.php';

// ---- Read all saved data from session ----
$savedCheckIn    = readBookingData('checkIn',  '');
$savedCheckOut   = readBookingData('checkOut', '');
$savedAdults     = readBookingData('adults',   2);
$savedChildren   = readBookingData('children', 0);
$savedRoomId     = readBookingData('selectedRoomId',    0);
$savedRoomName   = readBookingData('selectedRoomName',  '');
$savedRoomPrice  = readBookingData('selectedRoomPrice', 0);
$savedAmenities  = readBookingData('selectedAmenities', '{}');
$savedFirstName  = readBookingData('guestFirstName', '');
$savedLastName   = readBookingData('guestLastName',  '');
$savedEmail      = readBookingData('guestEmail',     '');
$savedPhone      = readBookingData('guestPhone',     '');

// Safely encode for JS
$jsCheckIn   = json_encode($savedCheckIn);
$jsCheckOut  = json_encode($savedCheckOut);
$jsAdults    = intval($savedAdults);
$jsChildren  = intval($savedChildren);
$jsRoomId    = intval($savedRoomId);
$jsRoomName  = json_encode($savedRoomName);
$jsRoomPrice = intval($savedRoomPrice);
$jsAmenities = $savedAmenities ?: '{}';
$jsFirstName = json_encode($savedFirstName);
$jsLastName  = json_encode($savedLastName);
$jsEmail     = json_encode($savedEmail);
$jsPhone     = json_encode($savedPhone);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | The Riviera</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- All styles unchanged from payment.html -->
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
        .payment-column { flex: 2; min-width: 0; }
        .section-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 3px; color: #999; margin-bottom: 5px; }
        .section-title { font-size: 1.8rem; color: #000; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px; }
        .section-subtitle { font-size: 0.9rem; color: #888; margin-bottom: 30px; }
        .form-card { background: #fff; border: 1px solid #e5e5e5; padding: 35px 40px; margin-bottom: 20px; }
        .form-section-heading { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 3px; color: #000; padding-bottom: 12px; border-bottom: 1px solid #eee; margin-bottom: 25px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; color: #888; margin-bottom: 8px; }
        .form-group input { width: 100%; border: 1px solid #ddd; padding: 12px 15px; font-family: inherit; font-size: 0.9rem; color: #333; background: #fff; outline: none; transition: border-color 0.2s; border-radius: 0; }
        .form-group input:focus { border-color: #000; }
        .form-group input.invalid { border-color: #cc0000; }
        .field-error { font-size: 0.7rem; color: #cc0000; margin-top: 5px; display: none; }
        .secure-notice { display: flex; align-items: center; gap: 10px; background: #f9f9f9; border: 1px solid #e8e8e8; padding: 12px 16px; margin-bottom: 25px; font-size: 0.8rem; color: #666; }
        .booking-reminder { background: #f9f9f9; padding: 20px; margin-bottom: 0; }
        .reminder-row { display: flex; justify-content: space-between; padding: 7px 0; font-size: 0.85rem; border-bottom: 1px solid #eee; }
        .reminder-row:last-child { border-bottom: none; }
        .reminder-row label { color: #888; }
        .reminder-row span { color: #000; font-weight: 600; }
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
        .pay-btn { width: 100%; background: #000; color: #fff; border: none; padding: 16px; font-family: inherit; font-size: 0.9rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; margin-top: 18px; transition: background 0.3s; }
        .pay-btn:hover { background: #222; }
        .pay-btn:disabled { background: #888; cursor: not-allowed; }
        .success-screen { display: none; padding: 80px 5%; text-align: center; max-width: 700px; margin: 0 auto; }
        .success-icon { width: 80px; height: 80px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #fff; margin: 0 auto 30px; }
        .success-screen h2 { font-size: 2rem; letter-spacing: 3px; color: #000; text-transform: uppercase; margin-bottom: 15px; }
        .success-screen p { font-size: 1rem; color: #666; line-height: 1.8; margin-bottom: 10px; }
        .booking-ref { font-size: 1.4rem; font-weight: 700; letter-spacing: 4px; color: #000; margin: 25px 0; padding: 20px; background: #f5f5f5; border: 1px solid #e0e0e0; }
        .success-details { text-align: left; background: #fff; border: 1px solid #e0e0e0; padding: 30px; margin: 30px 0; }
        .success-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f5f5f5; font-size: 0.9rem; }
        .success-row:last-child { border-bottom: none; }
        .success-row label { color: #888; }
        .success-row span { color: #000; font-weight: 600; text-align: right; max-width: 60%; }
        .home-btn { background: #000; color: #fff; border: none; padding: 14px 40px; font-family: inherit; font-size: 0.88rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; margin-top: 10px; transition: background 0.3s; }
        .home-btn:hover { background: #333; }
        .server-error-msg { background: #fff0f0; border: 1px solid #cc0000; color: #cc0000; padding: 14px 18px; font-size: 0.88rem; margin-bottom: 16px; display: none; }
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
                <li><a href="events.html">Events</a></li>
                <li><a href="dining.html">Dining</a></li>
                <li><a href="sustainability.html">Sustainability</a></li>
                <li><a href="login.html">Login</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-header">
        <h1>Secure Payment</h1>
        <p>Review your booking and complete your reservation</p>
    </div>

    <div class="progress-bar">
        <div class="step done"><div class="step-num">&#10003;</div><span>Select Room</span></div>
        <div class="step-line"></div>
        <div class="step done"><div class="step-num">&#10003;</div><span>Amenities</span></div>
        <div class="step-line"></div>
        <div class="step done"><div class="step-num">&#10003;</div><span>Your Details</span></div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-num">4</div><span>Confirmation</span></div>
    </div>

    <div id="payment-section">
        <div class="main-layout">

            <div class="payment-column">
                <p class="section-label">Step 4 of 4</p>
                <h2 class="section-title">Payment</h2>
                <p class="section-subtitle">Your booking is secured with 256-bit SSL encryption.</p>

                <button class="back-btn" onclick="window.location.href='details.php'">&#8592; Back to Details</button>

                <div class="server-error-msg" id="server-error-msg"></div>

                <div class="form-card">
                    <p class="form-section-heading">Booking Summary</p>
                    <div class="booking-reminder" id="booking-reminder-box"></div>
                </div>

                <div class="form-card">
                    <p class="form-section-heading">Card Details</p>
                    <div class="secure-notice"><span>&#128274;</span> Your payment information is encrypted and fully secure.</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Card Number *</label>
                            <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19" oninput="formatCardNumber(this)">
                            <p class="field-error" id="err-card-number">Please enter a valid 16-digit card number.</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cardholder Name *</label>
                            <input type="text" id="card-name" placeholder="e.g. RAHUL SHARMA" oninput="this.value = this.value.toUpperCase()">
                            <p class="field-error" id="err-card-name">Name must be at least 2 letters.</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date *</label>
                            <input type="text" id="card-expiry" placeholder="MM / YY" maxlength="7" oninput="formatExpiry(this)">
                            <p class="field-error" id="err-card-expiry">Enter a valid expiry in MM/YY format. Card must not be expired.</p>
                        </div>
                        <div class="form-group">
                            <label>CVV *</label>
                            <input type="password" id="card-cvv" placeholder="&#9679;&#9679;&#9679;" maxlength="4">
                            <p class="field-error" id="err-card-cvv">CVV must be 3 or 4 digits.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bill-column">
                <div class="bill-box">
                    <h3>Order Summary</h3>
                    <div class="bill-row"><label>Guest</label><span id="bill-guest-name">—</span></div>
                    <div class="bill-row"><label>Villa</label><span id="bill-room" style="font-size:0.78rem; text-align:right; max-width:55%;">—</span></div>
                    <div class="bill-row"><label>Dates</label><span id="bill-dates" style="font-size:0.8rem;">—</span></div>
                    <div class="bill-row"><label>Room Subtotal</label><span id="bill-room-total">—</span></div>
                    <hr class="bill-divider">
                    <div id="bill-amenity-lines"></div>
                    <div class="bill-row"><label>Amenities</label><span id="bill-amenity-total">—</span></div>
                    <div class="bill-row"><label>GST & Taxes (18%)</label><span id="bill-tax">—</span></div>
                    <div class="bill-total"><label>Total Due</label><span id="bill-grand-total">—</span></div>
                    <p class="bill-note">* Prices in Indian Rupees (&#8377;).</p>
                    <button class="pay-btn" id="pay-btn" onclick="processPayment()">&#128274; PAY NOW</button>
                </div>
            </div>
        </div>
    </div>

    <div class="success-screen" id="success-screen">
        <div class="success-icon">&#10003;</div>
        <h2>Booking Confirmed!</h2>
        <p>Thank you for choosing The Riviera. Your island sanctuary awaits.</p>
        <p>A confirmation has been sent to <strong id="confirm-email">—</strong></p>
        <div class="booking-ref" id="booking-ref"></div>
        <div class="success-details" id="success-details"></div>
        <button class="home-btn" onclick="window.location.href='index.php'">RETURN TO HOME</button>
    </div>


    <script>
        // ============================================================
        // payment.php — JavaScript
        //
        // CHANGES FROM payment.html:
        //   1. PHP injects all restored session data as JS variables
        //   2. These are used to populate the order summary immediately
        //      without depending on sessionStorage (better for back-nav)
        //   3. After successful payment, we call clear_session.php to
        //      wipe the booking data from the PHP session
        //   4. sessionStorage.clear() is also still called for compat
        // ============================================================


        // ---- PHP INJECTED DATA (all booking data from session) ----
        var restoredCheckIn  = <?= $jsCheckIn  ?>;
        var restoredCheckOut = <?= $jsCheckOut ?>;
        var restoredAdults   = <?= $jsAdults   ?>;
        var restoredChildren = <?= $jsChildren ?>;
        var restoredRoomId   = <?= $jsRoomId   ?>;
        var restoredRoomName = <?= $jsRoomName ?>;
        var restoredRoomPrice= <?= $jsRoomPrice?>;
        var restoredAmenities= <?= $jsAmenities?>;
        var restoredFirstName= <?= $jsFirstName?>;
        var restoredLastName = <?= $jsLastName ?>;
        var restoredEmail    = <?= $jsEmail    ?>;
        var restoredPhone    = <?= $jsPhone    ?>;


        // ---- Use PHP session data, fall back to sessionStorage ----
        var checkInStr  = restoredCheckIn   || sessionStorage.getItem('checkIn');
        var checkOutStr = restoredCheckOut  || sessionStorage.getItem('checkOut');
        var adults      = restoredAdults    || parseInt(sessionStorage.getItem('adults'))   || 2;
        var children    = restoredChildren  || parseInt(sessionStorage.getItem('children')) || 0;
        var roomName    = restoredRoomName  || sessionStorage.getItem('selectedRoomName')   || 'Villa';
        var roomPrice   = restoredRoomPrice || parseInt(sessionStorage.getItem('selectedRoomPrice')) || 0;
        var roomId      = restoredRoomId    || sessionStorage.getItem('selectedRoomId')     || '1';
        var firstName   = restoredFirstName || sessionStorage.getItem('guestFirstName')     || '';
        var lastName    = restoredLastName  || sessionStorage.getItem('guestLastName')      || '';
        var email       = restoredEmail     || sessionStorage.getItem('guestEmail')         || '';
        var phone       = restoredPhone     || sessionStorage.getItem('guestPhone')         || '';
        var selectedAmenities = (typeof restoredAmenities === 'object' && restoredAmenities !== null)
            ? restoredAmenities
            : (sessionStorage.getItem('selectedAmenities') ? JSON.parse(sessionStorage.getItem('selectedAmenities')) : {});

        var checkIn  = checkInStr  ? new Date(checkInStr)  : null;
        var checkOut = checkOutStr ? new Date(checkOutStr) : null;

        var nights = 0;
        if (checkIn && checkOut) nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        if (nights < 1) nights = 1;

        function formatDate(d) {
            if (!d) return 'Not set';
            var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return d.getDate() + ' ' + m[d.getMonth()] + ' ' + d.getFullYear();
        }

        function formatPrice(num) { return '&#8377;' + num.toLocaleString('en-IN'); }

        var roomSubtotal  = roomPrice * nights;
        var amenityTotal  = 0;
        var amenityKeys   = Object.keys(selectedAmenities);
        amenityKeys.forEach(function(id) { amenityTotal += selectedAmenities[id].price; });

        var grandSubtotal = roomSubtotal + amenityTotal;
        var tax           = Math.round(grandSubtotal * 0.18);
        var grandTotal    = grandSubtotal + tax;

        var shortName     = roomName.length > 28 ? roomName.substring(0, 28) + '...' : roomName;
        var guestFullName = (firstName + ' ' + lastName).trim() || 'Guest';

        document.getElementById('bill-guest-name').innerText  = guestFullName;
        document.getElementById('bill-room').innerHTML        = shortName;
        document.getElementById('bill-dates').innerHTML       = formatDate(checkIn) + ' — ' + formatDate(checkOut);
        document.getElementById('bill-room-total').innerHTML  = formatPrice(roomSubtotal);

        var amenityLinesHTML = '';
        if (amenityKeys.length === 0) {
            amenityLinesHTML = '<div class="bill-row small"><label>No amenities</label><span>&#8377;0</span></div>';
        } else {
            amenityKeys.forEach(function(id) {
                var item = selectedAmenities[id];
                amenityLinesHTML += '<div class="bill-row small"><label>' + item.name + '</label><span>' + formatPrice(item.price) + '</span></div>';
            });
        }
        document.getElementById('bill-amenity-lines').innerHTML = amenityLinesHTML;
        document.getElementById('bill-amenity-total').innerHTML = formatPrice(amenityTotal);
        document.getElementById('bill-tax').innerHTML           = formatPrice(tax);
        document.getElementById('bill-grand-total').innerHTML   = formatPrice(grandTotal);

        document.getElementById('booking-reminder-box').innerHTML =
            '<div class="reminder-row"><label>Guest</label><span>' + guestFullName + '</span></div>' +
            '<div class="reminder-row"><label>Villa</label><span>' + shortName + '</span></div>' +
            '<div class="reminder-row"><label>Check-in</label><span>' + formatDate(checkIn) + '</span></div>' +
            '<div class="reminder-row"><label>Check-out</label><span>' + formatDate(checkOut) + '</span></div>' +
            '<div class="reminder-row"><label>Guests</label><span>' + adults + ' Adults' + (children > 0 ? ', ' + children + ' Children' : '') + '</span></div>' +
            '<div class="reminder-row"><label>Total Due</label><span style="font-size:1.05rem;">' + formatPrice(grandTotal) + '</span></div>';


        // ---- Auto-formatting (unchanged) ----
        function formatCardNumber(input) { var d = input.value.replace(/\D/g,''); var g = d.match(/.{1,4}/g); input.value = g ? g.join(' ') : d; }
        function formatExpiry(input) { var d = input.value.replace(/\D/g,''); if (d.length >= 3) { input.value = d.substring(0,2) + ' / ' + d.substring(2,4); } else { input.value = d; } }

        // ---- Validation (unchanged) ----
        function showError(iid,eid) { document.getElementById(iid).classList.add('invalid'); document.getElementById(eid).style.display='block'; }
        function clearError(iid,eid) { document.getElementById(iid).classList.remove('invalid'); document.getElementById(eid).style.display='none'; }
        function validateCardNumber() { var d = document.getElementById('card-number').value.replace(/\s/g,''); if (/^\d{16}$/.test(d)) { clearError('card-number','err-card-number'); return true; } showError('card-number','err-card-number'); return false; }
        function validateCardName()   { var v = document.getElementById('card-name').value.trim(); if (/^[A-Za-z ]{2,}$/.test(v)) { clearError('card-name','err-card-name'); return true; } showError('card-name','err-card-name'); return false; }
        function validateExpiry() {
            var v = document.getElementById('card-expiry').value.trim();
            var m = v.match(/^(\d{2})\s*\/\s*(\d{2})$/);
            if (!m) { showError('card-expiry','err-card-expiry'); return false; }
            var mo = parseInt(m[1]), yr = parseInt(m[2]);
            if (mo < 1 || mo > 12) { showError('card-expiry','err-card-expiry'); return false; }
            var fy = 2000 + yr, now = new Date(), cy = now.getFullYear(), cm = now.getMonth() + 1;
            if (fy < cy || (fy === cy && mo < cm)) { showError('card-expiry','err-card-expiry'); return false; }
            clearError('card-expiry','err-card-expiry'); return true;
        }
        function validateCVV() { var v = document.getElementById('card-cvv').value.trim(); if (/^\d{3,4}$/.test(v)) { clearError('card-cvv','err-card-cvv'); return true; } showError('card-cvv','err-card-cvv'); return false; }

        document.getElementById('card-number').addEventListener('blur', validateCardNumber);
        document.getElementById('card-name').addEventListener('blur', validateCardName);
        document.getElementById('card-expiry').addEventListener('blur', validateExpiry);
        document.getElementById('card-cvv').addEventListener('blur', validateCVV);


        // ---- Show success screen ----
        function showSuccessScreen(bookingRef) {
            var amenityList = amenityKeys.length > 0
                ? amenityKeys.map(function(id) { return selectedAmenities[id].name; }).join(', ')
                : 'None';

            document.getElementById('confirm-email').innerText = email || 'your email';
            document.getElementById('booking-ref').innerHTML   = 'Booking Reference: <strong>' + bookingRef + '</strong>';
            document.getElementById('success-details').innerHTML =
                '<div class="success-row"><label>Booking Ref</label><span>' + bookingRef + '</span></div>' +
                '<div class="success-row"><label>Guest</label><span>' + guestFullName + '</span></div>' +
                '<div class="success-row"><label>Email</label><span>' + (email || '—') + '</span></div>' +
                '<div class="success-row"><label>Villa</label><span>' + shortName + '</span></div>' +
                '<div class="success-row"><label>Check-in</label><span>' + formatDate(checkIn) + '</span></div>' +
                '<div class="success-row"><label>Check-out</label><span>' + formatDate(checkOut) + '</span></div>' +
                '<div class="success-row"><label>Nights</label><span>' + nights + '</span></div>' +
                '<div class="success-row"><label>Guests</label><span>' + adults + ' Adults' + (children > 0 ? ', ' + children + ' Children' : '') + '</span></div>' +
                '<div class="success-row"><label>Experiences</label><span>' + amenityList + '</span></div>' +
                '<div class="success-row"><label>Total Paid</label><span style="font-size:1.1rem;">' + formatPrice(grandTotal) + '</span></div>';

            document.getElementById('payment-section').style.display = 'none';
            document.getElementById('success-screen').style.display  = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Clear sessionStorage
            sessionStorage.clear();

            // NEW: Also clear PHP session booking data (keeps admin session intact)
            fetch('clear_session.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ action: 'clearBooking' })
            });
        }


        // ---- Process payment (unchanged logic) ----
        function processPayment() {
            var allOk = true;
            if (!validateCardNumber()) allOk = false;
            if (!validateCardName())   allOk = false;
            if (!validateExpiry())     allOk = false;
            if (!validateCVV())        allOk = false;

            if (!allOk) { window.scrollTo({ top: 300, behavior: 'smooth' }); return; }

            var payBtn = document.getElementById('pay-btn');
            payBtn.disabled    = true;
            payBtn.textContent = 'PROCESSING...';

            var errorBox = document.getElementById('server-error-msg');
            errorBox.style.display = 'none';

            var bookingData = {
                first_name: firstName,
                last_name:  lastName,
                email:      email,
                phone:      phone,
                villa_id:   parseInt(roomId),
                check_in:   checkInStr,
                check_out:  checkOutStr,
                adults:     adults,
                children:   children,
                total_cost: grandTotal
            };

            fetch('save_booking.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(bookingData)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showSuccessScreen('RVR-' + data.reservation_id);
                } else {
                    errorBox.textContent   = '⚠ ' + (data.error || 'Booking failed. Please try again.');
                    errorBox.style.display = 'block';
                    payBtn.disabled    = false;
                    payBtn.textContent = '🔒 PAY NOW';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            })
            .catch(function() {
                errorBox.textContent   = '⚠ Network error. Please check your connection and try again.';
                errorBox.style.display = 'block';
                payBtn.disabled    = false;
                payBtn.textContent = '🔒 PAY NOW';
            });
        }
    </script>

</body>
</html>
