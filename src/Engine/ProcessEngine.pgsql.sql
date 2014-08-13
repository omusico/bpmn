
DROP TABLE IF EXISTS `#__bpm_process_subscription`;
DROP TABLE IF EXISTS `#__bpm_event_subscription`;
DROP TABLE IF EXISTS `#__bpm_user_task`;
DROP TABLE IF EXISTS `#__bpm_execution`;
DROP TABLE IF EXISTS `#__bpm_process_definition`;

CREATE TABLE `#__bpm_process_definition` (
	`id` character(32) NOT NULL,
	`process_key` varchar(250) NOT NULL,
	`revision` integer NOT NULL,
	`definition` bytea NOT NULL,
	`name` varchar(250) NOT NULL,
	`deployed_at` integer NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`process_key`, `revision`)
);

CREATE TABLE `#__bpm_process_subscription` (
	`id` character(32) NOT NULL,
	`definition_id` character(32) NOT NULL,
	`flags` integer NOT NULL,
	`name` varchar(250) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`definition_id`, `name`),
	FOREIGN KEY (`definition_id`) REFERENCES `#__bpm_process_definition` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_process_subscription_lookup` ON `#__bpm_process_subscription` (`name`, `flags`);

CREATE TABLE `#__bpm_execution` (
	`id` character(32) NOT NULL,
	`pid` character(32) NULL,
	`process_id` character(32) NULL,
	`definition_id` character(32) NOT NULL,
	`state` integer NOT NULL,
	`active` real NOT NULL,
	`node` varchar(250) NULL,
	`transition` varchar(250) NULL,
	`depth` integer NOT NULL,
	`business_key` varchar(250) NULL,
	`vars` bytea NOT NULL,
	PRIMARY KEY (`id`),
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
	`id` character(32) NOT NULL,
	`execution_id` character(32) NOT NULL,
	`activity_id` varchar(250) NOT NULL,
	`node` varchar(250) NULL,
	`process_instance_id` character(32) NOT NULL,
	`flags` integer NOT NULL,
	`name` varchar(250) NOT NULL,
	`created_at` integer NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_instance_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_event_subscription_execution_id` ON `#__bpm_event_subscription` (`execution_id`, `activity_id`);
CREATE INDEX `#__bpm_event_subscription_process_instance_id` ON `#__bpm_event_subscription` (`process_instance_id`);
CREATE INDEX `#__bpm_event_subscription_lookup` ON `#__bpm_event_subscription` (`name`, `flags`);

CREATE TABLE `#__bpm_user_task` (
	`id` character(32) NOT NULL,
	`execution_id` character(32) NOT NULL,
	`name` varchar(250) NOT NULL,
	`documentation` TEXT NULL,
	`activity` varchar(250) NOT NULL,
	`created_at` integer NOT NULL,
	`claimed_at` integer NULL,
	`claimed_by` varchar(250) NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`execution_id`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_user_task_created_at` ON `#__bpm_user_task` (`created_at`);
CREATE INDEX `#__bpm_user_task_activity` ON `#__bpm_user_task` (`activity`);
