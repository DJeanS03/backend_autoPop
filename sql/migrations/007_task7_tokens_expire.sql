USE autopop;

ALTER TABLE password_requests
  ADD COLUMN expires_at DATETIME NULL AFTER token,
  ADD INDEX ix_passreq_expires (expires_at);

ALTER TABLE deactivation_requests
  ADD COLUMN expires_at DATETIME NULL AFTER token,
  ADD INDEX ix_deact_expires (expires_at);
