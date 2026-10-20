
-- ============================================================
-- IoT DATABASE ASSIGNMENT
-- Sonoma State University
-- Parts 1.1, 1.2, and 1.3
--
-- This script demonstrates:
-- 1. Creating database tables
-- 2. Inserting and modifying records
-- 3. SQL constraints and primary keys
-- 4. SELECT, UPDATE, DELETE, LIKE, and INNER JOIN
-- 5. Creating sensor tables and database views
--
-- NOTES:
-- This script resets the assignment tables.
-- Run only in a fresh or disposable practice database.
-- ============================================================


-- ============================================================
-- PART 1.1: CREATE A BOOK TABLE
-- ============================================================

-- Remove old assignment tables in dependency order.
DROP VIEW IF EXISTS sensor_combined;
DROP VIEW IF EXISTS COMBINED;

DROP TABLE IF EXISTS sensor_data;
DROP TABLE IF EXISTS sensor_register;
DROP TABLE IF EXISTS Price2;
DROP TABLE IF EXISTS Book2;
DROP TABLE IF EXISTS Price;
DROP TABLE IF EXISTS Book;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS t2;

-- Create the Book table.
CREATE TABLE Book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Insert two books.
INSERT INTO Book (id, name)
VALUES
(1, 'MariaDB Book'),
(2, 'SQLDB Book');

-- Display the Book table.
SELECT * FROM Book;

-- Display available tables.
SHOW TABLES;

-- Display the Book table structure.
DESC Book;


-- ============================================================
-- PART 1.2: PRACTICE MYSQL COMMANDS
-- ============================================================

-- ------------------------------------------------------------
-- 1. TABLE CONSTRAINTS
-- ------------------------------------------------------------

-- Create a table with constraints on names and dates.
CREATE TABLE t2 (
    name VARCHAR(30)
        CHECK (CHAR_LENGTH(name) > 2),
    start_date DATE,
    end_date DATE,
    CHECK (
        start_date IS NULL
        OR end_date IS NULL
        OR start_date < end_date
    )
);

-- Valid record.
INSERT INTO t2
(name, start_date, end_date)
VALUES
('Ione', '2003-12-15', '2014-11-09');

-- Invalid name example: intentionally not executed.
-- INSERT INTO t2 VALUES
-- ('Io', '2003-12-15', '2014-11-09');

-- Valid record with a NULL starting date.
INSERT INTO t2
(name, start_date, end_date)
VALUES
('Ione', NULL, '2014-11-09');

-- Invalid date range: intentionally not executed.
-- INSERT INTO t2 VALUES
-- ('Ione', '2015-12-15', '2014-11-09');

SELECT * FROM t2;


-- ------------------------------------------------------------
-- 2. AUTO_INCREMENT
-- ------------------------------------------------------------

CREATE TABLE product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(200),
    price INT
);

INSERT INTO product (product_name, price)
VALUES ('Desk chair', 50);

SELECT * FROM product;


-- ------------------------------------------------------------
-- 3. RECREATE BOOK TABLE
-- ------------------------------------------------------------

-- The original tutorial removes and recreates Book.
DROP TABLE Book;

CREATE TABLE Book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);


-- ------------------------------------------------------------
-- 4. CREATE PRICE TABLE
-- ------------------------------------------------------------

CREATE TABLE Price (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price FLOAT NOT NULL
);


-- ------------------------------------------------------------
-- 5. INSERT BOOK RECORDS
-- ------------------------------------------------------------

INSERT INTO Book (id, name)
VALUES
(1, 'MariaDB Book1'),
(2, 'MariaDB Book2'),
(3, 'MariaDB Book3'),
(4, 'MariaDB Book4'),
(5, 'MariaDB Book5');

SELECT * FROM Book;


-- ------------------------------------------------------------
-- 6. INSERT PRICE RECORDS
-- ------------------------------------------------------------

INSERT INTO Price (id, price)
VALUES
(1, 251),
(2, 252),
(3, 223),
(4, 194),
(5, 305);

SELECT * FROM Price;


-- ------------------------------------------------------------
-- 7. UPDATE RECORDS
-- ------------------------------------------------------------

-- Update the price of Book 1.
UPDATE Price
SET price = 250
WHERE id = 1;

-- Update the name of Book 1.
UPDATE Book
SET name = 'MariaDB Book1a'
WHERE id = 1;

SELECT * FROM Book;
SELECT * FROM Price;


