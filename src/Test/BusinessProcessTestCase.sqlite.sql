
CREATE TABLE `#__bpm_process_definition` (
	`id` BINARY(16) NOT NULL,
	`process_key` VARCHAR(250) NOT NULL,
	`revision` INT UNSIGNED NOT NULL,
	`definition` LONGBLOB NOT NULL,
	`name` VARCHAR(250) NOT NULL,
	`deployed_at` INT UNSIGNED NOT NULL,
	PRIMARY KEY (`id`)
);

CREATE UNIQUE INDEX `#__bpm_process_definition_versioning` ON `#__bpm_process_definition` (`process_key`, `revision`);

CREATE TABLE `#__bpm_process_subscription` (
	`id` BINARY(16) NOT NULL,
	`definition_id` BINARY(16) NOT NULL,
	`flags` INT UNSIGNED NOT NULL,
	`name` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`definition_id`) REFERENCES `#__bpm_process_definition` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE UNIQUE INDEX `#__bpm_process_subscription_integrity` ON `#__bpm_process_subscription` (`definition_id`, `name`);

CREATE TABLE `#__bpm_execution` (
	`id` BINARY(16) NOT NULL,
	`pid` BINARY(16) NULL,
	`process_id` BINARY(16) NULL,
	`definition_id` BINARY(16) NOT NULL,
	`state` DOUBLE UNSIGNED NOT NULL,
	`active` INT UNSIGNED NOT NULL,
	`node` VARCHAR(250) NULL,
	`transition` VARCHAR(250) NULL,
	`depth` INT UNSIGNED NOT NULL,
	`business_key` VARCHAR(250) NULL,
	`vars` LONGBLOB NOT NULL,
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
	`id` BINARY(16) NOT NULL,
	`execution_id` BINARY(16) NOT NULL,
	`activity_id` VARCHAR(250) NOT NULL,
	`node` VARCHAR(250) NULL,
	`process_instance_id` BINARY(16) NOT NULL,
	`flags` INT UNSIGNED NOT NULL,
	`name` VARCHAR(250) NOT NULL,
	`created_at` INT UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_instance_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_event_subscription_execution_id` ON `#__bpm_event_subscription` (`execution_id`, `activity_id`);
CREATE INDEX `#__bpm_event_subscription_process_instance_id` ON `#__bpm_event_subscription` (`process_instance_id`);
CREATE INDEX `#__bpm_event_subscription_lookup` ON `#__bpm_event_subscription` (`name`, `flags`);

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
	FOREIGN KEY (`execution_id`) REFERENCES `#__bpm_execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__bpm_user_task_created_at` ON `#__bpm_user_task` (`created_at`);
CREATE UNIQUE INDEX `#__bpm_user_task_execution_id` ON `#__bpm_user_task` (`execution_id`);
CREATE INDEX `#__bpm_user_task_activity` ON `#__bpm_user_task` (`activity`);
