<?php
require_once '../config/config.php';
require_once '../includes/User.php';

session_start();

if(!User::isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit();
}

$error = '';
$success = '';

$user = new User();
$userData = $user->getUserById($_SESSION['user_id']);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    if(empty($name)) {
        $error = "Name is required";
    } else {
        $user->id = $_SESSION['user_id'];
        $user->name = $name;
        $user->bio = $bio;
        $user->gender = $gender;

        // Handle profile picture upload
        if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $file = $_FILES['profile_picture'];
            $fileSize = $file['size'];
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if($fileSize > MAX_FILE_SIZE) {
                $error = "File is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
            } elseif(!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
                $error = "Invalid file type. Allowed types: " . implode(', ', ALLOWED_IMAGE_TYPES);
            } else {
                $filename = uniqid() . '_' . time() . '.' . $fileType;
                $destination = "../" . UPLOAD_PROFILE_PATH . $filename;
                
                if(move_uploaded_file($file['tmp_name'], $destination)) {
                    $user->profile_picture = UPLOAD_PROFILE_PATH . $filename;
                    
                    // Delete old profile picture if exists
                    if(!empty($userData['profile_picture']) && file_exists("../" . $userData['profile_picture'])) {
                        unlink("../" . $userData['profile_picture']);
                    }
                }
            }
        }

        if(empty($error) && $user->updateProfile()) {
            $success = "Profile updated successfully!";
            $userData = $user->getUserById($_SESSION['user_id']); // Refresh user data
        } else if(empty($error)) {
            $error = "Error updating profile";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body class="bg-gray-50">
    <?php include '../components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 mt-16">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6" data-aos="fade-up">
                <h1 class="text-2xl font-bold text-center mb-6">Edit Profile</h1>

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
                    <!-- Current Profile Picture -->
                    <div class="text-center">
                        <img src="<?php echo !empty($userData['profile_picture']) ? BASE_URL . '/' . $userData['profile_picture'] : 'https://via.placeholder.com/150' ?>" 
                             alt="Current Profile Picture"
                             class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-white shadow-lg">
                        <p class="text-sm text-gray-600 mb-2">Current Profile Picture</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Name</label>
                        <input type="text" name="name" required
                            value="<?php echo htmlspecialchars($userData['name']); ?>"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Bio</label>
                        <textarea name="bio" rows="4"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="male" <?php echo ($userData['gender'] ?? '') === 'male' ? 'checked' : ''; ?> 
                                    class="form-radio text-blue-600">
                                <span class="ml-2">Male</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="female" <?php echo ($userData['gender'] ?? '') === 'female' ? 'checked' : ''; ?>
                                    class="form-radio text-blue-600">
                                <span class="ml-2">Female</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">New Profile Picture</label>
                        <input type="file" name="profile_picture" accept="<?php echo '.'.implode(',.', ALLOWED_IMAGE_TYPES); ?>"
                            class="mt-1 block w-full file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-blue-500 file:text-white hover:file:bg-blue-600 text-gray-700 cursor-pointer border border-gray-300 rounded-lg bg-white px-4 py-2.5">
                        <p class="text-sm text-gray-500 mt-1">
                            Max size: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB. 
                            Allowed types: <?php echo implode(', ', ALLOWED_IMAGE_TYPES); ?>
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">Back to Dashboard</a>
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-lg transition duration-200">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6" data-aos="fade-up">
                <h2 class="text-xl font-bold mb-4">Change Password</h2>
                <p class="text-gray-600 mb-4">To change your password, please contact support.</p>
                <a href="contact.php" class="text-blue-500 hover:text-blue-600">Contact Support →</a>
            </div>

            <!-- Account Settings -->
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6" data-aos="fade-up">
                <h2 class="text-xl font-bold mb-4">Account Settings</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold">Email Notifications</h3>
                            <p class="text-gray-600">Receive updates about your content</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold">Profile Visibility</h3>
                            <p class="text-gray-600">Make your profile public</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>