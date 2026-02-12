-- Database Sistem Pakar Tanaman Anggrek
-- Created: 2026-02-12

CREATE DATABASE IF NOT EXISTS sistem_pakar_anggrek;
USE sistem_pakar_anggrek;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Penyakit Anggrek
CREATE TABLE IF NOT EXISTS penyakit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_penyakit VARCHAR(10) NOT NULL UNIQUE,
    nama_penyakit VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    solusi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Gejala
CREATE TABLE IF NOT EXISTS gejala (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_gejala VARCHAR(10) NOT NULL UNIQUE,
    nama_gejala VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Relasi Penyakit-Gejala untuk Naive Bayes (dengan probabilitas)
CREATE TABLE IF NOT EXISTS penyakit_gejala (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penyakit_id INT NOT NULL,
    gejala_id INT NOT NULL,
    probabilitas DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (penyakit_id) REFERENCES penyakit(id) ON DELETE CASCADE,
    FOREIGN KEY (gejala_id) REFERENCES gejala(id) ON DELETE CASCADE
);

-- Tabel Rules Decision Tree
CREATE TABLE IF NOT EXISTS decision_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(50) NOT NULL,
    conditions TEXT NOT NULL, -- JSON format untuk kondisi gejala
    penyakit_id INT NOT NULL,
    priority INT DEFAULT 1,
    FOREIGN KEY (penyakit_id) REFERENCES penyakit(id) ON DELETE CASCADE
);

-- Tabel Pencatatan Diagnosa
CREATE TABLE IF NOT EXISTS pencatatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_tanaman VARCHAR(100),
    lokasi VARCHAR(100),
    tanggal_pencatatan DATE NOT NULL,
    gejala_dipilih TEXT, -- JSON array gejala yang dipilih
    metode_diagnosa VARCHAR(20), -- 'naive_bayes' atau 'decision_tree'
    hasil_penyakit_id INT,
    tingkat_kepercayaan DECIMAL(5,2),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hasil_penyakit_id) REFERENCES penyakit(id) ON DELETE SET NULL
);

-- Insert Data Admin Default
INSERT INTO users (username, password, nama_lengkap, email) VALUES
('admin', MD5('admin123'), 'Administrator', 'admin@anggrek.com'),
('petani1', MD5('petani123'), 'Budi Santoso', 'budi@email.com');

-- Insert Data Penyakit Anggrek
INSERT INTO penyakit (kode_penyakit, nama_penyakit, deskripsi, solusi) VALUES
('P01', 'Busuk Akar', 'Penyakit yang disebabkan oleh jamur Pythium atau Phytophthora yang menyerang akar tanaman anggrek', 'Kurangi penyiraman, gunakan fungisida, potong akar yang busuk, ganti media tanam'),
('P02', 'Bercak Daun Bakteri', 'Infeksi bakteri Pseudomonas yang menyebabkan bercak coklat kehitaman pada daun', 'Isolasi tanaman, buang daun terinfeksi, semprotkan bakterisida, tingkatkan sirkulasi udara'),
('P03', 'Virus Mosaik', 'Infeksi virus yang menyebabkan pola belang-belang pada daun dan bunga', 'Tidak ada obat, isolasi dan musnahkan tanaman terinfeksi untuk mencegah penyebaran'),
('P04', 'Jamur Jelaga (Sooty Mold)', 'Jamur hitam yang tumbuh pada permukaan daun akibat embun madu dari kutu', 'Basmi hama penghasil embun madu, lap daun dengan air sabun, tingkatkan kebersihan'),
('P05', 'Antraknosa', 'Penyakit jamur Colletotrichum yang menyebabkan bercak hitam cekung pada daun', 'Potong bagian terinfeksi, aplikasikan fungisida, kurangi kelembaban berlebih'),
('P06', 'Busuk Lunak Bakteri', 'Infeksi bakteri Erwinia yang menyebabkan pembusukan cepat pada jaringan tanaman', 'Isolasi segera, potong bagian busuk, aplikasikan bakterisida, jaga kebersihan alat potong');

