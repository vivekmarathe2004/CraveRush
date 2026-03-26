-- All-in-one schema + seed data for food_system
-- Run once on a fresh database. If re-run, you may get duplicate seed rows.

CREATE DATABASE IF NOT EXISTS food_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    address_line VARCHAR(255) NULL,
    city VARCHAR(60) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    cuisine VARCHAR(120) NOT NULL DEFAULT 'Multi-cuisine',
    rating DECIMAL(3,1) NULL,
    eta_minutes INT NULL,
    price_for_two INT NULL,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorites (
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, restaurant_id),
    CONSTRAINT fk_favorites_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_favorites_restaurant
        FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    category VARCHAR(80) NULL,
    price DECIMAL(10,2) NOT NULL,
    is_veg TINYINT(1) NOT NULL DEFAULT 1,
    image_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_restaurant
        FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    payment_id INT NULL,
    payment_label VARCHAR(120) NULL,
    address_id INT NULL,
    delivery_address VARCHAR(255) NULL,
    delivery_agent VARCHAR(100) NULL,
    delivery_status VARCHAR(50) NOT NULL DEFAULT 'not_assigned',
    estimated_delivery_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_items_menu
        FOREIGN KEY (menu_id) REFERENCES menu_items(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) NOT NULL,
    line1 VARCHAR(120) NOT NULL,
    line2 VARCHAR(120) NULL,
    city VARCHAR(60) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_addresses_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    method VARCHAR(30) NOT NULL,
    label VARCHAR(120) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_tracking_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tracking_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed data (run once)
INSERT IGNORE INTO users (name, email, phone, password, role) VALUES
('Nashik Admin', 'admin@nashik.test', '9822456780', '$2b$12$Y8T6qtbypdEWbxgjDPhfhenyflosL8MLr2u8D6wWpcciQAOdXrjp6', 'admin'),
('Nashik Customer', 'customer@nashik.test', '9822334455', '$2b$12$vXHRdLZFSyeYOwZ4bh/Ox.glFiDJXShCsYkEMnucHkzz/cM/KZr/u', 'customer');

INSERT INTO addresses (user_id, label, line1, line2, city, pincode, is_default) VALUES
(2, 'Home', 'Near College Road', 'Nashik', 'Nashik', '422005', 1),
(2, 'Work', 'Satpur MIDC', 'Nashik', 'Nashik', '422007', 0);

INSERT INTO payments (user_id, method, label, is_default) VALUES
(2, 'UPI', 'vivek@upi', 1),
(2, 'Card', 'Visa **** 4286', 0);

INSERT IGNORE INTO coupons (code, title, description, min_order, discount_amount, is_active) VALUES
('SAVE50', '50% OFF up to INR 50', 'Valid on orders above INR 299', 299, 50, 1),
('SAVE100', 'INR 100 OFF', 'Valid on orders above INR 499', 499, 100, 1),
('FREESHIP', 'Free Delivery', 'No delivery fee', 0, 25, 1),
('TASTY75', 'INR 75 OFF', 'Valid on orders above INR 349', 349, 75, 1);

INSERT INTO restaurants (name, location, cuisine, rating, eta_minutes, price_for_two, image_url) VALUES
('Misal Junction', 'College Road, Nashik', 'Maharashtrian, Misal', 4.6, 24, 180, 'assets/images/dishes/misal.jpg'),
('Panchavati Thali', 'Panchavati, Nashik', 'Maharashtrian, Thali', 4.5, 30, 260, 'assets/images/dishes/thali.jpg'),
('Gangapur Grill', 'Gangapur Road, Nashik', 'North Indian, Grill', 4.3, 28, 320, 'assets/images/dishes/kebab.jpg'),
('Indira Nagar Dosa', 'Indira Nagar, Nashik', 'South Indian', 4.4, 22, 220, 'assets/images/dishes/dosa.jpg'),
('CIDCO Chaat', 'CIDCO, Nashik', 'Chaat, Street Food', 4.1, 20, 160, 'assets/images/dishes/chaat.jpg'),
('Trimbak Tadka', 'Trimbak Road, Nashik', 'Veg Thali, Maharashtrian', 4.2, 27, 240, 'assets/images/dishes/thali.jpg'),
('Nashik Road Biryani', 'Nashik Road, Nashik', 'Biryani, Mughlai', 4.3, 32, 340, 'assets/images/dishes/biryani.jpg'),
('Satpur Sandwiches', 'Satpur MIDC, Nashik', 'Sandwiches, Snacks', 4.0, 21, 190, 'assets/images/dishes/vada-pav.jpg'),
('Deolali Kebab House', 'Deolali Camp, Nashik', 'Kebabs, North Indian', 4.2, 29, 360, 'assets/images/dishes/kebab.jpg'),
('Tapovan Tiffins', 'Tapovan, Nashik', 'Home Style, Breakfast', 4.5, 18, 150, 'assets/images/dishes/dosa.jpg'),
('Ashoka Sweets', 'Ashoka Marg, Nashik', 'Desserts, Mithai', 4.4, 24, 220, 'assets/images/dishes/gulab-jamun.jpg'),
('College Road Cafe', 'College Road, Nashik', 'Cafe, Beverages', 4.1, 25, 280, 'assets/images/dishes/coffee.jpg');

