<?php
// ============================================================
// book.php — Select Villa (was book.html)
//
// CHANGES FROM book.html:
//   1. Added require for session_manager.php
//   2. PHP reads saved session data (checkIn, checkOut, adults, children,
//      selectedRoomId, selectedRoomName, selectedRoomPrice)
//   3. These are echoed as JS variables so the page restores state
//      when user navigates back from amenities.php
//   4. JS now also calls save_session.php when a villa is selected
//      so the choice is stored server-side (not just in sessionStorage)
// ============================================================

require 'session_manager.php';  // starts session, provides helpers

// ---- Read saved booking data from session ----
// These were saved when user was on index.php or when they go back from amenities
$savedCheckIn    = readBookingData('checkIn',  '');
$savedCheckOut   = readBookingData('checkOut', '');
$savedAdults     = readBookingData('adults',   2);
$savedChildren   = readBookingData('children', 0);
$savedRoomId     = readBookingData('selectedRoomId',    0);
$savedRoomName   = readBookingData('selectedRoomName',  '');
$savedRoomPrice  = readBookingData('selectedRoomPrice', 0);

// Safely encode for JS (json_encode handles escaping)
$jsCheckIn    = json_encode($savedCheckIn);
$jsCheckOut   = json_encode($savedCheckOut);
$jsAdults     = intval($savedAdults);
$jsChildren   = intval($savedChildren);
$jsRoomId     = intval($savedRoomId);
$jsRoomName   = json_encode($savedRoomName);
$jsRoomPrice  = intval($savedRoomPrice);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Stay | The Riviera</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- All styles are unchanged from book.html -->
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
        .booking-summary-bar { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 20px 5%; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
        .summary-info { display: flex; gap: 40px; flex-wrap: wrap; }
        .summary-item label { display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px; color: #999; margin-bottom: 4px; }
        .summary-item span { font-size: 1rem; font-weight: 600; color: #000; }
        .edit-btn { background: transparent; border: 1px solid #000; color: #000; padding: 10px 25px; font-family: inherit; font-size: 0.8rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: all 0.3s; }
        .edit-btn:hover { background: #000; color: #fff; }
        .main-layout { display: flex; gap: 30px; padding: 40px 5%; max-width: 1400px; margin: 0 auto; align-items: flex-start; }
        .rooms-column { flex: 2; min-width: 0; }
        .section-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 3px; color: #999; margin-bottom: 5px; }
        .section-title { font-size: 1.8rem; color: #000; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 25px; }
        .room-card { background: #fff; margin-bottom: 25px; border: 1px solid #e5e5e5; display: flex; transition: box-shadow 0.3s; }
        .room-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
        .room-card.selected-room { border: 2px solid #000; }
        .room-card img { width: 320px; min-height: 240px; object-fit: cover; flex-shrink: 0; }
        .room-details { padding: 28px 35px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; }
        .room-name-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .room-name-row h3 { font-size: 1.05rem; color: #000; font-weight: 600; letter-spacing: 0.5px; }
        .room-name-row .arrow { font-size: 1rem; color: #000; }
        .room-desc { font-size: 0.88rem; color: #666; margin-bottom: 16px; line-height: 1.6; }
        .room-specs { margin-bottom: 18px; }
        .spec-row { display: flex; align-items: center; gap: 10px; padding: 5px 0; font-size: 0.88rem; color: #333; }
        .spec-icon { width: 20px; height: 20px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #555; }
        .spec-icon svg { width: 18px; height: 18px; stroke: #555; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
        .amenities { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 18px; }
        .amenity-tag { background: #f7f7f7; border: 1px solid #e8e8e8; font-size: 0.72rem; padding: 5px 11px; color: #555; letter-spacing: 0.3px; }
        .room-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #f0f0f0; }
        .room-price { font-size: 1.35rem; font-weight: 700; color: #000; }
        .room-price span { font-size: 0.8rem; font-weight: 400; color: #888; }
        .select-btn { background: #000; color: #fff; border: 2px solid #000; padding: 12px 28px; font-family: inherit; font-size: 0.82rem; font-weight: 700; letter-spacing: 1px; cursor: pointer; text-transform: uppercase; transition: all 0.3s; }
        .select-btn:hover { background: #333; border-color: #333; }
        .select-btn.selected { background: #fff; color: #000; border: 2px solid #000; }
        .fully-booked { color: #cc0000; font-size: 0.82rem; font-weight: 600; letter-spacing: 0.5px; }
        .bill-column { flex: 1; position: sticky; top: 90px; }
        .bill-box { background: #fff; border: 1px solid #e0e0e0; padding: 30px; }
        .bill-box h3 { font-size: 1rem; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; }
        .bill-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 0.9rem; border-bottom: 1px solid #f8f8f8; }
        .bill-row label { color: #666; }
        .bill-row span { color: #000; font-weight: 600; }
        .bill-total { margin-top: 20px; padding-top: 15px; border-top: 2px solid #000; display: flex; justify-content: space-between; align-items: center; }
        .bill-total label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: #000; }
        .bill-total span { font-size: 1.5rem; font-weight: 700; color: #000; }
        .bill-note { font-size: 0.75rem; color: #aaa; margin-top: 10px; line-height: 1.5; }
        .proceed-btn { width: 100%; background: #000; color: #fff; border: none; padding: 16px; font-family: inherit; font-size: 0.9rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; margin-top: 20px; transition: background 0.3s; }
        .proceed-btn:hover { background: #333; }
        .proceed-btn:disabled { background: #ccc; cursor: not-allowed; }
        .no-selection-msg { text-align: center; color: #aaa; font-size: 0.9rem; font-style: italic; padding: 20px 0; }
        .loading-msg { text-align: center; color: #888; font-size: 0.9rem; font-style: italic; padding: 40px 0; letter-spacing: 1px; }
        @media (max-width: 900px) { .main-layout { flex-direction: column; } .room-card { flex-direction: column; } .room-card img { width: 100%; height: 220px; } .bill-column { position: static; } }
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
        <h1>Select Your Villa</h1>
        <p>Choose from our exclusive collection of private pool villas</p>
    </div>

    <div class="progress-bar">
        <div class="step active"><div class="step-num">1</div><span>Select Room</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">2</div><span>Amenities</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">3</div><span>Your Details</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">4</div><span>Confirmation</span></div>
    </div>

    <div class="booking-summary-bar">
        <div class="summary-info">
            <div class="summary-item"><label>Check In</label><span id="display-checkin">—</span></div>
            <div class="summary-item"><label>Check Out</label><span id="display-checkout">—</span></div>
            <div class="summary-item"><label>Nights</label><span id="display-nights">—</span></div>
            <div class="summary-item"><label>Guests</label><span id="display-guests">—</span></div>
        </div>
        <button class="edit-btn" onclick="window.location.href='index.php'">&#8592; Edit Search</button>
    </div>

    <div class="main-layout">
        <div class="rooms-column">
            <p class="section-label">Step 1 of 4</p>
            <h2 class="section-title">Available Villas</h2>
            <div id="rooms-container">
                <p class="loading-msg">Loading availability from the database...</p>
            </div>
        </div>

        <div class="bill-column">
            <div class="bill-box">
                <h3>Your Stay Summary</h3>
                <p class="no-selection-msg" id="no-selection-msg">Please select a villa to see your estimated bill.</p>
                <div id="bill-details" style="display:none;">
                    <div class="bill-row"><label>Villa</label><span id="bill-room-name">—</span></div>
                    <div class="bill-row"><label>Rate per night</label><span id="bill-rate">—</span></div>
                    <div class="bill-row"><label>Nights</label><span id="bill-nights">—</span></div>
                    <div class="bill-row"><label>Subtotal</label><span id="bill-subtotal">—</span></div>
                    <div class="bill-row"><label>Taxes & Fees (18%)</label><span id="bill-tax">—</span></div>
                    <div class="bill-total"><label>Estimated Total</label><span id="bill-total">—</span></div>
                    <p class="bill-note">* Prices are in Indian Rupees (₹).<br>Final price confirmed at checkout.</p>
                </div>
                <button class="proceed-btn" id="proceed-btn" disabled>PROCEED TO AMENITIES →</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // book.php — JavaScript
        //
        // CHANGES FROM book.html:
        //   1. PHP injects restored session data at the top (as JS vars)
        //   2. These are used to pre-select the saved villa and dates
        //   3. When a villa is selected, we ALSO call save_session.php
        //      so the choice is preserved for back-navigation
        // ============================================================


        // ---- PHP INJECTED DATA (session-restored values) ----
        // PHP echoes these into the page so JS can use them
        var restoredCheckIn   = <?= $jsCheckIn   ?>;
        var restoredCheckOut  = <?= $jsCheckOut  ?>;
        var restoredAdults    = <?= $jsAdults    ?>;
        var restoredChildren  = <?= $jsChildren  ?>;
        var restoredRoomId    = <?= $jsRoomId    ?>;
        var restoredRoomName  = <?= $jsRoomName  ?>;
        var restoredRoomPrice = <?= $jsRoomPrice ?>;


        // ---- STEP 1: Read data (prefer session-restored values, fall back to sessionStorage) ----
        // We try PHP session first (more reliable across back navigation)
        // and fall back to sessionStorage for compatibility
        var checkInStr  = restoredCheckIn  || sessionStorage.getItem('checkIn');
        var checkOutStr = restoredCheckOut || sessionStorage.getItem('checkOut');
        var adults      = restoredAdults   || parseInt(sessionStorage.getItem('adults'))   || 2;
        var children    = restoredChildren || parseInt(sessionStorage.getItem('children')) || 0;

        // Also write back to sessionStorage so other pages can read them
        if (checkInStr)  sessionStorage.setItem('checkIn',  checkInStr);
        if (checkOutStr) sessionStorage.setItem('checkOut', checkOutStr);
        sessionStorage.setItem('adults',   adults);
        sessionStorage.setItem('children', children);

        var checkIn  = checkInStr  ? new Date(checkInStr)  : null;
        var checkOut = checkOutStr ? new Date(checkOutStr) : null;

        var nights = 0;
        if (checkIn && checkOut) {
            nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        }

        function formatDate(date) {
            if (!date) return 'Not selected';
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
        }

        function formatPrice(num) {
            return '₹' + num.toLocaleString('en-IN');
        }

        document.getElementById('display-checkin').innerText  = formatDate(checkIn);
        document.getElementById('display-checkout').innerText = formatDate(checkOut);
        document.getElementById('display-nights').innerText   = nights > 0 ? nights + ' nights' : '—';

        var guestText = adults + ' Adult' + (adults > 1 ? 's' : '');
        if (children > 0) guestText += ', ' + children + ' Child' + (children > 1 ? 'ren' : '');
        document.getElementById('display-guests').innerText = guestText;


        // ---- Room data (unchanged from book.html) ----
        var rooms = [
            { id: 1, name: 'Panoramic Ocean-View Pool Villa', image: 'https://images.unsplash.com/photo-1582610116397-edb318620f90?q=80&w=2070&auto=format&fit=crop', pricePerNight: 45000, description: 'Spectacular views from an elevated private pool. Ideal for couples or honeymooners.', beds: 'One king bed', size: '95 m² (1,022 sq.ft.)', guests: '2 adults, 1 child', amenities: ['Private Infinity Pool', 'Butler Service', 'Floating Breakfast', 'Outdoor Rain Shower', 'Mini Bar', 'Ocean Terrace'], available: true },
            { id: 2, name: 'Premier Ocean-View Pool Villa', image: 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=2070&auto=format&fit=crop', pricePerNight: 38000, description: 'Expansive living spaces with breathtaking sunset views. Perfect for a family.', beds: 'One king bed, one rollaway available', size: '110 m² (1,184 sq.ft.)', guests: '2 adults, 2 children', amenities: ['Private Pool', 'Sunset Deck', 'Living Room', 'Kitchenette', 'Garden View', 'Daily Housekeeping'], available: true },
            { id: 3, name: 'Oceanfront Pool Villa', image: 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?q=80&w=2049&auto=format&fit=crop', pricePerNight: 55000, description: 'Step directly onto the private beach from your villa. The ultimate barefoot luxury.', beds: 'One king bed', size: '130 m² (1,399 sq.ft.)', guests: '2 adults, 1 child', amenities: ['Direct Beach Access', 'Hammock', 'Floating Breakfast', 'Butler Service', 'Outdoor Dining Area', 'Sun Loungers'], available: true },
            { id: 4, name: 'Family Pool Villa', image: 'https://west.rawayanabeachfront.com/images/19-scaled.jpg', pricePerNight: 30000, description: 'Spacious family retreat with a scenic deck overlooking the turquoise Andaman waters.', beds: 'One king bed and two twin beds', size: '118 m² (1,273 sq.ft.)', guests: '3 adults and 2 children', amenities: ['Beach View', 'Kids Welcome Kit', 'Deck with Sunbeds', 'Open-air Bathroom', 'Minibar', 'Room Service'], available: true },
            { id: 5, name: 'Garden Pool Villa', image: 'https://www.dewaphuketresort.com/wp-content/uploads/2023/05/Dewa-Phuket-Hotel8639-Edit-1.jpg', pricePerNight: 25000, description: 'Surrounded by lush tropical gardens. A serene, green escape for a truly private retreat.', beds: 'One queen bed', size: '80 m² (861 sq.ft.)', guests: '2 adults', amenities: ['Outdoor Bathtub', 'Yoga Deck', 'Rainforest Shower', 'Complimentary Breakfast', 'Garden Terrace'], available: true }
        ];

        function isRoomAvailable(room) { return room.available; }

        // ---- Restore previously selected room ----
        // If user comes back from amenities, pre-select their villa
        var selectedRoomId    = restoredRoomId    || parseInt(sessionStorage.getItem('selectedRoomId'))    || null;
        var selectedRoomPrice = restoredRoomPrice || parseInt(sessionStorage.getItem('selectedRoomPrice')) || 0;
        var selectedRoomName  = restoredRoomName  || sessionStorage.getItem('selectedRoomName')            || '';

        // Validate the restored room id exists in our array
        if (selectedRoomId && !rooms.find(function(r) { return r.id === selectedRoomId; })) {
            selectedRoomId = null;
        }

        // ---- Render rooms ----
        function renderRooms() {
            var container = document.getElementById('rooms-container');
            container.innerHTML = '';

            rooms.forEach(function(room) {
                var available = isRoomAvailable(room);
                var card = document.createElement('div');
                card.className = 'room-card' + (selectedRoomId === room.id ? ' selected-room' : '');

                var amenitiesHTML = '';
                room.amenities.forEach(function(a) { amenitiesHTML += '<span class="amenity-tag">' + a + '</span>'; });

                var actionHTML = '';
                if (!available) {
                    actionHTML = '<span class="fully-booked">&#8856; Not Available</span>';
                } else if (selectedRoomId === room.id) {
                    actionHTML = '<button class="select-btn selected" onclick="selectRoom(' + room.id + ')">&#10003; Selected</button>';
                } else {
                    actionHTML = '<button class="select-btn" onclick="selectRoom(' + room.id + ')">Select Villa</button>';
                }

                var nightCount = nights > 0 ? nights : 1;
                var bedIcon   = '<svg viewBox="0 0 24 24"><path d="M2 10V17M2 14H22M22 10V17M5 10V7C5 6.448 5.448 6 6 6H18C18.552 6 19 6.448 19 7V10"/><rect x="5" y="7" width="4" height="3" rx="1"/><rect x="15" y="7" width="4" height="3" rx="1"/></svg>';
                var sizeIcon  = '<svg viewBox="0 0 24 24"><path d="M3 3H9M3 3V9M3 3L9 9M21 21H15M21 21V15M21 21L15 15M3 21H9M3 21V15M3 21L9 15M21 3H15M21 3V9M21 3L15 9"/></svg>';
                var guestIcon = '<svg viewBox="0 0 24 24"><circle cx="8" cy="7" r="3"/><path d="M2 20C2 17 4.686 15 8 15C11.314 15 14 17 14 20"/><circle cx="17" cy="9" r="2.5"/><path d="M14 20C14 17.8 15.6 16 17 16C18.4 16 20 17.8 20 20"/></svg>';

                card.innerHTML =
                    '<img src="' + room.image + '" alt="' + room.name + '">' +
                    '<div class="room-details"><div>' +
                        '<div class="room-name-row"><h3>' + room.name + '</h3><span class="arrow">&#8594;</span></div>' +
                        '<p class="room-desc">' + room.description + '</p>' +
                        '<div class="room-specs">' +
                            '<div class="spec-row"><span class="spec-icon">' + bedIcon   + '</span><span>' + room.beds   + '</span></div>' +
                            '<div class="spec-row"><span class="spec-icon">' + sizeIcon  + '</span><span>' + room.size   + '</span></div>' +
                            '<div class="spec-row"><span class="spec-icon">' + guestIcon + '</span><span>' + room.guests + '</span></div>' +
                        '</div>' +
                        '<div class="amenities">' + amenitiesHTML + '</div>' +
                    '</div>' +
                    '<div class="room-footer">' +
                        '<div class="room-price">' + formatPrice(room.pricePerNight) + ' <span>/ night</span>' +
                        (nights > 0 ? '<br><small style="font-size:0.75rem;color:#888;">' + formatPrice(room.pricePerNight * nights) + ' for ' + nights + ' nights</small>' : '') +
                        '</div>' + actionHTML +
                    '</div></div>';

                container.appendChild(card);
            });
        }

        // ---- Select a room ----
        function selectRoom(roomId) {
            var room = rooms.find(function(r) { return r.id === roomId; });
            if (!room) return;

            selectedRoomId    = roomId;
            selectedRoomPrice = room.pricePerNight;
            selectedRoomName  = room.name;

            // Save to sessionStorage (for JS pages)
            sessionStorage.setItem('selectedRoomId',    selectedRoomId);
            sessionStorage.setItem('selectedRoomName',  selectedRoomName);
            sessionStorage.setItem('selectedRoomPrice', selectedRoomPrice);

            // NEW: Also save to PHP session via fetch (for back-navigation restore)
            fetch('save_session.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    action:    'saveRoom',
                    roomId:    selectedRoomId,
                    roomName:  selectedRoomName,
                    roomPrice: selectedRoomPrice
                })
            });
            // Note: we don't wait for this — it's fire-and-forget

            renderRooms();
            updateBill();
        }

        // ---- Update bill ----
        function updateBill() {
            if (!selectedRoomId) return;

            var nightCount = nights > 0 ? nights : 1;
            var subtotal   = selectedRoomPrice * nightCount;
            var tax        = Math.round(subtotal * 0.18);
            var total      = subtotal + tax;

            document.getElementById('no-selection-msg').style.display = 'none';
            document.getElementById('bill-details').style.display     = 'block';

            var shortName = selectedRoomName.length > 20 ? selectedRoomName.substring(0, 20) + '...' : selectedRoomName;

            document.getElementById('bill-room-name').innerText = shortName;
            document.getElementById('bill-rate').innerText      = formatPrice(selectedRoomPrice);
            document.getElementById('bill-nights').innerText    = nightCount + (nightCount === 1 ? ' night' : ' nights');
            document.getElementById('bill-subtotal').innerText  = formatPrice(subtotal);
            document.getElementById('bill-tax').innerText       = formatPrice(tax);
            document.getElementById('bill-total').innerText     = formatPrice(total);

            document.getElementById('proceed-btn').disabled = false;
        }

        // ---- Proceed to amenities ----
        document.getElementById('proceed-btn').addEventListener('click', function() {
            if (!selectedRoomId) return;
            sessionStorage.setItem('selectedRoomId',    selectedRoomId);
            sessionStorage.setItem('selectedRoomName',  selectedRoomName);
            sessionStorage.setItem('selectedRoomPrice', selectedRoomPrice);
            window.location.href = 'amenities.php';
        });

        // ---- Load availability from DB (unchanged) ----
        function loadAvailability() {
            if (!checkIn || !checkOut) { renderRooms(); if (selectedRoomId) updateBill(); return; }

            function toDateString(d) {
                var y = d.getFullYear();
                var m = String(d.getMonth() + 1).padStart(2, '0');
                var dd = String(d.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + dd;
            }

            var params = 'check_in=' + toDateString(checkIn) + '&check_out=' + toDateString(checkOut) + '&adults=' + adults + '&children=' + children;

            fetch('get_availability.php?' + params)
                .then(function(r) { return r.json(); })
                .then(function(dbData) {
                    dbData.forEach(function(dbRoom) {
                        var room = rooms.find(function(r) { return r.id === dbRoom.villa_id; });
                        if (room) { room.available = dbRoom.available; room.pricePerNight = parseFloat(dbRoom.base_price); }
                    });
                    renderRooms();
                    // Auto-restore bill if a room was previously selected
                    if (selectedRoomId) {
                        var savedRoom = rooms.find(function(r) { return r.id === selectedRoomId; });
                        if (savedRoom) {
                            selectedRoomPrice = savedRoom.pricePerNight;
                            updateBill();
                        }
                    }
                })
                .catch(function() { renderRooms(); if (selectedRoomId) updateBill(); });
        }

        loadAvailability();
    </script>

</body>
</html>