-- ------------------------------------------------------------
-- 8. INSERT AND DELETE RECORDS
-- ------------------------------------------------------------

-- Add a new price using AUTO_INCREMENT.
INSERT INTO Price (price)
VALUES (251);

-- Remove the newly inserted price.
DELETE FROM Price
WHERE id = 6;

SELECT * FROM Price;


-- ------------------------------------------------------------
-- 9. SELECT WITH WHERE
-- ------------------------------------------------------------

-- Show prices below 250.
SELECT *
FROM Price
WHERE price < 250;

-- Show prices where ID > 3 OR price < 250.
SELECT *
FROM Price
WHERE id > 3
OR price < 250;


-- ------------------------------------------------------------
-- 10. LIKE AND ORDER BY
-- ------------------------------------------------------------

-- Find book names ending in 4.
SELECT name
FROM Book
WHERE name LIKE '%4';

-- Find prices beginning with 2.
SELECT *
FROM Price
WHERE price LIKE '2%'
ORDER BY price;


-- ------------------------------------------------------------
-- 11. INNER JOIN
-- ------------------------------------------------------------

-- Combine book names with their corresponding prices.
SELECT Book.name, Price.price
FROM Book
INNER JOIN Price
ON Book.id = Price.id;


-- ------------------------------------------------------------
-- 12. CREATE TABLES WITH FOREIGN KEY
-- ------------------------------------------------------------

CREATE TABLE Book2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE Price2 (
    id INT NOT NULL,
    price FLOAT NOT NULL,
    FOREIGN KEY (id) REFERENCES Book2(id)
);

-- Register books first.
INSERT INTO Book2 (id, name)
VALUES
(1, 'MariaDB Book1'),
(2, 'MariaDB Book2'),
(3, 'MariaDB Book3'),
(4, 'MariaDB Book4'),
(5, 'MariaDB Book5'),
(6, 'MariaDB Book6');

-- Add the price for Book 6.
INSERT INTO Price2 (id, price)
VALUES (6, 251);


-- ------------------------------------------------------------
-- 13. CREATE BOOK VIEW
-- ------------------------------------------------------------

CREATE VIEW COMBINED AS
SELECT
    Book2.name,
    Book2.id,
    Price2.price
FROM Book2
INNER JOIN Price2
ON Book2.id = Price2.id;

SELECT * FROM COMBINED;

-- Remove the Book VIEW as requested by the tutorial.
DROP VIEW IF EXISTS COMBINED;


-- ============================================================
-- PART 1.3: CREATE SENSOR DATABASE
-- ============================================================


-- ------------------------------------------------------------
-- 1. CREATE SENSOR_REGISTER TABLE
-- ------------------------------------------------------------

-- This table stores information about registered sensors.
-- Manufacturer is VARCHAR(50) to preserve the full
-- Sonoma State University name used in this project.

CREATE TABLE sensor_register (
    node_name VARCHAR(10) PRIMARY KEY,
    manufacturer VARCHAR(50),
    longitude DECIMAL(15,10),
    latitude DECIMAL(15,10)
);


-- ------------------------------------------------------------
-- 2. CREATE SENSOR_DATA TABLE
-- ------------------------------------------------------------

-- This table stores temperature and humidity readings.
-- The composite primary key prevents duplicate timestamps
-- for the same sensor node.

CREATE TABLE sensor_data (
    node_name VARCHAR(10) NOT NULL,
    time_received DATETIME NOT NULL,
    temperature DECIMAL(6,2),
    humidity DECIMAL(6,2),
    PRIMARY KEY (node_name, time_received)
);


-- ------------------------------------------------------------
-- 3. REGISTER FIVE SENSORS
-- ------------------------------------------------------------

-- Register the five sensor locations.

INSERT INTO sensor_register
(node_name, manufacturer, longitude, latitude)
VALUES
('node_1', 'Sonoma State University', -122.674396, 38.340345),
('node_2', 'Tucson ', -110.972280, 32.213025),
('node_3', 'New York', -74.111107, 40.625940),
('node_4', 'Dallas ', -97.017878, 32.714319),
('node_5', 'Omaha', -95.911985, 41.282150);

-- Display registered sensors.
SELECT * FROM sensor_register;


-- ------------------------------------------------------------
-- 4. INSERT SENSOR DATA
-- ------------------------------------------------------------

-- Starting date: October 1, 2022
-- Starting time: 11:00 AM
-- Data interval: Every 30 minutes
-- Four readings per sensor, 20 readings total.
-- All temperature and humidity values are sample data.

