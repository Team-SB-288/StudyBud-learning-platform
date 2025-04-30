<?php
require_once '../config/config.php';
require_once '../includes/User.php';

session_start();

if(User::isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/dashboard.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = new User();
    $user->name = $_POST['name'] ?? '';
    $user->username = $_POST['username'] ?? '';
    $user->email = $_POST['email'] ?? '';
    $user->password = $_POST['password'] ?? '';
    $user->bio = $_POST['bio'] ?? '';
    $user->gender = $_POST['gender'] ?? '';

    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $user->username)) {
        $error = "Username must be 3-20 characters long and can only contain letters, numbers, and underscores";
    } else {
        // Handle profile picture upload
        if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $file = $_FILES['profile_picture'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if(in_array($ext, ALLOWED_IMAGE_TYPES)) {
                $filename = uniqid() . '.' . $ext;
                $destination = UPLOAD_PROFILE_PATH . $filename;
                
                if(move_uploaded_file($file['tmp_name'], "../" . $destination)) {
                    $user->profile_picture = $destination;
                }
            }
        }

        try {
            if($user->create()) {
                $success = "Registration successful! Please login.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - StudyBud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Hide scrollbar while maintaining scroll functionality */
        * {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        *::-webkit-scrollbar {
            display: none;  /* Chrome, Safari and Opera */
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation Bar -->
    <?php include '../components/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-16">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-center mb-6">Create Account</h1>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Name</label>
                        <input type="text" name="name" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required pattern="[a-zA-Z0-9_]{3,20}"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            title="Username must be 3-20 characters long and can only contain letters, numbers, and underscores">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required minlength="8"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Gender</label>
                        <div class="flex space-x-6 mt-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="male" required class="form-radio text-blue-600">
                                <span class="ml-2">Male</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="female" required class="form-radio text-blue-600">
                                <span class="ml-2">Female</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*"
                            class="mt-1 block w-full file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-blue-500 file:text-white hover:file:bg-blue-600 text-gray-700 cursor-pointer border border-gray-300 rounded-lg bg-white px-4 py-2.5">
                    </div>
                </div>

                <div class="w-full">
                    <label class="block text-gray-700 mb-2">Bio</label>
                    <textarea name="bio" rows="3"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        placeholder="Tell us about yourself..."></textarea>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <p class="text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="text-blue-500 hover:text-blue-600">Login here</a>
                    </p>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-6 rounded-lg transition duration-200">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4"><?php echo SITE_NAME; ?></h3>
                    <p class="text-gray-400">Empowering education through community-driven content sharing.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white">Contact</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Connect With Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <span class="sr-only">Facebook</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <span class="sr-only">Twitter</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>