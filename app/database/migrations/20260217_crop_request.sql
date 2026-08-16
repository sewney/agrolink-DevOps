 --croprequest module--
-- Add product-level requests that support and propagate to orders.
--$min_qty = $_POST['min_order_qty'];--
CREATE DATABASE agrolink;
USE agrolink;
CREATE TABLE crop_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT,
    crop_name VARCHAR(100),
    quantity INT,
    target_price DECIMAL(10,2),
    delivery_date DATE,
    location VARCHAR(255),
    status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
--change these tables-- INSERT INTO products (..., min_order_qty)--
ALTER TABLE products ADD min_order_qty INT;
ALTER TABLE products DROP COLUMN min_order_qty;
ALTER TABLE products MODIFY quantity DECIMAL(10,2);
ALTER TABLE products CHANGE old_name new_name VARCHAR(100);
INSERT INTO crop_requests 
(buyer_id, crop_name, quantity, target_price, delivery_date, location, status)
VALUES 
(1, 'Carrots', 100, 200.00, '2026-05-01', 'Colombo', 'pending');
SELECT * FROM crop_requests;
SELECT * FROM crop_requests WHERE status = 'pending';
SELECT * FROM crop_requests 
WHERE status = 'pending' AND location = 'Colombo';
SELECT * FROM crop_requests ORDER BY created_at DESC;
SELECT * FROM crop_requests LIMIT 10;
UPDATE crop_requests
SET status = 'accepted'
WHERE id = 5;
UPDATE crop_requests
SET status = 'accepted', location = 'Kandy'
WHERE id = 5;
DELETE FROM crop_requests WHERE id = 5;
TRUNCATE TABLE crop_requests;
SELECT * FROM crop_requests WHERE crop_name LIKE '%carrot%';
SELECT * FROM crop_requests 
WHERE status IN ('pending', 'accepted');
SELECT * FROM crop_requests
WHERE target_price BETWEEN 100 AND 500;
SELECT cr.*, b.name
FROM crop_requests cr
JOIN buyers b ON cr.buyer_id = b.id;
ALTER TABLE crop_requests
ADD CONSTRAINT fk_buyer
FOREIGN KEY (buyer_id) REFERENCES buyers(id);
ALTER TABLE crop_requests
ADD CONSTRAINT chk_quantity CHECK (quantity > 0);
