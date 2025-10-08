-- =====================================================================
-- Safe GAI GA Migration - Handles existing columns gracefully
-- =====================================================================

-- Check if gai_ga column exists, if not add it
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'daily_txn' 
  AND column_name = 'gai_ga';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE daily_txn ADD COLUMN gai_ga DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT "GAI GA - Isolated field for specific transaction figures"',
    'SELECT "GAI GA column already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add constraint if it doesn't exist
SET @constraint_exists = 0;
SELECT COUNT(*) INTO @constraint_exists 
FROM information_schema.table_constraints 
WHERE table_schema = DATABASE() 
  AND table_name = 'daily_txn' 
  AND constraint_name = 'chk_txn_gai_ga_nonneg';

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE daily_txn ADD CONSTRAINT chk_txn_gai_ga_nonneg CHECK (gai_ga >= 0.00)',
    'SELECT "GAI GA constraint already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Always recreate the view to ensure it includes GAI GA
DROP VIEW IF EXISTS v_daily_txn;

CREATE VIEW v_daily_txn AS
SELECT
  t.id,
  t.txn_date,
  t.ca,
  t.ga,
  t.je,
  t.gai_ga,
  t.company_id,
  t.sequence_number,
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
  t.created_by,
  t.updated_by,
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

-- Update monthly totals view
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
  ROUND(SUM(gai_ga), 2) AS total_gai_ga,
  ROUND(SUM(re),  2) AS total_re,
  ROUND(SUM(je),  2) AS total_je,
  ROUND(SUM(fi),  2) AS total_fi,
  COUNT(*) AS days_count
FROM v_daily_txn
GROUP BY YEAR(txn_date), MONTH(txn_date);

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_daily_txn_gai_ga ON daily_txn (gai_ga);

-- Verify the changes
SELECT 'Migration completed successfully!' as status;
DESCRIBE daily_txn;
