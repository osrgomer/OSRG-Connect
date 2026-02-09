-- Fix series table - Add missing columns
-- Run this in phpMyAdmin (http://auth-db698.hstgr.io/)

USE u542077544_serieslist;

-- Check if columns exist, if not add them
ALTER TABLE series 
ADD COLUMN IF NOT EXISTS progress INT DEFAULT 0 AFTER status,
ADD COLUMN IF NOT EXISTS total INT DEFAULT 0 AFTER progress;

-- Verify the fix
SELECT * FROM series LIMIT 1;
