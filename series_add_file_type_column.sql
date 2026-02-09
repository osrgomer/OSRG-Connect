-- Add file_type column to admin_uploads table
ALTER TABLE admin_uploads 
ADD COLUMN file_type VARCHAR(100) DEFAULT 'image/jpeg' AFTER image_path;
