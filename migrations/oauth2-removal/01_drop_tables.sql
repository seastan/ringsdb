-- Drop the tables of the OAuth2 server (FOSOAuthServerBundle), removed from the application.
-- Run AFTER the application is deployed without the bundle (it no longer maps these tables).
--
-- The tokens and codes reference the clients: drop them first. Idempotent.

DROP TABLE IF EXISTS `oauth2_access_token`;
DROP TABLE IF EXISTS `oauth2_refresh_token`;
DROP TABLE IF EXISTS `oauth2_auth_code`;
DROP TABLE IF EXISTS `oauth2_client`;
