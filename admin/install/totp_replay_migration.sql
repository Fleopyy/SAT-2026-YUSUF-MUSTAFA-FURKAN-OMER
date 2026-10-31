-- Manual deployment only. Do not run from application bootstrap.
ALTER TABLE yoneticiler ADD COLUMN son_totp_zaman_dilimi BIGINT UNSIGNED NULL DEFAULT NULL;
