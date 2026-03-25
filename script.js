// ============================================================
// script.js — The Riviera Homepage JavaScript
// 
// What this file does:
// 1. Calendar: lets user pick check-in & check-out dates
//    - Blocks past dates (can't select them)
//    - Shows 3 months side by side
//    - Highlights selected range
//    - Persists calendar until "DONE" is clicked (Bug Fixed)
//
// 2. Guest Counter: lets user pick adults & children
//
// 3. BOOK A STAY button: saves chosen dates & guests into
//    sessionStorage, then goes to book.html
//
// 4. Carousel: infinite loop of accommodation cards
//
// 5. FAQ accordion: click to open/close answers
// ============================================================

document.addEventListener('DOMContentLoaded', function() {

    // =====================================================
    // SECTION 1 — CALENDAR
    // =====================================================

    var dateToggle  = document.getElementById('date-toggle');
    var guestToggle = document.getElementById('guest-toggle');
    var calPopup    = document.getElementById('calendar-popup');
    var guestPopup  = document.getElementById('guest-popup');

    var monthNames  = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    var shortMonths = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

    var today = new Date();
    today.setHours(0, 0, 0, 0); 

    var viewMonth = today.getMonth();
    var viewYear  = today.getFullYear();
    var selectedDates = [];

    // --- FIX: Prevent calendar and guest pop-ups from closing when clicked inside ---
    // By stopping event propagation, clicks inside the popups never bubble up to 
    // the document listener. This keeps the calendar open while picking dates!
    calPopup.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    guestPopup.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // ---- Toggle calendar popup open/close ----
    dateToggle.addEventListener('click', function(e) {
        var isOpening = !calPopup.classList.contains('show'); 
        
        calPopup.classList.toggle('show');
        guestPopup.classList.remove('show');

        if (isOpening) {
            setTimeout(function() {
                calPopup.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100); 
        }
    });

    // ---- Toggle guest popup open/close ----
    guestToggle.addEventListener('click', function(e) {
        var isOpening = !guestPopup.classList.contains('show');
        
        guestPopup.classList.toggle('show');
        calPopup.classList.remove('show');

        if (isOpening) {
            setTimeout(function() {
                guestPopup.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    });

    // ---- Close popups when clicking anywhere completely outside them ----
    document.addEventListener('click', function(e) {
        if (!dateToggle.contains(e.target))  calPopup.classList.remove('show');
        if (!guestToggle.contains(e.target)) guestPopup.classList.remove('show');
    });

    // ---- Main function: draws all 3 month cards ----
    function updateCalendarUI() {
        renderMonthCard(0, document.getElementById('month-1-name'), document.getElementById('days-1'));
        renderMonthCard(1, document.getElementById('month-2-name'), document.getElementById('days-2'));
        renderMonthCard(2, document.getElementById('month-3-name'), document.getElementById('days-3'));
    }

    // ---- Draws one month card ----
    function renderMonthCard(monthOffset, titleElement, daysContainer) {
        var targetDate = new Date(viewYear, viewMonth + monthOffset, 1);
        var m = targetDate.getMonth();
        var y = targetDate.getFullYear();

        titleElement.innerText = monthNames[m] + ' ' + y;
        daysContainer.innerHTML = '';

        var startDay = targetDate.getDay();
        var totalDays = new Date(y, m + 1, 0).getDate();

        for (var i = 0; i < startDay; i++) {
            var empty = document.createElement('span');
            empty.className = 'empty';
            daysContainer.appendChild(empty);
        }

        for (var d = 1; d <= totalDays; d++) {
            var daySpan = document.createElement('span');
            daySpan.innerText = d;

            var currentDate = new Date(y, m, d);

            if (currentDate < today) {
                daySpan.classList.add('past');
                daysContainer.appendChild(daySpan);
                continue; 
            }

            if (selectedDates.length >= 1) {
                if (currentDate.getTime() === selectedDates[0].getTime()) {
                    daySpan.classList.add('selected');
                }
            }

            if (selectedDates.length === 2) {
                if (currentDate.getTime() === selectedDates[1].getTime()) {
                    daySpan.classList.add('selected');
                }
                if (currentDate > selectedDates[0] && currentDate < selectedDates[1]) {
                    daySpan.classList.add('in-range');
                }
            }

            // Wrapping the listener to preserve current date mapping
            (function(dateClicked) {
                daySpan.addEventListener('click', function() {
                    handleDateClick(dateClicked);
                });
            })(new Date(currentDate));

            daysContainer.appendChild(daySpan);
        }
    }

    // ---- Handles what happens when a date is clicked ----
    function handleDateClick(clickedDate) {
        if (selectedDates.length === 0) {
            selectedDates = [clickedDate];
        } else if (selectedDates.length === 1) {
            if (clickedDate > selectedDates[0]) {
                selectedDates.push(clickedDate);
            } else {
                selectedDates = [clickedDate];
            }
        } else if (selectedDates.length === 2) {
            selectedDates = [clickedDate];
        }

        var displayEl = document.getElementById('popup-date-display');
        if (selectedDates.length === 1) {
            displayEl.innerText = 'Now select check-out date';
        } else if (selectedDates.length === 2) {
            var d1 = selectedDates[0];
            var d2 = selectedDates[1];
            var nights = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
            displayEl.innerText = shortMonths[d1.getMonth()] + ' ' + d1.getDate() +
                ' — ' + shortMonths[d2.getMonth()] + ' ' + d2.getDate() +
                ', ' + d2.getFullYear() + ' (' + nights + ' nights)';
        }

        updateCalendarUI(); // Calendar will not vanish anymore thanks to the stopPropagation!
    }

    // ---- Next / Previous month buttons ----
    document.getElementById('cal-next').addEventListener('click', function() {
        viewMonth++;
        if (viewMonth > 11) { viewMonth = 0; viewYear++; }
        updateCalendarUI();
    });

    document.getElementById('cal-prev').addEventListener('click', function() {
        var prevMonth = viewMonth - 1;
        var prevYear = viewYear;
        if (prevMonth < 0) { prevMonth = 11; prevYear--; }

        var firstOfPrevView = new Date(prevYear, prevMonth, 1);
        var firstOfThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        if (firstOfPrevView < firstOfThisMonth) return; 

        viewMonth = prevMonth;
        viewYear = prevYear;
        updateCalendarUI();
    });

    // ---- DONE button: apply dates and officially close the calendar ----
    document.getElementById('date-done-btn').addEventListener('click', function() {
        if (selectedDates.length === 2) {
            var text = document.getElementById('popup-date-display').innerText;
            document.getElementById('main-date-display').innerText = text;
        }
        calPopup.classList.remove('show');
    });

    updateCalendarUI();


    // =====================================================
    // SECTION 2 — GUEST COUNTER
    // =====================================================

    var adults   = 2;
    var children = 0;

    var adultCountEl  = document.getElementById('adult-count');
    var childCountEl  = document.getElementById('child-count');
    var guestSummaryEl = document.getElementById('popup-guest-summary');

    function updateGuestDisplay() {
        adultCountEl.innerText  = adults;
        childCountEl.innerText  = children;
        guestSummaryEl.innerText = '1 Room | ' + adults + ' Adults, ' + children + ' Children';
    }

    document.getElementById('adult-minus').addEventListener('click', function() {
        if (adults > 1) { adults--; updateGuestDisplay(); } 
    });

    document.getElementById('adult-plus').addEventListener('click', function() {
        adults++;
        updateGuestDisplay();
    });

    document.getElementById('child-minus').addEventListener('click', function() {
        if (children > 0) { children--; updateGuestDisplay(); }
    });

    document.getElementById('child-plus').addEventListener('click', function() {
        children++;
        updateGuestDisplay();
    });

    // ---- UPDATE button: applies guest count to main bar ----
    document.getElementById('guest-update-btn').addEventListener('click', function() {
        var guestText = '1 Room — ' + adults + ' Adults';
        if (children > 0) guestText += ', ' + children + ' Children';
        document.getElementById('main-guest-display').innerText = guestText;
        guestPopup.classList.remove('show');
    });


    // =====================================================
    // SECTION 3 — BOOK A STAY BUTTON
    // =====================================================

    document.getElementById('book-btn').addEventListener('click', function(e) {
        e.preventDefault(); 

        if (selectedDates.length === 2) {
            sessionStorage.setItem('checkIn',  selectedDates[0].toISOString());
            sessionStorage.setItem('checkOut', selectedDates[1].toISOString());
        }
        sessionStorage.setItem('adults',   adults);
        sessionStorage.setItem('children', children);
        sessionStorage.setItem('promo',    document.getElementById('promo-input').value);

        window.location.href = 'book.html';
    });


    // =====================================================
    // SECTION 4 — CAROUSEL (Infinite Loop)
    // =====================================================
    var track       = document.getElementById('carousel-track');
    var btnNext     = document.getElementById('carousel-next');
    var btnPrev     = document.getElementById('carousel-prev');
    var isAnimating = false; 

    btnNext.addEventListener('click', function() {
        if (isAnimating) return;
        isAnimating = true;

        var cardWidth = track.firstElementChild.offsetWidth;
        var gap = 30;

        track.style.transition = 'transform 0.4s ease-in-out';
        track.style.transform = 'translateX(-' + (cardWidth + gap) + 'px)';

        setTimeout(function() {
            track.appendChild(track.firstElementChild);
            track.style.transition = 'none';
            track.style.transform = 'translateX(0)';
            isAnimating = false;
        }, 400);
    });

    btnPrev.addEventListener('click', function() {
        if (isAnimating) return;
        isAnimating = true;

        var cardWidth = track.firstElementChild.offsetWidth;
        var gap = 30;

        track.insertBefore(track.lastElementChild, track.firstElementChild);
        track.style.transition = 'none';
        track.style.transform = 'translateX(-' + (cardWidth + gap) + 'px)';

        void track.offsetWidth;

        track.style.transition = 'transform 0.4s ease-in-out';
        track.style.transform = 'translateX(0)';

        setTimeout(function() {
            isAnimating = false;
        }, 400);
    });


    // =====================================================
    // SECTION 5 — FAQ ACCORDION
    // =====================================================
    var faqQuestions = document.querySelectorAll('.faq-question');

    faqQuestions.forEach(function(question) {
        question.addEventListener('click', function() {
            var item   = this.parentNode; 
            var isOpen = item.classList.contains('active');

            document.querySelectorAll('.faq-item').forEach(function(faqItem) {
                faqItem.classList.remove('active');
            });

            if (!isOpen) {
                item.classList.add('active');
            }
        });
    });

}); 

function goBook() {
    window.location.href = 'book.html';
}