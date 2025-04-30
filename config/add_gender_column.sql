USE studybud_db;
ALTER TABLE users ADD COLUMN gender ENUM('male', 'female') DEFAULT NULL AFTER email;