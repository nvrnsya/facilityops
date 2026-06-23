-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sept 25 , 2025 at 10:05 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- SET DEFAULT CHARACTER SET AND COLLATION
-- ========================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table users
-- --------------------------------------------------------
CREATE TABLE users (
  users_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  icnum VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  staffid VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  email VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  phone_num VARCHAR(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  depart VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  role ENUM('admin','staff','administration') COLLATE utf8mb4_unicode_ci NOT NULL,
  remember_token VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table admin_list
-- --------------------------------------------------------
CREATE TABLE admin_list (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staffid VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_list (staffid) VALUES 
('001'),
('003'),
('004');


-- --------------------------------------------------------
-- Table structure for table ULPL
-- --------------------------------------------------------
CREATE TABLE ulpl (
  ulpl_id INT AUTO_INCREMENT PRIMARY KEY,
  users_id INT NOT NULL,
  facilityName VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  programe_name VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  time_req TIME NOT NULL,
  depart VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ext_office VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  add_notes VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  select_date DATE NOT NULL,
  return_date DATE NOT NULL,
  status ENUM('Pending', 'Approved', 'Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  FOREIGN KEY (users_id) REFERENCES users(users_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table `keys` (FIXED with backticks)
-- --------------------------------------------------------
CREATE TABLE `keys` (
  keys_id INT AUTO_INCREMENT PRIMARY KEY,
  ulpl_id INT NOT NULL,
  users_id INT NOT NULL,
  recipient_name VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  depart_key VARCHAR(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  tel_num VARCHAR(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  staff_key VARCHAR(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  key_collect DATE NOT NULL,
  key_delivery DATE NOT NULL,
  FOREIGN KEY (ulpl_id) REFERENCES ulpl(ulpl_id) ON DELETE CASCADE,
  FOREIGN KEY (users_id) REFERENCES users(users_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table administration
-- --------------------------------------------------------
CREATE TABLE administration (
  administration_id INT AUTO_INCREMENT PRIMARY KEY,
  keys_id INT NULL,
  ulpl_id INT NULL,
  users_id INT NOT NULL,
  event_name VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  select_date_dd DATE NOT NULL,
  return_date_dd DATE NOT NULL,
  number_of_members INT NOT NULL,
  exam_table INT NOT NULL,
  banquet_chair INT NOT NULL,
  couch INT NOT NULL,
  rostrum INT NOT NULL,
  notes VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  status ENUM('Pending', 'Approved', 'Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  FOREIGN KEY (users_id) REFERENCES users(users_id),
  FOREIGN KEY (keys_id) REFERENCES `keys`(keys_id) ON DELETE SET NULL,
  FOREIGN KEY (ulpl_id) REFERENCES ulpl(ulpl_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table facilities
-- --------------------------------------------------------
CREATE TABLE facilities (
  facility_id INT AUTO_INCREMENT PRIMARY KEY,
  facility_name VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  facility_slug VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  description TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  image_path VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO facilities 
(facility_id, facility_name, facility_slug, description, image_path, is_active) 
VALUES
(1, 'DEWAN DAGANG', 'dewan-dagang', 'Dewan Dagang untuk pelbagai acara', 'dewandagang.jpg', 1),
(2, 'DEWAN KULIAH UTAMA', 'dewan-kuliah-utama', 'Dewan besar sesuai untuk majlis rasmi dan kuliah utama', 'dewankuliahutama.jpg', 1),
(3, 'BILIK MAKAN BAUK INN', 'bilik-makan-bauk-inn', 'Bilik makan untuk jamuan rasmi dan santai di Bauk Inn', 'bilikmakanbaukinn.jpg', 1),
(4, 'BILIK SEMINAR', 'bilik-seminar', 'Ruang seminar sesuai untuk kursus, bengkel, dan latihan', 'bilikseminar.jpg', 1),
(5, 'BILIK KULIAH 2', 'bilik-kuliah-2', 'Bilik kuliah yang selesa untuk sesi pengajaran dan pembelajaran', 'bilikkuliah.jpg', 1),
(6, 'PUSPANITA', 'puspanita', 'Dewan Puspanita sesuai untuk mesyuarat dan program komuniti', 'puspanita.jpg', 1);


-- --------------------------------------------------------
-- Table structure for table announcements
-- --------------------------------------------------------
CREATE TABLE announcements (
  announcement_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  content TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('active', 'inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  announcement_type ENUM('ulpl', 'administration') COLLATE utf8mb4_unicode_ci DEFAULT 'ulpl',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO announcements 
(title, content, start_date, end_date, status, announcement_type) 
VALUES
('Bilik Kuliah 2 Upgrade', 'Bilik Kuliah 2 equipment upgrade scheduled for 5 Nov 2025.', '2025-11-05', '2025-11-05', 'active', 'ulpl'),
('Bilik Makan Bauk Inn Closed', 'Bilik Makan Bauk Inn closed for deep cleaning on 10 Nov 2025.', '2025-11-10', '2025-11-10', 'active', 'ulpl'),
('Dewan Dagang New Year Closure', 'Dewan Dagang will be closed for New Year celebration from 28 Dec 2025 - 1 Jan 2026.', '2025-12-28', '2026-01-01', 'active', 'administration'),
('Dewan Dagang Sound System Upgrade', 'Sound system upgrade at Dewan Dagang on 15 Nov 2025, 8am - 12pm.', '2025-11-15', '2025-11-15', 'active', 'administration'),
('Dewan Dagang AC Maintenance', 'Air conditioning maintenance at Dewan Dagang on 5 Dec 2025.', '2025-12-05', '2025-12-05', 'active', 'administration');


-- --------------------------------------------------------
-- Table structure for table facility_gallery
-- --------------------------------------------------------
CREATE TABLE facility_gallery (
  gallery_id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  image_path VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (facility_id) REFERENCES facilities(facility_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO facility_gallery (facility_id, image_path) VALUES
(1, 'dewan-dagang/DD1.jpeg'),
(1, 'dewan-dagang/DD2.jpeg'),
(1, 'dewan-dagang/DD3.jpeg'),
(1, 'dewan-dagang/DD4.jpeg'),
(1, 'dewan-dagang/DD5.jpeg'),

(2, 'dewan-kuliah-utama/DKU1.jpg'),
(2, 'dewan-kuliah-utama/DKU2.jpg'),
(2, 'dewan-kuliah-utama/DKU3.jpg'),
(2, 'dewan-kuliah-utama/DKU4.jpg'),
(2, 'dewan-kuliah-utama/DKU5.jpg'),
(2, 'dewan-kuliah-utama/DKU7.jpg'),
(2, 'dewan-kuliah-utama/DKU8.jpg'),
(2, 'dewan-kuliah-utama/DKU9.jpg'),

(3, 'bilik-makan-bauk-inn/BMBI1.jpg'),
(3, 'bilik-makan-bauk-inn/BMBI2.jpg'),
(3, 'bilik-makan-bauk-inn/BMBI3.jpg'),
(3, 'bilik-makan-bauk-inn/BMBI4.jpg'),

(4, 'bilik-seminar/BS1.jpg'),
(4, 'bilik-seminar/BS2.jpg'),
(4, 'bilik-seminar/BS3.jpg'),
(4, 'bilik-seminar/BS4.jpg'),

(5, 'bilik-kuliah-2/BK1.jpg'),
(5, 'bilik-kuliah-2/BK2.jpg'),
(5, 'bilik-kuliah-2/BK3.jpg'),
(5, 'bilik-kuliah-2/BK4.jpg'),

(6, 'puspanita/PP1.jpg'),
(6, 'puspanita/PP2.jpg'),
(6, 'puspanita/PP3.jpg'),
(6, 'puspanita/PP4.jpg'),
(6, 'puspanita/PP5.jpg'),
(6, 'puspanita/PP6.jpg'),
(6, 'puspanita/PP7.jpg'),
(6, 'puspanita/PP8.jpg');

-- --------------------------------------------------------
-- Table structure for table calendar_dates (ROLE-BASED CALENDAR)
-- --------------------------------------------------------
CREATE TABLE calendar_dates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  status ENUM('available', 'holiday', 'unavailable') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  facility_type ENUM('ulpl', 'dewan_dagang') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ulpl',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_date_facility (date, facility_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table faqs
-- --------------------------------------------------------

CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    answer TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO faqs (question, answer, display_order, is_active) VALUES
('How do I make a booking?', 'Go to the Booking page, choose your facility, select a date on the calendar, and fill in the booking form. You will receive a confirmation email once submitted.', 1, 1),
('Can I change or cancel my booking?', 'Yes. Reply to your confirmation email or contact our support at least 24 hours before your scheduled time to modify or cancel.', 2, 1),
('What do the calendar colors mean?', 'Green dates are available, yellow indicates holidays or maintenance periods, and red dates are fully booked/unavailable.', 3, 1),
('Is there a booking fee?', 'No, booking through FacilityOps is free. Any usage fees are collected directly by the facility on the day of use.', 4, 1),
('Do I need an account to book?', 'No account is required for a one-time booking, but registering makes it easier to track and manage your reservations.', 5, 1),
('How will I know if my booking is approved?', 'You will get an email confirmation immediately. If the facility requires approval, a follow-up email will confirm final acceptance.', 6, 1);

-- ========================================
-- DUMMY DATA FOR TESTING PURPOSES
-- ========================================

-- ========================================
-- FIXED: Additional dummy data for users
-- ========================================
INSERT INTO users (users_id, name, icnum, staffid, email, phone_num, depart, role) 
VALUES 
('1', 'NUREEN SYAFINAZ','051205010828','001','nureen14syafinaz@gmail.com','01139633857','JTMK','admin'),
('2', 'ALI BIN ABU','990101015555','002','aliabu@gmail.com','0123456789','JTMK','staff'),
('3', 'MUAZ HILMAN','050219110235','003','hilmanmuaz@gmail.com','01135704395','JTMK','administration'),
('4', 'MUHAMMAD DINIE HAKIM','050728100369','004','hakimdinie022@gmail.com','01111577404','JTMK','admin'),
('5', 'SITI AMINAH BINTI HASSAN','920315045678','005','sitiaminah@gmail.com','0123456780','JTMK','staff'),
('6', 'AHMAD FAUZI BIN RAHMAN','880622031234','006','ahmadfauzi@gmail.com','0134567890','JKM','staff'),
('7', 'NOR AZLINA BINTI MOHD','950810025555','007','norazlina@gmail.com','0145678901','JKE','staff'),
('8', 'MOHD RIZAL BIN ABDULLAH','910405017777','008','mohdrizal@gmail.com','0156789012','JKA','staff'),
('9', 'FATIMAH BINTI ZAINAL','930920048888','009','fatimahmz@gmail.com','0167890123','JKE','staff'),
('10', 'KHAIRUL ANWAR BIN ISMAIL','890712039999','010','khairulanwar@gmail.com','0178901234','JKA','staff'),
('11', 'NORFADZILAH BINTI YUSOF','940505026666','011','norfadzilah@gmail.com','0189012345','JKM','staff');



-- ========================================
-- FIXED: Additional dummy data for ulpl
-- ========================================
INSERT INTO ulpl 
(ulpl_id, users_id, facilityName, programe_name, time_req, depart, ext_office, add_notes, select_date, return_date, status) 
VALUES
(1, 2, 'Dewan Kuliah Utama', 'Kursus Latihan', '14:00:00', 'JTMK', '102', 'Perlu whiteboard', '2025-10-20', '2025-10-21', 'Pending'),
(2, 5, 'Bilik Seminar', 'Workshop Inovasi', '10:00:00', 'JTMK', '103', 'Perlu sound system', '2025-10-25', '2025-10-25', 'Pending'),
(3, 6, 'Bilik Kuliah 2', 'Kelas Tambahan', '15:00:00', 'JTKM', '104', 'Perlu laptop', '2025-11-01', '2025-11-01', 'Approved'),
(4, 7, 'Puspanita', 'Majlis Anugerah', '18:00:00', 'JTMK', '105', 'Perlu meja VIP', '2025-11-10', '2025-11-10', 'Pending'),
(5, 9, 'Dewan Kuliah Utama', 'Konvokesyen', '08:00:00', 'JTMK', '107', 'Setup khas', '2025-12-01', '2025-12-02', 'Approved'),
(6, 10, 'Bilik Makan Bauk Inn', 'Jamuan Akhir Tahun', '19:00:00', 'JTKM', '108', 'Perlu katering', '2025-12-15', '2025-12-15', 'Pending'),
(7, 11, 'Bilik Seminar', 'Mesyuarat Agung', '14:30:00', 'JTMK', '109', 'Perlu flipchart', '2025-11-20', '2025-11-20', 'Rejected'),
(8, 2, 'Bilik Kuliah 2', 'Tutorial Khas', '13:00:00', 'JTMK', '111', 'Perlu whiteboard besar', '2025-12-05', '2025-12-05', 'Pending'),
(9, 3, 'Puspanita', 'Program Komuniti', '16:00:00', 'JTKM', '112', 'Perlu kerusi tambahan', '2025-12-10', '2025-12-10', 'Approved'),
(10, 4, 'Bilik Seminar', 'Latihan ICT', '09:00:00', 'JTMK', '113', 'Perlu WiFi kuat', '2025-12-12', '2025-12-12', 'Rejected'),
(11, 5, 'Bilik Kuliah 2', 'Kelas Pemulihan', '14:00:00', 'JTMK', '115', 'Perlu papan putih', '2025-12-14', '2025-12-14', 'Pending'),
(12, 6, 'Puspanita', 'Majlis Penutup', '15:30:00', 'JTKM', '116', 'Perlu meja besar', '2025-12-15', '2025-12-15', 'Approved'),
(13, 7, 'Dewan Kuliah Utama', 'Seminar Motivasi', '09:30:00', 'JTMK', '117', 'Setup pentas', '2025-12-16', '2025-12-16', 'Pending'),
(14, 8, 'Bilik Makan Bauk Inn', 'Jamuan Hari Raya', '19:00:00', 'JTKM', '118', 'Perlu makanan halal', '2025-12-17', '2025-12-17', 'Rejected'),
(15, 9, 'Bilik Seminar', 'Kursus Kepimpinan', '08:30:00', 'JTMK', '119', 'Perlu projektor', '2025-12-18', '2025-12-18', 'Pending'),
(16, 10, 'Bilik Kuliah 2', 'Tutorial Tambahan', '10:00:00', 'JTMK', '121', 'Perlu laptop tambahan', '2025-12-20', '2025-12-20', 'Pending'),
(17, 11, 'Puspanita', 'Majlis Perpisahan', '18:00:00', 'JTKM', '122', 'Perlu kerusi banquet', '2025-12-21', '2025-12-21', 'Rejected'),
(18, 1, 'Dewan Kuliah Utama', 'Hari Anugerah', '09:00:00', 'JTMK', '123', 'Perlu hiasan pentas', '2025-12-22', '2025-12-22', 'Pending'),
(19, 2, 'Bilik Seminar', 'Pembentangan Projek', '14:30:00', 'JTKM', '124', 'Perlu screen besar', '2025-12-23', '2025-12-23', 'Rejected'),
(20, 3, 'Bilik Makan Bauk Inn', 'Makan Malam Akhir Tahun', '19:00:00', 'JTMK', '125', 'Perlu dekorasi', '2025-12-24', '2025-12-24', 'Pending'),
(21, 4, 'Puspanita', 'Forum Kerjaya', '10:00:00', 'JTKM', '126', 'Perlu panel tetamu', '2025-12-25', '2025-12-25', 'Pending'),
(22, 5, 'Bilik Kuliah 2', 'Tutorial Math', '14:00:00', 'JTMK', '202', 'Perlu marker', '2025-10-08', '2025-10-08', 'Pending');




-- ========================================
-- FIXED: Additional dummy data for `keys` (FIXED with backticks)
-- ========================================
INSERT INTO `keys` 
(keys_id, ulpl_id, users_id, recipient_name, depart_key, tel_num, staff_key, key_collect, key_delivery) 
VALUES
(1, 1, 2, 'ALI BIN ABU', 'JTMK', '0123456789', '002', '2025-10-20', '2025-10-21'),
(2, 2, 5, 'SITI AMINAH BINTI HASSAN', 'JTMK', '0123456780', '005', '2025-10-25', '2025-10-25'),
(3, 3, 6, 'AHMAD FAUZI BIN RAHMAN', 'JTKM', '0134567890', '006', '2025-11-01', '2025-11-01'),
(4, 4, 7, 'NOR AZLINA BINTI MOHD', 'JTMK', '0145678901', '007', '2025-11-10', '2025-11-10'),
(5, 5, 9, 'FATIMAH BINTI ZAINAL', 'JTMK', '0167890123', '009', '2025-12-01', '2025-12-02'),
(6, 6, 10, 'KHAIRUL ANWAR BIN ISMAIL', 'JTKM', '0178901234', '010', '2025-12-15', '2025-12-15'),
(7, 7, 11, 'NORFADZILAH BINTI YUSOF', 'JTMK', '0189012345', '011', '2025-11-20', '2025-11-20'),
(8, 8, 2, 'ALI BIN ABU', 'JTMK', '0123456789', '002', '2025-12-05', '2025-12-05'),
(9, 9, 3, 'MUAZ HILMAN', 'JTKM', '01135704395', '003', '2025-12-10', '2025-12-10'),
(10, 10, 4, 'MUHAMMAD DINIE HAKIM', 'JTMK', '01111577404', '004', '2025-12-12', '2025-12-12'),
(11, 11, 5, 'SITI AMINAH BINTI HASSAN', 'JTMK', '0123456780', '005', '2025-12-14', '2025-12-14'),
(12, 12, 6, 'AHMAD FAUZI BIN RAHMAN', 'JTKM', '0134567890', '006', '2025-12-15', '2025-12-15'),
(13, 13, 7, 'NOR AZLINA BINTI MOHD', 'JTMK', '0145678901', '007', '2025-12-16', '2025-12-16'),
(14, 14, 8, 'MOHD RIZAL BIN ABDULLAH', 'JTKM', '0156789012', '008', '2025-12-17', '2025-12-17'),
(15, 15, 9, 'FATIMAH BINTI ZAINAL', 'JTMK', '0167890123', '009', '2025-12-18', '2025-12-18'),
(16, 16, 10, 'KHAIRUL ANWAR BIN ISMAIL', 'JTMK', '0178901234', '010', '2025-12-20', '2025-12-20'),
(17, 17, 11, 'NORFADZILAH BINTI YUSOF', 'JTKM', '0189012345', '011', '2025-12-21', '2025-12-21'),
(18, 18, 1, 'NUREEN SYAFINAZ', 'JTMK', '01139633857', '001', '2025-12-22', '2025-12-22'),
(19, 19, 2, 'ALI BIN ABU', 'JTKM', '0123456789', '002', '2025-12-23', '2025-12-23'),
(20, 20, 3, 'MUAZ HILMAN', 'JTMK', '01135704395', '003', '2025-12-24', '2025-12-24'),
(21, 21, 4, 'MUHAMMAD DINIE HAKIM', 'JTKM', '01111577404', '004', '2025-12-25', '2025-12-25'),
(22, 22, 5, 'SITI AMINAH BINTI HASSAN', 'JTMK', '0123456780', '005', '2025-10-08', '2025-10-08');


-- ========================================
-- FIXED: Additional dummy data for administration
-- ========================================
INSERT INTO administration 
(administration_id, users_id, event_name, start_time, end_time, select_date_dd, return_date_dd, number_of_members, exam_table, banquet_chair, couch, rostrum, notes, status) 
VALUES
(1, 1, 'Mesyuarat Pengurusan', '09:00:00', '12:00:00', '2025-10-15', '2025-10-16', 20, 5, 30, 2, 1, 'Perlu setup awal pagi', 'Pending'),
(2, 2, 'Bengkel ICT', '14:00:00', '17:00:00', '2025-10-20', '2025-10-21', 50, 10, 60, 0, 1, 'Pastikan internet stabil', 'Pending'),
(3, 3, 'Acara Kebudayaan', '10:00:00', '15:00:00', '2025-11-05', '2025-11-06', 100, 0, 120, 5, 2, 'Perlu sound system tambahan', 'Approved'),
(4, 4, 'Kursus Latihan', '14:00:00', '17:00:00', '2025-10-22', '2025-10-23', 30, 10, 40, 1, 1, 'Perlu whiteboard dan projector', 'Pending'),
(5, 5, 'Workshop Teknologi', '10:00:00', '13:00:00', '2025-10-25', '2025-10-25', 30, 8, 35, 0, 1, 'Perlu LCD projector', 'Rejected'),
(6, 6, 'Majlis Anugerah', '18:00:00', '22:00:00', '2025-11-10', '2025-11-10', 150, 0, 200, 10, 3, 'Perlu meja VIP dan dekorasi khas', 'Approved'),
(7, 7, 'Majlis Apresiasi', '18:00:00', '21:00:00', '2025-11-12', '2025-11-12', 80, 0, 90, 4, 2, 'Perlu stage dan lighting', 'Pending'),
(8, 8, 'Seminar Kewangan', '09:30:00', '12:30:00', '2025-11-15', '2025-11-15', 50, 15, 60, 2, 1, 'Perlu mic wireless dan projector', 'Pending'),
(9, 9, 'Jamuan Akhir Tahun', '19:00:00', '23:00:00', '2025-12-15', '2025-12-15', 200, 0, 250, 15, 4, 'Perlu katering dan hiburan live band', 'Pending'),
(10, 10, 'Kelas Tutorial', '13:00:00', '16:00:00', '2025-12-05', '2025-12-05', 20, 10, 25, 0, 0, 'Setup U-shape', 'Rejected'),
(11, 11, 'Forum Pendidikan', '10:30:00', '13:30:00', '2025-11-25', '2025-11-25', 40, 0, 50, 3, 2, 'Setup panel discussion', 'Approved'),
(12, 1, 'Persidangan Tahunan', '09:00:00', '17:00:00', '2025-11-28', '2025-11-29', 120, 25, 140, 6, 2, 'Full day event dengan makan tengahari', 'Pending'),
(13, 2, 'Majlis Penutup', '15:30:00', '18:00:00', '2025-12-17', '2025-12-17', 60, 10, 60, 3, 1, 'Perlu setup pentas', 'Approved'),
(14, 3, 'Taklimat Keselamatan', '11:00:00', '13:00:00', '2025-12-13', '2025-12-13', 40, 8, 50, 1, 1, 'Setup mic', 'Pending'),
(15, 4, 'Seminar Motivasi', '09:30:00', '12:00:00', '2025-12-16', '2025-12-16', 80, 10, 100, 2, 1, 'Tambah lighting', 'Rejected'),
(16, 5, 'Kursus Kepimpinan', '08:30:00', '11:30:00', '2025-12-18', '2025-12-18', 35, 7, 40, 0, 1, 'LCD dan mic', 'Pending'),
(17, 6, 'Tutorial Tambahan', '10:00:00', '12:00:00', '2025-12-20', '2025-12-20', 20, 5, 25, 0, 0, 'Setup kelas', 'Pending'),
(18, 7, 'Forum Kerjaya', '10:00:00', '13:00:00', '2025-12-25', '2025-12-25', 45, 5, 50, 1, 1, 'Panel diskusi', 'Rejected'),
(19, 8, 'Latihan ICT', '09:00:00', '12:00:00', '2025-11-20', '2025-11-20', 25, 5, 25, 0, 1, 'Perlu WiFi', 'Pending'),
(20, 9, 'Pameran Inovasi', '09:30:00', '15:30:00', '2025-11-12', '2025-11-12', 100, 20, 0, 0, 2, 'Setup booth', 'Rejected'),
(21, 10, 'Program Tanggungjawab Sosial', '10:00:00', '14:00:00', '2025-11-18', '2025-11-18', 60, 0, 70, 5, 1, 'Aktiviti outdoor', 'Pending'),
(22, 11, 'Breakfast with CEO', '07:30:00', '09:30:00', '2025-11-26', '2025-11-26', 20, 4, 25, 2, 0, 'Early morning setup', 'Approved');

COMMIT;