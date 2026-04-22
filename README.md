# The Riviera — Resort Booking System
### WPL Mini Project | Semester IV | SY COMPS C3 (2025-26) 

**Team Members:**
- Aditi Narkhede — 16010124200
- Shloka Nayak — 16010124202
- Rutuja Palshikar — 16010124208

---

## Project Overview

The Riviera is a full-stack resort booking website for a fictional luxury private island resort in the Andaman & Nicobar Islands. It includes a multi-step booking flow, an admin dashboard, and a MySQL database backend, all running locally on XAMPP.

**Pages included:**
- `index.html` — Homepage with date picker, guest selector, carousel, FAQ, contact
- `book.html` — Step 1: Select villa (availability fetched live from DB)
- `amenities.html` — Step 2: Add experiences (snorkelling, spa, dining, etc.)
- `details.html` — Step 3: Guest personal details + form validation
- `payment.html` — Step 4: Card payment + booking confirmation
- `login.html` — Admin portal: staff login + database dashboard (Reservations, Guests, Villas)

**Backend PHP files:**
- `db.php` — Database connection
- `get_availability.php` — Checks which villas are available for selected dates
- `save_booking.php` — Inserts guest and reservation into the database
- `admin_login.php` — Authenticates staff and returns all table data

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML, CSS, Vanilla JavaScript |
| Backend | PHP 8.x |
| Database | MySQL (via XAMPP) |
| Local Server | Apache (XAMPP) |
| Font | Google Fonts — Montserrat |

---

## How to Run the Project (XAMPP Setup)

The project needs a local server because of the PHP files.

### Step 1 — Install XAMPP

- Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
- Install it
- Open the **XAMPP Control Panel**
- Start **Apache** and **MySQL** — both status lights should turn green

### Step 2 — Copy the Project Folder

- Go to your XAMPP installation folder. On most systems this is:
  - Windows: `C:\xampp\htdocs\`
  - Mac: `/Applications/XAMPP/htdocs/`
- Create a new folder called `riviera` inside `htdocs`
- Copy **all project files** into `C:\xampp\htdocs\riviera\`
- Your folder structure should look like this:

```
htdocs/
└── riviera/
    ├── index.html
    ├── book.html
    ├── amenities.html
    ├── details.html
    ├── payment.html
    ├── login.html
    ├── db.php
    ├── get_availability.php
    ├── save_booking.php
    ├── admin_login.php
    ├── script.js
    └── style.css
```

### Step 3 — Create the Database

- Open your browser and go to: `http://localhost/phpmyadmin`
- Click **"New"** in the left sidebar to create a new database
- Name it exactly: `riviera_db` (case-sensitive)
- Click **Create**

### Step 4 — Import the SQL Schema

- With `riviera_db` selected in phpMyAdmin, click the **"Import"** tab at the top
- Click **"Choose File"** and select the `riviera_db.sql` file from this project
- Click **"Go"** at the bottom
- You should see a green success message — the tables (`Admin`, `Guest`, `Villa`, `Reservation`) will now be created and pre-populated

### Step 5 — Check `db.php` Settings

Open `db.php` and make sure the credentials match your XAMPP setup:

```php
$host     = 'localhost';
$dbname   = 'riviera_db';
$username = 'root';
$password = '';           // XAMPP default is no password — leave blank
```

If you've set a custom MySQL password in XAMPP, enter it in the `$password` field. Otherwise leave it as an empty string.

### Step 6 — Open the Website

- In your browser, go to: `http://localhost/riviera/index.html`
- The homepage should load with the video background, booking bar, and all sections
- Try the full booking flow: pick dates - select a villa - add amenities - fill in details - pay

---

## Admin Login

- Go to `http://localhost/riviera/login.html`
- Use the credentials you inserted into the Admin table:
  - **Employee Code:** `RVRF001`
  - **Password:** `Riviera@Front1`
- After logging in, the page will scroll down and show all three database tables live

---

## Database Schema

```
riviera_db
├── Villa          (villa_id, villa_name, base_price, total_units, max_adults, max_children)
├── Guest          (user_id, first_name, last_name, email, phone_number)
├── Reservation    (reservation_id, user_id, villa_id, check_in, check_out, adults_count, children_count, total_cost)
└── Admin          (admin_id, employee_code, password)
```

- `Reservation` has foreign keys linking to both `Guest` and `Villa`
- Guest emails are unique — repeat bookings by the same guest reuse the same `user_id`
- Villa availability is calculated dynamically using date-overlap logic in `get_availability.php`

---

## Booking Flow (How It Works End to End)

```
index.html         →  User picks dates + guests, clicks BOOK A STAY
    ↓ (sessionStorage)
book.html          →  JS fetches get_availability.php, shows available villas
    ↓ (sessionStorage)
amenities.html     →  User toggles experiences, bill updates live
    ↓ (sessionStorage)
details.html       →  Personal details form with regex validation
    ↓ (sessionStorage)
payment.html       →  Card details form → fetch() POST to save_booking.php
    ↓ (DB response)
Success Screen     →  Booking reference (RVR-{reservation_id}) displayed
```

All data between pages is stored in `sessionStorage`. On successful payment, `sessionStorage` is cleared.

---

## Features Implemented

- **Live availability checking** from MySQL (no hardcoded booked dates)
- **Dynamic pricing** — prices pulled from the Villa table in the DB
- **Regex form validation** on the details and payment pages (email, phone, card number, expiry, CVV)
- **Double-booking prevention** — availability is re-checked on the server at payment time before inserting the reservation
- **Admin dashboard** — staff can log in and view all three tables live
- **Sticky bill panel** — the cost summary updates in real time as amenities are selected
- **Responsive layout** — collapses to single column on mobile
- **Infinite carousel** on the homepage for villa photos
- **FAQ accordion** with smooth open/close animation
- **Sessions** PHP sessions maintain booking state across browser back/forward navigation 
- **Cookies** Remember returning visitor preferences using cookies with proper consent management
---

## Known Limitations / Notes

- The card payment is **simulated** — no real payment gateway is connected. The card fields are validated for format only.
- The site requires **XAMPP to be running** — opening HTML files directly from the file system will cause the PHP fetches to fail (the villas will still display but availability won't load from the DB).
- The `video.mp4` and `logo.png` assets are **not included** in this repository due to file size. The site still works without them — the hero section will just have a black background.

---

## Testing the Booking Flow

1. Start XAMPP, open `http://localhost/riviera/index.html`
2. Pick any future dates (at least 1 night apart)
3. Select 2 Adults, 0 Children
4. Click **BOOK A STAY** → you should land on `book.html`
5. If the DB is set up correctly, villas will show their real prices from the database
6. Select a villa, click **Proceed to Amenities**
7. Add a couple of experiences, check the bill updates on the right
8. Fill in the details form (try leaving fields blank to test validation)
9. On the payment page, use a test card like `4111 1111 1111 1111`, any future expiry, any 3-digit CVV
10. Click **Pay Now** — the booking gets saved to the DB and a booking reference is shown
11. Log into `login.html` with `RVRF001` / `Riviera@Front1` to see your reservation in the Reservations table

---

*© 2026 The Riviera | WPL Mini Project — Aditi Narkhede, Shloka Nayak, Rutuja Palshikar*
