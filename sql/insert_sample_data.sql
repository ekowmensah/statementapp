-- Insert realistic sample data for Account Statement Dashboard
-- This creates 3 months of daily transaction data with realistic financial amounts

-- First, ensure the rate columns exist (run this if not already done)
-- ALTER TABLE daily_txn 
-- ADD COLUMN IF NOT EXISTS rate_ag1 DECIMAL(5,4) NOT NULL DEFAULT 0.2100 COMMENT 'AG1 rate as decimal (21% = 0.2100)',
-- ADD COLUMN IF NOT EXISTS rate_ag2 DECIMAL(5,4) NOT NULL DEFAULT 0.0400 COMMENT 'AG2 rate as decimal (4% = 0.0400)';

-- Clear existing sample data (optional - remove if you want to keep existing data)
-- DELETE FROM daily_txn WHERE note LIKE '%Sample%';

-- Insert sample data for October 2024 (Current Month)
INSERT INTO daily_txn (txn_date, ca, ga, je, rate_ag1, rate_ag2, note, created_by, updated_by) VALUES
-- Week 1
('2024-10-01', 125000.00, 15000.00, 8500.00, 0.2100, 0.0400, 'Sample - October opening transactions', 1, 1),
('2024-10-02', 98500.00, 12200.00, 6800.00, 0.2150, 0.0380, 'Sample - Mid-week business activity', 1, 1),
('2024-10-03', 156000.00, 18500.00, 9200.00, 0.2080, 0.0420, 'Sample - High volume day', 1, 1),
('2024-10-04', 87300.00, 10500.00, 5900.00, 0.2120, 0.0390, 'Sample - Regular business day', 1, 1),

-- Week 2
('2024-10-07', 142000.00, 16800.00, 8900.00, 0.2100, 0.0400, 'Sample - Monday surge', 1, 1),
('2024-10-08', 118500.00, 14200.00, 7600.00, 0.2090, 0.0410, 'Sample - Steady growth', 1, 1),
('2024-10-09', 95600.00, 11400.00, 6300.00, 0.2110, 0.0395, 'Sample - Mid-week activity', 1, 1),
('2024-10-10', 167500.00, 19800.00, 10200.00, 0.2070, 0.0430, 'Sample - Peak performance day', 1, 1),
('2024-10-11', 134200.00, 15900.00, 8400.00, 0.2100, 0.0400, 'Sample - End of week strong', 1, 1),

-- Week 3
('2024-10-14', 89700.00, 10800.00, 5800.00, 0.2130, 0.0385, 'Sample - Monday restart', 1, 1),
('2024-10-15', 156800.00, 18500.00, 9800.00, 0.2080, 0.0420, 'Sample - Mid-month peak', 1, 1),
('2024-10-16', 123400.00, 14600.00, 7900.00, 0.2100, 0.0400, 'Sample - Consistent performance', 1, 1),
('2024-10-17', 98900.00, 11700.00, 6500.00, 0.2115, 0.0395, 'Sample - Regular operations', 1, 1),
('2024-10-18', 145600.00, 17200.00, 9100.00, 0.2085, 0.0415, 'Sample - Strong finish', 1, 1),

-- Week 4
('2024-10-21', 112300.00, 13400.00, 7200.00, 0.2105, 0.0398, 'Sample - Week 4 opening', 1, 1),
('2024-10-22', 134500.00, 15900.00, 8600.00, 0.2095, 0.0405, 'Sample - Building momentum', 1, 1),
('2024-10-23', 87600.00, 10500.00, 5700.00, 0.2125, 0.0388, 'Sample - Mid-week dip', 1, 1),
('2024-10-24', 178900.00, 21200.00, 11400.00, 0.2065, 0.0435, 'Sample - Month-end surge', 1, 1);

-- Insert sample data for September 2024 (Previous Month)
INSERT INTO daily_txn (txn_date, ca, ga, je, rate_ag1, rate_ag2, note, created_by, updated_by) VALUES
-- September data (for comparison)
('2024-09-02', 115000.00, 14000.00, 7800.00, 0.2100, 0.0400, 'Sample - September opening', 1, 1),
('2024-09-03', 92300.00, 11200.00, 6200.00, 0.2120, 0.0390, 'Sample - Early month activity', 1, 1),
('2024-09-04', 148500.00, 17500.00, 9300.00, 0.2080, 0.0420, 'Sample - Strong performance', 1, 1),
('2024-09-05', 83700.00, 10100.00, 5500.00, 0.2130, 0.0385, 'Sample - Regular day', 1, 1),
('2024-09-06', 126800.00, 15000.00, 8100.00, 0.2095, 0.0405, 'Sample - Week end strong', 1, 1),

('2024-09-09', 134200.00, 15900.00, 8600.00, 0.2100, 0.0400, 'Sample - Second week start', 1, 1),
('2024-09-10', 108900.00, 13000.00, 7000.00, 0.2110, 0.0395, 'Sample - Steady growth', 1, 1),
('2024-09-11', 89600.00, 10700.00, 5800.00, 0.2125, 0.0388, 'Sample - Mid-week', 1, 1),
('2024-09-12', 156700.00, 18500.00, 9900.00, 0.2075, 0.0425, 'Sample - Peak day', 1, 1),
('2024-09-13', 142300.00, 16800.00, 9000.00, 0.2090, 0.0410, 'Sample - Strong finish', 1, 1),

