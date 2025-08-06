-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2024 at 05:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `records`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `link_existing_sections` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_section_id INT;
    DECLARE v_section_code VARCHAR(20);
    DECLARE v_degree_id INT;
    
    -- Cursor for existing sections
    DECLARE cur CURSOR FOR 
        SELECT section_id, section_code 
        FROM sections;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_section_id, v_section_code;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Find matching degree
        SELECT degree_id INTO v_degree_id
        FROM degrees 
        WHERE degree_code = SUBSTRING_INDEX(v_section_code, ' ', 1);
        
        -- Insert if match found and not exists
        IF v_degree_id IS NOT NULL THEN
            INSERT IGNORE INTO degrees_sections (degree_id, section_id, semester)
            VALUES (v_degree_id, v_section_id, '1');
        END IF;
    END LOOP;
    
    CLOSE cur;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `ExtractYearLevel` (`section_code` VARCHAR(20)) RETURNS TINYINT(4) DETERMINISTIC BEGIN
    DECLARE year_num TINYINT;
    SET year_num = CAST(SUBSTRING(
        section_code,
        LOCATE(' ', section_code) + 1,
        1
    ) AS SIGNED);
    RETURN COALESCE(year_num, 1);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'password', '2024-11-08 12:36:33', '2024-11-08 12:47:16'),
