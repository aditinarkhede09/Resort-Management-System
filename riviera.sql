-- 1. Create the database and select it
CREATE DATABASE IF NOT EXISTS riviera_db;
USE riviera_db;

-- 2. Create the Guest table
CREATE TABLE Guest (
    user_id SERIAL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL
);

-- 3. Create the Admin table
CREATE TABLE Admin (
    user_id SERIAL PRIMARY KEY,
    employee_code VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- 4. Create the Villa table
CREATE TABLE Villa (
    villa_id SERIAL PRIMARY KEY,
    villa_name VARCHAR(100) NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    total_units INT NOT NULL,
    max_adults INT NOT NULL,
    max_children INT NOT NULL
);

-- 5. Create the Reservation table
-- Note: SERIAL automatically creates a BIGINT UNSIGNED column. 
-- Therefore, our Foreign Keys (user_id and villa_id) must also be BIGINT UNSIGNED.
CREATE TABLE Reservation (
    reservation_id SERIAL PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    villa_id BIGINT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    adults_count INT NOT NULL,
    children_count INT NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,    
    -- Define the Foreign Key constraints and their behaviors
    FOREIGN KEY (user_id) REFERENCES Guest(user_id) ON DELETE CASCADE,
    FOREIGN KEY (villa_id) REFERENCES Villa(villa_id) ON DELETE RESTRICT
);


-- Select your database first
USE riviera_db;

-- Insert the catalog of villas into the database
INSERT INTO Villa (villa_name, base_price, total_units, max_adults, max_children)
VALUES 
('Panoramic Ocean-View Pool Villa', 45000.00, 5, 2, 1),
('Premier Ocean-View Pool Villa', 38000.00, 4, 2, 2),
('Oceanfront Pool Villa', 55000.00, 3, 2, 1),
('Family Pool Villa', 30000.00, 4, 3, 2),
('Garden Pool Villa', 25000.00, 6, 2, 0);

-- Select your database first
USE riviera_db;

INSERT INTO admin (employee_code, password) 
VALUES 
('RVRM001', 'Riviera@Manager1'),
('RVRM002', 'Riviera@Manager2'),
('RVRF001', 'Riviera@Front1'),
('RVRF002', 'Riviera@Front2');
