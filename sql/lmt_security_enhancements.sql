ALTER TABLE `logins` ADD `time_last_action` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `time`;
ALTER TABLE `users` ADD `previous_passwords` TEXT NULL AFTER `feat_agent_tracker`;
ALTER TABLE `users` ADD `changedpw_time` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `modifiedby_userid`;
