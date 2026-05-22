-- MySQL query to delete all file submissions from the file_submissions table
-- WARNING: This will permanently delete all PPMP, LIB, APP, and PR submissions
-- Run this query in your MySQL database

DELETE FROM file_submissions;

-- Optional: Reset the auto-increment counter (uncomment if needed)
-- ALTER TABLE file_submissions AUTO_INCREMENT = 1;

-- Note: This only deletes database records. Physical files in the uploads/ directory
-- (uploads/ppmp/, uploads/lib/, uploads/app/, uploads/pr/) are NOT deleted.
-- You may want to manually clean up those directories if needed.

