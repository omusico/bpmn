
DROP TABLE IF EXISTS `#__process_subscription`;
DROP TABLE IF EXISTS `#__event_subscription`;
DROP TABLE IF EXISTS `#__user_task`;
DROP TABLE IF EXISTS `#__execution`;
DROP TABLE IF EXISTS `#__process_definition`;

CREATE TABLE `#__process_definition` (
	`id` TEXT PRIMARY KEY,
	`process_key` TEXT NOT NULL,
	`revision` INTEGER NOT NULL,
	`definition` BLOB NOT NULL,
	`name` TEXT NOT NULL,
	`deployed_at` INTEGER NOT NULL
);

CREATE UNIQUE INDEX `#__process_definition_versioning` ON `#__process_definition` (`process_key`, `revision`);

CREATE TABLE `#__process_subscription` (
	`id` TEXT PRIMARY KEY,
	`definition_id` TEXT NOT NULL,
	`flags` INTEGER NOT NULL,
	`name` TEXT NOT NULL,
	FOREIGN KEY (`definition_id`) REFERENCES `#__process_definition` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE UNIQUE INDEX `#__process_subscription_integrity` ON `#__process_subscription` (`definition_id`, `name`);
CREATE INDEX `#__process_subscription_lookup` ON `#__process_subscription` (`name`, `flags`);

CREATE TABLE `#__execution` (
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
	FOREIGN KEY (`definition_id`) REFERENCES `#__process_definition` (`id`) ON UPDATE CASCADE,
	FOREIGN KEY (`pid`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__execution_pid` ON `#__execution` (`pid`);
CREATE INDEX `#__execution_definition_id` ON `#__execution` (`definition_id`);
CREATE INDEX `#__execution_process_id` ON `#__execution` (`process_id`);
CREATE INDEX `#__execution_active` ON `#__execution` (`active`);
CREATE INDEX `#__execution_business_key` ON `#__execution` (`business_key`);
CREATE INDEX `#__execution_node` ON `#__execution` (`node`);

CREATE TABLE `#__event_subscription` (
	`id` TEXT PRIMARY KEY,
	`execution_id` TEXT NOT NULL,
	`activity_id` TEXT NOT NULL,
	`node` TEXT NULL,
	`process_instance_id` TEXT NOT NULL,
	`flags` INTEGER NOT NULL,
	`name` TEXT NOT NULL,
	`created_at` INTEGER NOT NULL,
	FOREIGN KEY (`execution_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_instance_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__event_subscription_execution_id` ON `#__event_subscription` (`execution_id`, `activity_id`);
CREATE INDEX `#__event_subscription_process_instance_id` ON `#__event_subscription` (`process_instance_id`);
CREATE INDEX `#__event_subscription_lookup` ON `#__event_subscription` (`name`, `flags`);

CREATE TABLE `#__user_task` (
	`id` TEXT PRIMARY KEY,
	`execution_id` TEXT NOT NULL,
	`name` TEXT NOT NULL,
	`documentation` TEXT NULL,
	`activity` TEXT NOT NULL,
	`created_at` INTEGER NOT NULL,
	`claimed_at` INTEGER NULL,
	`claimed_by` TEXT NULL,
	FOREIGN KEY (`execution_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__user_task_created_at` ON `#__user_task` (`created_at`);
CREATE UNIQUE INDEX `#__user_task_execution_id` ON `#__user_task` (`execution_id`);
CREATE INDEX `#__user_task_activity` ON `#__user_task` (`activity`);
