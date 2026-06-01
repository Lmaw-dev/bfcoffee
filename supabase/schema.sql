-- Supabase/PostgreSQL in-place replacement script for the existing BFC project.
-- Run this in the Supabase SQL editor after backing up the live database.

DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS cafe_settings CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS staff CASCADE;
DROP TABLE IF EXISTS registration CASCADE;

CREATE TABLE registration (
  id BIGSERIAL PRIMARY KEY,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  address VARCHAR(255) DEFAULT '',
  sex VARCHAR(20) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NOT NULL,
  price NUMERIC(10, 2) NOT NULL,
  image VARCHAR(255),
  available BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE products ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Public read products" ON products;
CREATE POLICY "Public read products"
  ON products
  FOR SELECT
  USING (true);

CREATE TABLE orders (
  id BIGSERIAL PRIMARY KEY,
  order_date TIMESTAMP NOT NULL,
  items JSONB NOT NULL,
  total NUMERIC(10, 2) NOT NULL,
  paid NUMERIC(10, 2),
  change_amount NUMERIC(10, 2),
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staff (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  role VARCHAR(50) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cafe_settings (
  id BIGSERIAL PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
  id BIGSERIAL PRIMARY KEY,
  fullname VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) DEFAULT 'user',
  date_registered TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (id, name, category, price, image, available, created_at, updated_at) VALUES
  (1, 'Espresso', 'Coffees', 42.00, 'images/espresso.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (2, 'Americano', 'Coffees', 52.50, 'images/americano.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (3, 'Cappuccino', 'Coffees', 43.00, 'images/cappuccino.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (4, 'Latte', 'Coffees', 33.50, 'images/latte.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (5, 'Mocha', 'Coffees', 34.00, 'images/mocha.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (6, 'Macchiato', 'Coffees', 32.75, 'images/macchiato.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (7, 'Malunggay Pandesal', 'Pastries', 5.00, 'images/pandesal.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (8, 'Egg Bread', 'Pastries', 5.00, 'images/egg.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (9, 'Pan de Coco', 'Pastries', 5.00, 'images/coco.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (10, 'Choco/Vanilla Bavarian', 'Pastries', 10.00, 'images/bavarian.jpg', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (17, 'Kape Stick', 'Coffees', 2.00, 'images/bfc.jpg', TRUE, '2026-03-30 12:30:48', '2026-03-30 12:30:48')
ON CONFLICT (id) DO NOTHING;

INSERT INTO orders (id, order_date, items, total, paid, change_amount, created_at, status) VALUES
  (2, '2026-03-30 17:08:51', '[{"id":2,"name":"Americano","price":52.5,"quantity":1},{"id":3,"name":"Cappuccino","price":43,"quantity":1},{"id":1,"name":"Espresso","price":42,"quantity":1},{"id":10,"name":"Choco/Vanilla Bavarian","price":10,"quantity":1},{"id":7,"name":"Malunggay Pandesal","price":5,"quantity":2}]', 157.50, 200.00, 42.50, '2026-03-30 15:08:51', 'pending'),
  (3, '2026-03-31 05:26:00', '[{"id":10,"name":"Choco/Vanilla Bavarian","price":10,"quantity":1},{"id":8,"name":"Egg Bread","price":5,"quantity":1}]', 15.00, 15.00, 0.00, '2026-03-31 03:26:00', 'pending'),
  (4, '2026-03-31 07:59:12', '[{"id":2,"name":"Americano","price":52.5,"quantity":3},{"id":3,"name":"Cappuccino","price":43,"quantity":1},{"id":10,"name":"Choco/Vanilla Bavarian","price":10,"quantity":1}]', 210.50, 300.00, 89.50, '2026-03-31 05:59:12', 'pending'),
  (5, '2026-03-31 09:04:47', '[{"id":2,"name":"Americano","price":52.5,"quantity":2},{"id":10,"name":"Choco/Vanilla Bavarian","price":10,"quantity":2}]', 125.00, 130.00, 5.00, '2026-03-31 07:04:47', 'pending'),
  (6, '2026-05-20 13:48:23', '[{"id":1,"name":"Espresso","price":42,"quantity":1},{"id":3,"name":"Cappuccino","price":43,"quantity":1},{"id":2,"name":"Americano","price":52.5,"quantity":1}]', 137.50, 150.00, 12.50, '2026-05-20 11:48:23', 'pending')
ON CONFLICT (id) DO NOTHING;

INSERT INTO staff (id, name, role, username, password, active, created_at, updated_at) VALUES
  (1, 'Administrator', 'Manager', 'admin', 'admin123', TRUE, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
  (2, 'Justin', 'Cashier', 'justin', '123123', TRUE, '2026-03-30 12:33:20', '2026-03-30 12:33:20')
ON CONFLICT (id) DO NOTHING;

INSERT INTO registration (id, firstname, lastname, email, password, address, sex, created_at) VALUES
  (1, 'test', 'user', 'test@gmail.com', 'user', 'Taytay', 'Male', '2026-03-29 12:42:59'),
  (3, 'test', 'user', 'user@gmail.com', '123123', 'Taytay', 'Male', '2026-03-30 12:09:04'),
  (5, 'qwerty', 'uiop', 'qwerty@gmail.com', 'qwerty', 'Taytay', 'Prefer not to say', '2026-03-30 12:39:49')
ON CONFLICT (id) DO NOTHING;

INSERT INTO users (id, fullname, email, username, password, role, date_registered) VALUES
  (1, 'Justin Roble', 'justin@gmail.com', 'larvondy', '$2y$10$uk8UZwoiInRd/mOhPX35BuWeiDOz8q5W/L3lk3gNE9eLpxkbaE9ei', 'user', '2026-03-08 06:03:22'),
  (2, 'Administrator', 'admin@admin.com', 'jireh', 'faith', 'admin', '2026-03-10 07:48:03')
ON CONFLICT (id) DO NOTHING;

SELECT setval(pg_get_serial_sequence('products', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM products), 1), true);
SELECT setval(pg_get_serial_sequence('orders', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM orders), 1), true);
SELECT setval(pg_get_serial_sequence('staff', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM staff), 1), true);
SELECT setval(pg_get_serial_sequence('registration', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM registration), 1), true);
SELECT setval(pg_get_serial_sequence('users', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM users), 1), true);
SELECT setval(pg_get_serial_sequence('cafe_settings', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM cafe_settings), 1), true);
