-- @package     External_Login
-- @subpackage  Component
-- @author      Christophe Demko <chdemko@gmail.com>
-- @author      Ioannis Barounis <contact@johnbarounis.com>
-- @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
-- @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
-- @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
-- @link        https://github.com/akunzai/joomla-external-login

CREATE TABLE IF NOT EXISTS `#__externallogin_servers` (
	`id` int NOT NULL AUTO_INCREMENT,
	`title` varchar(128) NOT NULL,
	`published` tinyint NOT NULL DEFAULT 0,
	`plugin` varchar(128) NOT NULL,
	`ordering` int NOT NULL DEFAULT 0,
	`checked_out` int unsigned,
	`checked_out_time` datetime,
	`params` text NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__externallogin_users` (
	`server_id` int NOT NULL,
	`user_id` int NOT NULL,
	INDEX (`server_id`),
	UNIQUE (`user_id`),
	UNIQUE (`server_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__externallogin_logs` (
	`priority` int NOT NULL DEFAULT 0,
	`category` varchar(128) NOT NULL,
	`date` decimal(20, 6) NOT NULL,
	`message` mediumtext NOT NULL,
	INDEX (`priority`),
	INDEX (`category`),
	INDEX (`date`),
	INDEX (`message`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__users` ADD INDEX `idx_externallogin` (`password`);
