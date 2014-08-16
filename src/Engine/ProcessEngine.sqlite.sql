
DROP TABLE IF EXISTS `#__bpm_process_subscription`;
DROP TABLE IF EXISTS `#__bpm_event_subscription`;
DROP TABLE IF EXISTS `#__bpm_user_task`;
DROP TABLE IF EXISTS `#__bpm_execution`;
DROP TABLE IF EXISTS `#__bpm_process_definition`;

CREATE TABLE `#__bpm_process_definition` (
	`id` TEXT PRIMARY KEY,
	`process_key` TEXT NOT NULL,
	`revision` INTEGER NOT NULL,
	`definition` BLOB NOT NULL,
	`name` TEXT NOT NULL,
	`deployed_at` INTEGER NOT NULL
);

CREATE UNIQUE INDEX `#__bpm_process_definition_versioning` ON `#__bpm_process_definition` (`process_key`, `revision`);

CREATE TABLE `#__bpm_process_subscription` (
	`id` TEXT PRIMARY KEY,
	`definition_id` TEXT NOT NULL,
	`flags` INTEGER NOT NULL,
	`name` TEXT NOT NULL,
	FOREIGN KEY (`definition_id`) REFERENCES `#__bpm_process_definition` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE UNIQUE INDEX `#__bpm_process_subscription_integrity` ON `#__bpm_process_subscription` (`definition_id`, `name`);
CREATE INDEX `#__bpm_process_subscription_lookup` ON `#__bpm_process_subscription` (`name`, `flags`);

CREATE TABLE `#__bpm_execution` (
	`id` TEXT PRIMARY KEY,
	`pid` TEXT NULL,
	`process_id` TEXT NULL,
	`definition_id` TEXT NOT NULL,
	`state` INTEGER NOT NULL,
	`active` REAL NOT NULL,
	`node` TEXT NULL,
	`transition` TEXT NULL,
	`depth` INTEGER NOT NULL,
	`business_key` TEXT NULL,
	`vars` BLOB NOT NULL,
	FOREIGN KEY (`definition_id`) REFERENCES `#__bpm_process_definition` (`id`) ON UPDATE CASCADE,
	FOREIGN KEY (`pid`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_execution_pid` ON `#__bpm_execution` (`pid`);
CREATE INDEX `#__bpm_execution_definition_id` ON `#__bpm_execution` (`definition_id`);
CREATE INDEX `#__bpm_execution_process_id` ON `#__bpm_execution` (`process_id`);
CREATE INDEX `#__bpm_execution_active` ON `#__bpm_execution` (`active`);
CREATE INDEX `#__bpm_execution_business_key` ON `#__bpm_execution` (`business_key`);
CREATE INDEX `#__bpm_execution_node` ON `#__bpm_execution` (`node`);

CREATE TABLE `#__bpm_event_subscription` (
	`id` TEXT PRIMARY KEY,
	`execution_id` TEXT NOT NULL,
	`activity_id` TEXT NOT NULL,
	`node` TEXT NULL,
	`process_instance_id` TEXT NOT NULL,
	`flags` INTEGER NOT NULL,
	`name` TEXT NOT NULL,
	`created_at` INTEGER NOT NULL,
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_instance_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_event_subscription_execution_id` ON `#__bpm_event_subscription` (`execution_id`, `activity_id`);
CREATE INDEX `#__bpm_event_subscription_process_instance_id` ON `#__bpm_event_subscription` (`process_instance_id`);
CREATE INDEX `#__bpm_event_subscription_lookup` ON `#__bpm_event_subscription` (`name`, `flags`);

CREATE TABLE `#__bpm_user_task` (
	`id` TEXT PRIMARY KEY,
	`execution_id` TEXT NOT NULL,
	`name` TEXT NOT NULL,
	`documentation` TEXT NULL,
	`activity` TEXT NOT NULL,
	`created_at` INTEGER NOT NULL,
	`claimed_at` INTEGER NULL,
	`claimed_by` TEXT NULL,
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_user_task_created_at` ON `#__bpm_user_task` (`created_at`);
CREATE UNIQUE INDEX `#__bpm_user_task_execution_id` ON `#__bpm_user_task` (`execution_id`);
CREATE INDEX `#__bpm_user_task_activity` ON `#__bpm_user_task` (`activity`);
