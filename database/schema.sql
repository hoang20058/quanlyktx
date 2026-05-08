-- Clean schema for the 5-table version requested by the user
-- Tables: Student, Room, Contract, UtilityBill, Notice

CREATE DATABASE IF NOT EXISTS quanlyktx
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE quanlyktx;

-- Drop both new and legacy/plural table names to enforce the 5-table constraint
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Notice;
DROP TABLE IF EXISTS notices;
DROP TABLE IF EXISTS UtilityBill;
DROP TABLE IF EXISTS utilitybill;
DROP TABLE IF EXISTS Contract;
DROP TABLE IF EXISTS contract;
DROP TABLE IF EXISTS contracts;
DROP TABLE IF EXISTS Room;
DROP TABLE IF EXISTS room;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS room_changes;
DROP TABLE IF EXISTS Student;
DROP TABLE IF EXISTS student;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS bills;
DROP TABLE IF EXISTS bill;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Student table: stores applicants and resident students
CREATE TABLE Student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    student_code VARCHAR(20) NULL UNIQUE,
    dob DATE NULL,
    phone VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    department VARCHAR(100) NULL,
    status ENUM('Chờ duyệt','Đang ở','Đã chuyển đi') DEFAULT 'Chờ duyệt',
    priority_level TINYINT DEFAULT 8,
    boarding_score INT DEFAULT 100
);
-- Schema for simplified Dormitory Management (5 tables only)
CREATE DATABASE IF NOT EXISTS quanlyktx
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE quanlyktx;

-- Drop existing tables (safe for development)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Notice;
DROP TABLE IF EXISTS UtilityBill;
DROP TABLE IF EXISTS Contract;
DROP TABLE IF EXISTS Room;
DROP TABLE IF EXISTS Student;
SET FOREIGN_KEY_CHECKS = 1;

-- 1) Student table
-- Stores applicant and resident data. 'student_code' can be NULL for public registrations.
CREATE TABLE Student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    student_code VARCHAR(20) NULL UNIQUE,
    dob DATE NULL,
    phone VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    department VARCHAR(100) NULL,
    status ENUM('Chờ duyệt','Đang ở','Đã chuyển đi') DEFAULT 'Chờ duyệt',
    priority_level TINYINT DEFAULT 8,
    boarding_score INT DEFAULT 100
);

-- 2) Room table
-- room_number is unique identifier for rooms; price stored as decimal.
CREATE TABLE Room (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number INT NOT NULL UNIQUE,
    floor_number INT NOT NULL,
    capacity INT NOT NULL,
    room_type ENUM('Dịch vụ','Thường') DEFAULT 'Thường',
    status ENUM('Hoạt động','Đang sửa chữa') DEFAULT 'Hoạt động',
    price DECIMAL(10,2) DEFAULT 0
);

-- 3) Contract table
-- Represents an assignment of a student to a room.
-- price = calculated room fee for duration (room.price × months) - discount applied
-- deposit = amount student has already paid towards room fee
-- discount_percent = discount percentage applied to price (e.g., 50 for 50% off)
CREATE TABLE Contract (
    contract_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    price DECIMAL(12,2) DEFAULT 0,
    deposit DECIMAL(12,2) DEFAULT 0,
    discount_percent INT DEFAULT 0,
    status ENUM('Đang ở','Đã chuyển ra','Đã hủy') DEFAULT 'Đang ở',
    FOREIGN KEY (student_id) REFERENCES Student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Room(room_id) ON DELETE CASCADE
);

-- 4) UtilityBill table
-- Stores monthly bill totals per room.
CREATE TABLE UtilityBill (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    billing_month TINYINT,
    billing_year INT,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('Chưa thanh toán','Đã thanh toán') DEFAULT 'Chưa thanh toán',
    FOREIGN KEY (room_id) REFERENCES Room(room_id) ON DELETE CASCADE
);

-- 5) Notice table
-- Notices may target building/room/individual and optionally change points.
CREATE TABLE Notice (
    notice_id INT AUTO_INCREMENT PRIMARY KEY,
    target_type ENUM('Cả tòa','Phòng','Cá nhân') NOT NULL,
    category ENUM('Thông báo chung','Khen thưởng','Kỷ luật') DEFAULT 'Thông báo chung',
    point_change INT DEFAULT 0,
    room_id INT NULL,
    student_id INT NULL,
    description TEXT,
    date DATE NOT NULL,
    FOREIGN KEY (room_id) REFERENCES Room(room_id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES Student(student_id) ON DELETE SET NULL
);

-- End of schema

