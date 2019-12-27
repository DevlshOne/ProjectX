CREATE TABLE `process_tracker_schedules` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `enabled` enum('yes','no') NOT NULL DEFAULT 'no',
 `schedule_name` varchar(128) DEFAULT NULL,
 `script_process_code` varchar(64) DEFAULT NULL,
 `script_frequency` enum('hourly','daily','weekly','monthly') DEFAULT NULL,
 `time_start` time NOT NULL DEFAULT '00:00:00',
 `time_end` time NOT NULL DEFAULT '00:00:00',
 `time_dayofweek` varchar(27) DEFAULT NULL,
 `time_dayofmonth` int(5) unsigned NOT NULL DEFAULT '0',
 `time_margin` int(5) unsigned NOT NULL DEFAULT '0',
 `notification_email` varchar(256) DEFAULT NULL,
 `last_success` int(10) unsigned NOT NULL DEFAULT '0',
 `last_failed` int(10) unsigned NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1
