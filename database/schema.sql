CREATE DATABASE IF NOT EXISTS inventory_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_pos;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('minimal','basic','advanced') NOT NULL DEFAULT 'minimal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name VARCHAR(160) NOT NULL,
  description TEXT,
  selling_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  manufacturing_date DATE NULL,
  expiry_date DATE NULL,
  barcode VARCHAR(100) UNIQUE,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE stock_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  entry_date DATE NOT NULL,
  remarks TEXT,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE pricelists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE price_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pricelist_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  rule_type ENUM('all','category','product') NOT NULL,
  category_id INT NULL,
  product_id INT NULL,
  base_type ENUM('list_price','pricelist') NOT NULL DEFAULT 'list_price',
  base_pricelist_id INT NULL,
  formula_type ENUM('percentage','fixed') NOT NULL,
  formula_value DECIMAL(12,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (pricelist_id) REFERENCES pricelists(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE payment_methods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE pos_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pos_name VARCHAR(120) NOT NULL,
  warehouse_name VARCHAR(120) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  default_pricelist_id INT NULL,
  receipt_footer_message VARCHAR(255),
  FOREIGN KEY (default_pricelist_id) REFERENCES pricelists(id) ON DELETE SET NULL
);

CREATE TABLE pos_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  opening_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  opening_time DATETIME NOT NULL,
  closing_amount DECIMAL(12,2) NULL,
  closing_time DATETIME NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE pos_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(40) NOT NULL UNIQUE,
  customer_name VARCHAR(140),
  session_id INT NOT NULL,
  user_id INT NOT NULL,
  order_date DATETIME NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL,
  tax DECIMAL(12,2) NOT NULL,
  grand_total DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (session_id) REFERENCES pos_sessions(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE pos_order_lines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES pos_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE pos_payment_lines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  payment_method_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES pos_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);

INSERT INTO users (name,email,password,role) VALUES
('Admin','admin@example.com','$2y$10$/xiDP7egKMNO6GFdsFh6DeUPd0RpTTHMJVn4yaV.YhTckAxZxAAYe','advanced');
INSERT INTO categories (name,description) VALUES ('General','Default category');
INSERT INTO pricelists (name,active) VALUES ('Retail Price',1),('Wholesale Price',1),('Distributor Price',1);
INSERT INTO payment_methods (name,active) VALUES ('Cash',1),('UPI',1),('Credit Card',1),('Debit Card',1),('Wallet',1);
INSERT INTO pos_settings (pos_name,warehouse_name,active,default_pricelist_id,receipt_footer_message)
VALUES ('Main POS','Main Warehouse',1,1,'Thank you for shopping with us!');