-- Insert Data Gejala
INSERT INTO gejala (kode_gejala, nama_gejala) VALUES
('G01', 'Akar berwarna coklat atau hitam'),
('G02', 'Akar terasa lunak dan berlendir'),
('G03', 'Daun menguning dan layu'),
('G04', 'Pertumbuhan tanaman terhambat'),
('G05', 'Bercak coklat kehitaman pada daun'),
('G06', 'Bercak basah dan berbau busuk'),
('G07', 'Daun berlubang atau rontok'),
('G08', 'Pola belang-belang kuning pada daun'),
('G09', 'Bunga mengalami perubahan warna abnormal'),
('G10', 'Pertumbuhan terdistorsi atau kerdil'),
('G11', 'Lapisan hitam seperti jelaga pada daun'),
('G12', 'Daun lengket karena embun madu'),
('G13', 'Adanya kutu atau serangga kecil'),
('G14', 'Bercak hitam cekung pada daun'),
('G15', 'Bagian tengah bercak berwarna abu-abu'),
('G16', 'Daun mengering dan rapuh'),
('G17', 'Pseudobulb atau batang lunak dan membusuk'),
('G18', 'Bau busuk yang menyengat'),
('G19', 'Pembusukan menyebar dengan cepat'),
('G20', 'Media tanam terlalu lembab atau basah');

-- Insert Relasi Penyakit-Gejala dengan Probabilitas (untuk Naive Bayes)
-- P01: Busuk Akar
INSERT INTO penyakit_gejala (penyakit_id, gejala_id, probabilitas) VALUES
(1, 1, 0.95), -- Akar coklat/hitam
(1, 2, 0.90), -- Akar lunak berlendir
(1, 3, 0.75), -- Daun menguning
(1, 4, 0.70), -- Pertumbuhan terhambat
(1, 20, 0.80); -- Media terlalu lembab

-- P02: Bercak Daun Bakteri
INSERT INTO penyakit_gejala (penyakit_id, gejala_id, probabilitas) VALUES
(2, 5, 0.90), -- Bercak coklat kehitaman
(2, 6, 0.85), -- Bercak basah berbau
(2, 7, 0.70), -- Daun berlubang
(2, 3, 0.60), -- Daun menguning
(2, 20, 0.75); -- Media lembab

-- P03: Virus Mosaik
INSERT INTO penyakit_gejala (penyakit_id, gejala_id, probabilitas) VALUES
(3, 8, 0.95), -- Pola belang kuning
(3, 9, 0.80), -- Bunga berubah warna
(3, 10, 0.85), -- Pertumbuhan terdistorsi
(3, 4, 0.70), -- Pertumbuhan terhambat
(3, 16, 0.60); -- Daun mengering

-- P04: Jamur Jelaga
INSERT INTO penyakit_gejala (penyakit_id, gejala_id, probabilitas) VALUES
(4, 11, 0.95), -- Lapisan hitam jelaga
(4, 12, 0.90), -- Daun lengket
(4, 13, 0.85), -- Ada kutu/serangga
(4, 4, 0.50), -- Pertumbuhan terhambat
(4, 3, 0.40); -- Daun menguning

-- P05: Antraknosa
INSERT INTO penyakit_gejala (penyakit_id, gejala_id, probabilitas) VALUES
(5, 14, 0.95), -- Bercak hitam cekung
(5, 15, 0.80), -- Tengah abu-abu
(5, 16, 0.75), -- Daun mengering
(5, 7, 0.65), -- Daun rontok
(5, 20, 0.70); -- Media lembab

-- P06: Busuk Lunak Bakteri
INSERT INTO penyakit_gejala (penyakit_id, gejala_id, probabilitas) VALUES
(6, 17, 0.95), -- Pseudobulb busuk
(6, 18, 0.90), -- Bau busuk menyengat
(6, 19, 0.85), -- Pembusukan cepat
(6, 6, 0.80), -- Bercak basah berbau
(6, 20, 0.75); -- Media lembab

-- Insert Decision Tree Rules
-- Rule format: JSON array of gejala codes that must be present
INSERT INTO decision_rules (rule_name, conditions, penyakit_id, priority) VALUES
('Rule_BusukAkar_1', '["G01", "G02", "G20"]', 1, 1),
('Rule_BusukAkar_2', '["G01", "G03", "G04"]', 1, 2),
('Rule_BercakBakteri_1', '["G05", "G06"]', 2, 1),
('Rule_BercakBakteri_2', '["G05", "G07", "G20"]', 2, 2),
('Rule_VirusMosaik_1', '["G08", "G09", "G10"]', 3, 1),
('Rule_VirusMosaik_2', '["G08", "G10"]', 3, 2),
('Rule_JamurJelaga_1', '["G11", "G12", "G13"]', 4, 1),
('Rule_JamurJelaga_2', '["G11", "G13"]', 4, 2),
('Rule_Antraknosa_1', '["G14", "G15", "G16"]', 5, 1),
('Rule_Antraknosa_2', '["G14", "G16"]', 5, 2),
('Rule_BusukLunak_1', '["G17", "G18", "G19"]', 6, 1),
('Rule_BusukLunak_2', '["G17", "G18"]', 6, 2);