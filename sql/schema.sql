-- =====================================================================
-- Daily Statement App — Full SQL Schema (MySQL 8)
-- =====================================================================

-- 0) DATABASE (optional; or use an existing one)
CREATE DATABASE IF NOT EXISTS daily_statement_app
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE daily_statement_app;

-- =====================================================================
-- 1) USERS & ROLES (optional but handy)
-- =====================================================================

CREATE TABLE users (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,  -- bcrypt/argon2 hash
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
  id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name       VARCHAR(50) NOT NULL UNIQUE,   -- 'admin','accountant','viewer'
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_user_roles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_roles_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Seed roles (optional)
INSERT INTO roles (name) VALUES ('admin'), ('accountant'), ('viewer');

-- =====================================================================
-- 2) RATES — effective percentage history (AG1 and AG2)
-- =====================================================================

CREATE TABLE rates (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  effective_on  DATE NOT NULL,            -- start date inclusive
  rate_ag1      DECIMAL(6,4) NOT NULL,    -- e.g., 0.2100 for 21%
  rate_ag2      DECIMAL(6,4) NOT NULL,    -- e.g., 0.0400 for 4%
  note          VARCHAR(255) NULL,
  created_by    BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_rates_effective UNIQUE (effective_on),
  CONSTRAINT fk_rates_user_created
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_rate_ag1 CHECK (rate_ag1 >= 0.0 AND rate_ag1 <= 1.0),
  CONSTRAINT chk_rate_ag2 CHECK (rate_ag2 >= 0.0 AND rate_ag2 <= 1.0)
) ENGINE=InnoDB;

CREATE INDEX idx_rates_effective_on ON rates (effective_on);

-- =====================================================================
-- 3) DAILY TRANSACTIONS — only INPUTS are stored
-- =====================================================================

CREATE TABLE daily_txn (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  txn_date    DATE NOT NULL,                      -- unique per day
  ca          DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- gross inflow
  ga          DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- daily fixed deduction/advance
  je          DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- extra journal/expense (can be 0)
  note        VARCHAR(255) NULL,
  created_by  BIGINT UNSIGNED NULL,
  updated_by  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_daily_txn_date UNIQUE (txn_date),
  CONSTRAINT fk_daily_txn_user_created
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_daily_txn_user_updated
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_txn_ca_nonneg CHECK (ca >= 0.00),
  CONSTRAINT chk_txn_ga_nonneg CHECK (ga >= 0.00),
  CONSTRAINT chk_txn_je_nonneg CHECK (je >= 0.00)
) ENGINE=InnoDB;

CREATE INDEX idx_daily_txn_date ON daily_txn (txn_date);

-- =====================================================================
-- 4) MONTH LOCKS (optional: prevent edits after closing a month)
-- =====================================================================

CREATE TABLE month_locks (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  year_num    INT NOT NULL,          -- e.g., 2025
  month_num   INT NOT NULL,          -- 1..12
  locked_by   BIGINT UNSIGNED NULL,
  locked_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  note        VARCHAR(255) NULL,
  CONSTRAINT uq_month_lock UNIQUE (year_num, month_num),
  CONSTRAINT chk_month_range CHECK (month_num BETWEEN 1 AND 12),
  CONSTRAINT fk_month_lock_user
    FOREIGN KEY (locked_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 5) COMPUTED VIEW — derives AG1, AV1, AG2, AV2, RE, FI per row
--    (Selects the most recent rates row with effective_on <= txn_date)
-- =====================================================================

DROP VIEW IF EXISTS v_daily_txn;

CREATE VIEW v_daily_txn AS
SELECT
  t.id,
  t.txn_date,
  t.ca,
  t.ga,
  t.je,
  -- pick applicable rates by date
  r.rate_ag1,
  r.rate_ag2,
  -- computed cascade (rounded to 2dp)
  ROUND(t.ca * r.rate_ag1, 2) AS ag1,
  ROUND(t.ca - (t.ca * r.rate_ag1), 2) AS av1,
  ROUND((t.ca - (t.ca * r.rate_ag1)) * r.rate_ag2, 2) AS ag2,
  ROUND((t.ca - (t.ca * r.rate_ag1)) - ((t.ca - (t.ca * r.rate_ag1)) * r.rate_ag2), 2) AS av2,
  ROUND(((t.ca - (t.ca * r.rate_ag1)) - ((t.ca - (t.ca * r.rate_ag1)) * r.rate_ag2)) - t.ga, 2) AS re,
  ROUND((((t.ca - (t.ca * r.rate_ag1)) - ((t.ca - (t.ca * r.rate_ag1)) * r.rate_ag2)) - t.ga) - t.je, 2) AS fi,
  t.note,
  t.created_at,
  t.updated_at
FROM daily_txn t
JOIN rates r
  ON r.id = (
    SELECT r2.id
    FROM rates r2
    WHERE r2.effective_on <= t.txn_date
    ORDER BY r2.effective_on DESC
    LIMIT 1
  );

-- Helpful monthly totals view (optional)
DROP VIEW IF EXISTS v_monthly_totals;
CREATE VIEW v_monthly_totals AS
SELECT
  YEAR(txn_date) AS year_num,
  MONTH(txn_date) AS month_num,
  ROUND(SUM(ca),  2) AS total_ca,
  ROUND(SUM(ag1), 2) AS total_ag1,
  ROUND(SUM(av1), 2) AS total_av1,
  ROUND(SUM(ag2), 2) AS total_ag2,
  ROUND(SUM(av2), 2) AS total_av2,
  ROUND(SUM(ga),  2) AS total_ga,
  ROUND(SUM(re),  2) AS total_re,
  ROUND(SUM(je),  2) AS total_je,
  ROUND(SUM(fi),  2) AS total_fi,
  COUNT(*) AS days_count
FROM v_daily_txn
GROUP BY YEAR(txn_date), MONTH(txn_date);

-- =====================================================================
-- 6) TRIGGERS (optional): block edits when month is locked
-- =====================================================================

