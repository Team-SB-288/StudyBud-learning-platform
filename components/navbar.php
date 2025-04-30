<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page type for active state
$current_page = basename($_SERVER['PHP_SELF']);
$type = $_GET['type'] ?? '';
?>

<!-- Include theme CSS and JS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/theme.css">
<script src="<?php echo BASE_URL; ?>/assets/js/theme.js" defer></script>

<nav class="bg-white shadow-lg fixed w-full top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex space-x-8">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?php echo isset($_SESSION['user_id']) ? BASE_URL . '/pages/dashboard.php' : BASE_URL . '/index.php'; ?>" 
                       class="text-2xl font-bold text-gray-800"><?php echo SITE_NAME; ?></a>
                </div>
                <!-- Main Navigation -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="<?php echo BASE_URL; ?>/pages/browse.php?type=video" 
                       class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md <?php echo $type == 'video' ? 'bg-gray-100' : ''; ?>">
                        Videos
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/browse.php?type=course" 
                       class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md <?php echo $type == 'course' ? 'bg-gray-100' : ''; ?>">
                        Courses
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/browse.php?type=note" 
                       class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md <?php echo $type == 'note' ? 'bg-gray-100' : ''; ?>">
                        Library
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/about.php" 
                       class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md <?php echo $current_page == 'about.php' ? 'bg-gray-100' : ''; ?>">
                        About
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/contact.php" 
                       class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md <?php echo $current_page == 'contact.php' ? 'bg-gray-100' : ''; ?>">
                        Contact
                    </a>
                </div>
            </div>
            <!-- User Navigation -->
            <div class="flex items-center space-x-6">
                <!-- Theme Switch -->
                <label for="themeSwitch" class="switch">
                    <input id="themeSwitch" type="checkbox" />
                    <span class="slider"></span>
                    <span class="decoration"></span>
                </label>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="flex items-center space-x-6">
                        <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="flex items-center">
                            <img src="<?php echo !empty($_SESSION['profile_picture']) ? BASE_URL . '/' . $_SESSION['profile_picture'] : BASE_URL . '/assets/images/' . ($_SESSION['gender'] === 'female' ? 'female.png' : 'male.png'); ?>" 
                                 alt="Profile" 
                                 class="w-8 h-8 rounded-full object-cover border border-gray-200">
                        </a>
                        <?php if($current_page == 'browse.php'): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/upload-<?php echo $type ?? 'video'; ?>.php" 
                               class="text-blue-600 hover:text-blue-700">
                                Upload <?php echo ucfirst($type ?? 'Video'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/pages/logout.php" class="text-gray-700 hover:text-gray-900">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/pages/login.php" 
                       class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md <?php echo $current_page == 'login.php' ? 'bg-gray-100' : ''; ?>">Login</a>
                    <a href="<?php echo BASE_URL; ?>/pages/register.php" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>