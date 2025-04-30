<?php
// Application configuration
define('BASE_URL', 'http://localhost/studybud');
define('SITE_NAME', 'StudyBud');

// File upload settings
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB for documents and images
define('MAX_VIDEO_SIZE', 0); // No limit for videos (limited by PHP/server settings)
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Upload directories
define('UPLOAD_VIDEO_PATH', 'uploads/videos/');
define('UPLOAD_COURSE_PATH', 'uploads/courses/');
define('UPLOAD_NOTE_PATH', 'uploads/notes/');
define('UPLOAD_PROFILE_PATH', 'uploads/profiles/');
define('DEFAULT_PROFILE_PICTURE', 'assets/images/default-avatar.png');

// Session settings
define('SESSION_LIFETIME', 86400); // 24 hours
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
?>