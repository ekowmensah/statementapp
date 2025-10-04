-- Add AG1 and AG2 rate columns to daily_txn table
-- This allows each transaction to have its own rates instead of using a global rate system

ALTER TABLE daily_txn 
ADD COLUMN rate_ag1 DECIMAL(5,4) NOT NULL DEFAULT 0.2100 COMMENT 'AG1 rate as decimal (21% = 0.2100)',
ADD COLUMN rate_ag2 DECIMAL(5,4) NOT NULL DEFAULT 0.0400 COMMENT 'AG2 rate as decimal (4% = 0.0400)';

-- Update existing transactions with default rates (21% and 4%)
UPDATE daily_txn 
SET rate_ag1 = 0.2100, rate_ag2 = 0.0400 
WHERE rate_ag1 = 0 OR rate_ag2 = 0;

-- Add indexes for performance
CREATE INDEX idx_daily_txn_rates ON daily_txn(rate_ag1, rate_ag2);
