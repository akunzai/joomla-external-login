-- OIDC subject binding (#304, ADR 0002): an OIDC account can be bound to the provider's
-- verified issuer + subject instead of the mutable username claim.
ALTER TABLE `#__externallogin_users` ADD COLUMN `issuer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;

ALTER TABLE `#__externallogin_users` ADD COLUMN `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;

ALTER TABLE `#__externallogin_users` ADD UNIQUE INDEX `idx_server_issuer_subject` (`server_id`, `issuer`, `subject`);

-- Keep OIDC servers that existed before the upgrade in username mode. Joomla forms show a
-- field's default when the stored params lack the key, so without an explicit value the
-- server form would switch them to subject mode on their next save.
UPDATE `#__externallogin_servers` SET `params` = JSON_INSERT(`params`, '$.binding_mode', 'username') WHERE `plugin` = 'system.oidclogin' AND JSON_VALID(`params`);
