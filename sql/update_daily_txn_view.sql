-- Update v_daily_txn view to use rates stored directly in daily_txn table
-- This replaces the old rate management system with per-transaction rates

DROP VIEW IF EXISTS v_daily_txn;

CREATE VIEW v_daily_txn AS
SELECT
  t.id,
  t.txn_date,
  t.ca,
  t.ga,
  t.je,
  -- use rates stored directly in the transaction
  t.rate_ag1,
  t.rate_ag2,
  -- computed cascade (rounded to 2dp)
  ROUND(t.ca * t.rate_ag1, 2) AS ag1,
  ROUND(t.ca - (t.ca * t.rate_ag1), 2) AS av1,
  ROUND((t.ca - (t.ca * t.rate_ag1)) * t.rate_ag2, 2) AS ag2,
  ROUND((t.ca - (t.ca * t.rate_ag1)) - ((t.ca - (t.ca * t.rate_ag1)) * t.rate_ag2), 2) AS av2,
  ROUND(((t.ca - (t.ca * t.rate_ag1)) - ((t.ca - (t.ca * t.rate_ag1)) * t.rate_ag2)) - t.ga, 2) AS re,
  ROUND((((t.ca - (t.ca * t.rate_ag1)) - ((t.ca - (t.ca * t.rate_ag1)) * t.rate_ag2)) - t.ga) - t.je, 2) AS fi,
  t.note,
  t.created_at,
  t.updated_at,
  t.created_by,
  t.updated_by
FROM daily_txn t
ORDER BY t.txn_date DESC;
