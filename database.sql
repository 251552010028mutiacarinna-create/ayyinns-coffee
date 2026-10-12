CREATE DATABASE IF NOT EXISTS ayyinns_coffee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ayyinns_coffee;

CREATE TABLE IF NOT EXISTS produk (
  id_produk INT AUTO_INCREMENT PRIMARY KEY,
  nama_produk VARCHAR(100) NOT NULL,
  kategori ENUM('Minuman','Camilan','Pastry') NOT NULL DEFAULT 'Minuman',
  harga DECIMAL(12,2) NOT NULL DEFAULT 0,
  stok INT NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pelanggan (
  id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
  nama_pelanggan VARCHAR(100) NOT NULL,
  no_hp VARCHAR(25) DEFAULT '',
  email VARCHAR(100) DEFAULT '',
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transaksi (
  id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
  id_pelanggan INT NOT NULL,
  tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan)
);

CREATE TABLE IF NOT EXISTS detail_transaksi (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_produk INT NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_transaksi)
        REFERENCES transaksi(id_transaksi)
        ON DELETE CASCADE,
    FOREIGN KEY (id_produk)
        REFERENCES produk(id_produk)
);

INSERT INTO produk (nama_produk,kategori,harga,stok) VALUES
('Iced Matcha Latte','Minuman',28000,25),
('Brown Sugar Boba','Minuman',26000,30),
('Sea Salt Cream Coffee','Minuman',30000,20),
('Strawberry Cloud','Minuman',27000,18),
('Croffle Cinnamon','Pastry',22000,15),
('Choco Chip Cookie','Camilan',15000,35),
('French Fries Truffle','Camilan',24000,16),
('Korean Garlic Bread','Pastry',23000,12);

INSERT INTO pelanggan (nama_pelanggan,no_hp,email) VALUES
('Nadia Putri','081234567801','nadia@example.com'),
('Alya Ramadhani','081234567802','alya@example.com'),
('Raka Pratama','081234567803','raka@example.com');
