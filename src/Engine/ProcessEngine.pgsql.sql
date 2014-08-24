
DROP TABLE IF EXISTS `#__process_subscription`;
DROP TABLE IF EXISTS `#__event_subscription`;
DROP TABLE IF EXISTS `#__user_task`;
DROP TABLE IF EXISTS `#__execution_variables`;
DROP TABLE IF EXISTS `#__execution`;
DROP TABLE IF EXISTS `#__process_definition`;

CREATE TABLE `#__process_definition` (
	`id` character(32) NOT NULL,
	`process_key` varchar(250) NOT NULL,
	`revision` integer NOT NULL,
	`definition` bytea NOT NULL,
	`name` varchar(250) NOT NULL,
	`deployed_at` integer NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`process_key`, `revision`)
);

CREATE TABLE `#__process_subscription` (
	`id` character(32) NOT NULL,
	`definition_id` character(32) NOT NULL,
	`flags` integer NOT NULL,
	`name` varchar(250) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`definition_id`, `name`),
	FOREIGN KEY (`definition_id`) REFERENCES `#__process_definition` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__process_subscription_lookup` ON `#__process_subscription` (`name`, `flags`);

CREATE TABLE `#__execution` (
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
	PRIMARY KEY (`id`),
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

CREATE TABLE `#__execution_variables` (
	`execution_id` character(32) NOT NULL,
	`name` varchar(150) NOT NULL,
	`value` varchar(250) NULL,
	`value_blob` bytea NOT NULL,
	PRIMARY KEY (`execution_id`, `name`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__execution_variables_lookup` ON `#__execution_variables` (`name`, `value`) WHERE `value` IS NOT NULL;

CREATE TABLE `#__event_subscription` (
	`id` character(32) NOT NULL,
	`execution_id` character(32) NOT NULL,
	`activity_id` varchar(250) NOT NULL,
	`node` varchar(250) NULL,
	`process_instance_id` character(32) NOT NULL,
	`flags` integer NOT NULL,
	`name` varchar(250) NOT NULL,
	`created_at` integer NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`process_instance_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__event_subscription_execution_id` ON `#__event_subscription` (`execution_id`, `activity_id`);
CREATE INDEX `#__event_subscription_process_instance_id` ON `#__event_subscription` (`process_instance_id`);
CREATE INDEX `#__event_subscription_lookup` ON `#__event_subscription` (`name`, `flags`);

CREATE TABLE `#__user_task` (
	`id` character(32) NOT NULL,
	`execution_id` character(32) NOT NULL,
	`name` varchar(250) NOT NULL,
	`documentation` text NULL,
	`activity` varchar(250) NOT NULL,
	`created_at` integer NOT NULL,
	`claimed_at` integer NULL,
	`claimed_by` varchar(250) NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`execution_id`),
	FOREIGN KEY (`execution_id`) REFERENCES `#__execution` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX `#__user_task_created_at` ON `#__user_task` (`created_at`);
CREATE INDEX `#__user_task_activity` ON `#__user_task` (`activity`);
CREATE INDEX `#__user_task_assignee` ON `#__user_task` (`claimed_by`) WHERE `claimed_by` IS NOT NULL;
