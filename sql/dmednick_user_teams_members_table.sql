CREATE TABLE `projectx`.`user_teams_members`
(
    `id`       INT          NOT NULL AUTO_INCREMENT,
    `team_id`  INT(8)       NOT NULL,
    `user_id`  INT          NOT NULL,
    `username` VARCHAR(128) NOT NULL,
    PRIMARY KEY (`id`(10))
) ENGINE = InnoDB;