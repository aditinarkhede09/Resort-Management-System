<?php
// ============================================================
// index.php — Homepage (was index.html)
//
// CHANGES FROM index.html:
//   1. Added require for session_manager.php and cookie_handler.php
//   2. PHP reads saved session data (dates, guests) to pass to JS
//      so the booking bar is pre-filled when user comes back
//   3. PHP reads preference cookies for the same purpose
//   4. Cookie consent popup added at bottom of page (hidden by CSS)
//   5. JS updated to save booking data to PHP session via fetch
//      in addition to sessionStorage (so back-nav restores data)
//   6. Cookie consent JS: show popup if "pending", send choice to server
// ============================================================

require 'session_manager.php';   // Starts session, provides helpers
require 'cookie_handler.php';    // Provides cookie helpers

// ---- Read saved booking data from session (for restoring state) ----
// These are set when user clicks "Book a Stay" and when they go back
$savedCheckIn  = readBookingData('checkIn',  '');
$savedCheckOut = readBookingData('checkOut', '');
$savedAdults   = readBookingData('adults',   2);
$savedChildren = readBookingData('children', 0);

// ---- If no session data, try cookies (returning visitor) ----
$cookieConsent = getCookieConsent();  // "yes", "no", or "pending"
$prefs = getPreferenceCookies();      // saved dates/guests from last visit

// Use cookie data only if consent was given and no session data exists
if ($cookieConsent === 'yes') {
    if (!$savedCheckIn  && $prefs['checkIn'])  $savedCheckIn  = $prefs['checkIn'];
    if (!$savedCheckOut && $prefs['checkOut']) $savedCheckOut = $prefs['checkOut'];
    if ($savedAdults   === 2 && $prefs['adults'])   $savedAdults   = $prefs['adults'];
    if ($savedChildren === 0 && $prefs['children']) $savedChildren = $prefs['children'];
}

// Safely encode for JS (prevents XSS when echoing into JS)
$jsCheckIn  = json_encode($savedCheckIn);
$jsCheckOut = json_encode($savedCheckOut);
$jsAdults   = intval($savedAdults);
$jsChildren = intval($savedChildren);

