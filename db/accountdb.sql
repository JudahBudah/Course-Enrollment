-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 21, 2026 at 09:46 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `accountdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
CREATE TABLE IF NOT EXISTS `articles` (
  `article_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `writer` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image_path` varchar(255) DEFAULT NULL,
  `view_count` int DEFAULT '0',
  `click_count` int DEFAULT '0',
  PRIMARY KEY (`article_id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`article_id`, `title`, `content`, `writer`, `created_at`, `image_path`, `view_count`, `click_count`) VALUES
(19, ' Path of Exile 2\'s \'Dawn of the Hunt\' Update Introduces New Class and Endgame Content', 'Summary:\r\nGrinding Gear Games releases the 0.2.0 patch for Path of Exile 2, titled \"Dawn of the Hunt,\" featuring the new Huntress class, additional Ascendancy classes, and expanded endgame content.__________________________________________________________________________________________\r\n\r\n The \"Dawn of the Hunt\" update introduces the Huntress, a new class specializing in beast taming and agility-based combat. Players can explore new Ascendancy classes, over 100 unique items, and seven additional endgame maps. A new mechanic, \"Corrupted Nexus,\" challenges players with zones overrun by corruption, culminating in battles against three formidable bosses. The update aims to enrich the game\'s depth and replayability, offering fresh challenges for veterans and newcomers alike.', 'GG.Jiro', '2025-05-02 02:57:06', 'src/uploads/1746154626_LayeredKeyArt_HighResLOGO-scaled.jpg', 2, 2),
(20, ' Far Cry 4 Receives 60 FPS Update on PlayStation 5 and Xbox Series X/S', 'Summary:\r\nUbisoft surprises fans by releasing a patch that enables Far Cry 4 to run at 60 frames per second on modern consoles, enhancing the decade-old game\'s performance.__________________________________________________________________________________________\r\n\r\n In a move that delighted long-time fans, Ubisoft updated Far Cry 4 to support 60 FPS gameplay on PlayStation 5 and Xbox Series X/S. This enhancement brings smoother visuals and a more responsive experience to the 2014 title, allowing a new generation of players to enjoy the game with improved performance. The update demonstrates Ubisoft\'s commitment to maintaining and enhancing its classic titles for modern hardware. \r\n', 'GG.Jiro', '2025-05-02 02:58:17', 'src/uploads/1746154697_capsule_616x353 (1).jpg', 18, 18),
(18, ' Assassin’s Creed Shadows Patch 1.0.2 Adds Horse Auto-Follow and Quality-of-Life Improvements', 'Summary:\r\n Ubisoft\'s first major update for Assassin’s Creed Shadows introduces several quality-of-life enhancements, including a horse auto-follow feature, batch item management, and skill tree refinements. __________________________________________________________________________________________\r\n\r\n The 1.0.2 patch for Assassin’s Creed Shadows brings back the beloved horse auto-follow feature, allowing players to have their mounts automatically follow roads to designated waypoints. This addition streamlines long-distance travel across the game\'s expansive Japanese countryside. Players can now also sell or dismantle multiple items simultaneously, simplifying inventory management. The update permits resetting individual Mastery nodes within skill trees, offering more flexibility in character customization. Additionally, the frame rate cap has been removed in the Hideout area, enhancing performance. These updates aim to improve the overall gameplay experience without altering core mechanics.', 'GG.Jiro', '2025-05-02 02:54:38', 'src/uploads/1746154478_image_2025-05-02_105436835.png', 2, 2),
(17, 'Oblivion Remastered Lacks Official Mod Support, But Community Responds Quickly', 'Summary:\r\n-Oblivion Remastered does not include official mod support from Bethesda.\r\n-Players can still install and use mods, though Bethesda advises caution.\r\n-The modding community is already active, with over 100 mods available shortly after release.\r\n__________________________________________________________________________________________\r\n\r\nWhile The Elder Scrolls V: Skyrim is widely known for its large and active modding community, its predecessor The Elder Scrolls IV: Oblivion has maintained a smaller but dedicated group of modders over the years. With the recent release of Oblivion Remastered, interest in modding the game has grown significantly. However, Bethesda Game Studios has stated that this remastered version does not include official mod support.\r\n\r\nThis does not mean that mods cannot be used. Players can still modify the game using third-party tools, though Bethesda makes it clear on its support site that any issues caused by mods should be resolved by removing them and verifying game files through platforms such as Steam or the Xbox app.\r\n\r\nHistorically, Bethesda has provided tools like the Creation Kit — used for titles like Skyrim, Starfield, and Fallout 4 — to facilitate mod creation. The original version of Oblivion had a similar tool called the Construction Set, available to PC users. Some players have reported being able to use this older software to access the remastered game’s files, though this has not been officially confirmed.\r\n\r\nDespite the lack of official support, modders have already begun uploading content for Oblivion Remastered. Within 24 hours of its launch, the game has accumulated over 100 mods on NexusMods, offering a variety of enhancements such as performance tweaks, reshaded visuals, and gameplay adjustments.\r\n', 'GG.Judah', '2025-05-01 15:12:32', 'src/uploads/1746112352_the-first-images-from-the-oblivion-remake-seem-to-have-leaked-cover67fe6abb7d3fa.jpg', 4, 4),
(16, 'Overwatch and Gundam Wing Crossover Introduces Mech-Themed Skins', 'Summary:\r\n-Overwatch introduces new skins inspired by Mobile Suit Gundam Wing to celebrate the anime\'s 30th anniversary.\r\n-Heroes receiving Gundam-themed skins include Mercy, Reaper, Soldier: 76, and Ramattra.\r\n-The development team collaborated closely with Bandai and drew inspiration from building Gunpla kits.\r\n__________________________________________________________________________________________\r\n\r\nTo commemorate the 30th anniversary of Mobile Suit Gundam Wing, Blizzard Entertainment has launched a crossover event within Overwatch 2, introducing new character skins inspired by the popular 1990s anime series. Starting April 29, players will be able to obtain Gundam-themed appearances for four Overwatch heroes: Mercy, Reaper, Soldier: 76, and Ramattra.\r\n\r\nEach hero is matched with a specific mobile suit from the anime:\r\n-Mercy represents Wing Zero\r\n-Reaper appears as Deathscythe\r\n-Soldier: 76 takes on the look of Tallgeese\r\n-Ramattra embodies Epyon\r\n\r\nDespite Overwatch\'s large roster of 43 heroes, only a select few were chosen for this collaboration. According to art director Dion Rogers and associate product management director Aimee Dennett, other potential pairings were considered during development. For instance, Ana was initially tested for a Tallgeese-inspired skin due to similarities in weaponry, but the result did not meet expectations. Similarly, Mauga was briefly considered as a match for Heavyarms, but visual inconsistencies led to alternative selections.\r\n\r\nTo better capture the aesthetic of the Gundam universe, the Overwatch design team even assembled actual Gunpla model kits. This hands-on experience helped them replicate intricate mechanical details in the skins—such as articulated joints and hinge elements.\r\n\r\nAs with previous collaborations, these skins do not transform Overwatch characters into actual Gundam pilots or mobile suits. Instead, the heroes retain their original abilities and identities while dressed in Gundam-inspired gear. Feedback from Bandai also influenced the final design direction, ensuring recognizable elements of the Overwatch characters remained visible. For example, Mercy’s signature wings are still prominent within her Wing Zero-themed appearance.\r\n\r\nRogers noted that Gundam has had a lasting impact on pop culture and gaming, and this project was particularly meaningful for several artists on the team. For many, contributing to this collaboration was a personal milestone.\r\n\r\nThe Overwatch x Gundam Wing crossover officially begins on April 29.\r\n', 'GG.Judah', '2025-05-01 15:10:26', 'src/uploads/1746112226_hq720 (1).jpg', 0, 0),
(15, 'Nintendo Switch System Update Released: Adds Virtual Game Cards, Switch 2 Compatibility And More!', 'Summary:\r\n-The latest Nintendo Switch system update introduces Virtual Game Cards and GameShare.\r\n-The update enables cross-compatibility with the upcoming Switch 2 console.\r\n-Additional improvements include a new data transfer tool and visual updates to interface icons.\r\n__________________________________________________________________________________________\r\n\r\nNintendo has released a new system update for the Nintendo Switch, bringing several significant features ahead of the upcoming launch of the Switch 2. This update includes the introduction of Virtual Game Cards, GameShare functionality, and expanded compatibility with the next-generation console.\r\n\r\nOnce the update is installed, users will notice that their purchased digital games and downloadable content (DLC) are now converted into Virtual Game Cards. These digital equivalents to physical cartridges make it easier to organize and access your digital library. They also allow users to share or temporarily loan games to others in the same family group.\r\n\r\nA major new feature is GameShare, which supports multiplayer gameplay across Switch generations. This function allows Switch 2 owners to share games virtually with Switch 1 users, enabling multiplayer sessions even if only one user owns the game—a feature reminiscent of the DS’s Download Play.\r\n\r\nIn preparation for the Switch 2’s global release on June 5, Nintendo has also introduced a system transfer tool to move data from the original Switch to the new console. Other additions in this update include improved save data management—such as batch transfers—and visual changes to the eShop and News icons.\r\nPreorders for the Nintendo Switch 2 began on April 24 in North America and sold out quickly, though various games and accessories remain available. For more detailed information about the Switch 2, users are encouraged to visit the official Nintendo website.\r\n', 'GG.Judah', '2025-05-01 15:08:41', 'src/uploads/1746112121_Nintendo-Switch-Console-with-Neon-Blue-Red-Joy-Con_554c5f3c-5221-4984-8cd3-1f2ffd922f60.511e0f4426c11e9be89e00ea9c599791.webp', 3, 3),
(11, 'Fortnite to Premiere New Star Wars Animated Series Ahead of Disney+ Launch', 'Summary:\n Fortnite is hosting the premiere of a new Star Wars animated series before its Disney+ debut.\n A themed island and exclusive in-game rewards will be available starting May 2.\n New music and crossover cosmetics will roll out during the event, including a rare Star Wars song.\n__________________________________________________________________________________________\n\n In an unexpected collaboration, Disney and Epic Games are teaming up to debut Star Wars: Tales of the Underworld inside Fortnite—days before its official release on Disney+. The highly anticipated animated series will premiere in-game on May 2 as part of Fortnite\'s Star Wars-themed season, Galactic Battle.\n\nPlayers can join the Star Wars Watch Party island beginning at 10 AM ET / 7 AM PT to view the first two episodes of the new series. Alongside the screening, fans will be able to jump into special battles, fighting stormtroopers with iconic weapons like blasters and lightsabers. To access the event, players can find the island on Fortnite’s main experience row or enter the island code 2124-6713-8076. Those who complete the viewing will earn an exclusive Asajj Ventress loading screen.\n\nThis event is just one part of a month-long Star Wars celebration inside Fortnite. Epic is introducing account linking between Epic Games and MyDisney accounts, which unlocks a First Order Stormtrooper skin styled after the sequel trilogy.\n\n\nOn the musical front, Fortnite Festival is set to bring back \"Lapti Nek\"—the original tune from Jabba\'s Palace in Return of the Jedi, which was famously replaced in later editions. Additionally, three new Star Wars-themed tracks will drop during the event: “Rebel’s Run,” “The Dark Side,” and “Where My Wookiees At?”\n\n\nThe Galactic Battle season officially launches on May 2, promising fans a deep dive into the Star Wars universe like never before.\n', 'GG.Judah', '2025-05-01 14:47:31', 'src/uploads/1746110851_image_2025-05-01_224731123.png', 4, 4),
(14, 'Returnal Gets a PS5 Pro Upgrade With Sharper Visuals and Higher Resolution', 'Summary:\r\nReturnal now supports up to 2.5x more pixels on PS5 Pro for higher-resolution visuals. Developer Housemarque is working on Saros, a new action game launching in 2026. The Returnal universe has expanded into graphic novels and art books.\r\n__________________________________________________________________________________________\r\n\r\nHousemarque has officially released a performance patch for Returnal on the PS5 Pro, boosting the game\'s resolution significantly. The studio claims that the updated version delivers up to 2.5 times more pixels compared to the base PS5, offering a crisper, more detailed visual experience—especially for players with high-end displays. Complete patch notes are yet to be shared, but this upgrade alone is a major improvement for fans of the sci-fi action game.\r\n\r\nSince its launch in 2021, Returnal has stood out with its intense roguelite gameplay and deep, psychological themes. Players guide Selene, an astronaut trapped in a time loop on a hostile alien planet, as she fights to escape both her surroundings and the emotional weight of her past. The game’s unique blend of action and storytelling earned widespread acclaim and led to Housemarque’s acquisition by Sony.\r\n\r\nReturnal\'s eerie setting and cryptic narrative also inspired additional media. Housemarque’s team expanded on the game’s universe with a graphic novel adaptation, offering more insight into Selene’s journey. Additionally, fans can explore the artistic side of the game through a deluxe hardcover art book that features behind-the-scenes designs and concept art of its surreal environments and alien creatures.\r\n\r\nThe update arrives as Housemarque gears up for its next ambitious project: Saros. First revealed during the February 2025 PlayStation State of Play, Saros is another single-player action experience that builds on Returnal’s legacy. Featuring Rahul Kohli as the protagonist Arjun Devraj, the game explores a world where death is part of the progression system. Scheduled for release in 2026 on PS5 and PS5 Pro, Saros will introduce new mechanics including permanent resources and evolving player abilities.\r\n', 'GG.Judah', '2025-05-01 15:05:37', 'src/uploads/1746111937_capsule_616x353.jpg', 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `article_comments`
--

DROP TABLE IF EXISTS `article_comments`;
CREATE TABLE IF NOT EXISTS `article_comments` (
  `comment_id` int NOT NULL AUTO_INCREMENT,
  `article_id` int DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `comment_content` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `comment_media` varchar(255) DEFAULT NULL,
  `user_no` int DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `article_id` (`article_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_comments`
--

INSERT INTO `article_comments` (`comment_id`, `article_id`, `user_name`, `comment_content`, `created_at`, `comment_media`, `user_no`) VALUES
(12, 14, 'GameGrind', 'article comment test', '2025-05-06 03:22:44', '1746501764_5773b8a280a35a8af72bcc35ced9cb53.webp', 40),
(11, 11, 'GameGrind', 'Woah!', '2025-05-02 00:15:58', NULL, 40);

-- --------------------------------------------------------

--
-- Table structure for table `article_flags`
--

DROP TABLE IF EXISTS `article_flags`;
CREATE TABLE IF NOT EXISTS `article_flags` (
  `flag_id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `user_id` int NOT NULL,
  `flag_reason` varchar(50) NOT NULL,
  `additional_details` text,
  `flag_date` datetime NOT NULL,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  PRIMARY KEY (`flag_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_flags`
--

INSERT INTO `article_flags` (`flag_id`, `article_id`, `user_id`, `flag_reason`, `additional_details`, `flag_date`, `status`) VALUES
(1, 17, 40, 'misinformation', 'Yes', '2025-05-02 08:19:10', 'pending'),
(2, 17, 40, 'misinformation', 'Yes', '2025-05-02 08:19:14', 'pending'),
(3, 17, 40, 'offensive', 'Does this worl', '2025-05-02 08:19:30', 'pending'),
(4, 17, 40, 'offensive', 'Does this worl', '2025-05-02 08:19:34', 'pending'),
(5, 20, 53, 'spam', 'hj', '2026-03-08 15:37:20', 'pending'),
(6, 20, 53, 'spam', 'hj', '2026-03-08 15:37:25', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
CREATE TABLE IF NOT EXISTS `comment` (
  `comment_no` int NOT NULL AUTO_INCREMENT,
  `post_ID` int DEFAULT NULL,
  `user_no` int DEFAULT NULL,
  `comment` text NOT NULL,
  `comment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `comment_media` varchar(255) DEFAULT NULL,
  `likes` int DEFAULT '0',
  PRIMARY KEY (`comment_no`),
  KEY `post_ID` (`post_ID`),
  KEY `user_no` (`user_no`)
) ENGINE=MyISAM AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`comment_no`, `post_ID`, `user_no`, `comment`, `comment_date`, `created_at`, `comment_media`, `likes`) VALUES
(1, NULL, 40, 'true', '2025-04-24 02:18:50', '2025-04-24 02:18:50', NULL, 0),
(2, NULL, 40, '123', '2025-04-24 02:20:56', '2025-04-24 02:20:56', NULL, 0),
(3, NULL, 40, '123', '2025-04-24 02:22:37', '2025-04-24 02:22:37', NULL, 0),
(4, NULL, 40, 'test', '2025-04-24 02:23:53', '2025-04-24 02:23:53', NULL, 0),
(5, 1, 40, 'test', '2025-04-24 02:26:55', '2025-04-24 02:26:55', NULL, 0),
(6, 1, 40, 'hello qvq', '2025-04-24 02:27:00', '2025-04-24 02:27:00', NULL, 0),
(7, 3, 40, 'funny boy', '2025-04-24 02:31:08', '2025-04-24 02:31:08', NULL, 0),
(8, 3, 40, 'hi\'d', '2025-04-24 02:43:48', '2025-04-24 02:43:48', NULL, 0),
(9, 1, 38, 'Nice post!', '2025-04-25 01:12:45', '2025-04-25 01:12:45', NULL, 0),
(10, 5, 38, 'wew', '2025-04-25 04:27:41', '2025-04-25 04:27:41', NULL, 0),
(11, 3, 38, 'test', '2025-04-25 04:41:22', '2025-04-25 04:41:22', NULL, 0),
(12, 8, 40, 'hs', '2025-04-25 04:57:44', '2025-04-25 04:57:44', NULL, 0),
(31, 12, NULL, 'cute', '2025-05-03 02:51:47', '2025-05-03 02:51:47', NULL, 0),
(30, 12, NULL, 'cute', '2025-05-03 02:51:47', '2025-05-03 02:51:47', NULL, 0),
(29, 12, 40, 'cute', '2025-05-03 02:51:25', '2025-05-03 02:51:25', NULL, 0),
(16, 12, 38, 'MARCHHHHHHHH', '2025-04-26 01:41:33', '2025-04-26 01:41:33', NULL, 1),
(17, 12, 38, 'test', '2025-04-26 02:57:55', '2025-04-26 02:57:55', NULL, 0),
(18, 12, 38, 'mewo', '2025-04-26 02:59:13', '2025-04-26 02:59:13', NULL, 0),
(24, 38, 40, 'hi\r\n', '2025-05-03 02:41:48', '2025-05-03 02:41:48', NULL, 0),
(25, 38, 40, 'hi\r\n', '2025-05-03 02:42:02', '2025-05-03 02:42:02', NULL, 0),
(26, 38, 40, 'hey', '2025-05-03 02:42:29', '2025-05-03 02:42:29', NULL, 0),
(27, 12, 40, 'cute', '2025-05-03 02:50:25', '2025-05-03 02:50:25', NULL, 0),
(28, 12, 40, 'cute', '2025-05-03 02:51:25', '2025-05-03 02:51:25', NULL, 0),
(32, 18, NULL, 'hi', '2025-05-03 03:21:44', '2025-05-03 03:21:44', '68158bc8148a3_maxresdefault (34).jpg', 0),
(33, 8, NULL, 'hi', '2025-05-03 03:22:02', '2025-05-03 03:22:02', '68158bdac21c7_maxresdefault (34).jpg', 0),
(34, 40, NULL, 'Test', '2025-05-03 09:02:56', '2025-05-03 09:02:56', NULL, 0),
(35, 40, 40, 'Test', '2025-05-03 09:03:20', '2025-05-03 09:03:20', NULL, 0),
(36, 40, 40, 'Test', '2025-05-03 09:03:21', '2025-05-03 09:03:21', NULL, 0),
(37, 40, 40, 'Test', '2025-05-03 09:03:21', '2025-05-03 09:03:21', NULL, 0),
(38, 40, 40, 'Test', '2025-05-03 09:03:22', '2025-05-03 09:03:22', NULL, 0),
(39, 40, 40, 'Test', '2025-05-03 09:03:43', '2025-05-03 09:03:43', NULL, 0),
(40, 40, 40, 'Test', '2025-05-03 09:03:44', '2025-05-03 09:03:44', NULL, 0),
(41, 40, 40, 'Test', '2025-05-03 09:04:30', '2025-05-03 09:04:30', NULL, 0),
(42, 40, 40, 'Test', '2025-05-03 09:04:30', '2025-05-03 09:04:30', NULL, 0),
(43, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(44, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(45, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(46, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(47, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(48, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(49, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(50, 40, 40, 'Test', '2025-05-03 09:04:31', '2025-05-03 09:04:31', NULL, 0),
(51, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(52, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(53, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(54, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(55, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(56, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(57, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(58, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(59, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(60, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(61, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(62, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(63, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(64, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(65, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(66, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(67, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(68, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(69, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(70, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(71, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(72, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(73, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(74, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(75, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(76, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(77, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(78, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(79, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(80, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(81, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(82, 40, 40, 'Test', '2025-05-03 09:04:32', '2025-05-03 09:04:32', NULL, 0),
(83, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(84, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(85, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(86, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(87, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(88, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(89, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(90, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(91, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(92, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(93, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(94, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(95, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(96, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(97, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(98, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(99, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(100, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(101, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(102, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(103, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(104, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(105, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(106, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(107, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(108, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(109, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(110, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(111, 40, 40, 'Test', '2025-05-03 09:04:33', '2025-05-03 09:04:33', NULL, 0),
(112, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(113, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(114, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(115, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(116, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(117, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(118, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(119, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(120, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(121, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(122, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(123, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(124, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(125, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(126, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(127, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(128, 40, 40, 'Test', '2025-05-03 09:04:34', '2025-05-03 09:04:34', NULL, 0),
(129, 40, 40, 'Test', '2025-05-03 09:05:48', '2025-05-03 09:05:48', NULL, 0),
(130, 40, 40, 'Test', '2025-05-03 09:05:48', '2025-05-03 09:05:48', NULL, 0),
(131, 40, 40, 'Test', '2025-05-03 09:05:48', '2025-05-03 09:05:48', NULL, 0),
(132, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(133, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(134, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(135, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(136, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(137, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(138, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(139, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(140, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(141, 40, 40, 'Test', '2025-05-03 09:05:49', '2025-05-03 09:05:49', NULL, 0),
(142, 40, 40, 'Test', '2025-05-03 09:06:21', '2025-05-03 09:06:21', NULL, 0),
(143, 40, 40, 'Test', '2025-05-03 09:06:22', '2025-05-03 09:06:22', NULL, 0),
(144, 40, 40, 'Test', '2025-05-03 09:06:22', '2025-05-03 09:06:22', NULL, 0),
(145, 40, NULL, 'Test', '2025-05-03 09:06:58', '2025-05-03 09:06:58', NULL, 0),
(146, 40, NULL, 'Test', '2025-05-03 09:06:58', '2025-05-03 09:06:58', NULL, 0),
(147, 40, NULL, 'Test', '2025-05-03 09:07:30', '2025-05-03 09:07:30', NULL, 0),
(148, 38, NULL, 'Test', '2025-05-03 09:07:34', '2025-05-03 09:07:34', NULL, 0),
(149, 12, NULL, 'CYRE', '2025-05-03 09:07:55', '2025-05-03 09:07:55', NULL, 0),
(150, 33, NULL, 'FUNNY', '2025-05-03 09:08:37', '2025-05-03 09:08:37', NULL, 0),
(151, 33, NULL, 'FUNNY', '2025-05-03 09:08:47', '2025-05-03 09:08:47', NULL, 0),
(152, 40, NULL, 'Rizz', '2025-05-03 09:08:52', '2025-05-03 09:08:52', NULL, 0),
(153, 12, NULL, 'CUYIEEEEE', '2025-05-03 09:10:02', '2025-05-03 09:10:02', NULL, 0),
(154, 13, 40, 'Greater', '2025-05-03 09:16:28', '2025-05-03 09:16:28', NULL, 0),
(155, 13, 40, 'Sus', '2025-05-03 09:16:47', '2025-05-03 09:16:47', '6815deff47387_among_us_discord_icon_156922.png', 0),
(156, 37, 40, 'GG!', '2025-05-04 14:13:10', '2025-05-04 14:13:10', NULL, 0),
(157, 13, 40, 'Nice!', '2025-05-04 14:14:47', '2025-05-04 14:14:47', NULL, 0),
(158, 10, 40, 'Test', '2025-05-05 02:28:54', '2025-05-05 02:28:54', NULL, 0),
(159, 10, 40, 'Test', '2025-05-05 02:30:28', '2025-05-05 02:30:28', NULL, 0),
(160, 12, 40, 'Set', '2025-05-05 02:31:44', '2025-05-05 02:31:44', NULL, 0),
(161, 12, 40, 'Set', '2025-05-05 02:31:46', '2025-05-05 02:31:46', NULL, 0),
(162, 12, 40, 'Set', '2025-05-05 02:32:14', '2025-05-05 02:32:14', NULL, 0),
(163, 12, 40, 'Set', '2025-05-05 02:32:15', '2025-05-05 02:32:15', NULL, 0),
(164, 12, 40, 'Set', '2025-05-05 02:32:15', '2025-05-05 02:32:15', NULL, 0),
(165, 12, 40, 'Set', '2025-05-05 02:32:16', '2025-05-05 02:32:16', NULL, 0),
(166, 12, 40, 'Set', '2025-05-05 02:32:20', '2025-05-05 02:32:20', NULL, 0),
(167, 12, 40, 'Set', '2025-05-05 02:32:42', '2025-05-05 02:32:42', NULL, 0),
(168, 12, 40, 'Set', '2025-05-05 02:32:42', '2025-05-05 02:32:42', NULL, 0),
(169, 12, 40, 'test', '2025-05-05 02:35:34', '2025-05-05 02:35:34', NULL, 0),
(170, 12, 40, 'test', '2025-05-05 02:35:36', '2025-05-05 02:35:36', NULL, 0),
(171, 10, 40, 'test', '2025-05-05 02:39:05', '2025-05-05 02:39:05', NULL, 0),
(172, 12, 40, 'test', '2025-05-05 02:40:59', '2025-05-05 02:40:59', NULL, 0),
(173, 16, 40, 'test', '2025-05-05 02:45:16', '2025-05-05 02:45:16', NULL, 1),
(174, 42, 40, 'test', '2025-05-05 02:45:27', '2025-05-05 02:45:27', NULL, 2),
(175, 42, 40, 'test', '2025-05-05 02:45:33', '2025-05-05 02:45:33', '6818264d702d4_5773b8a280a35a8af72bcc35ced9cb53.webp', 2),
(176, 42, 40, 'testtttt', '2025-05-05 04:50:21', '2025-05-05 04:50:21', NULL, 0),
(177, 42, 40, 'waw', '2025-05-05 04:50:25', '2025-05-05 04:50:25', NULL, 0),
(178, 28, 38, '@miao', '2025-05-05 09:28:15', '2025-05-05 09:28:15', NULL, 0),
(179, 37, 38, '@miao', '2025-05-05 09:45:27', '2025-05-05 09:45:27', NULL, 0),
(180, 37, 40, 'eyyy\r\n', '2025-05-06 03:23:27', '2025-05-06 03:23:27', NULL, 0),
(181, 13, 40, 'rwerewr\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n', '2025-05-06 04:28:19', '2025-05-06 04:28:19', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

DROP TABLE IF EXISTS `comment_likes`;
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `like_id` int NOT NULL AUTO_INCREMENT,
  `comment_no` int NOT NULL,
  `user_no` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `unique_comment_like` (`comment_no`,`user_no`),
  KEY `user_no` (`user_no`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_likes`
--

INSERT INTO `comment_likes` (`like_id`, `comment_no`, `user_no`, `created_at`) VALUES
(3, 16, 40, '2025-05-05 04:31:30'),
(7, 175, 40, '2025-05-05 06:18:00'),
(8, 174, 40, '2025-05-05 06:18:01'),
(9, 174, 38, '2025-05-06 00:09:11'),
(10, 175, 38, '2025-05-06 00:09:14'),
(11, 173, 38, '2025-05-06 00:09:31');

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

DROP TABLE IF EXISTS `friends`;
CREATE TABLE IF NOT EXISTS `friends` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `friend_id` int NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `friend_id` (`friend_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friends`
--

INSERT INTO `friends` (`id`, `user_id`, `friend_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 40, 38, 'accepted', '2025-04-26 14:39:16', '2025-04-26 15:04:48'),
(2, 40, 38, 'accepted', '2025-04-26 14:41:40', '2025-04-26 15:04:48'),
(3, 41, 40, 'accepted', '2025-05-03 03:31:25', '2025-05-05 05:25:46'),
(4, 53, 40, 'pending', '2026-03-08 07:37:41', '2026-03-08 07:37:41');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
CREATE TABLE IF NOT EXISTS `games` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `cover_image` varchar(255) NOT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `description` text,
  `screenshots` text,
  `age_rating` varchar(32) DEFAULT NULL,
  `content_warnings` varchar(255) DEFAULT NULL,
  `platform` varchar(64) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `trailer_youtube_url` varchar(255) DEFAULT NULL,
  `download_links` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `title`, `cover_image`, `genre`, `description`, `screenshots`, `age_rating`, `content_warnings`, `platform`, `tags`, `created_at`, `trailer_youtube_url`, `download_links`) VALUES
(6, 'PUBG: Battlegrounds', 'games/cover_681450289ff02.jpg', 'Battle Royale, Survival, Shooter, FPS', 'PUBG: Battlegrounds, originally known as PlayerUnknown’s Battlegrounds, is a pioneer in the battle royale genre that pits up to 100 players against each other in a massive, open-world arena where the last person or team standing wins. Players parachute onto a vast island, scavenge for weapons, armor, and supplies, and eliminate others while the playable zone steadily shrinks, forcing confrontations and strategic positioning. The game’s realism, drawn from its military-style gun mechanics, bullet drop physics, and immersive environmental audio, makes each match a tense and tactical experience. With multiple maps ranging from dense forests to arid deserts, dynamic weather systems, and a mix of solo, duo, and squad modes, PUBG rewards both tactical planning and quick reflexes. Regular updates introduce new gear, seasonal modes, and esports-level tournaments, making it a staple in competitive multiplayer gaming.', 'screenshots/ss_68145028a0673.jpg,screenshots/ss_68145028a0c71.jpg,screenshots/ss_68145028a10f3.jpg,screenshots/ss_68145028a14e2.jpg,screenshots/ss_68145028a19c0.jpg', '16+', 'Realistic violence, blood, gunfire, in-game purchases', 'PC, Console (PS4/5, Xbox)', 'Shooter, Survival, Multiplayer, Battle Royale', '2025-05-02 12:55:04', 'https://www.youtube.com/watch?v=URBy9t6e8rY', 'https://store.steampowered.com/app/578080/PUBG_BATTLEGROUNDS/, https://pubg.com/en-na/main'),
(1, 'Honkai: Star Rail', 'games/cover_6814482ae4dd9.png', 'Turn-based strategy, Science Fiction, Fantasy, Gacha', 'Honkai: Star Rail is a free-to-play role-playing gacha video game developed and published by miHoYo. It is the fourth installment in the Honkai series, utilizing some characters from Honkai Impact 3rd and some gameplay elements from Genshin Impact. It is a story-rich, turn-based role-playing game developed by HoYoverse, known for their expertise in blending anime aesthetics with immersive narratives and strategic combat systems. The player becomes a “Trailblazer” aboard the Astral Express—a cosmic train traveling between worlds. The game features turn-based combat that rewards thoughtful planning and synergy between characters, each possessing unique elemental skills, paths, and ultimates. Beyond its battle system, Honkai: Star Rail offers expansive, fully-voiced story arcs, emotional character quests, and visually striking cutscenes that elevate the experience to near cinematic levels. With frequent content updates, time-limited events, and a gacha system that introduces new characters and weapons, the game blends a traditional JRPG formula with modern mobile-style progression and resource management.', 'screenshots/ss_6814482ae51b8.jpg,screenshots/ss_6814482ae568d.jpg,screenshots/ss_6814482ae5bf7.webp,screenshots/ss_6814482ae60b9.jpg', '12+', 'Fantasy violence, suggestive themes, gambling mechanics (gacha), in-game purchases', 'PC, iOS, Android, PlayStation', 'Anime, Turn-Based, Sci-Fi, RPG, Gacha', '2025-05-02 12:20:58', 'https://www.youtube.com/watch?v=jCxq-jMMsAc', 'https://hsr.hoyoverse.com/en-us/download'),
(7, 'Among Us', 'games/cover_6814513d1fc42.png', 'Social Deduction, Murder Mystery, Strategy', 'Among Us is a multiplayer social deduction game that skyrocketed in popularity due to its uniquely chaotic and social gameplay. Set aboard a space-themed facility, players assume one of two roles: Crewmate or Impostor. Crewmates must work together to complete various tasks around the map while staying alert for suspicious behavior. Impostors, on the other hand, aim to secretly sabotage the mission and eliminate the Crewmates without getting caught. The tension arises from group meetings where players discuss, accuse, and vote—often leading to humorous or intense betrayals. The game’s simplistic visuals are deceptive, hiding a deeply engaging psychological experience centered on communication, deception, and trust. Its cross-platform compatibility, light system requirements, and support for custom game modes and mods make it accessible and endlessly replayable, whether among friends or with strangers online.', 'screenshots/ss_6814513d20503.jpg,screenshots/ss_6814513d20dc0.jpg,screenshots/ss_6814513d21c58.jpg,screenshots/ss_6814513d2300a.jpg', '10+', 'Mild cartoon violence, betrayal themes', 'PC, iOS, Android, Nintendo Switch, PlayStation, Xbox', 'Multiplayer, Casual, Party Game, Deduction', '2025-05-02 12:59:41', 'https://www.youtube.com/watch?v=xkCbV5-oNfI, https://www.youtube.com/watch?v=naU5NXH3C3I', 'https://innersloth.com/gameAmongUs.php, https://store.steampowered.com/app/945360/Among_Us/, https://play.google.com/store/apps/details?id=com.innersloth.spacemafia&hl=en'),
(8, 'Valorant', 'games/cover_6814533e5579a.png', 'Tactical Shooter, Multiplayer, Competitive', 'Valorant is Riot Games’ tactical first-person shooter that merges the precision and gunplay of classic games like Counter-Strike with a modern twist: hero-style abilities. In this 5v5 competitive shooter, players take on the roles of “Agents,” each with distinct powers ranging from smoke screens and teleportation to area-denial tools and recon skills. The game demands not just quick reflexes and accuracy, but also tight team coordination, strategic utility use, and advanced map control. Each match consists of attack and defense rounds where the attacking team must plant a spike (bomb) and defenders attempt to stop them. The art style is sleek and stylized, and the game runs efficiently even on lower-end hardware. With constant seasonal updates, new agents, map additions, and a thriving esports scene, Valorant has become a staple for competitive gaming communities worldwide.', 'screenshots/ss_6814533e55d31.png,screenshots/ss_6814533e5611f.jpg,screenshots/ss_6814533e56824.jpg', '16+', 'Realistic violence, in-game purchases, online interactions', 'PC, PS5', 'FPS, Tactical, Competitive, eSports', '2025-05-02 13:08:14', 'https://www.youtube.com/watch?v=Xe5b-9BEQTs', 'https://playvalorant.com/'),
(9, 'Apex Legends', 'games/cover_68145e5278a78.png', 'Battle Royale, Multiplayer, Sci-Fi', 'Apex Legends is a free-to-play, team-based battle royale set in the futuristic Titanfall universe. Developed by Respawn Entertainment, the game reimagines the battle royale genre with its signature high-octane mobility, polished gunplay, and distinct roster of “Legends,” each with unique abilities and playstyles. Matches typically consist of 20 teams of three players each, dropped into expansive, ever-changing maps where they fight to be the last squad standing. The game\'s innovation lies in its ping communication system, respawn mechanics, and emphasis on character synergy—combining hero shooter dynamics with a competitive BR format. Regular seasons bring new characters, weapons, map updates, and rich lore expansions that explore the stories of the Legends and the world they inhabit. Whether you prefer stealth, aggressive rushes, or tactical positioning, Apex Legends offers countless ways to engage and excel.', 'screenshots/ss_68145e5278e89.jpg,screenshots/ss_68145e5279291.jpg, screenshots/ss_68145e5279b12.png,screenshots/ss_68145e5279fe6.jpg', '16+', 'Violence, blood, fast-paced combat, in-game purchases', 'PC, PS4/5, Xbox, Nintendo Switch', 'Battle Royale, Shooter, Sci-Fi, Hero Shooter', '2025-05-02 13:55:30', 'https://www.youtube.com/watch?v=hg0_PBw1OMI', 'https://www.ea.com/games/apex-legends, https://store.steampowered.com/app/1172470/Apex_Legends/, https://www.playstation.com/en-us/games/apex-legends/'),
(10, 'Minecraft: Java Edition', 'games/cover_6814704a58297.png', 'Sandbox, Adventure, Survival, Creative', 'Minecraft: Java Edition is the original version of the iconic block-based sandbox game that encourages creativity, exploration, survival, and adventure in a procedurally generated world. Players are free to gather resources, craft tools, build structures, and face off against monsters in survival mode, or unleash their imagination in creative mode with unlimited resources. The game’s pixelated aesthetic masks a rich, open-ended gameplay experience where the only limits are your imagination. Redstone mechanics introduce electrical engineering-like logic, while mods and community servers offer everything from RPG quests to large-scale PvP games. With constant updates, seasonal events, and an active modding community, Minecraft remains one of the most versatile and popular games ever created, whether you\'re playing solo, collaborating with friends, or joining a massive online world.', 'screenshots/ss_6814704a58723.jpg,screenshots/ss_6814704a58acd.jpg,screenshots/ss_6814704a58e20.png,screenshots/ss_6814704a5967b.jpg', '7+', ': Mild cartoon violence, online interactions', 'PC', 'Sandbox, Building, Adventure, Creative', '2025-05-02 15:12:10', 'https://www.youtube.com/watch?v=MmB9b5njVbA', 'https://www.minecraft.net/en-us/store/minecraft-java-edition'),
(11, 'Ark: Survival Ascended', 'games/cover_6814720540fd9.jpg', 'Survival, Open World, Dinosaur, Crafting', 'Ark: Survival Ascended is a ground-up remake of Ark: Survival Evolved, rebuilt using Unreal Engine 5 for dramatically enhanced visuals, performance, and gameplay fidelity. Players awaken stranded on a mysterious island populated with prehistoric creatures, mythical beasts, and ancient ruins, with nothing but their wits and bare hands. The goal: survive. Hunt. Build. Dominate. The game offers a rich crafting system, base-building mechanics, and deep RPG-style progression that lets you tame and ride dinosaurs, form tribes, and even breed creatures with genetic traits. Its open-world sandbox includes dynamic weather, temperature systems, and harsh environments, forcing players to balance exploration with shelter, food, and security. The multiplayer environment creates thrilling PvP and PvE experiences, where players compete or cooperate for resources and territory. Survival Ascended adds cross-platform play, mod support, and rebalanced systems that elevate the original game\'s intensity and complexity to modern standards.', 'screenshots/ss_68147205413e9.jpg,screenshots/ss_681472054189f.jpg', '16+', 'Violence, survival stress, blood, online interactions', 'PC, PS5, Xbox Series X/S, Switch', 'Open World, Crafting, Dinosaurs, Survival, Multiplaye', '2025-05-02 15:19:33', 'https://www.youtube.com/watch?v=IMklgHkggrQ', 'https://survivetheark.com, https://www.nintendo.com/us/store/products/ark-survival-evolved-switch/, https://store.steampowered.com/app/2399830, https://www.xbox.com/en-GB/games/store/ark-survival-ascended/9p33vjgvphvp, https://store.playstation.com/en-us/concept/10008286/'),
(12, 'Cyberpunk 2077', 'games/cover_6814737693828.png', 'Sci-Fi, Action, RPG, Open World', 'Cyberpunk 2077 is an open-world action-RPG developed by CD Projekt Red, set in the dystopian metropolis of Night City—where corporate greed, cybernetic augmentation, and body hacking define human existence. You play as V, a customizable mercenary navigating the chaotic underworld of hackers, street gangs, and megacorporations, entangled in a fight for identity and power after a digital ghost of a rockstar-turned-rebel terrorist (voiced by Keanu Reeves) embeds itself in your brain. The game delivers a richly detailed, neon-soaked world filled with branching narratives, complex dialogue trees, deep character customization, and combat options ranging from stealth and hacking to full-blown shootouts. With its deep lore, philosophical undertones, and post-launch updates that have refined gameplay mechanics, Cyberpunk 2077 now stands as one of the most ambitious and immersive role-playing games ever made.', 'screenshots/ss_681473769414b.jpg,screenshots/ss_68147376947c8.jpg,screenshots/ss_681473769500a.jpg,screenshots/ss_6814737695492.jpg,screenshots/ss_6814737695a4c.jpg', '18+', 'Graphic violence, sexual content, strong language, drug use', 'PC, PS4/5, Xbox Series X/S, Switch 2', 'Sci-Fi, Cyberpunk, RPG, Open World, Mature', '2025-05-02 15:25:42', 'https://www.youtube.com/watch?v=Ugb80d5lxEM', 'https://www.cyberpunk.net/ph/en/, https://store.steampowered.com/agecheck/app/1091500/, https://store.epicgames.com/en-US/p/cyberpunk-2077, https://www.playstation.com/en-us/games/cyberpunk-2077/, https://www.gog.com/en/game/cyberpunk_2077, https://www.xbox.com/en-GB/games/store/cyberpunk-2077/BX3M8L83BBRW'),
(18, 'League of Legends', 'games/leagueoflegends.png', 'MOBA, Competitive, Strategy', 'League of Legends is one of the most iconic multiplayer online battle arenas (MOBA), where two teams of five players compete to destroy the opposing team’s Nexus while defending their own. Players select from a roster of over 160 champions, each with unique abilities, roles, and lore, and engage in fast-paced strategic matches that demand teamwork, precise mechanics, and game knowledge. The map, Summoner\'s Rift, has distinct lanes, jungle areas, and objectives such as turrets, dragons, and Baron Nashor, all of which contribute to the game’s strategic complexity. With frequent balance updates, seasonal ranked play, and international esports tournaments, League of Legends remains the cornerstone of the competitive gaming world. Its influence has also extended to other media, including music videos, cinematics, and the acclaimed Netflix series Arcane.', 'screenshots/ss_681476791021b.jpg,screenshots/ss_68147679106c6.jpg,screenshots/ss_6814767910b7c.jpg,screenshots/ss_681476791107b.jpg', '12+', 'Fantasy violence, competitive stress, online toxicity', 'PC', 'MOBA, Strategy, Competitive, eSports', '2025-05-02 15:38:33', 'https://www.youtube.com/watch?v=wqS9f-1lvdU', 'https://www.leagueoflegends.com/'),
(21, 'Overwatch 2', 'games/cover_681515cf03986.jpg', 'Team-Based Shooter, Hero Shooter, Competitive', 'Overwatch 2 is Blizzard’s fast-paced, team-based hero shooter that builds upon its predecessor with new maps, heroes, game modes, and a switch to a free-to-play model. Players form teams of five, selecting from a diverse cast of “heroes” across three roles: Damage, Tank, and Support. Each hero has a distinct personality, backstory, and set of abilities that define their place in the game’s strategy. Gameplay emphasizes team synergy, map control, and the smart use of ultimates—powerful, game-changing abilities. Whether escorting payloads, capturing points, or brawling in chaotic skirmishes, Overwatch 2 offers both casual fun and intense competition. The game also explores PvE and narrative-driven seasonal events, pushing its futuristic universe and character lore further. With regular updates and a vibrant community, it remains one of the most accessible and stylish hero shooters available.', 'screenshots/ss_68151749e50e5.jpg,screenshots/ss_68151749e578d.jpg,screenshots/ss_68151749e5ca7.jpg', '13+', 'Fantasy violence, online interactions, in-game purchases', 'PC, PS4/5, Xbox One/Series X, Nintendo Switch', 'Hero Shooter, Competitive, Multiplayer, Futuristic', '2025-05-02 15:56:40', 'https://www.youtube.com/watch?v=GKXS_YA9s7E', 'https://store.steampowered.com/app/2357570/Overwatch_2/, https://us.shop.battle.net/en-us/product/overwatch, https://overwatch.blizzard.com/en-us/'),
(22, 'Dead by Daylight', 'games/cover_681511278630f.jpg', 'Asymmetrical PvP', 'Dead by Daylight is a 4v1 asymmetrical horror game where four Survivors must escape from a relentless Killer in a series of eerie maps inspired by horror films and original lore. Each match is a heart-pounding chase, with Survivors working together to repair generators and avoid capture, while the Killer stalks, injures, and sacrifices them. With a large roster of both original characters and licensed icons (such as Michael Myers, Freddy Krueger, and Ghostface), the game combines stealth, strategy, and jump-scare tension. Survivors can use tools, perks, and the environment to evade pursuit, while Killers use unique powers to terrorize and control the map. With seasonal events, constant balance updates, and deep character progression, Dead by Daylight has established itself as a staple in horror gaming and streaming culture.', 'screenshots/ss_68151127867b3.jpg,screenshots/ss_6815112786c35.jpeg,screenshots/ss_6815112787093.jpg,screenshots/ss_681511278761a.jpg,screenshots/ss_6815112787a19.jpg', '18+', 'Intense horror, gore, blood, violence', 'PC, PS4/5, Xbox, Nintendo Switch, Mobile', 'Horror, Multiplayer, Survival, Stealth', '2025-05-02 15:56:40', 'https://www.youtube.com/watch?v=JGhIXLO3ul8', 'https://deadbydaylight.com, https://store.steampowered.com/app/381210/Dead_by_Daylight/, https://www.dbdmobile-sea.com, https://store.epicgames.com/en-US/p/dead-by-daylight'),
(23, 'Genshin Impact', 'games/cover_681512872ad66.jpg', 'Open World Gacha', 'Genshin Impact is a massive open-world action RPG developed by HoYoverse, offering an anime-inspired world full of breathtaking landscapes, mysterious dungeons, and elemental magic. Set in the world of Teyvat, players take on the role of the Traveler in search of their lost sibling, journeying through seven regions inspired by real-world cultures and mythologies. Gameplay centers around real-time combat, where players can swap between characters mid-battle to exploit elemental combinations for explosive results. The game\'s gacha system allows players to summon new characters and weapons, each with their own lore, playstyle, and elemental affinity. Daily commissions, massive boss fights, co-op multiplayer, and seasonal events ensure the experience remains fresh and rewarding. With a strong narrative arc and voice acting across multiple languages, Genshin Impact is a rare mix of quality, scale, and accessibility in the free-to-play landscape.', 'screenshots/ss_681512872b422.jpg,screenshots/ss_681512872b990.jpg,screenshots/ss_681512872c029.png,screenshots/ss_681512872c591.jpg', '12+', 'Fantasy violence, gambling mechanics, online play', 'PC, iOS, Android, PlayStation', 'Open World, RPG, Fantasy, Anime, Gacha', '2025-05-02 15:56:40', 'https://www.youtube.com/watch?v=_VneeYsGT9I', 'https://genshin.hoyoverse.com, https://apps.apple.com/us/app/genshin-impact/id1517783697, https://store.epicgames.com/en-US/p/genshin-impact, https://play.google.com/store/apps/details?id=com.miHoYo.GenshinImpact&hl=en'),
(24, 'Call of Duty: Mobile', 'games/cover_68150cf267a2a.jpg', 'Shooter, FPS, Multiplayer', 'Call of Duty: Mobile delivers a premium, console-quality first-person shooter experience optimized for mobile devices. Developed by TiMi Studio Group and published by Activision, the game brings together fan-favorite maps, modes, weapons, and characters from across the iconic Call of Duty franchise. Whether you\'re diving into classic 5v5 multiplayer battles in maps like Nuketown and Crash, competing in ranked matches, or surviving in the expansive battle royale mode with up to 100 players, the game offers something for every type of shooter fan. Call of Duty: Mobile also includes deep customization through loadouts, a variety of operator skills, scorestreaks, seasonal battle passes, and regular limited-time events that keep content fresh and engaging. The game\'s competitive scene has grown with its own global championship, and controller support, clan systems, and esports events make it a robust experience for both casual and hardcore players. The visuals are top-tier for mobile platforms, with stunning effects, responsive controls, and high-performance optimization for various devices. With an ever-evolving live service model, Call of Duty: Mobile stands as one of the most expansive and successful mobile shooters to date.', 'screenshots/ss_68150cf268055.jpg,screenshots/ss_68150cf26860c.jpg,screenshots/ss_68150cf268d59.jpg,screenshots/ss_68150cf2692bf.jpg', '16+', 'Violence (gunfights, explosions, bloodless combat); Online interactions (chat, voice, competitive environments); In-app purchases and gacha-style weapon draws', 'Android, iOS', 'Multiplayer, Shooter, FPS, Battle Royale, Competitive, Esports, Mobile, Tactical', '2025-05-02 19:08:20', 'https://www.youtube.com/watch?v=n4b8FRUDNZo', 'https://www.callofduty.com/mobile, https://play.google.com/store/apps/details?id=com.garena.game.codm&hl=en, https://www.activision.com/games/call-of-duty/call-of-duty-mobile, https://apps.apple.com/ph/app/call-of-duty-mobile-garena/id1465688043'),
(25, 'Clash Royale', 'games/cover_6815107a0ce42.jpg', 'Real-Time Strategy, PvP, Card-Based', 'Clash Royale is a real-time multiplayer strategy game developed by Supercell that blends tower defense, collectible card game mechanics, and fast-paced tactical combat into one uniquely addictive package. Players collect and upgrade cards featuring troops, spells, and defenses from the Clash universe and then battle opponents in head-to-head duels. Matches are fast and fierce, usually lasting around 3 minutes, where players must outthink and outmaneuver their opponents by deploying cards wisely to destroy enemy towers while defending their own. With a focus on tactical depth, every card and placement matters—players must master elixir management, timing, and strategy to rise through the ranked ladder. Clash Royale also includes Clan Wars, tournaments, seasonal challenges, and frequent balance updates, ensuring that the meta constantly evolves and that competitive play stays fresh. With a vibrant, cartoonish art style, rewarding progression systems, and seamless matchmaking, Clash Royale is both accessible for newcomers and deep enough for seasoned strategy fans.', 'screenshots/ss_6815107a0d53b.jpg,screenshots/ss_6815107a0dfd2.jpg,screenshots/ss_6815107a0e545.png,screenshots/ss_6815107a0e9d9.jpg', '10+', 'Online player interactions (chat, competition); In-app purchases (cosmetics, progression boosts)', 'Android, iOS', 'Strategy, PvP, Card Game, Real-Time, Multiplayer, Competitive, Clash Universe', '2025-05-02 19:08:20', 'https://www.youtube.com/watch?v=1RC1yxqTTd8', 'https://play.google.com/store/apps/details?id=com.supercell.clashroyale&hl=en, https://supercell.com/en/games/clashroyale/, https://apps.apple.com/us/app/clash-royale/id1053012308'),
(26, 'Clash of Clans', 'games/cover_68150da585d22.jpg', 'Strategy, Base Building, Tower Defense', 'Clash of Clans is a mobile strategy game developed by Supercell, where players build and upgrade their villages, train troops, and raid other players to earn resources. The game combines base-building with strategic combat, requiring players to design effective defenses and plan offensive attacks. Players can join clans to participate in Clan Wars, Clan Games, and other cooperative events. With a variety of troops, spells, and heroes, as well as regular updates introducing new content, Clash of Clans offers a deep and engaging experience for strategy enthusiasts.', 'screenshots/ss_68150da586575.jpg,screenshots/ss_68150da586c65.png,screenshots/ss_68150da5872ca.jpg,screenshots/ss_68150da5877b4.jpg', '13+', 'Cartoon violence; Online interactions; In-app purchases', 'Android, iOS', 'Strategy, Base Building, Multiplayer, Clan Wars, Mobile', '2025-05-02 19:08:20', 'https://www.youtube.com/watch?v=oNEB3GGeleI', 'https://play.google.com/store/apps/details?id=com.supercell.clashofclans&utm_source=apac_med&utm_medium=hasem&utm_content=Jan0325&utm_campaign=Evergreen&pcampaignid=MKT-EDR-apac-ph-1710488-med-hasem-gm-Evergreen-Jan0325-Text_Search_SKWS-SKWS-BASDMF|ONSEM_kwid_43700081158126867_creativeid_723314656547_device_c&gad_source=1&gad_campaignid=19874505559&gclid=Cj0KCQjw2tHABhCiARIsANZzDWrzqHuH2a0nv7jlDLbc3O9Q6Y-CyhcZhu6K5HJ9B_hD1fGwPDSWAtQaAt2yEALw_wcB, https://supercell.com/en/games/clashofclans/, https://apps.apple.com/us/app/clash-of-clans/id529479190'),
(27, 'Soul Knight', 'games/cover_681518d8ef305.jpg', 'Roguelike, Action, Pixel, Dungeon Crawler', 'Soul Knight is a pixel-art roguelike game where players explore procedurally generated dungeons, battling enemies with a variety of weapons and abilities. The game features a vast arsenal of weapons, unique heroes with distinct skills, and cooperative multiplayer modes. With its fast-paced gameplay, charming visuals, and endless replayability, Soul Knight offers an engaging experience for fans of dungeon crawlers and action games.', 'screenshots/ss_681518d8ef83e.png,screenshots/ss_681518d8efe21.jpg,screenshots/ss_681518d8f02d5.png', '12+', 'Fantasy violence; In-app purchases', 'Android, iOS', 'Roguelike, Action, Pixel Art, Dungeon Crawler, Multiplayer', '2025-05-02 19:08:20', 'https://www.youtube.com/watch?v=CTrSVxV5OhA&pp=0gcJCdgAo7VqN5tD', 'https://play.google.com/store/apps/details?id=com.ChillyRoom.DungeonShooter&hl=en, https://apps.apple.com/us/app/soul-knight/id1184159988, https://soulknight.chillyroom.com/et'),
(28, 'God of War: Ragnarök', 'games/cover_6815133343f95.jpg', 'Action, Mythology, Adventure', 'God of War: Ragnarök is an action-adventure game developed by Santa Monica Studio and published by Sony Interactive Entertainment. As the sequel to 2018\'s God of War, it continues the journey of Kratos and his son Atreus through the Nine Realms of Norse mythology. Players engage in visceral combat, solve intricate puzzles, and explore expansive environments, all while unraveling a deeply emotional narrative centered on fate, destiny, and the bonds of family. The game features enhanced combat mechanics, a variety of enemies, and a richly detailed world that brings Norse legends to life.', 'screenshots/ss_6815133344632.jpg,screenshots/ss_6815133344bbb.png,screenshots/ss_6815133345163.jpg,screenshots/ss_6815133345696.jpg,screenshots/ss_6815133345bbd.jpg,screenshots/ss_6815133346100.jpg', 'Mature 17+', 'Intense violence and gore; Strong language; Depictions of alcohol consumption; Frightening scenes', 'PlayStation 4, PlayStation 5, PC', 'Story-Driven, Mythology, Hack and Slash, Single-player, Narrative', '2025-05-02 19:16:36', 'https://www.youtube.com/watch?v=hfJ4Km46A-0', 'https://store.steampowered.com/app/2322010/God_of_War_Ragnark/, https://www.playstation.com/en-ph/games/god-of-war-ragnarok/, https://store.epicgames.com/en-US/p/god-of-war-ragnarok-3ca641'),
(29, 'The Last of Us Part II', 'games/cover_68151aa185a34.jpg', 'Post-Apocalyptic, Survival', 'The Last of Us Part II is an action-adventure game developed by Naughty Dog and published by Sony Interactive Entertainment. Set in a post-apocalyptic United States, players follow Ellie as she embarks on a journey of vengeance and justice. The game features stealth mechanics, crafting systems, and intense combat scenarios against both human and infected enemies. With its emotionally charged narrative, complex characters, and realistic environments, The Last of Us Part II offers a harrowing exploration of the human condition in times of despair.', 'screenshots/ss_68151aa186075.jpg,screenshots/ss_68151aa186584.jpg,screenshots/ss_68151aa1869b7.jpg,screenshots/ss_68151aa186d16.jpg,screenshots/ss_68151aa18717f.jpg,screenshots/ss_68151aa18750f.jpg', 'Mature 17+', 'Graphic violence and gore; Strong language; Depictions of drug use; Sexual content; Intense emotional themes', 'PlayStation 4, PlayStation 5, PC', 'Narrative-Driven, Stealth, Survival Horror, Single-player, Emotional', '2025-05-02 19:16:36', 'https://www.youtube.com/watch?v=Tg1oRHd5zlw', 'https://www.playstation.com/en-ph/games/the-last-of-us-part-ii/,https://store.steampowered.com/app/2531310/The_Last_of_Us_Part_II_Remastered/'),
(30, 'Bloodborne', 'games/cover_68150b4257832.jpg', 'Dark Fantasy, RPG, Horror', 'Bloodborne is an action role-playing game developed by FromSoftware and published by Sony Computer Entertainment. Set in the gothic city of Yharnam, plagued by a mysterious blood-borne disease, players take on the role of a Hunter seeking to uncover the city\'s dark secrets. The game is renowned for its challenging combat, intricate world design, and Lovecraftian horror elements. With a focus on aggressive combat and exploration, Bloodborne offers a haunting and immersive experience.', 'screenshots/ss_68150b4257f60.jpg,screenshots/ss_68150b425843c.jpg,screenshots/ss_68150b42588fa.jpg,screenshots/ss_68150b4258da7.jpg', 'Mature 17+', 'Intense violence and gore; Disturbing imagery; Horror themes', 'PlayStation 4', 'Soulslike, Gothic Horror, Action RPG, Challenging, Atmospheric', '2025-05-02 19:16:36', 'https://www.youtube.com/watch?v=G203e1HhixY', 'https://www.playstation.com/en-us/games/bloodborne/'),
(31, 'Horizon Forbidden West', 'games/cover_681514c566e3a.jpg', 'Sci-Fi, Open World, Action', 'Horizon Forbidden West is an action role-playing game developed by Guerrilla Games and published by Sony Interactive Entertainment. As the sequel to Horizon Zero Dawn, players continue the journey of Aloy as she ventures into the uncharted lands of the Forbidden West. The game features a vast open world filled with diverse ecosystems, robotic creatures, and dynamic weather systems. Players engage in strategic combat using a variety of weapons and tools, while uncovering the mysteries of a world on the brink of collapse.', 'screenshots/ss_681514c56753e.jpg,screenshots/ss_681514c567b79.jpg,screenshots/ss_681514c56815e.jpg', 'Teen 13+', 'Violence; Mild language; Frightening scenes', 'PlayStation 4, PlayStation 5, Windows', 'Open World, Sci-Fi, Exploration, Action RPG, Story-Driven', '2025-05-02 19:16:36', 'https://www.youtube.com/watch?v=Lq594XmpPBg&', 'https://www.playstation.com/en-ph/games/horizon-forbidden-west/, https://store.steampowered.com/app/2420110/Horizon_Forbidden_West_Complete_Edition/'),
(32, 'Gran Turismo 7', 'games/cover_6815141438e26.jpg', 'Simulation, Racing, Realistic', 'Gran Turismo 7 is a racing simulation game developed by Polyphony Digital and published by Sony Interactive Entertainment. Celebrating the 25th anniversary of the series, the game offers a comprehensive driving experience with over 400 cars and 90 track layouts. Players can engage in various modes, including GT Campaign, Arcade, and Driving School, catering to both casual and competitive racers. With meticulous attention to detail, realistic physics, and extensive customization options, Gran Turismo 7 stands as a pinnacle of racing simulations.', 'screenshots/ss_681514143966d.jpg,screenshots/ss_6815141439c05.jpg,screenshots/ss_681514143a0c4.jpg,screenshots/ss_681514143a4a7.jpg', 'Everyone', 'Mild alcohol references', 'PlayStation 4, PlayStation 5', 'Racing Simulator, Realistic Driving, Car Customization, Multiplayer, Competitive', '2025-05-02 19:16:36', 'https://www.youtube.com/watch?v=1tBUsXIkG1A', 'https://www.gran-turismo.com/us/, https://www.playstation.com/en-us/games/gran-turismo-7/'),
(33, 'Demon’s Souls (Remake)', 'games/cover_681511c61fcc5.jpg', 'RPG, Dark Fantasy, Hardcore', 'Demon’s Souls is a remake of the 2009 classic, developed by Bluepoint Games and published by Sony Interactive Entertainment. Set in the kingdom of Boletaria, players venture into a fog-ridden land plagued by demons and dark magic. The game is renowned for its challenging combat, atmospheric world, and intricate lore. Rebuilt from the ground up for PlayStation 5, it features enhanced visuals, improved performance, and updated mechanics while preserving the original\'s punishing difficulty and gameplay depth.', 'screenshots/ss_681511c620818.jpg,screenshots/ss_681511c621091.png,screenshots/ss_681511c6216dc.jpg,screenshots/ss_681511c6221b0.jpg,screenshots/ss_681511c622806.jpg', 'Mature 17+', 'Intense violence and blood; Dark fantasy themes; Frightening imagery', 'PlayStation 5', 'Soulslike, Action RPG, Challenging, Atmospheric, Single-player', '2025-05-02 19:24:39', 'https://www.youtube.com/watch?v=qjZIw0VUezU', 'https://www.playstation.com/en-ph/games/demons-souls/'),
(34, 'Uncharted 4: A Thief’s End', 'games/cover_68151b22838fe.jpg', 'Treasure Hunt, Story-Driven', 'Uncharted 4: A Thief’s End is an action-adventure game developed by Naughty Dog and published by Sony Interactive Entertainment. Players follow Nathan Drake, a retired fortune hunter, as he\'s drawn back into the world of thieves by his presumed-dead brother, Sam. The game combines exploration, puzzle-solving, and third-person shooting in a globe-trotting quest for a legendary pirate treasure. Praised for its cinematic storytelling, character development, and stunning visuals, it\'s considered a fitting conclusion to Drake\'s saga.', 'screenshots/ss_68151b2283dfa.jpg,screenshots/ss_68151b228418c.jpg,screenshots/ss_68151b22846af.jpg,screenshots/ss_68151b2284c2c.jpg', 'Teen', 'Violence and blood; Use of alcohol and tobacco; Mild language', 'PlayStation 4, PlayStation 5, Windows', 'Action-Adventure, Narrative-Driven, Exploration, Third-Person Shooter, Single-player', '2025-05-02 19:24:39', 'https://www.youtube.com/watch?v=hh5HV4iic1Y&pp=0gcJCdgAo7VqN5tD', 'PlayStation Store, Steam (Legacy of Thieves Collection)'),
(35, 'Persona 5 Royal', 'games/cover_6815180d02624.jpg', 'Anime, RPG, School Life', 'Persona 5 Royal is an enhanced version of the original Persona 5, developed by Atlus. Players assume the role of a high school student in Tokyo who becomes a Phantom Thief, entering the Metaverse to change the hearts of corrupt individuals. The game blends traditional turn-based JRPG combat with life simulation elements, allowing players to build relationships, attend school, and engage in various activities. Royal introduces new characters, story arcs, locations, and gameplay mechanics, offering a deeper and more expansive experience.', 'screenshots/ss_6815180d02c0e.jpg,screenshots/ss_6815180d030ad.jpg,screenshots/ss_6815180d03625.jpg,screenshots/ss_6815180d039d9.jpg', 'Mature 17+', 'Sexual themes; Violence; Strong language; Depictions of abuse and mental health issues', 'PlayStation 4, PlayStation 5, Nintendo Switch, Xbox One, Xbox Se', 'JRPG, Turn-Based Combat, Social Simulation, Dungeon Crawling, Story-Rich', '2025-05-02 19:24:39', 'https://www.youtube.com/watch?v=MDSXGIigC3Q&pp=0gcJCdgAo7VqN5tD', 'https://persona.atlus.com/p5r/, https://store.steampowered.com/app/1687950/Persona_5_Royal/, https://www.nintendo.com/us/store/products/persona-5-royal-switch/?srsltid=AfmBOopCnSnX7zstpSyaGeZGbdln2RRl6xk9DhFUGo7I2YlAe80z-G8e,'),
(36, 'Brawl Stars', 'games/cover_68150beeca1ec.jpg', 'Arena Battler, MOBA, Action', 'Brawl Stars is a fast-paced multiplayer game featuring various 3v3 modes and solo events. Players choose from a wide roster of unique “Brawlers,” each with their own abilities and super moves. The game includes modes like Gem Grab, Showdown, Bounty, Heist, and more. It emphasizes quick matches, character progression, and team strategy.', 'screenshots/ss_68150beeca640.jpg,screenshots/ss_68150beeca9fa.jpg,screenshots/ss_68150beecae37.jpg,screenshots/ss_68150beecb2d9.jpg', '10+', 'Cartoon violence, online communication', 'Android, iOS', 'PvP, MOBA, Team-Based, Arena Shooter, Casual', '2025-05-02 22:34:43', 'https://www.youtube.com/watch?v=Fik4Rp6S1Bs', 'https://play.google.com/store/apps/details?id=com.supercell.brawlstars&hl=en, https://apps.apple.com/us/app/brawl-stars/id1229016807'),
(37, 'Stumble Guys', 'games/cover_681519dd0b335.jpg', 'Obstacle Royale, Party, Casual', 'Stumble Guys is a lighthearted multiplayer party game where players race through chaotic obstacle courses filled with hazards and physics-based challenges. Inspired by Fall Guys, it supports up to 32 players online, with the goal of being the last one standing. Its colorful visuals and comedic ragdoll physics make it appealing to players of all ages.', 'screenshots/ss_681519dd0b914.jpg,screenshots/ss_681519dd0bd8b.png,screenshots/ss_681519dd0c16f.jpg,screenshots/ss_681519dd0c596.jpg,screenshots/ss_681519dd0c9bd.jpg', '7+', 'Mild cartoon violence', 'Android, iOS, Playstation', 'Casual, Multiplayer, Platformer, Party Game, Family-Friendly', '2025-05-02 22:34:43', 'https://www.youtube.com/watch?v=fhoYcLSMyaM', 'https://www.stumbleguys.com, https://play.google.com/store/apps/details?id=com.kitkagames.fallbuddies&hl=en, https://store.steampowered.com/app/1677740/Stumble_Guys/, https://www.nintendo.com/us/store/products/stumble-guys-switch/?srsltid=AfmBOorHOOTF_yCw8QQEx4kWpf7m-A-dP_rMAtTEpJ6tySCuYXTm23RA, https://apps.apple.com/us/app/stumble-guys/id1541153375, https://store.playstation.com/en-us/concept/10007588/'),
(39, 'Standoff 2', 'games/cover_681507f09a9fb.jpg', 'Shooter, FPS, Competitive', 'Standoff 2 is a competitive first-person shooter that brings the fast-paced action of games like Counter-Strike to mobile. With realistic weapons, tactical gameplay, and a growing esports scene, players compete in various maps and modes like Team Deathmatch and Defuse. Its tight gunplay and frequent updates make it a favorite among FPS fans.', 'screenshots/ss_681507f09b053.jpg,screenshots/ss_681507f09b6a3.jpg,screenshots/ss_681507f09bbf8.jpg', '16+', 'Realistic violence, online communication', 'Android, iOS', 'FPS, Tactical, Realistic, Online Shooter, Competitive', '2025-05-02 22:34:43', 'https://www.youtube.com/watch?v=bJZ1QaeLaIo', 'https://play.google.com/store/apps/details?id=com.axlebolt.standoff2&hl=en,'),
(40, 'Dead Cells', 'games/cover_681502aec51ca.png', 'Metroidvania, Roguelike, Action Platformer', 'Dead Cells is a critically acclaimed action-platformer that combines procedurally generated levels, permadeath, and fluid combat. Players fight their way through a constantly evolving castle with a variety of weapons and powers, uncovering secrets and upgrades with each run. The mobile version retains the smooth gameplay and challenge of the original console/PC game.', 'screenshots/ss_681502aec5752.jpg,screenshots/ss_681502aec5ac8.jpg,screenshots/ss_681502aec5ded.png,screenshots/ss_681502aec6151.jpg,screenshots/ss_681502aec6576.jpg', '12+', 'Fantasy violence, mild gore', 'Android, iOS, PC, Nintendo Switch, PlayStation 5, PlayStation 4 ', 'Roguelike, Indie, Action, Permadeath, Offline Capable', '2025-05-02 22:34:43', 'https://www.youtube.com/watch?v=RvGaSPTcTxc&pp=0gcJCfcAhR29_xXO', 'Google Play, App Store'),
(41, 'Sky: Children of the Light', 'games/cover_6814d96a3265f.jpg', 'Social Adventure, Exploration, Casual', 'Sky: Children of the Light is a visually stunning social adventure game where players explore a vast kingdom and connect with others through cooperative play. The game encourages kindness and friendship through unique interaction systems like hand-holding, shared flight, and gifting. It’s a calming and emotional experience ideal for all ages.', 'screenshots/ss_6814d96a32b2e.jpg,screenshots/ss_6814d96a32f1f.jpg,screenshots/ss_6814d96a3336c.jpg', '7+', 'Emotional themes', 'Android, iOS', 'Social, Casual, Adventure, Emotional, Multiplayer', '2025-05-02 22:34:43', 'https://www.youtube.com/watch?v=feOR2GZxs2s', 'https://www.thatskygame.com, https://play.google.com/store/apps/details?id=com.tgc.sky.android&hl=en, https://store.steampowered.com/app/2325290/Sky_Children_of_the_Light/'),
(42, 'Call of Duty: Black Ops', 'games/cover_68151e1a264bb.png', 'First-Person Shooter, Military, Action', 'Released in 2010, Call of Duty: Black Ops is the seventh installment in the Call of Duty series and a direct sequel to World at War. Set during the 1960s Cold War era, the game follows CIA operative Alex Mason as he attempts to recall memories to uncover a Soviet plot involving a numbers station. The campaign spans various global locations, including Cuba, Vietnam, and the Soviet Union.​\r\nWikipedia\r\n\r\nThe game features a robust multiplayer mode with customizable loadouts, a currency system called COD Points for purchasing weapons and cosmetics, and various game modes. Additionally, it introduced the popular Zombies mode, allowing up to four players to fight off waves of the undead in cooperative gameplay.', 'screenshots/ss_68151e1a26944.jpg,screenshots/ss_68151e1a26cd9.jpg,screenshots/ss_68151e1a270bc.jpg,screenshots/ss_68151e1a27598.jpg', 'Mature 17+', 'Intense violence, strong language, blood and gore', 'PC, PlayStation 3, Xbox 360, Wii, macOS, Nintendo DS', 'FPS, Multiplayer, Campaign, Zombies, Competitive', '2025-05-03 03:28:46', 'https://www.youtube.com/watch?v=HxlVAZcQ-CE', 'https://store.steampowered.com/app/42700/Call_of_Duty_Black_Ops/, https://www.xbox.com/en-US/games/store/call-of-duty-black-ops/bvmgcsx6xpc9, https://call-of-duty-black-ops.en.softonic.com'),
(43, 'Left 4 Dead 2', 'games/cover_681520a2280c0.jpg', 'First-Person Shooter, Survival Horror, Co-op', 'A cooperative shooter where players fight through hordes of zombies across various campaigns. Features dynamic AI that adjusts difficulty, multiple game modes, and a variety of weapons.', 'screenshots/ss_681520a228540.jpg,screenshots/ss_681520a2288a4.jpg,screenshots/ss_681520a228bf5.jpg,screenshots/ss_681520a228f11.jpg,screenshots/ss_681520a229237.jpg', 'Mature 17+', 'Blood and gore, intense violence, strong language', 'PC, Xbox 360, Mac OS X, Linux', 'Co-op, Zombies, Multiplayer, Survival, Horror', '2025-05-03 03:28:46', 'https://www.youtube.com/watch?v=9XIle_kLHKU', 'https://store.steampowered.com/app/550/Left_4_Dead_2/, https://www.xbox.com/en-US/games/store/left-4-dead-2/BWVZHJN0G3C3'),
(45, 'Fallout 3', 'games/cover_68151ff8cc718.png', 'Action RPG, Open World, Post-Apocalyptic', 'Set in a post-nuclear Washington D.C., players explore a vast wasteland, making choices that affect the game\'s outcome. Features a mix of first-person shooting and role-playing elements.', 'screenshots/ss_68151ff8ccbd4.jpg,screenshots/ss_68151ff8ccfc3.jpg,screenshots/ss_68151ff8cd419.jpg,screenshots/ss_68151ff8cd7c6.jpg,screenshots/ss_68151ff8cdcb0.jpg', 'Mature 17+', 'Intense violence, blood and gore, sexual content, strong language', 'PC, PS3, Xbox 360', 'Open World, RPG, Post-Apocalyptic, Story Rich', '2025-05-03 03:28:46', 'https://www.youtube.com/watch?v=iYZpR51XgW0', 'https://store.steampowered.com/app/22300/Fallout_3/, https://www.xbox.com/en-US/games/store/fallout-3/c29hq887kh4b, https://store.epicgames.com/en-US/p/fallout-3-game-of-the-year-edition,'),
(46, 'Rainbow Six Siege', 'games/cover_6815216d83694.jpg', 'Tactical Shooter, Multiplayer, Competitive', 'A team-based tactical shooter emphasizing strategy, teamwork, and destructible environments. Players assume roles of various operators with unique abilities.', 'screenshots/ss_6815216d83c0b.jpg,screenshots/ss_6815216d84088.jpg,screenshots/ss_6815216d845b4.png,screenshots/ss_6815216d84b7c.jpg,screenshots/ss_6815216d8504e.jpg', 'Mature 17+', 'Violence, strong language', 'PC, PS4, PS5, Xbox One, Xbox Series X|S', 'Tactical, Multiplayer, Competitive, Shooter', '2025-05-03 03:28:46', 'https://www.youtube.com/watch?v=mj99yIzCQpc&pp=0gcJCdgAo7VqN5tD', 'https://www.ubisoft.com/en-gb/game/rainbow-six/siege, https://store.steampowered.com/app/359550/Tom_Clancys_Rainbow_Six_Siege/, https://www.playstation.com/en-us/games/tom-clancys-rainbow-six-siege/, https://www.xbox.com/en-US/games/store/tom-clancys-rainbow-six-siege/C12T09DSVP8J'),
(47, 'Red Dead Redemption', 'games/cover_681522315bb3c.jpg', 'Action-Adventure, Open World, Western', 'Set in the American frontier, players control John Marston, a former outlaw seeking redemption. Features an expansive open world, narrative-driven missions, and a morality system.', 'screenshots/ss_681522315bfcb.jpg,screenshots/ss_681522315c4ac.png,screenshots/ss_681522315c9ad.jpg', 'Mature 17+', 'Blood, intense violence, nudity, strong language, sexual content', 'PS3, Xbox 360, PS4, Switch, PC', 'Open World, Western, Story Rich, Action', '2025-05-03 03:28:46', 'https://www.youtube.com/watch?v=-o7rES_3ymA&pp=0gcJCdgAo7VqN5tD', 'https://www.rockstargames.com/reddeadredemption, https://store.steampowered.com/app/2668510/Red_Dead_Redemption/, https://www.xbox.com/en-US/games/store/red-dead-redemption/BWKLFHWT7DHC/0001, https://www.nintendo.com/us/store/products/red-dead-redemption-switch/?srsltid=AfmBOor0rx_YwW5zOH9iVKYaKybfJOuYxceGtYLfprM6U_hmAemrObfc'),
(48, 'Cuphead', 'games/cover_68151ee901dba.png', 'Run and Gun, Platformer, Indie', 'A challenging platformer known for its 1930s cartoon art style and jazz soundtrack. Players control Cuphead and Mugman as they battle various bosses to repay a debt to the Devil.', 'screenshots/ss_68151ee90242f.jpg,screenshots/ss_68151ee902aad.jpg,screenshots/ss_68151ee9030ee.jpg,screenshots/ss_68151ee903b28.jpg,screenshots/ss_68151ee903fb8.jpg', 'Everyone 10+', 'Mild cartoon violence', 'PC, Xbox One, PS4, Switch, macOS', 'Indie, Platformer, Co-op, Challenging', '2025-05-03 03:28:46', 'https://www.youtube.com/watch?v=NN-9SQXoi50', 'https://store.steampowered.com/app/268910/Cuphead/, https://www.nintendo.com/us/store/products/cuphead-switch/?srsltid=AfmBOooVZL9Fi8yViAd_9tN4VsasL4un-VMO0gO9DxhFVpnDKolDD4hV, https://www.xbox.com/en-US/games/store/cuphead/9NJRX71M5X9P');

-- --------------------------------------------------------

--
-- Table structure for table `game_comments`
--

DROP TABLE IF EXISTS `game_comments`;
CREATE TABLE IF NOT EXISTS `game_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_comments`
--

INSERT INTO `game_comments` (`id`, `game_id`, `user_id`, `comment`, `date_added`) VALUES
(1, 1, 38, 'Great game!', '2025-05-05 09:25:38'),
(2, 9, 40, 'I like this game', '2025-05-06 02:24:55'),
(3, 9, 40, 'Test', '2025-05-06 02:29:01'),
(4, 9, 40, 'game good', '2025-05-06 02:29:07'),
(5, 7, 38, 'Test', '2025-05-07 04:04:23'),
(6, 7, 38, 'Test', '2025-05-07 04:04:45'),
(7, 7, 38, 'Test', '2025-05-07 13:37:20'),
(8, 7, 38, 'Test', '2025-05-07 16:08:28'),
(9, 8, 40, 'Test', '2025-05-08 03:17:51');

-- --------------------------------------------------------

--
-- Table structure for table `game_guides`
--

DROP TABLE IF EXISTS `game_guides`;
CREATE TABLE IF NOT EXISTS `game_guides` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `media_paths` text,
  `game_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `views` int DEFAULT '0',
  `status` enum('draft','published','archived') DEFAULT 'published',
  PRIMARY KEY (`id`),
  KEY `idx_game_id` (`game_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_guides`
--

INSERT INTO `game_guides` (`id`, `title`, `content`, `date_added`, `last_modified`, `media_paths`, `game_id`, `user_id`, `views`, `status`) VALUES
(2, 'Valorant Maps Overview', 'Valorant features a rotation of uniquely designed maps with distinct mechanics. Here are some standout maps:\r\n', '2025-05-07 16:53:07', '2025-05-08 08:22:37', 'src/images/guide_thumbnails/guide_681c1916ce9f60.12311749.png', 8, NULL, 6, 'published'),
(4, 'Valorant Competitive Map Pool (as of May 2025)', 'As of Patch 10.04 in March 2025, Valorant’s Competitive and Premier modes feature a curated rotation of seven maps. These maps are selected to provide a dynamic and strategically diverse experience, each offering unique layouts, mechanics, and tactical demands. Here\'s a full overview of each map currently in the pool:\r\n\r\n', '2025-05-08 03:06:20', '2025-05-08 08:21:23', 'src/images/guide_thumbnails/guide_681c2005ecfd36.01780431.jpg', 8, NULL, 50, 'published'),
(5, 'asd', '', '2026-03-12 03:06:10', '2026-03-12 03:06:10', NULL, 7, NULL, 0, 'published');

-- --------------------------------------------------------

--
-- Table structure for table `game_user_data`
--

DROP TABLE IF EXISTS `game_user_data`;
CREATE TABLE IF NOT EXISTS `game_user_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `game_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `played` tinyint(1) DEFAULT '0',
  `review` text,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_game` (`user_id`,`game_id`),
  KEY `game_id` (`game_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_user_data`
--

INSERT INTO `game_user_data` (`id`, `user_id`, `game_id`, `rating`, `played`, `review`, `date_added`) VALUES
(1, 40, 7, 5, 0, 'Test', '2025-05-05 04:55:22'),
(2, 40, 12, 5, 1, 'Cyberpunk 2077 offers an expansive open-world experience set in the dystopian Night City. With its stunning visuals and immersive storytelling, players are drawn into a world of intrigue and danger. Despite some technical challenges, the game delivers a compelling narrative and a rich environment that keeps players engaged. The character customization and diverse gameplay options provide a unique experience for each player, making Cyberpunk 2077 a must-play for fans of the genre.', '2025-05-05 06:09:33'),
(3, 40, 30, 5, 1, 'Bloodborne is a masterful action RPG developed by FromSoftware, known for its challenging gameplay and atmospheric storytelling. Set in the hauntingly beautiful yet terrifying city of Yharnam, players take on the role of a Hunter, navigating through a world plagued by a mysterious blood-borne disease.\n\nThe game\'s gothic aesthetic is both captivating and unsettling, with intricately designed environments that evoke a sense of dread and curiosity. The combat system is fast-paced and unforgiving, rewarding players who master its mechanics with a deep sense of satisfaction. Bloodborne\'s lore is rich and enigmatic, encouraging players to piece together the story through exploration and discovery.\n\nOne of the standout features of Bloodborne is its unique approach to multiplayer, allowing players to engage in cooperative and competitive gameplay seamlessly. The Chalice Dungeons offer endless replayability, providing procedurally generated challenges that test even the most seasoned Hunters.\n\nOverall, Bloodborne is a must-play for fans of the genre, offering a thrilling and immersive experience that lingers long after the final boss is defeated. Its combination of challenging gameplay, stunning visuals, and intricate storytelling make it a standout title in the world of video games.', '2025-05-05 06:23:11'),
(4, 40, 48, 5, 1, 'Cuphead is a visually stunning and challenging run-and-gun platformer developed by Studio MDHR. Inspired by 1930s cartoons, the game features hand-drawn animations, watercolor backgrounds, and a jazz soundtrack that perfectly captures the era\'s aesthetic.\n\nPlayers take on the role of Cuphead and his brother Mugman as they embark on a journey to repay their debt to the devil. The game is renowned for its difficulty, requiring precise timing and skill to overcome its numerous boss battles and platforming levels.\n\nThe gameplay is both rewarding and punishing, with each victory feeling like a hard-earned triumph. The controls are tight and responsive, allowing players to execute complex maneuvers with ease. Cuphead\'s cooperative mode adds an extra layer of fun, enabling players to team up and tackle the game\'s challenges together.\n\nCuphead\'s unique art style and challenging gameplay make it a standout title in the world of indie games. It\'s a must-play for those who appreciate a blend of classic animation and modern gaming mechanics, offering an experience that is as visually captivating as it is demanding.', '2025-05-05 06:23:44'),
(5, 38, 11, 5, 1, 'Test', '2025-05-05 06:38:23'),
(6, 40, 23, 5, 1, 'good', '2025-05-06 03:24:12'),
(7, 38, 7, 5, 1, 'TEST', '2025-05-06 16:45:32'),
(8, 40, 9, 5, 1, 'vfwefve', '2025-05-08 08:17:23');

-- --------------------------------------------------------

--
-- Table structure for table `guide_categories`
--

DROP TABLE IF EXISTS `guide_categories`;
CREATE TABLE IF NOT EXISTS `guide_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guide_id` int NOT NULL,
  `category_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `guide_id` (`guide_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guide_comments`
--

DROP TABLE IF EXISTS `guide_comments`;
CREATE TABLE IF NOT EXISTS `guide_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guide_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `likes` int DEFAULT '0',
  `dislikes` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `guide_id` (`guide_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guide_comments`
--

INSERT INTO `guide_comments` (`id`, `guide_id`, `user_id`, `comment`, `created_at`, `likes`, `dislikes`) VALUES
(1, 4, 40, 'test', '2025-05-08 03:15:50', 6, 2);

-- --------------------------------------------------------

--
-- Table structure for table `guide_contents`
--

DROP TABLE IF EXISTS `guide_contents`;
CREATE TABLE IF NOT EXISTS `guide_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guide_id` int NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_paths` text,
  PRIMARY KEY (`id`),
  KEY `guide_id` (`guide_id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guide_contents`
--

INSERT INTO `guide_contents` (`id`, `guide_id`, `description`, `image_path`, `image_paths`) VALUES
(1, 2, 'Valorant features a rotation of uniquely designed maps with distinct mechanics. Here are some standout maps:\r\n', NULL, 'a:1:{i:0;s:82:\"uploads/guide/681c107c4b921_aefe3c7dbe39ef1da3f4241f4c6b771c535038fc-1920x1080.jpg\";}'),
(2, 2, 'Bind\r\n\r\nA teleport-heavy desert map that removes a traditional mid area, forcing engagements through well-defined corridors and teleporter usage.\r\n\r\nKey Mechanics:\r\n\r\nOne-Way Teleporters: Instantly transfer players between specific parts of the map, enabling quick flanks or emergency rotations.\r\n\r\nLack of Central Mid: The map structure encourages pushing through designated sites rather than splitting the defense.\r\n\r\nStrategic Notes: Requires good timing and awareness of teleporter use; often favors aggressive or deceptive playstyles.', NULL, 'a:3:{i:0;s:37:\"uploads/guide/681c121ccd321_bind2.png\";i:1;s:49:\"uploads/guide/681c121ccdcf1_Valorant-Bind-map.png\";i:2;s:58:\"uploads/guide/681c121cce6db_1734863963_mapbindvalorant.png\";}'),
(3, 2, 'Ascent\r\n\r\nAscent is one of Valorant\'s most iconic maps, set in a Venetian-inspired setting. It features a large open mid area that is critical to controlling the flow of the match.\r\n\r\nKey Mechanics:\r\n\r\nMid-Centric Layout: Mid control is vital and directly impacts access to both bomb sites.\r\n\r\nMechanical Doors: Each bomb site has a closable door that can be activated to block enemy movement or isolate fights. These doors can be destroyed over time.\r\n\r\nStrategic Notes: Ideal for teams that excel in mid-pressure, rotations, and executing site retakes.', NULL, 'a:3:{i:0;s:39:\"uploads/guide/681c12532b035_ascent3.jpg\";i:1;s:47:\"uploads/guide/681c12532b7a2_valorant_ascent.jpg\";i:2;s:58:\"uploads/guide/681c12532bdc4_Ascent-Guide-Feature-Image.jpg\";}'),
(4, 2, 'Haven\r\n\r\nA unique map located in a monastery, Haven breaks convention by offering three bomb sites instead of the usual two.\r\n\r\nKey Mechanics:\r\n\r\nThree Sites (A, B, C): Increases the need for spread-out defenses and coordinated rotations.\r\n\r\nRotational Complexity: Mid control becomes a powerful tool for attackers and defenders to pivot between sites.\r\n\r\nStrategic Notes: Defense is more challenging due to the third site; attackers can capitalize on splitting attention or over-rotations.', NULL, 'a:3:{i:0;s:38:\"uploads/guide/681c12afb6b24_haven3.jpg\";i:1;s:55:\"uploads/guide/681c12afb7101_Valorant-Haven-B-Window.jpg\";i:2;s:42:\"uploads/guide/681c12afb7820_wp14470890.jpg\";}'),
(5, 2, 'Split\r\n\r\nSet in a futuristic urban center, Split is known for its verticality and tight choke points, which promote close-quarters engagements.\r\n\r\nKey Mechanics:\r\n\r\nRopes: Allow vertical movement between levels, increasing unpredictability.\r\n\r\nHigh Ground Advantage: Many areas favor defenders holding from elevated positions.\r\n\r\nStrategic Notes: Strong site anchoring and control of choke points are essential; utilities like smokes and flashes are highly valuable.\r\n\r\n', NULL, 'a:3:{i:0;s:82:\"uploads/guide/681c12c43012e_cab3f477599adf39d494bd9fa7f31ec214f8536f-1786x1264.jpg\";i:1;s:40:\"uploads/guide/681c12c4308c2_split4-2.jpg\";i:2;s:46:\"uploads/guide/681c12c43105e_Split-VALORANT.png\";}'),
(6, 2, 'Lotus\r\n\r\nA mystical, ancient-themed map introduced with unique architecture and the second map with three bomb sites after Haven.\r\n\r\nKey Mechanics:\r\n\r\nRotating Doors: Can be activated to rotate entire walls, creating or blocking new paths.\r\n\r\nThree Sites (A, B, C): Similar to Haven, offers complex rotation opportunities and site pressures.\r\n\r\nStrategic Notes: Emphasizes creativity and timing; mastering door control and mid-space is key to victory.', NULL, 'a:3:{i:0;s:75:\"uploads/guide/681c12e1a77c6_c0f544_56a16d96e2764acda9ebe947e5312436~mv2.jpg\";i:1;s:81:\"uploads/guide/681c12e1a7def_05ea5a1b66bf52b9f64eb003903c9962b6c43136-1694x952.jpg\";i:2;s:49:\"uploads/guide/681c12e1a8549_maxresdefault (1).jpg\";}'),
(7, 2, 'Sunset\r\n\r\nOne of the newer additions, Sunset offers a Los Angeles-inspired urban backdrop with an emphasis on wide mid and duel-friendly angles.\r\n\r\nKey Mechanics:\r\n\r\nOpen Mid Engagements: Mid control determines flow and site access.\r\n\r\nSimplified Layout: Less verticality than other maps, focusing more on gunfights and tactical positioning.\r\n\r\nStrategic Notes: Ideal for teams with strong mid-round calling and flexible entry fraggers.\r\n\r\n', NULL, 'a:2:{i:0;s:47:\"uploads/guide/681c12f212299_VALORANT-Sunset.png\";i:1;s:64:\"uploads/guide/681c12f2128c7_d8a8215c2e0596f87191f14847c8012f.jpg\";}'),
(8, 2, 'Icebox\r\n\r\nA snowy, industrial map centered around tight corridors and vertical angles, demanding crisp aim and fast reactions.\r\n\r\nKey Mechanics:\r\n\r\nZiplines: Enable quick vertical movement, opening multi-layered combat scenarios.\r\n\r\nComplex Site Structures: Bomb sites are multi-leveled with lots of corners, offering many hiding and peeking spots.\r\n\r\nStrategic Notes: Requires high mechanical skill and coordination; defenders benefit from strong crossfires and elevated holds.', NULL, 'a:3:{i:0;s:60:\"uploads/guide/681c1312ce367_icebox_attacker_spawn_before.jpg\";i:1;s:82:\"uploads/guide/681c1312cecf3_79695e5eb1573890a82bd3fc57f5d6cb9f7bfde5-2560x1400.jpg\";i:2;s:44:\"uploads/guide/681c1312cf457_Icebox_Guide.jpg\";}'),
(9, 2, 'Fracture\r\n\r\nFracture breaks conventional Valorant map design with its H-shaped layout, allowing attackers to pinch defenders from both sides right from spawn.\r\n\r\nKey Mechanics:\r\n\r\nSplit Attacker Spawn: Attackers start from both A and B sides of the map, enabling flanks and complex push strategies.\r\n\r\nZiplines Under Map: Connect the two attacker spawn zones beneath the map for fast rotation.\r\n\r\nDual Defender Entrances: Each site has multiple entry points that defenders must guard.\r\n\r\nStrategic Notes: Communication and timing are critical; defenders must adapt quickly to split pushes or coordinated pinches.\r\n\r\n', NULL, 'a:3:{i:0;s:68:\"uploads/guide/681c132fb7b70_Valorant_Fracture_ADish_506-1024x576.jpg\";i:1;s:51:\"uploads/guide/681c132fb8345_valorant-fracture-6.jpg\";i:2;s:72:\"uploads/guide/681c132fb8b19_kevin-brunt-fracture-render-2560x1440-01.jpg\";}'),
(10, 2, 'Pearl\r\n\r\nPearl is Valorant’s first traditional map with no special mechanics, offering a classic lane-based design set in an underwater Portuguese city.\r\n\r\nKey Mechanics:\r\n\r\nTraditional Layout: A straightforward two-site structure (A and B) with strong emphasis on lane control.\r\n\r\nMid Control Crucial: The central area offers direct access to both bomb sites and is often the site of early engagements.\r\n\r\nStrategic Notes: Perfect for fundamental tactical play; rewards coordinated executes and clean team positioning.\r\n\r\n', NULL, 'a:3:{i:0;s:54:\"uploads/guide/681c1342c18ff_VALORANT-Pearl-Changes.jpg\";i:1;s:75:\"uploads/guide/681c1342c1ec2_c0f544_5367892dd69d4db29fb67148e16eb771~mv2.jpg\";i:2;s:53:\"uploads/guide/681c1342c260c_Valorant_Pearl_Statue.jpg\";}'),
(11, 2, 'Breeze\r\n\r\nSet on a tropical island, Breeze is the largest map in Valorant and features open spaces and long sightlines ideal for snipers and long-range weapons.\r\n\r\nKey Mechanics:\r\n\r\nWide Open Mid: Offers fast rotation potential and long-range fights.\r\n\r\nMassive Bomb Sites: Require extensive utility usage and strong post-plant setups.\r\n\r\nMultiple Entry Routes: High flanking potential on both attack and defense.\r\n\r\nStrategic Notes: Sharpshooters thrive here; effective smoke and recon utility is key to managing its size.', NULL, 'a:3:{i:0;s:71:\"uploads/guide/681c13636e1e0_valorant-episode-2-act-iii-breeze-map-2.jpg\";i:1;s:67:\"uploads/guide/681c13636e8fb_Valorant_Breeze_E7A2_Mid_1-1024x576.jpg\";i:2;s:82:\"uploads/guide/681c13636ef99_51a19717da53e0b7f8223615ab294217aca783f5-1920x1080.jpg\";}'),
(12, 2, 'Abyss\r\n\r\nAbyss is a visually striking map suspended over a bottomless void, introducing no boundaries in certain areas — making falling off the map a real possibility. It’s Valorant’s first map with environmental hazards as a core mechanic.\r\n\r\nKey Mechanics:\r\n\r\nNo Edge Barriers: Players can fall off the map if they aren\'t careful during movement or duels near the edges.\r\n\r\nTight Corridors & High-Risk Routes: Offers fast flanks and dynamic rotations but punishes careless positioning.\r\n\r\nVertical Lanes & Ledges: Emphasizes elevation control and movement precision, especially on site retakes or entries.\r\n\r\nStrategic Notes:\r\n\r\nMap awareness is vital — one wrong step and you\'re out.\r\n\r\nAgents with mobility (like Jett, Raze, or Omen) can use risky paths effectively.\r\n\r\nPushing near the edges can be high-risk, high-reward.\r\n\r\nSome choke points reward close-range utility, while others punish overextending due to drop hazards.', NULL, 'a:3:{i:0;s:47:\"uploads/guide/681c137a2c1df_GPl-o05WsAA1TXg.jpg\";i:1;s:44:\"uploads/guide/681c137a2c718_shot-02_akyk.jpg\";i:2;s:74:\"uploads/guide/681c137a2cf85_Valorant_AbyssMap_OfficialImage_2-1024x576.jpg\";}'),
(14, 4, 'Valorant operates on a seven-map rotation system for its Competitive and Premier modes. This structure keeps gameplay fresh, balanced, and strategically diverse across ranks and tournaments. The current map pool was updated with Patch 10.04 in March 2025 and reflects Riot Games\' ongoing commitment to tactical variety and competitive integrity.', NULL, 'a:1:{i:0;s:76:\"uploads/guide/681c20f5b73ed_valorant-project-gameplay-reveal-trailer (1).jpg\";}'),
(15, 4, 'Ascent \r\n\r\nAscent is one of Valorant’s most iconic maps and a staple in both ranked and professional play. Set in a floating Italian city, its defining feature is an open central area—mid—that acts as a pivotal battleground for both attackers and defenders. Controlling mid opens up flexible routes to either bomb site and allows for efficient rotations. The A and B sites are well-fortified with multiple entry points and vertical positions, requiring coordinated utility use for successful takes or retakes. Ascent rewards balanced playstyles, emphasizing map control, structured team pushes, and reactive defense strategies.\r\n\r\n', NULL, 'a:1:{i:0;s:39:\"uploads/guide/681c21086ad04_ascent3.jpg\";}'),
(16, 4, 'Fracture\r\n\r\nFracture breaks away from traditional map design by spawning attackers on opposite sides of the map, essentially “sandwiching” defenders between two fronts. Set in a futuristic research facility split by a giant crevasse, the map enables highly creative flanks, fast site executes, and surprise rotations. Its two bomb sites are accessible through multiple corridors, ziplines, and drop-downs, forcing defenders to constantly adapt and stay alert. Fracture encourages innovative strategies and is particularly suited for teams that enjoy unorthodox approaches and high-pressure multi-angle attacks.', NULL, 'a:1:{i:0;s:68:\"uploads/guide/681c21126e89f_Valorant_Fracture_ADish_506-1024x576.jpg\";}'),
(17, 4, 'Haven\r\n\r\nHaven is the only map in Valorant that features three bomb sites—A, B, and C—making it stand out in both layout and strategic complexity. Located in a tranquil monastery surrounded by the Himalayas, Haven requires teams to make quick decisions and maintain constant map awareness. Defenders are stretched thin across three sites, often relying on information-gathering agents to detect enemy movements early. Attackers benefit from well-coordinated pushes and can exploit gaps in defense with split attacks. Haven supports a wide variety of agent compositions and is known for its dynamic pacing and constant need for adaptability.', NULL, 'a:1:{i:0;s:55:\"uploads/guide/681c2120daea0_Valorant-Haven-B-Window.jpg\";}'),
(18, 4, 'Icebox\r\n\r\nIcebox brings verticality and tight engagements to the forefront, with layered bomb sites, ziplines, and high ground angles defining its icy terrain. Set in a frozen tundra shipping facility, this map demands precision in crosshair placement and strong communication for site control. Its narrow corridors lead to compact sites that reward aggressive entries and sharp post-plant setups. Icebox challenges teams with fast tempo combat and frequent close-quarters engagements, making agents with vertical mobility or strong area denial abilities (like Viper or Sage) highly effective.', NULL, 'a:1:{i:0;s:82:\"uploads/guide/681c212f41f57_79695e5eb1573890a82bd3fc57f5d6cb9f7bfde5-2560x1400.jpg\";}'),
(19, 4, 'Lotus\r\n\r\nLotus offers one of the most unique designs in Valorant, combining three bomb sites with rotating doors and destructible elements that add unpredictability and fluidity to every round. Located within mystical jungle ruins, Lotus provides multiple access routes, hidden flanking paths, and interactive mechanics that open and close passages with audible cues. Its complexity rewards teams with strong coordination and map knowledge. Attacking teams can apply pressure from several fronts, while defenders must be smart with utility and rotations. Lotus is favored for its creative potential and constantly shifting engagements.', NULL, 'a:1:{i:0;s:75:\"uploads/guide/681c213f2c0c5_c0f544_56a16d96e2764acda9ebe947e5312436~mv2.jpg\";}'),
(20, 4, 'Pearl\r\n\r\nPearl is a visually striking map set in a high-tech underwater city, designed with traditional two-site geometry but a heavy emphasis on mid control and long-range engagements. Unlike many other maps, Pearl features minimal verticality, focusing instead on wide lanes, open sightlines, and deep angles that cater to sharpshooters and precise team executes. Mid presence is often the key to unlocking both A and B sites, and slow, tactical play is often rewarded. Pearl shines in coordinated environments where utility is layered carefully to control space and isolate defenders.\r\n\r\n', NULL, 'a:1:{i:0;s:53:\"uploads/guide/681c214df1736_Valorant_Pearl_Statue.jpg\";}'),
(21, 4, 'Split\r\n\r\nSplit is a compact, high-intensity map centered around tight chokepoints, elevated mid control, and vertical zipline movement. Set in a futuristic Japanese metropolis, Split’s narrow corridors and vertically-stacked terrain force players to commit utility to entry and defense. Mid is a critical area that can tilt the momentum in either team’s favor, and both sites require well-timed executes or layered defense setups. With limited flanking routes and choke-heavy structure, Split is ideal for agents who can block vision, create space, or capitalize on isolated duels.\r\n\r\n', NULL, 'a:1:{i:0;s:40:\"uploads/guide/681c216a7e449_split4-2.jpg\";}'),
(22, 5, 'asd', NULL, 'a:0:{}'),
(23, 5, 'asd', NULL, 'a:0:{}'),
(24, 5, 'asd', NULL, 'a:0:{}');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('message','comment','like','reply','friend_request','friend_accept') NOT NULL,
  `content` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `content`, `is_read`, `created_at`) VALUES
(1, 41, 'comment', ' commented on your post', 0, '2025-05-03 17:07:30'),
(2, 40, 'comment', ' commented on your post', 0, '2025-05-03 17:07:34'),
(3, 38, 'comment', ' commented on your post', 0, '2025-05-03 17:07:55');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

DROP TABLE IF EXISTS `post_likes`;
CREATE TABLE IF NOT EXISTS `post_likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_no` int NOT NULL,
  `post_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_no`,`post_id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `user_no`, `post_id`) VALUES
(21, 40, 33),
(5, 40, 13),
(38, 40, 42),
(12, 41, 18),
(22, 40, 28),
(30, 40, 38),
(27, 40, 16),
(29, 40, 40),
(34, 40, 41),
(37, 40, 12),
(39, 38, 41),
(47, 38, 43),
(43, 38, 42),
(46, 40, 37),
(50, 53, 42);

-- --------------------------------------------------------

--
-- Table structure for table `private_messages`
--

DROP TABLE IF EXISTS `private_messages`;
CREATE TABLE IF NOT EXISTS `private_messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `message_content` text NOT NULL,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_messages`
--

INSERT INTO `private_messages` (`message_id`, `sender_id`, `recipient_id`, `message_content`, `sent_at`, `image_path`) VALUES
(1, 38, 40, 'Hey bro!', '2025-04-26 23:25:31', NULL),
(2, 40, 38, 'Wassup!', '2025-04-26 23:26:13', NULL),
(3, 40, 40, 'Hey bro!', '2025-04-26 23:26:15', NULL),
(4, 40, 40, 'Hey bro!', '2025-04-26 23:26:16', NULL),
(5, 40, 40, 'Hey bro!', '2025-04-26 23:26:17', NULL),
(6, 40, 40, 'Hey bro!', '2025-04-26 23:26:17', NULL),
(7, 40, 40, 'Hey bro!', '2025-04-26 23:26:17', NULL),
(8, 40, 40, 'Hey bro!', '2025-04-26 23:26:17', NULL),
(11, 40, 38, '', '2025-04-26 23:32:33', 'src/uploads/messages/680cfc9140156.gif'),
(18, 40, 38, 'Test', '2025-05-03 05:25:46', ''),
(17, 40, 38, 'sick post', '2025-04-26 23:34:05', '');

-- --------------------------------------------------------

--
-- Table structure for table `review_reactions`
--

DROP TABLE IF EXISTS `review_reactions`;
CREATE TABLE IF NOT EXISTS `review_reactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `review_id` int NOT NULL,
  `reaction_type` enum('like','dislike') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_review` (`user_id`,`review_id`),
  KEY `review_id` (`review_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_reactions`
--

INSERT INTO `review_reactions` (`id`, `user_id`, `review_id`, `reaction_type`) VALUES
(1, 40, 1, 'like'),
(2, 38, 2, 'like');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_no` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('user','moderator','admin') NOT NULL DEFAULT 'user',
  `email` varchar(50) NOT NULL,
  `bio` text,
  `verification_code` varchar(8) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`user_no`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_no`, `username`, `first_name`, `last_name`, `password`, `birthday`, `date_created`, `profile_picture`, `role`, `email`, `bio`, `verification_code`, `verified`) VALUES
(1, 'admin', 'admin', 'admin', '$2y$10$EZ4dAlWBj82qAWpeSQ2.R.D8WzEh0FUraOx/PB9UV7b', '2025-04-03', '2025-03-07 06:36:33', 'default.jpg', 'user', 'vnoir17@gmail.com', 'skbidiSkbiidisad', NULL, 0),
(40, 'GameGrind', 'Game', 'Sense', '$2y$10$N1VfB8zABWe3VPavnctll.AWBx1o9PJQyEEFdjgKw0qZcaQf/bozi', '2025-04-30', '2025-04-23 17:58:30', '680b167b411d7_blackwhite.png', 'moderator', 'gg.gamegrind@gmail.com', '', NULL, 0),
(41, 'callmeaiene', 'Ailene', 'Aquino', '$2y$10$nnHFZyK04zBrtVf9LNhF8.1zd/sPW5ZbQ0ajvhkeVfJpaCbxYe7oa', '2017-06-13', '2025-05-02 19:17:18', 'default-profile.png', 'user', 'skibidi@gmail.com', 'skibid', NULL, 0),
(38, 'miao', 'miao', 'miao', '$2y$10$dA4K.Zo8vME/Ba4NbS5lfe/GsEcq40lwlMQwduNgZdIszVJDCpUOW', '2006-03-15', '2025-04-15 06:29:03', '680cf7b56128c.png', 'user', 'teakeroppi@gmail.com', 'hope\r\n', NULL, 0),
(37, 'yourUsername', 'First Name', 'Lastname', '$2y$10$XjP/WuYyEyJB0swMQ4qUc.a6MPESNojDPOYviNFvLGJG7/5QNBgnG', '2025-03-13', '2025-03-07 14:36:52', '67cb753f5be9d_Screenshot 2024-11-18 214346.png', 'user', 'email@gmail.com', 'OTUAFUAFOASFgd', NULL, 0),
(52, 'luwi', 'luwi', 'luwi', '$2y$10$7w7YxT1V5ZRVEW7jCRDkDeRnPX3VPOfVK31huc3hOwDvQ4XmV24n6', '2025-06-02', '2025-05-08 07:02:53', 'default-profile.png', 'user', 'xsurethingx@gmail.com', NULL, 'e0cf0283', 1),
(53, 'sen', 'sen', 'sen', '$2y$10$nsOvhw4TZh5kGjB1rfZ2GOTAtGNCWOblRaj2nGaxb8RGz5vFszPdW', '2005-11-16', '2026-02-18 06:48:34', 'default-profile.png', 'user', 'naonaoshi17@gmail.com', NULL, '6894ea2d', 0),
(54, '123', '123', '123', '$2y$10$Mx3vUMk4s/3IBl8izfVg2u942cLHT6nvpnB.WD1h7buAb0gngzsIi', '1111-11-11', '2026-03-11 18:48:01', 'default-profile.png', 'moderator', '123@gmail.com', NULL, 'ee9b09e1', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_post`
--

DROP TABLE IF EXISTS `user_post`;
CREATE TABLE IF NOT EXISTS `user_post` (
  `post_ID` int NOT NULL AUTO_INCREMENT,
  `user_no` int DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `caption` text,
  `post_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `post_type` enum('text','image','video') DEFAULT NULL,
  `game` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `content` text,
  `post_media` varchar(255) DEFAULT NULL,
  `likes` int DEFAULT '0',
  `comments` text,
  `media_comments` text,
  PRIMARY KEY (`post_ID`),
  KEY `user_no` (`user_no`),
  KEY `game` (`game`)
) ENGINE=MyISAM AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_post`
--

INSERT INTO `user_post` (`post_ID`, `user_no`, `username`, `caption`, `post_date`, `post_type`, `game`, `created_at`, `content`, `post_media`, `likes`, `comments`, `media_comments`) VALUES
(13, 40, 'GameGrind', NULL, '2025-04-29 06:15:31', NULL, NULL, '2025-04-29 06:15:31', 'simple space', '2obw9yylddr51.jpg', 1, '', ''),
(8, 40, 'GameGrind', NULL, '2025-04-25 04:45:27', NULL, NULL, '2025-04-25 04:45:27', 'New post', 'tumblr_1a30eec5a7f81e7c61cf705ae4b86c0d_6b7dc487_540.gif', 0, '', ''),
(10, 38, 'miao', NULL, '2025-04-25 05:19:28', NULL, NULL, '2025-04-25 05:19:28', 'MINECRAFT\r\n', 'Hz_4Bqb0tdXtWlcX.mp4', 0, '', ''),
(12, 38, 'miao', NULL, '2025-04-25 14:24:33', NULL, NULL, '2025-04-25 14:24:33', 'Cutie <3', 'GlaNckQWEAAxQb_.jpg', 1, '', ''),
(28, 40, 'GameGrind', NULL, '2025-05-02 20:49:27', NULL, NULL, '2025-05-02 20:49:27', 'AHA', NULL, 1, '', ''),
(16, 40, 'GameGrind', NULL, '2025-05-02 12:51:50', NULL, NULL, '2025-05-02 12:51:50', 'Best game', NULL, 1, '', ''),
(18, 40, 'GameGrind', NULL, '2025-05-02 13:35:06', NULL, NULL, '2025-05-02 13:35:06', 'HEY', 'media_6814ca0a36b0f6.78240873.jpg', 1, '', ''),
(37, 40, 'GameGrind', NULL, '2025-05-02 21:03:08', NULL, NULL, '2025-05-02 21:03:08', 'Fun Game!', 'media_6815330ce13e41.47432084.mp4', 1, '', ''),
(33, 40, 'GameGrind', NULL, '2025-05-02 20:58:44', NULL, NULL, '2025-05-02 20:58:44', 's', 'media_68153204b8ed12.67458101.mp4', 1, '', ''),
(40, 41, NULL, NULL, '2025-05-03 03:18:10', NULL, NULL, '2025-05-03 03:18:10', 'skibidi', NULL, 1, '', ''),
(38, 40, 'GameGrind', NULL, '2025-05-03 02:07:11', NULL, NULL, '2025-05-03 02:07:11', 'hello', NULL, 1, '', ''),
(41, 40, 'GameGrind', NULL, '2025-05-03 09:14:15', NULL, NULL, '2025-05-03 09:14:15', 'Yes', NULL, 2, '', ''),
(42, 40, 'GameGrind', NULL, '2025-05-04 14:15:22', NULL, NULL, '2025-05-04 14:15:22', 'Test', NULL, 3, '', ''),
(43, 38, 'miao', NULL, '2025-05-05 09:28:20', NULL, NULL, '2025-05-05 09:28:20', '@miao ', NULL, 1, '', ''),
(44, 54, NULL, NULL, '2026-03-12 02:48:19', NULL, NULL, '2026-03-12 02:48:19', 'panget', NULL, 0, '', ''),
(45, 54, NULL, NULL, '2026-03-12 02:48:38', NULL, NULL, '2026-03-12 02:48:38', '1231231', NULL, 0, '', '');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