INSERT INTO menu_items (restaurant_id, item_name, description, category, price, is_veg, image_url, is_active) VALUES
(1, 'Nashik Special Misal Pav', 'Spicy tarri misal with farsan and pav.', 'Misal', 120.00, 1, 'assets/images/dishes/misal.jpg', 1),
(1, 'Tarri Poha', 'Poha soaked in spicy tarri.', 'Breakfast', 90.00, 1, 'assets/images/dishes/dosa.jpg', 1),
(1, 'Kanda Bhaji', 'Crispy onion fritters.', 'Snacks', 80.00, 1, 'assets/images/dishes/vada-pav.jpg', 1),
(2, 'Maharashtrian Thali', 'Complete thali with sabzi, bhakri, rice.', 'Thali', 220.00, 1, 'assets/images/dishes/thali.jpg', 1),
(2, 'Pithla Bhakri', 'Gram flour curry with jowar bhakri.', 'Thali', 170.00, 1, 'assets/images/dishes/thali.jpg', 1),
(2, 'Bharli Vangi', 'Stuffed brinjals in peanut gravy.', 'Curry', 180.00, 1, 'assets/images/dishes/thali.jpg', 1),
(3, 'Paneer Tikka', 'Tandoor paneer with smoky spices.', 'Starters', 210.00, 1, 'assets/images/dishes/kebab.jpg', 1),
(3, 'Butter Chicken', 'Creamy tomato gravy with chicken.', 'Curry', 260.00, 0, 'assets/images/dishes/biryani.jpg', 1),
(3, 'Dal Fry', 'Yellow dal tempered with garlic.', 'Curry', 160.00, 1, 'assets/images/dishes/thali.jpg', 1),
(4, 'Masala Dosa', 'Crisp dosa with potato masala.', 'South Indian', 140.00, 1, 'assets/images/dishes/dosa.jpg', 1),
(4, 'Idli Sambhar', 'Soft idlis with sambhar and chutney.', 'South Indian', 110.00, 1, 'assets/images/dishes/dosa.jpg', 1),
(4, 'Filter Coffee', 'Traditional South Indian coffee.', 'Beverages', 70.00, 1, 'assets/images/dishes/coffee.jpg', 1),
(5, 'Samosa Chaat', 'Crispy samosa with chaat toppings.', 'Chaat', 90.00, 1, 'assets/images/dishes/chaat.jpg', 1),
(5, 'Sev Puri', 'Sweet-tangy sev puri.', 'Chaat', 80.00, 1, 'assets/images/dishes/chaat.jpg', 1),
(5, 'Dahi Puri', 'Crispy puris with yogurt.', 'Chaat', 90.00, 1, 'assets/images/dishes/chaat.jpg', 1),
(6, 'Varhadi Chicken', 'Spicy Maharashtrian chicken curry.', 'Maharashtrian', 260.00, 0, 'assets/images/dishes/thali.jpg', 1),
(6, 'Bhakri Thali', 'Bhakri with seasonal sabzi and dal.', 'Thali', 200.00, 1, 'assets/images/dishes/thali.jpg', 1),
(6, 'Solkadhi', 'Kokum-coconut digestive drink.', 'Beverages', 60.00, 1, 'assets/images/dishes/coffee.jpg', 1),
(7, 'Chicken Biryani', 'Fragrant rice with chicken.', 'Biryani', 240.00, 0, 'assets/images/dishes/biryani.jpg', 1),
(7, 'Veg Biryani', 'Spiced rice with vegetables.', 'Biryani', 200.00, 1, 'assets/images/dishes/biryani.jpg', 1),
(7, 'Mutton Biryani', 'Slow-cooked mutton biryani.', 'Biryani', 320.00, 0, 'assets/images/dishes/biryani.jpg', 1),
(8, 'Veg Grilled Sandwich', 'Grilled sandwich with chutney.', 'Sandwich', 120.00, 1, 'assets/images/dishes/vada-pav.jpg', 1),
(8, 'Cheese Corn Sandwich', 'Sweet corn and cheese filling.', 'Sandwich', 140.00, 1, 'assets/images/dishes/vada-pav.jpg', 1),
(8, 'Masala Fries', 'Crispy fries with spice mix.', 'Snacks', 110.00, 1, 'assets/images/dishes/vada-pav.jpg', 1);