// Should we show the cookie popup? Only if the user hasn't decided yet
$showCookiePopup = ($cookieConsent === 'pending') ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Riviera | Andaman Luxury Resort</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =====================================================
           Cookie Consent Popup — matches The Riviera aesthetic
           (Black & white, Montserrat, minimal)
           NEW: Added only for cookie functionality
           ===================================================== */

        .cookie-banner {
            /* Fixed at bottom of screen, full width */
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #000;
            color: #fff;
            padding: 20px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            z-index: 9999;
            border-top: 2px solid #333;

            /* Hidden by default — JS shows it if consent is pending */
            display: none;
        }

        /* Make it visible when JS adds this class */
        .cookie-banner.visible { display: flex; }

        .cookie-text {
            flex: 1;
            min-width: 200px;
        }

        .cookie-text h4 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 6px;
            color: #fff;
        }

        .cookie-text p {
            font-size: 0.78rem;
            color: #aaa;
            line-height: 1.5;
        }

        .cookie-text a {
            color: #fff;
            text-decoration: underline;
        }

        /* Buttons container */
        .cookie-buttons {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
        }

        /* Accept button — white on black */
        .cookie-accept-btn {
            background: #fff;
            color: #000;
            border: 1px solid #fff;
            padding: 10px 24px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.25s;
        }

        .cookie-accept-btn:hover { background: #e0e0e0; border-color: #e0e0e0; }

        /* Reject button — transparent outline */
        .cookie-reject-btn {
            background: transparent;
            color: #fff;
            border: 1px solid #555;
            padding: 10px 24px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.25s;
        }

        .cookie-reject-btn:hover { border-color: #fff; }
    </style>
</head>
<body>

    <header class="navbar" id="navbar">
        <div class="logo">
            <img src="img/logo.png" alt="Riviera Logo" class="logo-img">
            THE RIVIERA
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.html" class="active-link">Events</a></li>
                <li><a href="dining.html">Dining</a></li>
                <li><a href="sustainability.html">Sustainability</a></li>
                <li><a href="login.html">Login</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero-section">
        <video autoplay muted loop playsinline class="hero-video">
            <source src="img/video.mp4" type="video/mp4">
        </video>

        <div class="hero-overlay">
            <h1>THE RIVIERA</h1>
            <p>Where Luxury Meets the Sea.<br>Discover your private island sanctuary in Andaman.</p>
        </div>

        <div class="booking-bar">

            <div class="booking-item date-item" id="date-toggle">
                <label>CHECK IN — CHECK OUT</label>
                <div class="booking-input">
                    <span id="main-date-display">Select Dates</span>
                    <span class="icon">&#8964;</span>
                </div>

                <div class="calendar-popup" id="calendar-popup">
                    <div class="popup-header">
                        <span class="nav-arrow" id="cal-prev">&#10094;</span>
                        <span style="font-weight:700; letter-spacing:2px; color:#000;">SELECT YOUR DATES</span>
                        <span class="nav-arrow" id="cal-next">&#10095;</span>
                    </div>

                    <div class="calendar-grid">
                        <div class="month-card">
                            <h4 class="month-name" id="month-1-name"></h4>
                            <div class="weekdays"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                            <div class="days" id="days-1"></div>
                        </div>
                        <div class="month-card">
                            <h4 class="month-name" id="month-2-name"></h4>
                            <div class="weekdays"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                            <div class="days" id="days-2"></div>
                        </div>
                        <div class="month-card">
                            <h4 class="month-name" id="month-3-name"></h4>
                            <div class="weekdays"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                            <div class="days" id="days-3"></div>
                        </div>
                    </div>

                    <div class="popup-footer">
                        <div class="legend">
                            <span class="circle"></span> Selected &nbsp;&nbsp;
                            <span class="circle" style="background:#f0f0f0; border:1px solid #ccc;"></span> In Range
                        </div>
                        <div class="footer-actions">
                            <span id="popup-date-display" style="color:#333; font-style:italic;">Select check-in date</span>
                            <button class="btn-dark" id="date-done-btn">DONE</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="booking-item guest-item" id="guest-toggle">
                <label>GUESTS</label>
                <div class="booking-input">
                    <span id="main-guest-display">1 Room - 2 Adults</span>
                    <span class="icon">&#8964;</span>
                </div>

                <div class="guest-popup" id="guest-popup">
                    <div class="room-row">
                        <h4>ROOM 1</h4>
                        <div class="guest-counters">
                            <div class="counter-group">
                                <span>ADULTS (19+)</span>
                                <div class="counter-controls">
                                    <button id="adult-minus">−</button>
                                    <span id="adult-count">2</span>
                                    <button id="adult-plus">+</button>
                                </div>
                            </div>
                            <div class="counter-group">
                                <span>CHILDREN</span>
                                <div class="counter-controls">
                                    <button id="child-minus">−</button>
                                    <span id="child-count">0</span>
                                    <button id="child-plus">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="popup-footer" style="justify-content:flex-end; gap:20px;">
                        <span id="popup-guest-summary">1 Room | 2 Adults, 0 Children</span>
                        <button class="btn-dark" id="guest-update-btn">UPDATE</button>
                    </div>
                </div>
            </div>

            <div class="booking-item">
                <label>PROMO</label>
                <input type="text" class="promo-input" placeholder="Promo Code" id="promo-input">
            </div>

            <a href="book.php" class="search-btn" id="book-btn">BOOK A STAY</a>
        </div>
    </section>

    <section class="section-pad about-us" id="about">
        <div class="container-narrow text-center">
            <h2 class="section-title black-text">Discover Barefoot Luxury</h2>
            <p>Immerse yourself in the untouched beauty of the Andaman Islands. The Riviera offers an exclusive escape where pristine nature blends seamlessly with world-class comfort. Experience serenity redefined.</p>
        </div>
    </section>

    <section class="section-pad highlights-section" id="highlights">
        <div class="container text-center">
            <h2 class="section-title black-text">Accomodations</h2>
        </div>

        <div class="carousel-viewport">
            <div class="carousel-track" id="carousel-track">
                <div class="carousel-card">
                    <img src="https://images.unsplash.com/photo-1582610116397-edb318620f90?q=80&w=2070&auto=format&fit=crop" alt="Panoramic Villa">
                    <div class="card-info">
                        <h3>PANORAMIC OCEAN-VIEW POOL VILLA</h3>
                        <p>Soak in panoramic views of the Andaman Sea from your private infinity pool.</p>
                    </div>
                </div>
                <div class="carousel-card">
                    <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=2070&auto=format&fit=crop" alt="Premier Villa">
                    <div class="card-info">
                        <h3>PREMIER OCEAN-VIEW POOL VILLA</h3>
                        <p>Elevated luxury with expansive living areas and unparalleled sunset views.</p>
                    </div>
                </div>
                <div class="carousel-card">
                    <img src="https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?q=80&w=2049&auto=format&fit=crop" alt="Oceanfront Villa">
                    <div class="card-info">
                        <h3>OCEANFRONT POOL VILLA</h3>
                        <p>Step directly onto the pristine white sands from your secluded sanctuary.</p>
                    </div>
                </div>
                <div class="carousel-card">
                    <img src="https://west.rawayanabeachfront.com/images/19-scaled.jpg" alt="Beach Villa">
                    <div class="card-info">
                        <h3>FAMILY POOL VILLA</h3>
                        <p>Lounge on the scenic deck of your private beachfront villa in the warm sea breeze.</p>
                    </div>
                </div>
                <div class="carousel-card">
                    <img src="https://www.dewaphuketresort.com/wp-content/uploads/2023/05/Dewa-Phuket-Hotel8639-Edit-1.jpg" alt="Garden Villa">
                    <div class="card-info">
                        <h3>GARDEN POOL VILLA</h3>
                        <p>Surrounded by lush tropical gardens, enjoy total seclusion in this serene retreat.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-controls">
            <button id="carousel-prev" class="nav-arrow">&#10094;</button>
            <button id="carousel-next" class="nav-arrow">&#10095;</button>
        </div>
    </section>

    <section class="review-section" id="reviews" style="background-image: url('https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?q=80&w=2070&auto=format&fit=crop');">
        <div class="review-overlay">
            <div class="container text-center">
                <h2 class="section-title" style="color:white; margin-bottom:40px;">Guest Experiences</h2>
                <div class="reviews-grid">
                    <div class="review-card">
                        <p class="review-quote">"The Riviera is not just a resort; it's a spiritual experience. The blend of luxury and raw nature is unparalleled."</p>
                        <span class="reviewer-name">— Anjali Desai</span>
                    </div>
                    <div class="review-card">
                        <p class="review-quote">"Absolute perfection. Waking up to the sound of the ocean in our private pool villa was a dream come true."</p>
                        <span class="reviewer-name">— Marcus Sterling</span>
                    </div>
                    <div class="review-card">
                        <p class="review-quote">"The staff anticipated our every need. The dining experiences were exquisite. A true 5-star island escape."</p>
                        <span class="reviewer-name">— Priya & Rahul</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-pad faq-section" id="faq">
        <div class="container-narrow">
            <h2 class="section-title black-text text-center">Frequently Asked Questions</h2>
            <div class="faq-container">
                <div class="faq-item">
                    <button class="faq-question">How do we reach the private island? <span class="icon">+</span></button>
                    <div class="faq-answer"><p>We provide complimentary luxury speedboat transfers from Port Blair airport directly to The Riviera's private dock. The journey takes approximately 45 minutes.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">Are meals included in the stay? <span class="icon">+</span></button>
                    <div class="faq-answer"><p>We offer various packages. Our base rate includes a gourmet breakfast. Our "Island Indulgence" package is fully all-inclusive covering all meals and select beverages.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">Is the resort kid-friendly? <span class="icon">+</span></button>
                    <div class="faq-answer"><p>Yes, we have a dedicated Kids Club, family villas, and babysitting services available upon request. Some areas like the primary infinity pool remain adult-only for tranquility.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">What activities are available on the island? <span class="icon">+</span></button>
                    <div class="faq-answer"><p>We offer snorkelling, scuba diving, kayaking, sunset yacht cruises, ayurvedic spa, private beach bonfires, canvas painting, and much more. Our concierge can curate a full itinerary for you.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-pad contact-section" id="contact">
        <div class="container">
            <h2 class="section-title black-text text-center">Contact Us</h2>
            <div class="contact-wrapper">
                <div class="contact-info">
                    <h3>We'd love to hear from you.</h3>
                    <p>Get in touch with our concierge team to start planning your perfect island getaway.</p>
                    <div class="contact-details">
                        <p><strong>Address:</strong><br>The Riviera Private Isle,<br>Andaman & Nicobar Islands, India 744101</p>
                        <p><strong>Email:</strong><br>reservations@theriviera.in</p>
                        <p><strong>Phone:</strong><br>+91 98765 43210</p>
                    </div>
                </div>
                <div class="contact-image">
                    <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=2070&auto=format&fit=crop" alt="Resort Reception">
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-col logo-col">
                <div class="logo" style="color:white;">THE RIVIERA</div>
                <p style="margin-top:15px; opacity:0.6; font-size:0.85rem; max-width:200px; line-height:1.6;">Where Luxury Meets the Sea. Andaman Islands, India.</p>
            </div>
            <div class="footer-col">
                <h4>Menu</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.html">Events</a></li>
                    <li><a href="dining.html">Dining</a></li>
                    <li><a href="sustainability.html">Sustainability</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Terms Of Use</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Connect</h4>
                <ul>
                    <li><a href="#contact">Contact Us</a></li>
                    <li><a href="#">Instagram</a></li>
                    <li><a href="#">Facebook</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p>© 2026 The Riviera. All rights reserved.</p>
        </div>
    </footer>


    <!-- =====================================================
         COOKIE CONSENT BANNER (NEW)
         Shown at the bottom of the page if consent is pending.
         Matches the black & white Riviera aesthetic.
         ===================================================== -->
    <div class="cookie-banner" id="cookie-banner">
        <div class="cookie-text">
            <h4>Cookie Preferences</h4>
            <p>
                We use cookies to remember your booking preferences and enhance your experience.
                <!-- Preference cookies are only set with consent -->
            </p>
        </div>
        <div class="cookie-buttons">
            <button class="cookie-reject-btn" id="cookie-reject-btn">Reject</button>
            <button class="cookie-accept-btn" id="cookie-accept-btn">Accept All</button>
        </div>
    </div>


    <script src="script.js"></script>

    <script>
        // ============================================================
        // CHANGES TO script.js LOGIC (inline here to avoid editing script.js):
        //
        // NEW ADDITIONS:
        //   1. Restore saved dates and guest counts from PHP session
        //      (PHP echoes them as JS variables at the top)
        //   2. When "BOOK A STAY" is clicked, save to PHP session
        //      via a fetch() POST to save_session.php BEFORE navigating
        //   3. Cookie popup: show if pending, send Accept/Reject to server
        // ============================================================


        // ---- PHP INJECTED DATA (restored from session / cookies) ----
        // PHP echoes these values into the page — safe because we json_encode'd them
        var restoredCheckIn  = <?= $jsCheckIn  ?>;   // e.g. "2026-05-01T00:00:00.000Z" or ""
        var restoredCheckOut = <?= $jsCheckOut ?>;   // e.g. "2026-05-05T00:00:00.000Z" or ""
        var restoredAdults   = <?= $jsAdults   ?>;   // e.g. 2
        var restoredChildren = <?= $jsChildren ?>;   // e.g. 0
        var showCookiePopup  = <?= $showCookiePopup ?>;  // true or false


        // ============================================================
        // COOKIE BANNER LOGIC
        // ============================================================
        window.addEventListener('DOMContentLoaded', function() {

            // Show the banner if PHP says consent is still pending
            if (showCookiePopup) {
                document.getElementById('cookie-banner').classList.add('visible');
            }

            // ---- Accept button ----
            document.getElementById('cookie-accept-btn').addEventListener('click', function() {
                sendConsentChoice('yes');
            });

            // ---- Reject button ----
            document.getElementById('cookie-reject-btn').addEventListener('click', function() {
                sendConsentChoice('no');
            });
        });

        // Sends the consent choice to cookie_handler.php and hides the banner
        function sendConsentChoice(choice) {
            // Send to server via fetch so the PHP cookie is set
            fetch('cookie_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'  // triggers the AJAX block in cookie_handler.php
                },
                body: JSON.stringify({ consent: choice })
            })
            .then(function() {
                // Hide the banner regardless of response
                document.getElementById('cookie-banner').classList.remove('visible');
            })
            .catch(function() {
                // Even on network error, hide the banner
                document.getElementById('cookie-banner').classList.remove('visible');
            });
        }


        // ============================================================
        // RESTORE DATES IN CALENDAR (NEW)
        //
        // The original script.js calendar runs after DOMContentLoaded.
        // We hook in AFTER script.js runs to restore dates.
        // We use a small delay to ensure script.js has run first.
        // ============================================================
        window.addEventListener('load', function() {
            // Only restore if we actually have saved dates
            if (!restoredCheckIn || !restoredCheckOut) return;

            try {
                var d1 = new Date(restoredCheckIn);
                var d2 = new Date(restoredCheckOut);

                // Make sure dates are valid and in the future
                var now = new Date();
                now.setHours(0, 0, 0, 0);
                if (isNaN(d1) || isNaN(d2) || d1 < now) return;

                // script.js exposes selectedDates via the global scope.
                // We directly set it here and trigger a display update.
                // Note: selectedDates is declared in script.js with var (not let/const)
                // so it's accessible globally.

                // Update the date display bar
                var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                var nights = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
                var displayText = months[d1.getMonth()] + ' ' + d1.getDate() +
                    ' — ' + months[d2.getMonth()] + ' ' + d2.getDate() +
                    ', ' + d2.getFullYear() + ' (' + nights + ' nights)';

                document.getElementById('main-date-display').innerText = displayText;

                // Also restore to sessionStorage so script.js book-btn click works
                sessionStorage.setItem('checkIn',  d1.toISOString());
                sessionStorage.setItem('checkOut', d2.toISOString());

            } catch(e) {
                // Silently ignore date restore errors
            }

            // Restore guest counts
            if (restoredAdults > 0) {
                var guestText = '1 Room — ' + restoredAdults + ' Adult' + (restoredAdults > 1 ? 's' : '');
                if (restoredChildren > 0) guestText += ', ' + restoredChildren + ' Children';
                document.getElementById('main-guest-display').innerText = guestText;

                // Also restore to sessionStorage
                sessionStorage.setItem('adults',   restoredAdults);
                sessionStorage.setItem('children', restoredChildren);
            }
        });


        // ============================================================
        // OVERRIDE BOOK BUTTON to also save to PHP session
        //
        // The original book-btn listener is in script.js.
        // We ADD another listener here that fires AFTER it.
        // This saves to the PHP session so back-navigation restores state.
        // ============================================================
        window.addEventListener('DOMContentLoaded', function() {

            document.getElementById('book-btn').addEventListener('click', function() {
                // Read the current sessionStorage values (set by script.js)
                var checkIn  = sessionStorage.getItem('checkIn');
                var checkOut = sessionStorage.getItem('checkOut');
                var adults   = sessionStorage.getItem('adults')   || 2;
                var children = sessionStorage.getItem('children') || 0;

                // Save to PHP session via fetch
                // We use keepalive:true so the request completes even as the page navigates
                fetch('save_session.php', {
                    method:    'POST',
                    headers:   { 'Content-Type': 'application/json' },
                    keepalive: true,
                    body: JSON.stringify({
                        action:   'saveBooking',
                        checkIn:  checkIn,
                        checkOut: checkOut,
                        adults:   parseInt(adults),
                        children: parseInt(children)
                    })
                });
                // Note: we don't await this — navigation happens in script.js
                // The keepalive flag ensures the request still completes
            });
        });

    </script>

</body>
</html>
