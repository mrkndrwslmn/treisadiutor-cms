-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 03:40 PM
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
-- Database: `ta_cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `clientID` int(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `companyName` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `documentID` int(11) NOT NULL,
  `taskID` int(11) NOT NULL,
  `fileName` varchar(255) DEFAULT NULL,
  `filePath` varchar(255) DEFAULT NULL,
  `uploadDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`documentID`, `taskID`, `fileName`, `filePath`, `uploadDate`) VALUES
(7, 20, 'forgcard-Margaret Reid (1).pdf', '/ta-cms/documents/user-uploads/682dd3b8073e9_forgcard-Margaret Reid (1).pdf', '2025-05-21 13:23:04'),
(8, 20, 'forgcard-Margaret Reid (1).png', '/ta-cms/documents/user-uploads/682dd3b80a609_forgcard-Margaret Reid (1).png', '2025-05-21 13:23:04'),
(9, 20, 'forgcard-Margaret Reid.pdf', '/ta-cms/documents/user-uploads/682dd3b80acee_forgcard-Margaret Reid.pdf', '2025-05-21 13:23:04');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `giver_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `formID` int(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `contact_method` varchar(100) DEFAULT NULL,
  `contact_details` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `project_name` varchar(100) DEFAULT NULL,
  `request_description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `expectations` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `STATUS` enum('pending','approved') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`formID`, `userID`, `contact_method`, `contact_details`, `service_type`, `project_name`, `request_description`, `deadline`, `expectations`, `additional_notes`, `submitted_at`, `STATUS`) VALUES
(2, 8, 'Messenger', 'mrkndrwslmn', 'Programming Help', 'Pseudocode', 'Write a pseudocode about our python script.', '2025-05-25', 'A short, concise, and rubic following code.', 'I want it to be as short as possible.\r\n', '2025-05-21 07:52:32', 'approved'),
(3, 8, 'tilerelypy@mailinator.com', 'nomasupon', 'Et explicabo Non se', 'Kaden Burgess', 'Commodi eum eu cumqu', '1974-06-12', 'Maiores accusantium ', 'Reiciendis temporibu', '2025-05-21 08:04:53', 'pending'),
(4, 8, 'tilerelypy@mailinator.com', 'nomasupon', 'Et explicabo Non se', 'Kaden Burgess', 'Commodi eum eu cumqu', '1974-06-12', 'Maiores accusantium ', 'Reiciendis temporibu', '2025-05-21 08:05:06', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `form_files`
--

CREATE TABLE `form_files` (
  `id` int(11) NOT NULL,
  `formid` int(11) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `date_uploaded` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_files`
--

INSERT INTO `form_files` (`id`, `formid`, `filepath`, `date_uploaded`) VALUES
(1, 2, '/ta-cms/documents/user-uploads/682d86407246d_forgcard-Margaret Reid (1).pdf', '2025-05-21 15:52:32'),
(2, 2, '/ta-cms/documents/user-uploads/682d864072f59_forgcard-Margaret Reid (1).png', '2025-05-21 15:52:32'),
(3, 2, '/ta-cms/documents/user-uploads/682d86407396f_forgcard-Margaret Reid.pdf', '2025-05-21 15:52:32');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `noteID` int(11) NOT NULL,
  `clientID` int(11) DEFAULT NULL,
  `addedBy` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `noteDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `taskID` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assignedBy` int(11) DEFAULT NULL,
  `assignedTo` int(11) DEFAULT NULL,
  `dueDate` date DEFAULT NULL,
  `status` enum('pending','in progress','completed') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `dateAssigned` timestamp NOT NULL DEFAULT current_timestamp(),
  `taskType` varchar(50) DEFAULT NULL,
  `formID` int(11) DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`taskID`, `title`, `description`, `assignedBy`, `assignedTo`, `dueDate`, `status`, `priority`, `dateAssigned`, `taskType`, `formID`, `createdAt`, `updatedAt`) VALUES
(20, 'Pseudocode', 'Write a pseudocode about our python script.', 1, 3, '2025-05-25', 'completed', 'medium', '2025-05-21 02:38:57', 'Programming Help', 2, '2025-05-21 16:29:12', '2025-05-21 17:29:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `fullName` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','client','adiutor') NOT NULL,
  `phoneNumber` varchar(20) DEFAULT NULL,
  `profilePic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `fullName`, `email`, `password`, `role`, `phoneNumber`, `profilePic`, `status`, `dateCreated`) VALUES
(1, 'Kyla Rivero', 'krivero@treisadiutor.com', '$2y$10$blT6qU7rL5uZciolDskX9Oaa.kakVYgf4Pa2WK5SFywOgI2z/eemu', 'admin', '+1 (834) 891-7893', NULL, 'active', '2025-05-20 05:35:15'),
(2, 'Iona Jackson', 'koqasi@treisadiutor.com', '$2y$10$VZwJz/gNO6nGegynmdw2pOD6jVGOt/2/JYmPHqv34jWkQOMP1cyDS', 'client', NULL, NULL, 'active', '2025-05-20 07:48:01'),
(3, 'Alison Calderon', 'alison@treisadiutor.com', '$2y$10$blT6qU7rL5uZciolDskX9Oaa.kakVYgf4Pa2WK5SFywOgI2z/eemu', 'adiutor', '1234567890', NULL, 'active', '2025-05-21 07:23:59'),
(4, 'Teddy Delacruz', 'teddy@treisadiutor.com', '$2y$10$blT6qU7rL5uZciolDskX9Oaa.kakVYgf4Pa2WK5SFywOgI2z/eemu', 'adiutor', '1234567890', NULL, 'active', '2025-05-21 07:23:59'),
(5, 'Jane Doe', 'jane@treisadiutor.com', '$2y$10$blT6qU7rL5uZciolDskX9Oaa.kakVYgf4Pa2WK5SFywOgI2z/eemu', 'adiutor', '1234567890', NULL, 'active', '2025-05-21 07:23:59'),
(8, 'Mark Andrew Soliman', 'markandrewsoliman.studies@gmail.com', '$2y$10$CRiJiQ2vMGhU0od4AGRWl.OBgPvkhDJQVWrx58TGcJ4SPuSQOKJyy', 'client', NULL, NULL, 'active', '2025-05-21 07:52:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`clientID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`documentID`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `giver_id` (`giver_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`formID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `form_files`
--
ALTER TABLE `form_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formid` (`formid`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`noteID`),
  ADD KEY `clientID` (`clientID`),
  ADD KEY `addedBy` (`addedBy`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`taskID`),
  ADD KEY `assignedBy` (`assignedBy`),
  ADD KEY `assignedTo` (`assignedTo`),
  ADD KEY `fk_tasks_form` (`formID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `clientID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `documentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `formID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `form_files`
--
ALTER TABLE `form_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `noteID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `taskID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`taskID`),
  ADD CONSTRAINT `feedbacks_ibfk_2` FOREIGN KEY (`giver_id`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `feedbacks_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`userID`);

--
-- Constraints for table `forms`
--
ALTER TABLE `forms`
  ADD CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `form_files`
--
ALTER TABLE `form_files`
  ADD CONSTRAINT `form_files_ibfk_1` FOREIGN KEY (`formid`) REFERENCES `forms` (`formID`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`clientID`) REFERENCES `clients` (`clientID`),
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`addedBy`) REFERENCES `users` (`userID`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assignedBy`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assignedTo`) REFERENCES `users` (`userID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
