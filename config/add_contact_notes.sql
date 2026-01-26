-- Add notes field to contact_submissions table if it doesn't exist
-- Run this if you get an error about 'notes' column not found

USE bossify_academy;

ALTER TABLE contact_submissions 
ADD COLUMN IF NOT EXISTS notes TEXT AFTER status;