('2024-09-16', 95400.00, 11400.00, 6200.00, 0.2115, 0.0395, 'Sample - Third week', 1, 1),
('2024-09-17', 167800.00, 19800.00, 10600.00, 0.2070, 0.0430, 'Sample - Exceptional day', 1, 1),
('2024-09-18', 118500.00, 14100.00, 7600.00, 0.2100, 0.0400, 'Sample - Consistent', 1, 1),
('2024-09-19', 87900.00, 10500.00, 5700.00, 0.2120, 0.0390, 'Sample - Regular ops', 1, 1),
('2024-09-20', 139600.00, 16500.00, 8800.00, 0.2085, 0.0415, 'Sample - Week close', 1, 1),

('2024-09-23', 123700.00, 14600.00, 7900.00, 0.2105, 0.0398, 'Sample - Final week', 1, 1),
('2024-09-24', 98200.00, 11700.00, 6400.00, 0.2115, 0.0395, 'Sample - Building up', 1, 1),
('2024-09-25', 145800.00, 17200.00, 9200.00, 0.2085, 0.0415, 'Sample - Strong day', 1, 1),
('2024-09-26', 189300.00, 22400.00, 12100.00, 0.2060, 0.0440, 'Sample - Month end peak', 1, 1),
('2024-09-27', 156900.00, 18500.00, 9900.00, 0.2080, 0.0420, 'Sample - Month close', 1, 1);

-- Insert sample data for August 2024 (Historical comparison)
INSERT INTO daily_txn (txn_date, ca, ga, je, rate_ag1, rate_ag2, note, created_by, updated_by) VALUES
-- August data (for YTD calculations)
('2024-08-01', 108500.00, 13000.00, 7000.00, 0.2100, 0.0400, 'Sample - August start', 1, 1),
('2024-08-02', 89700.00, 10800.00, 5900.00, 0.2120, 0.0390, 'Sample - Early August', 1, 1),
('2024-08-05', 134600.00, 15900.00, 8500.00, 0.2095, 0.0405, 'Sample - First week strong', 1, 1),
('2024-08-06', 156800.00, 18500.00, 9800.00, 0.2080, 0.0420, 'Sample - Peak performance', 1, 1),
('2024-08-07', 92300.00, 11100.00, 6100.00, 0.2115, 0.0395, 'Sample - Mid-week', 1, 1),
('2024-08-08', 145200.00, 17100.00, 9200.00, 0.2085, 0.0415, 'Sample - Strong day', 1, 1),
('2024-08-09', 118900.00, 14100.00, 7600.00, 0.2100, 0.0400, 'Sample - Week end', 1, 1),

('2024-08-12', 87600.00, 10500.00, 5700.00, 0.2125, 0.0388, 'Sample - Second week', 1, 1),
('2024-08-13', 167400.00, 19700.00, 10500.00, 0.2070, 0.0430, 'Sample - Exceptional', 1, 1),
('2024-08-14', 123800.00, 14600.00, 7900.00, 0.2100, 0.0400, 'Sample - Steady', 1, 1),
('2024-08-15', 98500.00, 11800.00, 6400.00, 0.2110, 0.0395, 'Sample - Regular', 1, 1),
('2024-08-16', 142700.00, 16800.00, 9000.00, 0.2090, 0.0410, 'Sample - Strong close', 1, 1),

('2024-08-19', 134500.00, 15900.00, 8600.00, 0.2095, 0.0405, 'Sample - Third week', 1, 1),
('2024-08-20', 89200.00, 10700.00, 5800.00, 0.2120, 0.0390, 'Sample - Mid-week', 1, 1),
('2024-08-21', 178600.00, 21100.00, 11300.00, 0.2065, 0.0435, 'Sample - Peak day', 1, 1),
('2024-08-22', 156300.00, 18400.00, 9800.00, 0.2080, 0.0420, 'Sample - High volume', 1, 1),
('2024-08-23', 112800.00, 13400.00, 7200.00, 0.2105, 0.0398, 'Sample - Week close', 1, 1),

('2024-08-26', 145900.00, 17200.00, 9200.00, 0.2085, 0.0415, 'Sample - Final week', 1, 1),
('2024-08-27', 98700.00, 11800.00, 6400.00, 0.2115, 0.0395, 'Sample - Building', 1, 1),
('2024-08-28', 167800.00, 19800.00, 10600.00, 0.2070, 0.0430, 'Sample - Strong', 1, 1),
('2024-08-29', 189700.00, 22400.00, 12000.00, 0.2060, 0.0440, 'Sample - Month peak', 1, 1),
('2024-08-30', 134200.00, 15900.00, 8500.00, 0.2095, 0.0405, 'Sample - Month end', 1, 1);

-- Update the view to ensure calculations are current
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

-- Summary of inserted data:
-- - 18 transactions for October 2024 (current month)
-- - 20 transactions for September 2024 (previous month for comparison)
-- - 20 transactions for August 2024 (historical data for YTD)
-- - Total: 58 realistic transactions with varying amounts
-- - CA amounts range from ~87K to ~189K (realistic business volumes)
-- - GA amounts are proportional (10-12% of CA typically)
-- - JE amounts are smaller operational expenses (5-7% of CA)
-- - AG1 rates vary between 20.6% to 21.3% (realistic rate fluctuation)
-- - AG2 rates vary between 3.8% to 4.4% (realistic rate fluctuation)
-- - All transactions have descriptive notes indicating they are sample data