INSERT INTO sensor_data
(node_name, time_received, temperature, humidity)
VALUES

-- Node 1: Sonoma State University
('node_1', '2022-10-01 11:00:00', 25, 60),
('node_1', '2022-10-01 11:30:00', 26, 62),
('node_1', '2022-10-01 12:00:00', 27, 58),
('node_1', '2022-10-01 12:30:00', 28, 61),

-- Node 2: Tucson
('node_2', '2022-10-01 11:00:00', 30, 55),
('node_2', '2022-10-01 11:30:00', 31, 54),
('node_2', '2022-10-01 12:00:00', 32, 57),
('node_2', '2022-10-01 12:30:00', 29, 56),

-- Node 3: New York
('node_3', '2022-10-01 11:00:00', 22, 70),
('node_3', '2022-10-01 11:30:00', 23, 72),
('node_3', '2022-10-01 12:00:00', 24, 68),
('node_3', '2022-10-01 12:30:00', 25, 69),

-- Node 4: Dallas
('node_4', '2022-10-01 11:00:00', 28, 45),
('node_4', '2022-10-01 11:30:00', 29, 46),
('node_4', '2022-10-01 12:00:00', 30, 44),
('node_4', '2022-10-01 12:30:00', 31, 43),

-- Node 5: Omaha
('node_5', '2022-10-01 11:00:00', 20, 80),
('node_5', '2022-10-01 11:30:00', 21, 78),
('node_5', '2022-10-01 12:00:00', 22, 76),
('node_5', '2022-10-01 12:30:00', 23, 75);


-- ------------------------------------------------------------
-- 5. DISPLAY SENSOR DATA
-- ------------------------------------------------------------

SELECT *
FROM sensor_data
ORDER BY node_name, time_received;


-- ------------------------------------------------------------
-- 6. CREATE SENSOR_COMBINED VIEW
-- ------------------------------------------------------------

-- Combine sensor information and sensor readings.

CREATE VIEW sensor_combined AS
SELECT
    sensor_data.node_name,
    sensor_register.manufacturer,
    sensor_register.longitude,
    sensor_register.latitude,
    sensor_data.time_received,
    sensor_data.temperature,
    sensor_data.humidity
FROM sensor_data
INNER JOIN sensor_register
ON sensor_data.node_name = sensor_register.node_name;


-- ------------------------------------------------------------
-- 7. DISPLAY COMBINED SENSOR DATA
-- ------------------------------------------------------------

SELECT *
FROM sensor_combined
ORDER BY node_name, time_received;


-- ------------------------------------------------------------
-- 8. VERIFY SENSOR COUNTS
-- ------------------------------------------------------------

-- Each sensor should have four readings.

SELECT
    node_name,
    COUNT(*) AS total_readings
FROM sensor_data
GROUP BY node_name;


-- ------------------------------------------------------------
-- 9. SENSOR VALIDATION
-- ------------------------------------------------------------

-- The following checks are needed for the full assignment.
-- Add the constraints below after cleaning any invalid data.

-- Reject unregistered sensor nodes.
-- ALTER TABLE sensor_data
-- ADD CONSTRAINT fk_sensor_register
-- FOREIGN KEY (node_name)
-- REFERENCES sensor_register(node_name);

-- Restrict temperature to -10 through 100 Celsius.
-- ALTER TABLE sensor_data
-- ADD CONSTRAINT chk_temperature
-- CHECK (
--     temperature BETWEEN -10 AND 100
--     AND temperature IS NOT NULL
-- );

-- Restrict humidity to 0 through 100 percent.
-- ALTER TABLE sensor_data
-- ADD CONSTRAINT chk_humidity
-- CHECK (
--     humidity BETWEEN 0 AND 100
--     AND humidity IS NOT NULL
-- );

-- Test an unregistered sensor after enabling the foreign key.
-- This INSERT should fail.
-- INSERT INTO sensor_data
-- (node_name, time_received, temperature, humidity)
-- VALUES
-- ('node_6', '2022-10-01 11:00:00', 25, 60);


-- ============================================================
-- FINAL DATABASE VERIFICATION
-- ============================================================

SHOW TABLES;

SELECT * FROM Book;
SELECT * FROM Price;
SELECT * FROM sensor_register;
SELECT * FROM sensor_data;
SELECT * FROM sensor_combined;

-- ============================================================
-- END OF DATABASE ASSIGNMENT
-- ============================================================
