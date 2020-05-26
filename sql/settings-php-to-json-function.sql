UPDATE `report_emails` AS `destination`,
(
    SELECT
    id,
    @num_settings := 0 + LENGTH(settings) - LENGTH(REPLACE(settings, ';', '')) AS num_settings,
    JSON_ARRAY(
        REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 1), ' = ', 1), '$', ''),
    TRIM(BOTH '"' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 1), ' = ', 2), ' = ', -1)),
    IF(@num_settings > 1,
       SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 2), ';', -1), CHAR(13), ''), CHAR(10), ''), '$', ''), ' = ', 1)
       , ''),
    IF(@num_settings > 1,
       TRIM(BOTH '"' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 2), ';', -1), CHAR(13), ''), CHAR(10), ''), '$', ''), ' = ', 2), ' = ' , -1))
       , ''),
    IF(@num_settings > 2,
       SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 3), ';', -1), CHAR(13), ''), CHAR(10), ''), '$', ''), ' = ', 1)
       , ''),
    IF(@num_settings > 2,
       TRIM(BOTH '"' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 3), ';', -1), CHAR(13), ''), CHAR(10), ''), '$', ''), ' = ', 2), ' = ' , -1))
       , ''),
    IF(@num_settings > 3,
       SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 4), ';', -1), CHAR(13), ''), CHAR(10), ''), '$', ''), ' = ', 1)
       , ''),
    IF(@num_settings > 3,
       TRIM(BOTH '"' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(settings, ';', 4), ';', -1), CHAR(13), ''), CHAR(10), ''), '$', ''), ' = ', 2), ' = ' , -1))
       , '')
    ) AS `json_temp`
FROM report_emails) AS `source` SET `destination`.`json_settings` = `source`.`json_temp` WHERE `destination`.`id` = `source`.`id`