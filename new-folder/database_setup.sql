-- Educational Game System Database Tables
-- Execute this SQL to create the required tables

-- 1. Educational Games table - stores game metadata and content
CREATE TABLE `educational_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `game_type` enum('quiz','matching') NOT NULL,
  `game_content` longtext NOT NULL COMMENT 'JSON structure containing game data',
  `class_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'Staff ID who created the game',
  `max_attempts` int(11) DEFAULT 3,
  `time_limit` int(11) DEFAULT NULL COMMENT 'Time limit in minutes, NULL for no limit',
  `points_per_question` int(11) DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `difficulty_level` enum('easy','medium','hard') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_class_section` (`class_id`,`section_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_game_type` (`game_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 2. Game Results table - stores individual game attempt results
CREATE TABLE `game_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_session_id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `total_questions` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL DEFAULT 0,
  `time_taken` int(11) DEFAULT NULL COMMENT 'Time taken in seconds',
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `game_data` text COMMENT 'JSON data of student answers and performance',
  `completed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_game_student` (`game_id`,`student_id`),
  KEY `idx_student_session` (`student_session_id`),
  KEY `idx_completed_at` (`completed_at`),
  FOREIGN KEY (`game_id`) REFERENCES `educational_games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 3. Student Points table - tracks overall student gaming progress
CREATE TABLE `student_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `student_session_id` int(11) NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `current_level` int(11) NOT NULL DEFAULT 1,
  `points_to_next_level` int(11) NOT NULL DEFAULT 10,
  `games_played` int(11) NOT NULL DEFAULT 0,
  `games_completed` int(11) NOT NULL DEFAULT 0,
  `average_score` decimal(5,2) DEFAULT 0.00,
  `best_score` int(11) DEFAULT 0,
  `total_time_played` int(11) DEFAULT 0 COMMENT 'Total time in seconds',
  `achievements` text COMMENT 'JSON array of earned achievements',
  `last_played` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_session` (`student_id`,`student_session_id`),
  KEY `idx_total_points` (`total_points`),
  KEY `idx_current_level` (`current_level`),
  KEY `idx_last_played` (`last_played`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 4. Add Game Builder permissions to permission_group table
INSERT INTO `permission_group` (`name`, `short_code`, `system`, `sort_order`, `is_active`)
VALUES ('Game Builder', 'game_builder', 0, 100, 1);

-- Get the permission group ID for game_builder
SET @game_group_id = LAST_INSERT_ID();

-- 5. Add Game Builder permission categories
INSERT INTO `permission_category` (`perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(@game_group_id, 'Games Management', 'games_management', 1, 1, 1, 1, NOW()),
(@game_group_id, 'Game Results', 'game_results', 1, 0, 0, 0, NOW()),
(@game_group_id, 'Student Gaming', 'student_gaming', 1, 0, 0, 0, NOW());

-- 6. Add Student Games permissions to permission_group table
INSERT INTO `permission_group` (`name`, `short_code`, `system`, `sort_order`, `is_active`)
VALUES ('Student Games', 'student_games', 0, 101, 1);

-- Get the permission group ID for student_games
SET @student_game_group_id = LAST_INSERT_ID();

-- 7. Add Student Games permission categories
INSERT INTO `permission_category` (`perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(@student_game_group_id, 'Play Games', 'play_games', 1, 0, 0, 0, NOW()),
(@student_game_group_id, 'View Results', 'view_game_results', 1, 0, 0, 0, NOW()),
(@student_game_group_id, 'Leaderboard', 'game_leaderboard', 1, 0, 0, 0, NOW());

-- 8. Create indexes for better performance
CREATE INDEX `idx_educational_games_class_section_active` ON `educational_games` (`class_id`, `section_id`, `is_active`);
CREATE INDEX `idx_game_results_student_game` ON `game_results` (`student_id`, `game_id`, `completed_at`);
CREATE INDEX `idx_student_points_leaderboard` ON `student_points` (`total_points` DESC, `current_level` DESC);