(2, 'admin2', 'password', '2024-11-20 16:18:58', '2024-11-20 16:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `degrees`
--

CREATE TABLE `degrees` (
  `degree_id` int(11) NOT NULL,
  `degree_code` varchar(20) NOT NULL,
  `degree_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `degrees`
--

INSERT INTO `degrees` (`degree_id`, `degree_code`, `degree_name`, `description`, `created_at`) VALUES
(1, 'BEED', 'Bachelor of Elementary Education', NULL, '2024-11-12 09:50:13'),
(2, 'BSIT', 'Bachelor of Science in Information Technology', NULL, '2024-11-12 09:50:13'),
(3, 'BSED', 'Bachelor of Secondary Education', NULL, '2024-11-12 09:50:13'),
(4, 'BSHM', 'Bachelor of Science in Hospitality Management', NULL, '2024-11-12 09:50:13');

-- --------------------------------------------------------

--
-- Table structure for table `degrees_sections`
--

CREATE TABLE `degrees_sections` (
  `degree_id` int(11) NOT NULL,
  `degree_code` varchar(20) DEFAULT NULL,
  `degree_name` varchar(100) DEFAULT NULL,
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) DEFAULT NULL,
  `section_code` varchar(20) DEFAULT NULL,
  `year_level` tinyint(4) DEFAULT NULL,
  `semester` enum('1','2') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `degrees_sections`
--

INSERT INTO `degrees_sections` (`degree_id`, `degree_code`, `degree_name`, `section_id`, `section_name`, `section_code`, `year_level`, `semester`, `created_at`, `updated_at`) VALUES
(1, 'BEED', 'Bachelor of Elementary Education', 29, 'Elementary Education 3A', 'BEED 3A', 3, '1', '2024-11-20 07:42:23', '2024-11-20 07:42:23'),
(2, 'BSIT', 'Bachelor of Science in Information Technology', 1, 'Information Technology 3A', 'BSIT 3A', 3, '1', '2024-11-18 06:12:51', '2024-11-18 06:12:51'),
(2, 'BSIT', 'Bachelor of Science in Information Technology', 25, 'Secondary Education 1A', 'BSIT 1A', 1, '1', '2024-11-20 07:31:47', '2024-11-20 07:31:47'),
(3, 'BSED', 'Bachelor of Secondary Education', 17, 'Secondary Education 1A', 'BSED 1A', 1, '1', '2024-11-19 20:29:16', '2024-11-19 20:29:16'),
(4, 'BSHM', 'Bachelor of Science in Hospitality Management', 2, 'Hospitality Management 3A', 'BSHM 3A', 3, '1', '2024-11-18 06:17:29', '2024-11-18 06:17:29');

--
-- Triggers `degrees_sections`
--
DELIMITER $$
CREATE TRIGGER `before_degrees_sections_insert` BEFORE INSERT ON `degrees_sections` FOR EACH ROW BEGIN
    DECLARE v_degree_code VARCHAR(20);
    DECLARE v_degree_name VARCHAR(100);
    DECLARE v_section_name VARCHAR(50);
    DECLARE v_section_code VARCHAR(20);
    DECLARE v_year_level TINYINT;
    
    SELECT degree_code, degree_name 
    INTO v_degree_code, v_degree_name
    FROM degrees 
    WHERE degree_id = NEW.degree_id;
    
    SELECT section_name, section_code, year_level 
    INTO v_section_name, v_section_code, v_year_level
    FROM sections 
    WHERE section_id = NEW.section_id;
    
    SET NEW.degree_code = v_degree_code;
    SET NEW.degree_name = v_degree_name;
    SET NEW.section_name = v_section_name;
    SET NEW.section_code = v_section_code;
    SET NEW.year_level = v_year_level;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `degrees_sections_view`
-- (See below for the actual view)
--
CREATE TABLE `degrees_sections_view` (
`degree_id` int(11)
,`degree_code` varchar(20)
,`degree_name` varchar(100)
,`section_id` int(11)
,`section_name` varchar(50)
,`section_code` varchar(20)
,`year_level` tinyint(4)
,`semester` enum('1','2')
);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `capacity`, `created_at`, `updated_at`) VALUES
(3, 'Computer Laboratory', 65, '2024-11-20 12:16:05', '2024-11-21 13:15:44'),
(5, 'Computer Laboratory 2', 65, '2024-11-20 15:47:26', '2024-11-20 15:47:26'),
(7, 'Speech Laboratory', 65, '2024-11-21 13:06:53', '2024-11-21 13:08:50'),
(9, '101', 65, '2024-11-22 04:17:20', '2024-11-22 04:17:20'),
(10, '102', 65, '2024-11-22 04:17:31', '2024-11-22 04:17:31');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `section_code` varchar(20) NOT NULL,
  `degree_id` int(11) DEFAULT NULL,
  `year_level` tinyint(4) GENERATED ALWAYS AS (cast(substr(`section_code`,locate(' ',`section_code`) + 1,1) as signed)) STORED,
  `max_students` int(11) DEFAULT 40,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`, `section_code`, `degree_id`, `max_students`, `created_at`, `updated_at`) VALUES
(1, 'Information Technology 3A', 'BSIT 3A', 2, 65, '2024-11-18 03:30:43', '2024-11-21 12:42:01'),
(2, 'Hospitality Management 3A', 'BSHM 3A', 4, 65, '2024-11-18 03:31:12', '2024-11-20 13:48:15'),
(17, 'Secondary Education 1A', 'BSED 1A', 3, 65, '2024-11-19 20:29:16', '2024-11-20 13:48:15'),
(25, 'Secondary Education 1A', 'BSIT 1A', 2, 65, '2024-11-20 07:31:47', '2024-11-20 13:48:15'),
(29, 'Elementary Education 3A', 'BEED 3A', 1, 65, '2024-11-20 07:42:23', '2024-11-20 13:40:34');

--
-- Triggers `sections`
--
DELIMITER $$
CREATE TRIGGER `after_section_insert` AFTER INSERT ON `sections` FOR EACH ROW BEGIN
    DECLARE v_degree_id INT;
    DECLARE v_degree_code VARCHAR(20);
    
    -- Extract degree code from section_code (get text before space)
    SET v_degree_code = SUBSTRING_INDEX(NEW.section_code, ' ', 1);
    
    -- Find matching degree
    SELECT degree_id INTO v_degree_id
    FROM degrees 
    WHERE degree_code = v_degree_code;
    
    -- If matching degree found, insert into degrees_sections
    IF v_degree_id IS NOT NULL THEN
        INSERT INTO degrees_sections (degree_id, section_id, semester) 
        VALUES (v_degree_id, NEW.section_id, '1');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `sections_advisors`
--

CREATE TABLE `sections_advisors` (
  `sa_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_code` varchar(20) DEFAULT NULL,
  `t_id` int(11) DEFAULT NULL,
  `t_lname` varchar(50) DEFAULT NULL,
  `t_fname` varchar(50) DEFAULT NULL,
  `t_mname` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections_advisors`
--

INSERT INTO `sections_advisors` (`sa_id`, `section_id`, `section_code`, `t_id`, `t_lname`, `t_fname`, `t_mname`, `created_at`, `updated_at`) VALUES
(22, 17, 'BSED 1A', 32, 'Amoin', 'Nicasio', NULL, '2024-11-20 19:39:09', '2024-11-20 19:39:09'),
(25, 1, 'BSIT 3A', 33, 'Alerta', 'Jeffrey', NULL, '2024-11-21 09:36:17', '2024-11-21 09:36:30');

--
-- Triggers `sections_advisors`
--
DELIMITER $$
CREATE TRIGGER `before_sections_advisors_insert` BEFORE INSERT ON `sections_advisors` FOR EACH ROW BEGIN
    DECLARE v_section_code VARCHAR(20);
    DECLARE v_lname VARCHAR(50);
    DECLARE v_fname VARCHAR(50);
    DECLARE v_mname VARCHAR(50);
    
    -- Get section code
    SELECT section_code INTO v_section_code
    FROM sections 
    WHERE section_id = NEW.section_id;
    
    -- Get teacher details
    SELECT t_lname, t_fname, t_mname 
    INTO v_lname, v_fname, v_mname
    FROM teachers 
    WHERE t_id = NEW.t_id;
    
    -- Set the values
    SET NEW.section_code = v_section_code;
    SET NEW.t_lname = v_lname;
    SET NEW.t_fname = v_fname;
    SET NEW.t_mname = v_mname;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `sections_schedules`
--

CREATE TABLE `sections_schedules` (
  `ss_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_code` varchar(20) DEFAULT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `teacher_name` varchar(150) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `semester` enum('First','Second','Summer') DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections_schedules`
--

INSERT INTO `sections_schedules` (`ss_id`, `section_id`, `section_code`, `subject_code`, `teacher_id`, `teacher_name`, `day_of_week`, `start_time`, `end_time`, `room_id`, `semester`, `academic_year`, `created_at`, `updated_at`) VALUES
(8, 25, NULL, 'OOP 2', 32, 'Amoin, Nicasio .', 'Monday', '16:05:00', '17:05:00', 5, NULL, NULL, '2024-11-20 18:06:03', '2024-11-20 18:15:42'),
(10, 25, NULL, 'PROG 2', 32, 'Amoin, Nicasio .', 'Friday', '08:37:00', '10:37:00', 3, NULL, NULL, '2024-11-20 19:38:05', '2024-11-20 19:38:05'),
(12, 29, NULL, 'NET 2', 33, 'Alerta, Jeffrey .', 'Friday', '05:23:00', '06:23:00', 7, NULL, NULL, '2024-11-21 19:23:25', '2024-11-21 19:23:25'),
(13, 1, NULL, 'NET 2', 33, 'Alerta, Jeffrey .', 'Friday', '07:39:00', '08:39:00', 7, NULL, NULL, '2024-11-21 20:39:57', '2024-11-22 03:04:23'),
(14, 1, NULL, 'OOP 2', 32, 'Amoin, Nicasio .', 'Tuesday', '10:00:00', '00:00:00', 5, NULL, NULL, '2024-11-22 00:00:40', '2024-11-22 00:00:40'),
(16, 1, NULL, 'OS', 32, 'Amoin, Nicasio .', 'Monday', '13:31:00', '14:31:00', 3, NULL, NULL, '2024-11-22 02:31:45', '2024-11-22 03:04:17'),
(17, 1, NULL, 'OOP 2', 32, 'Amoin, Nicasio .', 'Friday', '08:40:00', '09:39:00', 3, NULL, NULL, '2024-11-22 03:05:50', '2024-11-22 03:05:50');

-- --------------------------------------------------------

--
-- Table structure for table `sections_schedules_backup`
--

CREATE TABLE `sections_schedules_backup` (
  `ss_id` int(11) NOT NULL DEFAULT 0,
  `section_id` int(11) DEFAULT NULL,
  `section_code` varchar(20) DEFAULT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `teacher_name` varchar(150) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(20) DEFAULT NULL,
  `semester` enum('First','Second','Summer') DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `s_id` int(11) NOT NULL,
  `s_fname` varchar(50) NOT NULL,
  `s_lname` varchar(50) NOT NULL,
  `s_mname` varchar(50) DEFAULT NULL,
  `s_suffix` varchar(10) DEFAULT NULL,
  `s_gender` enum('Male','Female','Other') NOT NULL,
  `s_bdate` date NOT NULL,
  `s_age` int(11) GENERATED ALWAYS AS (timestampdiff(YEAR,`s_bdate`,curdate())) VIRTUAL,
  `s_cnum` varchar(15) NOT NULL,
  `s_email` varchar(100) NOT NULL,
  `s_password` varchar(255) NOT NULL,
  `s_status` enum('active','inactive') DEFAULT 'active',
  `s_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `s_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`s_id`, `s_fname`, `s_lname`, `s_mname`, `s_suffix`, `s_gender`, `s_bdate`, `s_cnum`, `s_email`, `s_password`, `s_status`, `s_created_at`, `s_updated_at`) VALUES
(9, 'Edgar Clent', 'Cubero', 'Albarico', 'Jr.', 'Male', '2002-07-16', '09239503863', 'edgarclentcubero@gmail.com', '123123', 'active', '2024-11-12 10:06:29', '2024-11-22 01:36:49'),
(21, 'Cristine', 'Cubero', 'Tangian', NULL, 'Female', '2002-01-04', '09239503864', 'cris@yahoo.com', '123', 'active', '2024-11-13 14:36:40', '2024-11-20 20:00:37'),
(44, 'Eric Dave', 'Estrera', NULL, NULL, 'Male', '2004-06-08', '09798784515', 'eric@yahoo.com', '123123', 'active', '2024-11-20 19:39:52', '2024-11-20 19:40:29'),
(48, 'Jumar', 'Alibong', NULL, NULL, 'Male', '2024-11-12', '09497986454', 'jumar@yahoo.com', '123123', 'active', '2024-11-21 10:33:05', '2024-11-21 10:33:05'),
(49, 'Clint Jhon', 'Tajanlangit', NULL, NULL, 'Male', '2024-11-17', '09123454234', 'clint@yahoo.com', '123123', 'active', '2024-11-21 10:50:09', '2024-11-21 10:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `students_degrees`
--

CREATE TABLE `students_degrees` (
  `sd_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  `degree_id` int(11) NOT NULL,
  `degree_code` varchar(20) DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Inactive','Graduated','LOA') DEFAULT 'Active',
  `s_lname` varchar(50) DEFAULT NULL,
  `s_fname` varchar(50) DEFAULT NULL,
  `s_mname` varchar(50) DEFAULT NULL,
  `s_gender` enum('Male','Female','Other') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students_degrees`
--

INSERT INTO `students_degrees` (`sd_id`, `s_id`, `degree_id`, `degree_code`, `enrollment_date`, `status`, `s_lname`, `s_fname`, `s_mname`, `s_gender`) VALUES
(1, 9, 2, 'BSIT', '2024-11-12 10:06:29', 'Active', 'Cubero', 'Edgar Clent', 'Albarico', 'Male'),
(13, 21, 1, 'BEED', '2024-11-13 14:36:40', 'Active', 'Cubero', 'Cristine', 'Tangian', 'Female'),
(30, 44, 1, 'BSIT', '2024-11-20 19:39:52', 'Active', 'Estrera', 'Eric Dave', NULL, 'Male'),
(34, 48, 2, 'BSIT', '2024-11-21 10:33:05', 'Active', 'Alibong', 'Jumar', NULL, 'Male'),
(35, 49, 2, 'BSIT', '2024-11-21 10:50:09', 'Active', 'Tajanlangit', 'Clint Jhon', NULL, 'Male');

--
-- Triggers `students_degrees`
--
DELIMITER $$
CREATE TRIGGER `before_students_degrees_insert` BEFORE INSERT ON `students_degrees` FOR EACH ROW BEGIN
    DECLARE v_degree_code VARCHAR(20);
    
    SELECT degree_code INTO v_degree_code
    FROM degrees
    WHERE degree_id = NEW.degree_id;
    
    SET NEW.degree_code = v_degree_code;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_students_degrees_update` BEFORE UPDATE ON `students_degrees` FOR EACH ROW BEGIN
    DECLARE v_degree_code VARCHAR(20);
    
    IF NEW.degree_id != OLD.degree_id THEN
        SELECT degree_code INTO v_degree_code
        FROM degrees
        WHERE degree_id = NEW.degree_id;
        
        SET NEW.degree_code = v_degree_code;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `students_sections`
--

CREATE TABLE `students_sections` (
  `ss_id` int(11) NOT NULL,
  `s_id` int(11) DEFAULT NULL,
  `s_lname` varchar(50) DEFAULT NULL,
  `s_fname` varchar(50) DEFAULT NULL,
  `s_mname` varchar(50) DEFAULT NULL,
  `s_suffix` varchar(10) DEFAULT NULL,
  `s_gender` enum('Male','Female','Other') DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students_sections`
--

INSERT INTO `students_sections` (`ss_id`, `s_id`, `s_lname`, `s_fname`, `s_mname`, `s_suffix`, `s_gender`, `section_id`, `section_code`, `created_at`, `updated_at`) VALUES
(4, 9, 'Cubero', 'Edgar Clent', 'Albarico', 'Jr.', 'Male', 1, 'BSIT 3A', '2024-11-20 07:31:34', '2024-11-21 09:36:30'),
(13, 21, 'Cubero', 'Cristine', 'Tangian', NULL, 'Female', 29, 'BEED 3A', '2024-11-20 12:54:31', '2024-11-20 12:54:31'),
(15, 44, 'Estrera', 'Eric Dave', NULL, NULL, 'Male', 25, 'BSIT 1A', '2024-11-21 09:38:18', '2024-11-21 09:38:18'),
(16, 48, 'Alibong', 'Jumar', NULL, NULL, 'Male', 25, 'BSIT 3A', '2024-11-21 10:51:07', '2024-11-21 12:44:32'),
(17, 49, 'Tajanlangit', 'Clint Jhon', NULL, NULL, 'Male', 1, 'BSIT 1A', '2024-11-21 10:51:11', '2024-11-21 10:51:28');

--
-- Triggers `students_sections`
--
DELIMITER $$
CREATE TRIGGER `before_students_sections_insert` BEFORE INSERT ON `students_sections` FOR EACH ROW BEGIN
    DECLARE student_degree_code VARCHAR(20);
    DECLARE section_degree_code VARCHAR(20);
    DECLARE v_lname VARCHAR(50);
    DECLARE v_fname VARCHAR(50);
    DECLARE v_mname VARCHAR(50);
    DECLARE v_suffix VARCHAR(10);
    DECLARE v_gender VARCHAR(10);
    DECLARE v_section_code VARCHAR(20);
    
    -- Get student's degree code for validation
    SELECT degree_code INTO student_degree_code
    FROM students_degrees
    WHERE s_id = NEW.s_id;
    
    -- Get section details
    SELECT section_code, SUBSTRING_INDEX(section_code, ' ', 1) 
    INTO v_section_code, section_degree_code
    FROM sections
    WHERE section_id = NEW.section_id;
    
    -- Get student details (from existing trigger functionality)
    SELECT s_lname, s_fname, s_mname, s_suffix, s_gender 
    INTO v_lname, v_fname, v_mname, v_suffix, v_gender
    FROM students 
    WHERE s_id = NEW.s_id;
    
    -- Validate degree match (new functionality)
    IF student_degree_code != section_degree_code THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Student can only be assigned to sections matching their degree program';
    END IF;
    
    -- Set values (from existing trigger functionality)
    SET NEW.s_lname = v_lname;
    SET NEW.s_fname = v_fname;
    SET NEW.s_mname = v_mname;
    SET NEW.s_suffix = v_suffix;
    SET NEW.s_gender = v_gender;
    SET NEW.section_code = v_section_code;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_description` varchar(100) NOT NULL,
  `units` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_code`, `subject_description`, `units`, `created_at`, `updated_at`) VALUES
(1, 'OOP 2', 'Object-Oriented Programming 2', 3, '2024-11-20 10:13:33', '2024-11-20 20:19:44'),
(2, 'OS', 'All about operating systems', 3, '2024-11-20 10:43:45', '2024-11-21 23:36:40'),
(5, 'NET 2', 'Networking 2', 3, '2024-11-20 10:50:16', '2024-11-21 23:36:46'),
(11, 'PROG 2', 'Programming 2', 3, '2024-11-20 19:37:14', '2024-11-20 19:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `subjects_teachers`
--

CREATE TABLE `subjects_teachers` (
  `st_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `t_id` int(11) NOT NULL,
  `t_lname` varchar(50) DEFAULT NULL,
  `t_fname` varchar(50) DEFAULT NULL,
  `t_mname` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects_teachers`
--

INSERT INTO `subjects_teachers` (`st_id`, `subject_id`, `subject_code`, `t_id`, `t_lname`, `t_fname`, `t_mname`, `created_at`, `updated_at`) VALUES
(2, 1, 'OOP 2', 32, 'Amoin', 'Nicasio', NULL, '2024-11-20 15:26:41', '2024-11-20 15:26:41'),
(3, 2, 'OS', 32, 'Amoin', 'Nicasio', NULL, '2024-11-20 15:41:10', '2024-11-20 15:41:10'),
(4, 11, 'PROG 2', 32, 'Amoin', 'Nicasio', NULL, '2024-11-20 19:37:21', '2024-11-20 19:37:21'),
(8, 5, 'NET 2', 33, 'Alerta', 'Jeffrey', NULL, '2024-11-21 12:43:04', '2024-11-21 12:43:04');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `t_id` int(11) NOT NULL,
  `t_fname` varchar(50) NOT NULL,
  `t_lname` varchar(50) NOT NULL,
  `t_mname` varchar(50) DEFAULT NULL,
  `t_suffix` varchar(10) DEFAULT NULL,
  `t_gender` enum('Male','Female','Other') NOT NULL,
  `t_bdate` date NOT NULL,
  `t_age` int(11) GENERATED ALWAYS AS (timestampdiff(YEAR,`t_bdate`,curdate())) VIRTUAL,
  `t_cnum` varchar(15) NOT NULL,
  `t_email` varchar(100) NOT NULL,
  `t_password` varchar(255) NOT NULL,
  `t_department` varchar(50) DEFAULT NULL,
  `t_status` enum('active','inactive') DEFAULT 'active',
  `t_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `t_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`t_id`, `t_fname`, `t_lname`, `t_mname`, `t_suffix`, `t_gender`, `t_bdate`, `t_cnum`, `t_email`, `t_password`, `t_department`, `t_status`, `t_created_at`, `t_updated_at`) VALUES
(32, 'Nicasio', 'Amoin', '', 'Jr', 'Male', '2002-01-02', '09494949445', 'nick@yahoo.com', '123123', 'IT', 'active', '2024-11-17 19:11:06', '2024-11-22 01:04:08'),
(33, 'Jeffrey', 'Alerta', NULL, NULL, 'Male', '2011-11-11', '09494494777', 'jalerta@yahoo.com', '123', 'IT', 'active', '2024-11-17 19:21:26', '2024-11-18 03:04:45'),
(34, 'Raeshelle', 'Anguit', '', '', 'Male', '2012-12-12', '09412121212', 'rae@yahoo.com', '123', 'IT', 'active', '2024-11-17 19:22:05', '2024-11-17 19:22:05'),
(46, 'Danny', 'Obidas', '', '', 'Male', '2024-11-10', '09784154523', 'danny@yahoo.com', '123123', 'IT', 'active', '2024-11-21 10:34:11', '2024-11-21 10:34:11');

-- --------------------------------------------------------

--
-- Structure for view `degrees_sections_view`
--
DROP TABLE IF EXISTS `degrees_sections_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `degrees_sections_view`  AS SELECT `ds`.`degree_id` AS `degree_id`, `d`.`degree_code` AS `degree_code`, `d`.`degree_name` AS `degree_name`, `ds`.`section_id` AS `section_id`, `s`.`section_name` AS `section_name`, `s`.`section_code` AS `section_code`, `s`.`year_level` AS `year_level`, `ds`.`semester` AS `semester` FROM ((`degrees_sections` `ds` join `degrees` `d` on(`ds`.`degree_id` = `d`.`degree_id`)) join `sections` `s` on(`ds`.`section_id` = `s`.`section_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `degrees`
--
ALTER TABLE `degrees`
  ADD PRIMARY KEY (`degree_id`),
  ADD UNIQUE KEY `degree_code` (`degree_code`);

--
-- Indexes for table `degrees_sections`
--
ALTER TABLE `degrees_sections`
  ADD PRIMARY KEY (`degree_id`,`section_id`,`semester`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `section_code` (`section_code`),
  ADD KEY `fk_section_degree` (`degree_id`);

--
-- Indexes for table `sections_advisors`
--
ALTER TABLE `sections_advisors`
  ADD PRIMARY KEY (`sa_id`),
  ADD UNIQUE KEY `section_id` (`section_id`),
  ADD KEY `t_id` (`t_id`);

--
-- Indexes for table `sections_schedules`
--
ALTER TABLE `sections_schedules`
  ADD PRIMARY KEY (`ss_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `idx_section_schedule` (`section_id`,`day_of_week`,`start_time`),
  ADD KEY `fk_schedule_subject` (`subject_code`),
  ADD KEY `fk_schedule_room` (`room_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`s_id`),
  ADD UNIQUE KEY `s_email` (`s_email`),
  ADD UNIQUE KEY `unique_student` (`s_fname`,`s_lname`,`s_mname`,`s_suffix`,`s_bdate`),
  ADD UNIQUE KEY `unique_student_name_gender` (`s_lname`,`s_fname`,`s_mname`,`s_gender`),
  ADD KEY `idx_student_email` (`s_email`);

--
-- Indexes for table `students_degrees`
--
ALTER TABLE `students_degrees`
  ADD PRIMARY KEY (`sd_id`),
  ADD UNIQUE KEY `unique_student_degree` (`s_id`,`degree_id`),
  ADD KEY `students_degrees_ibfk_2` (`degree_id`),
  ADD KEY `fk_s_lname_s_fname_s_mname_s_gender` (`s_lname`,`s_fname`,`s_mname`,`s_gender`);

--
-- Indexes for table `students_sections`
--
ALTER TABLE `students_sections`
  ADD PRIMARY KEY (`ss_id`),
  ADD UNIQUE KEY `unique_student_section` (`s_id`,`section_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `idx_subject_code` (`subject_code`);

--
-- Indexes for table `subjects_teachers`
--
ALTER TABLE `subjects_teachers`
  ADD PRIMARY KEY (`st_id`),
  ADD UNIQUE KEY `unique_subject_teacher` (`subject_code`,`t_id`),
  ADD KEY `t_id` (`t_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`t_id`),
  ADD UNIQUE KEY `t_email` (`t_email`),
  ADD UNIQUE KEY `unique_teacher` (`t_fname`,`t_lname`,`t_mname`,`t_suffix`,`t_bdate`),
  ADD KEY `idx_teacher_email` (`t_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `degrees`
--
ALTER TABLE `degrees`
  MODIFY `degree_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `sections_advisors`
--
ALTER TABLE `sections_advisors`
  MODIFY `sa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sections_schedules`
--
ALTER TABLE `sections_schedules`
  MODIFY `ss_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `s_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `students_degrees`
--
ALTER TABLE `students_degrees`
  MODIFY `sd_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `students_sections`
--
ALTER TABLE `students_sections`
  MODIFY `ss_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `subjects_teachers`
--
ALTER TABLE `subjects_teachers`
  MODIFY `st_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `t_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `degrees_sections`
--
ALTER TABLE `degrees_sections`
  ADD CONSTRAINT `degrees_sections_ibfk_1` FOREIGN KEY (`degree_id`) REFERENCES `degrees` (`degree_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `degrees_sections_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_section_degree` FOREIGN KEY (`degree_id`) REFERENCES `degrees` (`degree_id`);

--
-- Constraints for table `sections_advisors`
--
ALTER TABLE `sections_advisors`
  ADD CONSTRAINT `sections_advisors_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sections_advisors_ibfk_2` FOREIGN KEY (`t_id`) REFERENCES `teachers` (`t_id`) ON DELETE CASCADE;

--
-- Constraints for table `sections_schedules`
--
ALTER TABLE `sections_schedules`
  ADD CONSTRAINT `fk_schedule_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_subject` FOREIGN KEY (`subject_code`) REFERENCES `subjects` (`subject_code`) ON UPDATE CASCADE,
  ADD CONSTRAINT `sections_schedules_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sections_schedules_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`t_id`) ON DELETE SET NULL;

--
-- Constraints for table `students_degrees`
--
ALTER TABLE `students_degrees`
  ADD CONSTRAINT `fk_s_id` FOREIGN KEY (`s_id`) REFERENCES `students` (`s_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_s_lname_s_fname_s_mname_s_gender` FOREIGN KEY (`s_lname`,`s_fname`,`s_mname`,`s_gender`) REFERENCES `students` (`s_lname`, `s_fname`, `s_mname`, `s_gender`),
  ADD CONSTRAINT `fk_students_s_id` FOREIGN KEY (`s_id`) REFERENCES `students` (`s_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_degrees_ibfk_1` FOREIGN KEY (`s_id`) REFERENCES `students` (`s_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_degrees_ibfk_2` FOREIGN KEY (`degree_id`) REFERENCES `degrees` (`degree_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students_sections`
--
ALTER TABLE `students_sections`
  ADD CONSTRAINT `students_sections_ibfk_1` FOREIGN KEY (`s_id`) REFERENCES `students` (`s_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_sections_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects_teachers`
--
ALTER TABLE `subjects_teachers`
  ADD CONSTRAINT `subjects_teachers_ibfk_1` FOREIGN KEY (`subject_code`) REFERENCES `subjects` (`subject_code`) ON UPDATE CASCADE,
  ADD CONSTRAINT `subjects_teachers_ibfk_2` FOREIGN KEY (`t_id`) REFERENCES `teachers` (`t_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
