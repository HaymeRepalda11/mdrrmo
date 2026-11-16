-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Merged: data from mdrrmo_db.sql + users rows from database.sql
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
-- Database: `mdrrmo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(20) NOT NULL DEFAULT '',
  `topic` varchar(80) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','resolved') NOT NULL DEFAULT 'new',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gobag_progress`
--

CREATE TABLE `gobag_progress` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `items` varchar(500) NOT NULL DEFAULT '[]',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gobag_progress`
--

INSERT INTO `gobag_progress` (`user_id`, `items`, `updated_at`) VALUES
(2, '[]', '2026-11-16 14:42:34');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip`, `success`, `created_at`) VALUES
(1, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 09:19:04'),
(2, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 09:29:22'),
(3, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 09:30:12'),
(4, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 09:31:56'),
(5, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 09:33:08'),
(6, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 09:35:40'),
(7, 'jcrepalda123456789@gmail.com', '::1', 1, '2026-11-16 12:58:09'),
(8, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 14:03:17'),
(10, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 14:33:45'),
(11, 'jcrepalda123456789@gmail.com', '::1', 1, '2026-11-16 14:42:08'),
(12, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 15:26:07'),
(18, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 20:28:22'),
(19, 'mdrrmolb@gmail.com', '::1', 1, '2026-11-16 21:05:35'),
(20, 'jcrepalda123456789@gmail.com', '::1', 1, '2026-11-16 21:45:32');

-- --------------------------------------------------------

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `page` varchar(60) NOT NULL,
  `region` varchar(30) NOT NULL,
  `html` mediumtext NOT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_content`
--

INSERT INTO `page_content` (`page`, `region`, `html`, `updated_by`, `updated_at`) VALUES
('contact', '.site-footer', '\n    <div class=\"container footer-grid\">\n      <div>\n        <div class=\"footer-logos\">\n          <img src=\"images/logo.png\" alt=\"MDRRMO logo\">\n          <img src=\"images/losbanos.png\" alt=\"Seal of the Municipality of Los Baños\">\n          <img class=\"wide\" src=\"images/bagongpilipinas.png\" alt=\"Bagong Pilipinas\">\n        </div>\n        <div class=\"footer-brand\"><strong>MDRRMO<br><small style=\"font-weight:400\">Los Baños, Laguna</small></strong></div>\n        <p>Protecting lives and building resilient communities through disaster risk reduction and management.</p>\n      </div>\n      <div><h4>Services</h4><ul><li>Emergency Response</li><li>First Aid</li><li>Fire Safety</li><li>Rescue &amp; Retrieval Operations</li><li>Seminars for Community Members</li></ul></div>\n      <div><h4>Contact</h4><ul><li>📍 Brgy. Timugan, Municipal Hall, Los Baños, Laguna</li><li>🚨 Emergency: <a href=\"tel:\">(0977) 204 9641</a></li><li>✉️ mdrrmo@losbanos.gov.ph</li></ul></div>\n    </div>\n    <div class=\"footer-bottom\">© <span id=\"year\">2026</span> MDRRMO Los Baños, Laguna. All rights reserved.</div>\n  ', 1, '2026-11-16 14:38:52'),
('contact', '.topbar', '\n    <div class=\"container\">\n      <span><span class=\"siren\">🚨</span> <strong>Emergency Hotline:</strong> <a href=\"tel:\">(0977) 204 9641</a></span>\n      <span>Los Baños, Laguna • Open 24/7</span>\n    </div>\n  ', 1, '2026-11-16 14:38:52'),
('contact', 'main', '\n    <section class=\"hero page-hero\">\n      <div class=\"container\">\n        <span class=\"hero-badge\">Get in Touch</span>\n        <h1>Contact <span>Us</span></h1>\n        <p>Questions, training requests or feedback? Send us a message.</p>\n        <div class=\"breadcrumb\"><a href=\"home.html\">Home</a> / Contact Us</div>\n      </div>\n      <div class=\"wave\"><svg viewBox=\"0 0 1440 60\" preserveAspectRatio=\"none\"><path d=\"M0,30 C240,70 480,0 720,25 C960,50 1200,10 1440,35 L1440,60 L0,60 Z\"></path></svg></div>\n    </section>\n\n    <section class=\"section\">\n      <div class=\"container\">\n        <div class=\"contact-grid\">\n          <div class=\"contact-info reveal left\" style=\"transition-delay: 0ms;\">\n            <h2 style=\"margin-bottom:1.5rem\">We\'re Ready to Help</h2>\n            <div class=\"item\"><div class=\"icon\">📍</div><div><h4>Office Address</h4><p>Brgy. Timugan, Municipal Hall, Los Baños, Laguna, Philippines<br></p></div></div>\n            <div class=\"item\"><div class=\"icon\">📞</div><div><h4>Phone</h4><p>(0977) 204 9641</p></div></div>\n            <div class=\"item\"><div class=\"icon\">✉️</div><div><h4>Email</h4><p>mdrrmo@losbanos.gov.ph</p></div></div>\n            <div class=\"item\"><div class=\"icon\">🕒</div><div><h4>Office Hours</h4><p>Mon–Fri, 8:00 AM – 5:00 PM<br>Emergency response: 24/7</p></div></div>\n            <div class=\"hotline-box\">\n              <h3>🚨 Emergency Hotline</h3>\n              <div class=\"big\">(0977) 204 9641</div>\n              <p>For life-threatening emergencies, call immediately.</p>\n            </div>\n          </div>\n\n          <form class=\"card reveal right\" id=\"contactForm\" novalidate=\"\" style=\"transition-delay: 90ms;\"><div class=\"form-alert\" role=\"alert\"></div>\n            <h2 style=\"margin-bottom:1.3rem\">Send a Message</h2>\n            <div class=\"form-row\">\n              <div class=\"field\" data-required=\"\">\n                <label for=\"name\">Full Name *</label>\n                <input type=\"text\" id=\"name\" placeholder=\"Juan Dela Cruz\">\n                <span class=\"error\">Please enter your name.</span>\n              </div>\n              <div class=\"field\" data-required=\"\">\n                <label for=\"email\">Email *</label>\n                <input type=\"email\" id=\"email\" placeholder=\"you@example.com\">\n                <span class=\"error\">Please enter a valid email.</span>\n              </div>\n            </div>\n            <div class=\"form-row\">\n              <div class=\"field\">\n                <label for=\"phone\">Phone</label>\n                <input type=\"tel\" id=\"phone\" placeholder=\"09XX XXX XXXX\">\n              </div>\n              <div class=\"field\" data-required=\"\">\n                <label for=\"topic\">Subject *</label>\n                <select id=\"topic\">\n                  <option value=\"\">Select a topic</option>\n                  <option>Training request</option>\n                  <option>Volunteer application</option>\n                  <option>Incident report / feedback</option>\n                  <option>Partnership</option>\n                  <option>Other</option>\n                </select>\n                <span class=\"error\">Please choose a subject.</span>\n              </div>\n            </div>\n            <div class=\"field\" data-required=\"\">\n              <label for=\"message\">Message *</label>\n              <textarea id=\"message\" placeholder=\"How can we help you?\"></textarea>\n              <span class=\"error\">Please enter your message.</span>\n            </div>\n            <button type=\"submit\" class=\"btn btn-red\">📨 Send Message</button>\n            <div class=\"form-success\" id=\"formSuccess\">✅ Thank you! Your message was validated.</div>\n          </form>\n        </div>\n\n        \n      </div>\n    </section>\n  ', 1, '2026-11-16 14:38:52'),
('news', '.site-footer', '\n    <div class=\"container footer-grid\">\n      <div>\n        <div class=\"footer-logos\">\n          <img src=\"images/logo.png\" alt=\"MDRRMO logo\">\n          <img src=\"images/losbanos.png\" alt=\"Seal of the Municipality of Los Baños\">\n          <img class=\"wide\" src=\"images/bagongpilipinas.png\" alt=\"Bagong Pilipinas\">\n        </div>\n        <div class=\"footer-brand\"><strong>MDRRMO<br><small style=\"font-weight:400\">Los Baños, Laguna</small></strong></div>\n        <p>Protecting lives and building resilient communities through disaster risk reduction and management.</p>\n      </div>\n      <div><h4>Services</h4><ul><li>Emergency Response</li><li>First Aid</li><li>Fire Safety</li><li>Rescue &amp; Retrieval Operations</li><li>Seminars for Community Members</li></ul></div>\n      <div><h4>Contact</h4><ul><li>📍Brgy. Timugan, Municipal Hall, Los Baños, Laguna</li><li>🚨 Emergency: <a href=\"tel:\">(0977) 204 9641</a></li><li>✉️ mdrrmo@losbanos.gov.ph</li></ul></div>\n    </div>\n    <div class=\"footer-bottom\">© <span id=\"year\">2026</span> MDRRMO Los Baños, Laguna. All rights reserved.</div>\n  ', 1, '2026-11-16 14:34:11'),
('news', '.topbar', '\n    <div class=\"container\">\n      <span><span class=\"siren\">🚨</span> <strong>Emergency Hotline:</strong> <a href=\"tel:\">(0977) 204 9641</a></span>\n      <span>Los Baños, Laguna • Open 24/7</span>\n    </div>\n  ', 1, '2026-11-16 14:34:11'),
('news', 'main', '\n    <section class=\"hero page-hero\">\n      <div class=\"container\">\n        <span class=\"hero-badge\">Stay Informed</span>\n        <h1>News &amp; <span>Advisories</span></h1>\n        <p>Official announcements, safety tips and updates from MDRRMO Los Baños.</p>\n        <div class=\"breadcrumb\"><a href=\"home.html\">Home</a> / News</div>\n      </div>\n      <div class=\"wave\"><svg viewBox=\"0 0 1440 60\" preserveAspectRatio=\"none\"><path d=\"M0,30 C240,70 480,0 720,25 C960,50 1200,10 1440,35 L1440,60 L0,60 Z\"></path></svg></div>\n    </section>\n\n\n        <div class=\"grid grid-3\">\n          <article class=\"card news-card reveal\" data-cat=\"advisory\" style=\"transition-delay: 0ms;\">\n            <div class=\"news-thumb\">🌀</div>\n            <div class=\"news-body\"><span class=\"tag\">Advisory</span><div class=\"date\">Sample date</div><h3>Typhoon Preparedness Reminder</h3><p>Know your evacuation center, prepare your go-bag, and monitor official weather bulletins.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"training\" style=\"transition-delay: 90ms;\">\n            <div class=\"news-thumb red\">🏫</div>\n            <div class=\"news-body\"><span class=\"tag\">Training</span><div class=\"date\">Sample date</div><h3>Community First Aid &amp; CPR Training</h3><p>Open to volunteers, teachers and residents. Learn skills that save lives.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"safety\" style=\"transition-delay: 180ms;\">\n            <div class=\"news-thumb yellow\">🔥</div>\n            <div class=\"news-body\"><span class=\"tag\">Safety Tip</span><div class=\"date\">Sample date</div><h3>Fire Prevention at Home</h3><p>Check wiring, never leave cooking unattended, and keep exits clear.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"operations\" style=\"transition-delay: 270ms;\">\n            <div class=\"news-thumb red\">🚤</div>\n            <div class=\"news-body\"><span class=\"tag\">Operations</span><div class=\"date\">Sample date</div><h3>Rescue Team Completes Flood Drill</h3><p>Responders practiced water rescue and evacuation procedures with partner agencies.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"advisory\" style=\"transition-delay: 0ms;\">\n            <div class=\"news-thumb yellow\">🌋</div>\n            <div class=\"news-body\"><span class=\"tag\">Advisory</span><div class=\"date\">Sample date</div><h3>Earthquake Drill Schedule</h3><p>Participate in the quarterly nationwide simultaneous earthquake drill.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"training\" style=\"transition-delay: 90ms;\">\n            <div class=\"news-thumb\">🎓</div>\n            <div class=\"news-body\"><span class=\"tag\">Training</span><div class=\"date\">Sample date</div><h3>Barangay DRRM Volunteer Orientation</h3><p>Join the accredited community disaster volunteer program.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"safety\" style=\"transition-delay: 180ms;\">\n            <div class=\"news-thumb\">🎒</div>\n            <div class=\"news-body\"><span class=\"tag\">Safety Tip</span><div class=\"date\">Sample date</div><h3>What to Pack in Your Go-Bag</h3><p>Water, food, flashlight, radio, medicines, documents and a first aid kit.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"operations\" style=\"transition-delay: 270ms;\">\n            <div class=\"news-thumb yellow\">🏕️</div>\n            <div class=\"news-body\"><span class=\"tag\">Operations</span><div class=\"date\">Sample date</div><h3>Evacuation Centers Ready</h3><p>Evacuation sites inspected and stocked ahead of the rainy season.</p><a href=\"#\">Read more →</a></div>\n          </article>\n          <article class=\"card news-card reveal\" data-cat=\"safety\" style=\"transition-delay: 0ms;\">\n            <div class=\"news-thumb red\">🌊</div>\n            <div class=\"news-body\"><span class=\"tag\">Safety Tip</span><div class=\"date\">Sample date</div><h3>Flood Safety: Do\'s and Don\'ts</h3><p>Avoid floodwaters, turn off electricity, and move to higher ground early.</p><a href=\"#\">Read more →</a></div>\n          </article>\n        </div>\n      \n    \n  ', 1, '2026-11-16 14:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `page_content_history`
--

CREATE TABLE `page_content_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `page` varchar(60) NOT NULL,
  `region` varchar(30) NOT NULL,
  `html` mediumtext NOT NULL,
  `saved_by` int(10) UNSIGNED DEFAULT NULL,
  `saved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `code_hash`, `attempts`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 2, '$2y$10$HVweZnKru9CNSPsKstmFUu33TnUjYXe0/QduN8EFrF5Akdd8XLhQG', 0, '2026-11-16 11:59:59', '2026-11-16 11:54:10', '2026-11-16 11:49:59'),
(2, 2, '$2y$10$cIgzUyzLzZxl3f6ls9/tue2Iwjx271CA5Mzyjn0ZKDDkRDas6tV2O', 0, '2026-11-16 13:00:12', '2026-11-16 12:57:56', '2026-11-16 12:50:12'),
(3, 2, '$2y$10$TsAJkMpFIkptYayYxMBv4.yol.tmkKMJv0Q2AvNM5kzmm26KW1t9a', 0, '2026-11-16 13:08:57', '2026-11-16 13:10:49', '2026-11-16 12:58:57'),
(4, 2, '$2y$10$jM43t9PgQn9H5d3IIvnl4uaEV3xLsymH2CaQq5jWbANZld0o53FCW', 0, '2026-11-16 13:20:49', '2026-11-16 13:54:11', '2026-11-16 13:10:49'),
(5, 2, '$2y$10$erJYjsdGU3Gv.PL00WkotOCcsKPwyfg6J2u.sTphnS.rxPtvcHVRW', 0, '2026-11-16 14:04:11', '2026-11-16 14:32:04', '2026-11-16 13:54:11'),
(6, 2, '$2y$10$nlVbyD7KSgUpkj4R2OCg8eiF5rYIPwCDzFkn/WQHdUXfTyb9/JiZm', 0, '2026-11-16 14:42:04', '2026-11-16 14:41:37', '2026-11-16 14:32:04'),
(7, 2, '$2y$10$7PIXO.zeYomPlBebMCi59uiWr4zklvc7S5wbRctETRtl90hcAHTQy', 0, '2026-11-16 14:51:37', '2026-11-16 14:42:02', '2026-11-16 14:41:37'),
(8, 2, '$2y$10$97/YjE8ygdlNnpCgZ.woNekNFnfhJQZEaTNcCTHKraL0nEjpoQUU.', 0, '2026-11-16 15:34:37', '2026-11-16 16:22:31', '2026-11-16 15:24:37'),
(9, 2, '$2y$10$dJMBDPPoaLFJPNoqpQcSDeDrTP.rLDNfuIkh4oTloSkPrzEgjUyaq', 0, '2026-11-16 16:32:31', '2026-11-16 16:44:06', '2026-11-16 16:22:31'),
(10, 2, '$2y$10$DlcEJ5yfaVd06Jh8uB5r7eC1s0CvbUMJMSolh7sYRYOXgI/W8f.tu', 0, '2026-11-16 16:54:06', '2026-11-16 20:52:33', '2026-11-16 16:44:06'),
(11, 1, '$2y$10$6tpWMxrQPu3apG3dGLsH5uWLMmp4IPKI8f/mYXHzJNI0y3l2h3J.e', 0, '2026-11-16 17:31:37', '2026-11-16 17:22:23', '2026-11-16 17:21:37'),
(12, 2, '$2y$10$PAMiYYE/vRIcv6OVAv3ZBuH9l5m00w1bB2zKzyzT4vPuRGxNb7TB.', 0, '2026-11-16 21:02:33', '2026-11-16 20:55:55', '2026-11-16 20:52:33'),
(13, 2, '$2y$10$6IHXsv6sWRZqEGDf0Y8xm.dqOzISstn7xJfs8h74uilMuuEXKsllu', 0, '2026-11-16 21:05:55', '2026-11-16 21:04:03', '2026-11-16 20:55:55'),
(14, 2, '$2y$10$9Zl/ByRIgpjK.LF1Pi01keSzuuB.4BlV32INNiHFu8S7yur8Oe6Q2', 0, '2026-11-16 21:14:03', '2026-11-16 21:11:55', '2026-11-16 21:04:03'),
(15, 2, '$2y$10$AVSWLlTJeFAbv8sqoAi3IuJDtdH7Gtcius4yc3t6LLc3.AEfbTp12', 0, '2026-11-16 21:21:55', '2026-11-16 21:12:08', '2026-11-16 21:11:55'),
(16, 2, '$2y$10$.MOdF1PP5Feg/7bOoWT0bOhBX72olonsQU3oPmKUZp07hzo4GZUF6', 0, '2026-11-16 21:22:08', '2026-11-16 21:44:44', '2026-11-16 21:12:08'),
(17, 2, '$2y$10$JxP4jsBDEdTw5b5UGX.s4uAYoHOyFddi6GLDvXxOuiF734mmxbehe', 0, '2026-11-16 21:54:44', '2026-11-16 21:44:59', '2026-11-16 21:44:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `barangay` varchar(60) NOT NULL DEFAULT '',
  `birthday` date DEFAULT NULL,
  `sex` varchar(20) NOT NULL DEFAULT '',
  `blood` varchar(5) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `ec_name` varchar(120) NOT NULL DEFAULT '',
  `ec_phone` varchar(20) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`, `barangay`, `birthday`, `sex`, `blood`, `address`, `ec_name`, `ec_phone`, `is_active`, `created_at`, `updated_at`, `last_login_at`) VALUES
(1, 'MDRRMO Los Baños', 'mdrrmolb@gmail.com', '$2y$10$xo8uCbgwZzItxQMABTLw8e4pmpcscxVol0hs1SLg800GIwtOIj0gq', 'admin', '', '', NULL, '', '', '', '', '', 1, '2026-11-16 20:58:51', '2026-11-16 21:56:33', '2026-11-16 21:56:33'),
(2, 'James Repalda', 'repaldahayme@gmail.com', '$2y$10$AX2g/ORkz/kVwH9F14js.u/ifC9wZB9kXZpn9tYV4KPx.Iow35ctu', 'user', '', 'Putho-Tuntungin', NULL, '', '', '', '', '', 1, '2026-11-16 21:52:17', '2026-11-16 21:55:13', '2026-11-16 21:52:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_status` (`status`,`created_at`),
  ADD KEY `idx_msg_ip` (`ip`,`created_at`),
  ADD KEY `fk_msg_user` (`user_id`);

--
-- Indexes for table `gobag_progress`
--
ALTER TABLE `gobag_progress`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempts_email` (`email`,`created_at`),
  ADD KEY `idx_attempts_ip` (`ip`,`created_at`);

--
-- Indexes for table `page_content`
--
ALTER TABLE `page_content`
  ADD PRIMARY KEY (`page`,`region`),
  ADD KEY `fk_content_user` (`updated_by`);

--
-- Indexes for table `page_content_history`
--
ALTER TABLE `page_content_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_page` (`page`,`region`,`saved_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resets_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_barangay` (`barangay`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `page_content_history`
--
ALTER TABLE `page_content_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `fk_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gobag_progress`
--
ALTER TABLE `gobag_progress`
  ADD CONSTRAINT `fk_gobag_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_content`
--
ALTER TABLE `page_content`
  ADD CONSTRAINT `fk_content_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
