<?php
// ============================================================
// amenities.php — Select Amenities (was amenities.html)
//
// CHANGES FROM amenities.html:
//   1. Added require for session_manager.php
//   2. PHP reads saved session data (all booking fields + saved amenities)
//   3. These are echoed as JS variables so previously-selected amenities
//      are restored when the user goes back from details.php
//   4. JS now calls save_session.php when amenities change
// ============================================================

require 'session_manager.php';

// ---- Read all saved data from session ----
$savedCheckIn    = readBookingData('checkIn',  '');
$savedCheckOut   = readBookingData('checkOut', '');
$savedAdults     = readBookingData('adults',   2);
$savedChildren   = readBookingData('children', 0);
$savedRoomName   = readBookingData('selectedRoomName',  '');
$savedRoomPrice  = readBookingData('selectedRoomPrice', 0);
// Saved amenities are stored as a JSON string
$savedAmenitiesJSON = readBookingData('selectedAmenities', '{}');

// Safely encode for JS
$jsCheckIn          = json_encode($savedCheckIn);
$jsCheckOut         = json_encode($savedCheckOut);
$jsAdults           = intval($savedAdults);
$jsChildren         = intval($savedChildren);
$jsRoomName         = json_encode($savedRoomName);
$jsRoomPrice        = intval($savedRoomPrice);
$jsAmenities        = $savedAmenitiesJSON ?: '{}';  // already valid JSON from save_session.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Amenities | The Riviera</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- All styles unchanged from amenities.html -->
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
        .progress-bar { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 18px 5%; display: flex; align-items: center; gap: 0; }
        .step { display: flex; align-items: center; gap: 10px; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1.5px; color: #aaa; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; color: #ccc; }
        .step.active .step-num { background: #000; color: #fff; border-color: #000; }
        .step.active { color: #000; }
        .step.done .step-num { background: #555; color: #fff; border-color: #555; }
        .step.done { color: #555; }
        .step-line { flex: 1; height: 1px; background: #ddd; margin: 0 15px; }
        .booking-summary-bar { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 16px 5%; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
        .summary-info { display: flex; gap: 35px; flex-wrap: wrap; }
        .summary-item label { display: block; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 2px; color: #999; margin-bottom: 3px; }
        .summary-item span { font-size: 0.95rem; font-weight: 600; color: #000; }
        .back-btn { background: transparent; border: 1px solid #000; color: #000; padding: 9px 22px; font-family: inherit; font-size: 0.78rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: all 0.3s; }
        .back-btn:hover { background: #000; color: #fff; }
        .main-layout { display: flex; gap: 30px; padding: 40px 5%; max-width: 1400px; margin: 0 auto; align-items: flex-start; }
        .amenities-column { flex: 2; min-width: 0; }
        .section-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 3px; color: #999; margin-bottom: 5px; }
        .section-title { font-size: 1.8rem; color: #000; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px; }
        .section-subtitle { font-size: 0.9rem; color: #888; margin-bottom: 30px; }
        .category-heading { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 3px; color: #000; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px; margin-bottom: 15px; margin-top: 35px; }
        .category-heading:first-child { margin-top: 0; }
        .amenity-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px; }
        .amenity-card { background: #fff; border: 1.5px solid #e5e5e5; cursor: pointer; transition: border-color 0.2s, box-shadow 0.2s; position: relative; display: flex; flex-direction: column; overflow: hidden; }
        .amenity-card:hover { border-color: #aaa; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .amenity-card.selected { border-color: #000; background: #fafafa; }
        .amenity-card .tick { position: absolute; top: 10px; right: 10px; width: 26px; height: 26px; background: #000; border-radius: 50%; display: none; align-items: center; justify-content: center; color: #fff; font-size: 0.8rem; z-index: 2; }
        .amenity-card.selected .tick { display: flex; }
        .amenity-photo { width: 100%; height: 140px; object-fit: cover; display: block; background: #e8e8e8; }
        .amenity-info { padding: 15px 16px 16px; }
        .amenity-info h4 { font-size: 0.88rem; font-weight: 600; color: #000; margin-bottom: 5px; letter-spacing: 0.3px; }
        .amenity-info p { font-size: 0.76rem; color: #777; line-height: 1.5; margin-bottom: 10px; }
        .amenity-price { font-size: 0.88rem; font-weight: 700; color: #000; }
        .amenity-price span { font-size: 0.72rem; font-weight: 400; color: #999; }
        .bill-column { flex: 1; position: sticky; top: 90px; }
        .bill-box { background: #fff; border: 1px solid #e0e0e0; padding: 28px; }
        .bill-box h3 { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f0f0f0; }
        .bill-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.85rem; border-bottom: 1px solid #f8f8f8; }
        .bill-row label { color: #666; }
        .bill-row span { color: #000; font-weight: 600; }
        .bill-row.amenity-line { font-size: 0.78rem; }
        .bill-row.amenity-line label { color: #888; font-style: italic; }
        .bill-divider { border: none; border-top: 1px solid #e8e8e8; margin: 8px 0; }
        .bill-total { margin-top: 16px; padding-top: 14px; border-top: 2px solid #000; display: flex; justify-content: space-between; align-items: center; }
        .bill-total label { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: #000; }
        .bill-total span { font-size: 1.4rem; font-weight: 700; color: #000; }
        .bill-note { font-size: 0.72rem; color: #aaa; margin-top: 10px; line-height: 1.5; }
        .proceed-btn { width: 100%; background: #000; color: #fff; border: none; padding: 15px; font-family: inherit; font-size: 0.88rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; margin-top: 18px; transition: background 0.3s; }
        .proceed-btn:hover { background: #333; }
        @media (max-width: 900px) { .main-layout { flex-direction: column; } .amenity-grid { grid-template-columns: 1fr; } .bill-column { position: static; } }
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
        <h1>Curate Your Experience</h1>
        <p>Add exclusive experiences to your stay</p>
    </div>

    <div class="progress-bar">
        <div class="step done"><div class="step-num">&#10003;</div><span>Select Room</span></div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-num">2</div><span>Amenities</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">3</div><span>Your Details</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-num">4</div><span>Confirmation</span></div>
    </div>

    <div class="booking-summary-bar">
        <div class="summary-info">
            <div class="summary-item"><label>Villa</label><span id="display-room">—</span></div>
            <div class="summary-item"><label>Check In</label><span id="display-checkin">—</span></div>
            <div class="summary-item"><label>Check Out</label><span id="display-checkout">—</span></div>
            <div class="summary-item"><label>Guests</label><span id="display-guests">—</span></div>
        </div>
        <button class="back-btn" onclick="window.location.href='book.php'">&#8592; Change Room</button>
    </div>

    <div class="main-layout">
        <div class="amenities-column">
            <p class="section-label">Step 2 of 4</p>
            <h2 class="section-title">Island Experiences</h2>
            <p class="section-subtitle">Select as many as you like. All prices are per booking unless stated otherwise.</p>
            <div id="amenities-container"></div>
        </div>

        <div class="bill-column">
            <div class="bill-box">
                <h3>Your Stay Summary</h3>
                <div class="bill-row"><label>Villa</label><span id="bill-room-name">—</span></div>
                <div class="bill-row"><label>Room subtotal</label><span id="bill-room-cost">—</span></div>
                <hr class="bill-divider">
                <div id="bill-amenity-rows"><p style="font-size:0.78rem; color:#bbb; font-style:italic; padding:6px 0;">No amenities selected yet.</p></div>
                <div class="bill-row" style="margin-top:8px;"><label>Amenities subtotal</label><span id="bill-amenity-total">₹0</span></div>
                <div class="bill-row"><label>Taxes & Fees (18%)</label><span id="bill-tax">—</span></div>
                <div class="bill-total"><label>Estimated Total</label><span id="bill-grand-total">—</span></div>
                <p class="bill-note">* Prices in Indian Rupees (₹). Final amount confirmed at checkout.</p>
                <button class="proceed-btn" onclick="goToDetails()">PROCEED TO YOUR DETAILS &#8594;</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // amenities.php — JavaScript
        //
        // CHANGES FROM amenities.html:
        //   1. PHP injects restored session data at top as JS variables
        //   2. selectedAmenities object is pre-populated from session
        //      so previously checked amenities show as selected on back-nav
        //   3. saveAmenitiesToSession() is called on every toggle
        //      so state is always kept up to date in PHP session
        // ============================================================


        // ---- PHP INJECTED DATA ----
        var restoredCheckIn  = <?= $jsCheckIn  ?>;
        var restoredCheckOut = <?= $jsCheckOut ?>;
        var restoredAdults   = <?= $jsAdults   ?>;
        var restoredChildren = <?= $jsChildren ?>;
        var restoredRoomName = <?= $jsRoomName ?>;
        var restoredRoomPrice = <?= $jsRoomPrice ?>;

        // Restored amenities — PHP echoes the JSON string, JS parses it
        // This is the key to restoring selections on back navigation
        var restoredAmenities = <?= $jsAmenities ?>;


        // ---- Read booking data (session first, sessionStorage fallback) ----
        var checkInStr  = restoredCheckIn  || sessionStorage.getItem('checkIn');
        var checkOutStr = restoredCheckOut || sessionStorage.getItem('checkOut');
        var adults      = restoredAdults   || parseInt(sessionStorage.getItem('adults'))   || 2;
        var children    = restoredChildren || parseInt(sessionStorage.getItem('children')) || 0;
        var roomName    = restoredRoomName  || sessionStorage.getItem('selectedRoomName')  || 'Villa';
        var roomPrice   = restoredRoomPrice || parseInt(sessionStorage.getItem('selectedRoomPrice')) || 0;

        // Write back to sessionStorage for forward-navigation compat
        if (checkInStr)  sessionStorage.setItem('checkIn',  checkInStr);
        if (checkOutStr) sessionStorage.setItem('checkOut', checkOutStr);
        sessionStorage.setItem('adults',           adults);
        sessionStorage.setItem('children',         children);
        sessionStorage.setItem('selectedRoomName', roomName);
        sessionStorage.setItem('selectedRoomPrice',roomPrice);

        var checkIn  = checkInStr  ? new Date(checkInStr)  : null;
        var checkOut = checkOutStr ? new Date(checkOutStr) : null;

        var nights = 0;
        if (checkIn && checkOut) nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        if (nights < 1) nights = 1;

        function formatDate(date) {
            if (!date) return 'Not set';
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
        }

        function formatPrice(num) {
            return '&#8377;' + num.toLocaleString('en-IN');
        }

        var shortRoomName = roomName.length > 28 ? roomName.substring(0, 28) + '...' : roomName;
        document.getElementById('display-room').innerText     = shortRoomName;
        document.getElementById('display-checkin').innerText  = formatDate(checkIn);
        document.getElementById('display-checkout').innerText = formatDate(checkOut);
        var guestText = adults + ' Adult' + (adults > 1 ? 's' : '');
        if (children > 0) guestText += ', ' + children + ' Child' + (children > 1 ? 'ren' : '');
        document.getElementById('display-guests').innerText = guestText;

        var roomSubtotal = roomPrice * nights;
        document.getElementById('bill-room-name').innerHTML = shortRoomName;
        document.getElementById('bill-room-cost').innerHTML = formatPrice(roomSubtotal);

        // ---- Amenity data (unchanged from amenities.html) ----
        var categories = [
            { name: 'Beach & Water', amenities: [
                { id: 'beach1', photo: 'https://silentworld.com/wp-content/uploads/2023/11/AdobeStock_77963276-min-scaled.jpeg.webp', name: 'Snorkelling Experience', desc: 'Guided snorkelling in the coral reefs with all equipment provided.', price: 2500, priceType: 'per-person' },
                { id: 'beach2', photo: 'https://res.cloudinary.com/gofjords-com/images/w_1024,h_683,c_scale/f_auto,q_auto:eco/v1683890721/Experiences/XXLofoten/Kayaking/Evening%20kayaking%202020/Evening-kayaking-Svolvaer-Lofoten-XXlofoten-1/Evening-kayaking-Svolvaer-Lofoten-XXlofoten-1.jpg?_i=AA', name: 'Kayaking Session', desc: 'Explore the island coastline at your own pace on a double kayak.', price: 1800, priceType: 'per-person' },
                { id: 'beach3', photo: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80', name: 'Private Beach Activities', desc: 'Full-day exclusive use of the private beach with attendant service.', price: 8000, priceType: 'flat' },
                { id: 'beach4', photo: 'https://upload.wikimedia.org/wikipedia/commons/e/ef/Bavaria_Cruiser_45.jpg', name: 'Sunset Yacht Cruise', desc: 'A 2-hour private sunset cruise on our luxury yacht with refreshments.', price: 15000, priceType: 'flat' },
                { id: 'beach5', photo: 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=600&q=80', name: 'Dinner Cruise on Yacht', desc: 'A magical evening cruise with a private 4-course dinner on the water.', price: 22000, priceType: 'flat' }
            ]},
            { name: 'Dining & Bar', amenities: [
                { id: 'dine1', photo: 'https://my100yearoldhome.com/wp-content/uploads/2020/10/Dinner-on-the-Beach-10-1024x768.jpg', name: 'Sunset Dinner on the Beach', desc: 'A romantic private dinner set on the sand as the sun goes down.', price: 12000, priceType: 'flat' },
                { id: 'dine2', photo: 'https://prod.static9.net.au/fs/9f355774-eeaf-4309-9cf6-2a1d5ed7535f', name: 'Private Floating Breakfast', desc: 'A beautifully arranged breakfast tray floating in your private pool.', price: 3500, priceType: 'flat' },
                { id: 'dine3', photo: 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=600&q=80', name: 'Bar Access (All Day)', desc: 'Unlimited access to our curated island cocktail bar throughout your stay.', price: 4500, priceType: 'per-person' },
                { id: 'dine4', photo: 'https://www.mosquitomagnet.com/media/Articles/Mosquito-Magnet/Dont-Fear-the-Fire.jpg', name: 'Private Bonfire Night', desc: 'A beachside bonfire for the evening with live music, snacks and drinks.', price: 6000, priceType: 'flat' }
            ]},
            { name: 'Wellness & Spa', amenities: [
                { id: 'spa1', photo: 'https://images.unsplash.com/photo-1600334129128-685c5582fd35?w=600&q=80', name: 'Ayurvedic Spa Package', desc: 'A 90-minute traditional Ayurvedic massage and full treatment session.', price: 7500, priceType: 'per-person' },
                { id: 'spa2', photo: 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?w=600&q=80', name: 'Couples Massage', desc: 'A relaxing 60-minute couples massage in our ocean-view suite.', price: 9000, priceType: 'flat' },
                { id: 'spa3', photo: 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80', name: 'Personal Gym Session', desc: 'One-on-one personal training session with our certified island trainer.', price: 2000, priceType: 'per-person' },
                { id: 'spa4', photo: 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=600&q=80', name: 'Morning Yoga on the Beach', desc: 'A guided sunrise yoga session with breathtaking ocean views.', price: 1500, priceType: 'per-person' }
            ]},
            { name: 'Activities & Games', amenities: [
                { id: 'game1', photo: 'https://images.unsplash.com/photo-1611891487122-207579d67d98?w=600&q=80', name: 'Pool Table & Games Room', desc: 'Access to pool table, foosball table, and table tennis for 3 hours.', price: 2000, priceType: 'flat' },
                { id: 'game2', photo: 'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=600&q=80', name: 'Golf on the Island Course', desc: 'A round on our scenic 9-hole island golf course with a personal caddy.', price: 5000, priceType: 'per-person' },
                { id: 'game3', photo: 'https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?w=600&q=80', name: 'Canvas Painting Class', desc: 'A 2-hour guided painting session with all materials included.', price: 3000, priceType: 'per-person' },
                { id: 'game4', photo: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQD7YG3DQfFLLy8VA7qg9wIgd-Og0XsGwK0ZA&s', name: 'Pottery Workshop', desc: 'Create your own pottery piece guided by a master craftsman.', price: 2500, priceType: 'per-person' },
                { id: 'game5', photo: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=600&q=80', name: 'Private Movie Night', desc: 'Outdoor cinema under the stars with a blanket and fresh popcorn.', price: 5500, priceType: 'flat' }
            ]},
            { name: 'Services & Transfers', amenities: [
                { id: 'svc1', photo: 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=600&q=80', name: 'Airport Taxi Pickup', desc: 'Luxury car transfer from Port Blair airport directly to the jetty.', price: 3000, priceType: 'flat' },
                { id: 'svc2', photo: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQyxxp5t1BG-pbcdRPR_3tbc5SLLtip3BeMKQ&s', name: 'Airport Taxi Drop', desc: 'Luxury car transfer from the jetty back to Port Blair airport.', price: 3000, priceType: 'flat' },
                { id: 'svc3', photo: 'https://www.area83.in/blog/discover-the-best-outdoor-activities-at-resorts-around-bangalore-for-a-memorable-stay-162.webp', name: 'Kids Activity Club', desc: 'Full-day supervised activities, arts, crafts and games for children.', price: 2500, priceType: 'flat' }
            ]}
        ];

        // ---- Pre-populate selectedAmenities from PHP session ----
        // restoredAmenities was echoed by PHP from the saved session JSON
        var selectedAmenities = (typeof restoredAmenities === 'object' && restoredAmenities !== null)
            ? restoredAmenities
            : {};

        // Also sync to sessionStorage for compat
        sessionStorage.setItem('selectedAmenities', JSON.stringify(selectedAmenities));


        // ---- NEW: Save amenities to PHP session ----
        function saveAmenitiesToSession() {
            fetch('save_session.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    action:    'saveAmenities',
                    amenities: selectedAmenities
                })
            });
        }

        // ---- Render amenity cards ----
        function renderAmenities() {
            var container = document.getElementById('amenities-container');
            container.innerHTML = '';

            categories.forEach(function(category) {
                var heading = document.createElement('p');
                heading.className = 'category-heading';
                heading.innerText = category.name;
                container.appendChild(heading);

                var grid = document.createElement('div');
                grid.className = 'amenity-grid';

                category.amenities.forEach(function(amenity) {
                    var isSelected = selectedAmenities[amenity.id] !== undefined;
                    var actualPrice = amenity.priceType === 'per-person' ? amenity.price * adults : amenity.price;

                    var card = document.createElement('div');
                    card.className = 'amenity-card' + (isSelected ? ' selected' : '');

                    var priceLabel = amenity.priceType === 'per-person'
                        ? ' <span>(' + formatPrice(amenity.price) + '/person)</span>'
                        : '';

                    card.innerHTML =
                        '<div class="tick">&#10003;</div>' +
                        '<img class="amenity-photo" src="' + amenity.photo + '" alt="' + amenity.name + '" onerror="this.style.background=\'#ddd\'; this.style.minHeight=\'140px\';">' +
                        '<div class="amenity-info">' +
                            '<h4>' + amenity.name + '</h4>' +
                            '<p>' + amenity.desc + '</p>' +
                            '<div class="amenity-price">' + formatPrice(actualPrice) + priceLabel + '</div>' +
                        '</div>';

                    card.addEventListener('click', function() {
                        toggleAmenity(amenity.id, amenity, actualPrice);
                    });

                    grid.appendChild(card);
                });

                container.appendChild(grid);
            });
        }

        function toggleAmenity(id, amenity, price) {
            if (selectedAmenities[id]) {
                delete selectedAmenities[id];
            } else {
                selectedAmenities[id] = { name: amenity.name, price: price };
            }

            // Save to sessionStorage
            sessionStorage.setItem('selectedAmenities', JSON.stringify(selectedAmenities));

            // NEW: Also save to PHP session for back-navigation restore
            saveAmenitiesToSession();

            renderAmenities();
            updateBill();
        }

        function updateBill() {
            var amenityTotal = 0;
            var billRowsHTML = '';
            var keys = Object.keys(selectedAmenities);

            if (keys.length === 0) {
                billRowsHTML = '<p style="font-size:0.78rem; color:#bbb; font-style:italic; padding:6px 0;">No amenities selected yet.</p>';
            } else {
                keys.forEach(function(id) {
                    var item = selectedAmenities[id];
                    amenityTotal += item.price;
                    billRowsHTML += '<div class="bill-row amenity-line"><label>' + item.name + '</label><span>' + formatPrice(item.price) + '</span></div>';
                });
            }

            document.getElementById('bill-amenity-rows').innerHTML = billRowsHTML;
            document.getElementById('bill-amenity-total').innerHTML = formatPrice(amenityTotal);

            var grandSubtotal = roomSubtotal + amenityTotal;
            var tax = Math.round(grandSubtotal * 0.18);
            var grandTotal = grandSubtotal + tax;

            document.getElementById('bill-tax').innerHTML = formatPrice(tax);
            document.getElementById('bill-grand-total').innerHTML = formatPrice(grandTotal);
        }

        function goToDetails() {
            sessionStorage.setItem('selectedAmenities', JSON.stringify(selectedAmenities));
            // Save to session before navigating
            fetch('save_session.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
                body:    JSON.stringify({ action: 'saveAmenities', amenities: selectedAmenities })
            }).then(function() {
                window.location.href = 'details.php';
            }).catch(function() {
                window.location.href = 'details.php';  // navigate anyway
            });
        }

        renderAmenities();
        updateBill();
    </script>

</body>
</html>