DELIMITER $$

CREATE TRIGGER trg_daily_txn_before_insert
BEFORE INSERT ON daily_txn
FOR EACH ROW
BEGIN
  DECLARE y INT; DECLARE m INT; DECLARE is_locked INT;
  SET y = YEAR(NEW.txn_date);
  SET m = MONTH(NEW.txn_date);
  SELECT COUNT(*) INTO is_locked
  FROM month_locks
  WHERE year_num = y AND month_num = m;
  IF is_locked > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Month is locked; cannot insert.';
  END IF;
END$$

CREATE TRIGGER trg_daily_txn_before_update
BEFORE UPDATE ON daily_txn
FOR EACH ROW
BEGIN
  DECLARE y INT; DECLARE m INT; DECLARE is_locked INT;
  SET y = YEAR(NEW.txn_date);
  SET m = MONTH(NEW.txn_date);
  SELECT COUNT(*) INTO is_locked
  FROM month_locks
  WHERE year_num = y AND month_num = m;
  IF is_locked > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Month is locked; cannot update.';
  END IF;
END$$

CREATE TRIGGER trg_daily_txn_before_delete
BEFORE DELETE ON daily_txn
FOR EACH ROW
BEGIN
  DECLARE y INT; DECLARE m INT; DECLARE is_locked INT;
  SET y = YEAR(OLD.txn_date);
  SET m = MONTH(OLD.txn_date);
  SELECT COUNT(*) INTO is_locked
  FROM month_locks
  WHERE year_num = y AND month_num = m;
  IF is_locked > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Month is locked; cannot delete.';
  END IF;
END$$

DELIMITER ;

-- =====================================================================
-- 7) SEEDS (optional)
-- =====================================================================

-- Admin user (password placeholder)
INSERT INTO users (name, email, password_hash)
VALUES ('Admin', 'admin@example.com', '$2y$10$REPLACE_WITH_BCRYPT_HASH'); -- bcrypt

-- Assign admin role
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r
WHERE u.email='admin@example.com' AND r.name='admin';

-- Seed starting rates (21% and 4% effective 2025-10-01)
INSERT INTO rates (effective_on, rate_ag1, rate_ag2, note, created_by)
VALUES ('2025-10-01', 0.2100, 0.0400, 'Initial rates', 1);

-- Sample daily inputs (reflecting your spreadsheet pattern)
INSERT INTO daily_txn (txn_date, ca, ga, je, note, created_by)
VALUES
('2025-10-01', 3000.00, 1000.00, 840.00, 'JE applied', 1),
('2025-10-02', 2000.00, 1000.00,   0.00, NULL, 1),
('2025-10-03', 1500.00, 1000.00,   0.00, NULL, 1),
('2025-10-06',    0.00, 1000.00,   0.00, 'Payout-only day', 1),
('2025-10-07',    0.00, 1000.00,   0.00, 'Payout-only day', 1);

-- =====================================================================
-- 8) EXAMPLES (read)
-- =====================================================================

-- Per-day computed rows:
-- SELECT * FROM v_daily_txn WHERE txn_date BETWEEN '2025-10-01' AND '2025-10-31' ORDER BY txn_date;

-- Monthly totals:
-- SELECT * FROM v_monthly_totals WHERE year_num=2025 AND month_num=10;

-- Lock a month:
-- INSERT INTO month_locks (year_num, month_num, locked_by, note)
-- VALUES (2025, 10, 1, 'Close October 2025');

-- Attempting INSERT/UPDATE/DELETE in a locked month will raise an error via triggers.