-- Optional: normalize images after data load (safe to run again)
UPDATE restaurants SET image_url = 'assets/images/dishes/misal.jpg' WHERE name = 'Misal Junction';
UPDATE restaurants SET image_url = 'assets/images/dishes/thali.jpg' WHERE name = 'Panchavati Thali';
UPDATE restaurants SET image_url = 'assets/images/dishes/kebab.jpg' WHERE name = 'Gangapur Grill';
UPDATE restaurants SET image_url = 'assets/images/dishes/dosa.jpg' WHERE name = 'Indira Nagar Dosa';
UPDATE restaurants SET image_url = 'assets/images/dishes/chaat.jpg' WHERE name = 'CIDCO Chaat';
UPDATE restaurants SET image_url = 'assets/images/dishes/thali.jpg' WHERE name = 'Trimbak Tadka';
UPDATE restaurants SET image_url = 'assets/images/dishes/biryani.jpg' WHERE name = 'Nashik Road Biryani';
UPDATE restaurants SET image_url = 'assets/images/dishes/vada-pav.jpg' WHERE name = 'Satpur Sandwiches';
UPDATE restaurants SET image_url = 'assets/images/dishes/kebab.jpg' WHERE name = 'Deolali Kebab House';
UPDATE restaurants SET image_url = 'assets/images/dishes/dosa.jpg' WHERE name = 'Tapovan Tiffins';
UPDATE restaurants SET image_url = 'assets/images/dishes/gulab-jamun.jpg' WHERE name = 'Ashoka Sweets';
UPDATE restaurants SET image_url = 'assets/images/dishes/coffee.jpg' WHERE name = 'College Road Cafe';

UPDATE menu_items SET image_url = 'assets/images/dishes/misal.jpg' WHERE category = 'Misal';
UPDATE menu_items SET image_url = 'assets/images/dishes/dosa.jpg' WHERE category = 'Breakfast';
UPDATE menu_items SET image_url = 'assets/images/dishes/vada-pav.jpg' WHERE category = 'Snacks';
UPDATE menu_items SET image_url = 'assets/images/dishes/thali.jpg' WHERE category = 'Thali';
UPDATE menu_items SET image_url = 'assets/images/dishes/thali.jpg' WHERE category = 'Curry';
UPDATE menu_items SET image_url = 'assets/images/dishes/kebab.jpg' WHERE category = 'Starters';
UPDATE menu_items SET image_url = 'assets/images/dishes/dosa.jpg' WHERE category = 'South Indian';
UPDATE menu_items SET image_url = 'assets/images/dishes/coffee.jpg' WHERE category = 'Beverages';
UPDATE menu_items SET image_url = 'assets/images/dishes/chaat.jpg' WHERE category = 'Chaat';
UPDATE menu_items SET image_url = 'assets/images/dishes/thali.jpg' WHERE category = 'Maharashtrian';
UPDATE menu_items SET image_url = 'assets/images/dishes/biryani.jpg' WHERE category = 'Biryani';
UPDATE menu_items SET image_url = 'assets/images/dishes/vada-pav.jpg' WHERE category = 'Sandwich';
UPDATE menu_items SET image_url = 'assets/images/dishes/kebab.jpg' WHERE category = 'Kebabs';
UPDATE menu_items SET image_url = 'assets/images/dishes/gulab-jamun.jpg' WHERE category = 'Dessert';
UPDATE menu_items SET image_url = 'assets/images/dishes/gulab-jamun.jpg' WHERE category = 'Bakery';

-- Optional: keep only the first 25 menu items
-- DELETE FROM menu_items
-- WHERE id NOT IN (
--     SELECT id FROM (
--         SELECT id FROM menu_items ORDER BY id ASC LIMIT 25
--     ) t
-- );
