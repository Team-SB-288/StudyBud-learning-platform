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

        .form__group {
            position: relative;
            padding: 20px 0 0;
            width: 100%;
        }

        .form__field {
            font-family: inherit;
            width: 100%;
            border: none;
            border-bottom: 2px solid #9b9b9b;
            outline: 0;
            font-size: 17px;
            color: #000;
            padding: 7px 0;
            background: transparent;
            transition: border-color 0.2s;
        }

        .form__field::placeholder {
            color: transparent;
        }

        .form__field:placeholder-shown ~ .form__label {
            font-size: 17px;
            cursor: text;
            top: 20px;
        }

        .form__label {
            position: absolute;
            top: 0;
            display: block;
            transition: 0.2s;
            font-size: 17px;
            color: #9b9b9b;
            pointer-events: none;
        }

        .form__field:focus {
            padding-bottom: 6px;
            font-weight: 700;
            border-width: 3px;
            border-image: linear-gradient(to right, #116399, #38caef);
            border-image-slice: 1;
        }

        .form__field:focus ~ .form__label {
            position: absolute;
            top: 0;
            display: block;
            transition: 0.2s;
            font-size: 17px;
            color: #38caef;
            font-weight: 700;
        }

        .form__field:required, .form__field:invalid {
            box-shadow: none;
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
                        <div class="form__group field">
                            <input type="text" name="name" class="form__field" placeholder="Name" required>
                            <label for="name" class="form__label">Name</label>
                        </div>
                    </div>

                    <div>
                        <div class="form__group field">
                            <input type="text" name="username" class="form__field" placeholder="Username" required pattern="[a-zA-Z0-9_]{3,20}">
                            <label for="username" class="form__label">Username</label>
                        </div>
                    </div>

                    <div>
                        <div class="form__group field">
                            <input type="email" name="email" class="form__field" placeholder="Email" required>
                            <label for="email" class="form__label">Email</label>
                        </div>
                    </div>

                    <div>
                        <div class="form__group field">
                            <input type="password" name="password" class="form__field" placeholder="Password" required minlength="8">
                            <label for="password" class="form__label">Password</label>
                        </div>
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
    <?php include '../components/footer.php' ?>

</body>
</html>