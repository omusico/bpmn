
DROP TABLE IF EXISTS `#__bpm_process_subscription`;
DROP TABLE IF EXISTS `#__bpm_event_subscription`;
DROP TABLE IF EXISTS `#__bpm_user_task`;
DROP TABLE IF EXISTS `#__bpm_execution`;
DROP TABLE IF EXISTS `#__bpm_process_definition`;

CREATE TABLE `#__bpm_process_definition` (
	`id` BINARY(16) NOT NULL,
	`process_key` VARCHAR(250) NOT NULL,
	`revision` INT UNSIGNED NOT NULL,
	`definition` LONGBLOB NOT NULL,
	`name` VARCHAR(250) NOT NULL,
	`deployed_at` INT UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `#__bpm_process_definition_versioning` (`process_key`, `revision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `#__bpm_process_subscription` (
	`id` BINARY(16) NOT NULL,
	`definition_id` BINARY(16) NOT NULL,
	`flags` INT UNSIGNED NOT NULL,
	`name` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `#__bpm_process_subscription_integrity` (`definition_id`, `name`),
	INDEX `#__bpm_process_subscription_lookup` (`name`, `flags`),
	FOREIGN KEY (`definition_id`) REFERENCES `#__bpm_process_definition` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `#__bpm_execution` (
	`id` BINARY(16) NOT NULL,
	`pid` BINARY(16) NULL,
	`process_id` BINARY(16) NULL,
	`definition_id` BINARY(16) NOT NULL,
	`state` INT UNSIGNED NOT NULL,
	`active` DOUBLE NOT NULL,
	`node` VARCHAR(250) NULL,
	`transition` VARCHAR(250) NULL,
	`depth` INT UNSIGNED NOT NULL,
	`business_key` VARCHAR(250) NULL,
	`vars` LONGBLOB NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `#__bpm_execution_pid` (`pid`),
	INDEX `#__bpm_execution_definition_id` (`definition_id`),
	INDEX `#__bpm_execution_process_id` (`process_id`),
	INDEX `#__bpm_execution_active` (`active`),
	INDEX `#__bpm_execution_business_key` (`business_key`),
	INDEX `#__bpm_execution_node` (`node`),
	FOREIGN KEY (`definition_id`) REFERENCES `#__bpm_process_definition` (`id`) ON UPDATE CASCADE,
	FOREIGN KEY (`pid`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `#__bpm_event_subscription` (
	`id` BINARY(16) NOT NULL,
	`execution_id` BINARY(16) NOT NULL,
	`activity_id` VARCHAR(250) NOT NULL,
	`node` VARCHAR(250) NULL,
	`process_instance_id` BINARY(16) NOT NULL,
	`flags` INT UNSIGNED NOT NULL,
	`name` VARCHAR(250) NOT NULL,
	`created_at` INT UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `#__bpm_event_subscription_execution_id` (`execution_id`, `activity_id`),
	INDEX `#__bpm_event_subscription_process_instance_id` (`process_instance_id`),
	INDEX `#__bpm_event_subscription_lookup` (`name`, `flags`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_instance_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `#__bpm_user_task` (
	`id` BINARY(16) NOT NULL,
	`execution_id` BINARY(16) NOT NULL,
	`name` VARCHAR(250) NOT NULL,
	`documentation` TEXT NULL,
	`activity` VARCHAR(250) NOT NULL,
	`created_at` INT UNSIGNED NOT NULL,
	`claimed_at` INT UNSIGNED NULL,
	`claimed_by` VARCHAR(250) NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `#__bpm_user_task_execution_id` (`execution_id`),
	INDEX `#__bpm_user_task_created_at` (`created_at`),
	INDEX `#__bpm_user_task_activity` (`activity`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
