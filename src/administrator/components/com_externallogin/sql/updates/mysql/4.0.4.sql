ALTER TABLE `#__externallogin_servers` MODIFY `checked_out` int unsigned;

ALTER TABLE `#__externallogin_servers` MODIFY `checked_out_time` datetime;

UPDATE `#__externallogin_servers` SET `checked_out` = NULL WHERE `checked_out` = 0;

UPDATE `#__externallogin_servers` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';
