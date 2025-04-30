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

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categories = $_POST['categories'] ?? [];

    if(empty($title)) {
        $error = "Title is required";
    } elseif(!isset($_FILES['video']) || $_FILES['video']['error'] !== 0) {
        $error = "Please select a video file";
    } else {
        $file = $_FILES['video'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validate file type only
        if(!in_array($fileType, ALLOWED_VIDEO_TYPES)) {
            $error = "Invalid file type. Allowed types: " . implode(', ', ALLOWED_VIDEO_TYPES);
        } else {
            // Generate unique filename
            $filename = uniqid() . '_' . time() . '.' . $fileType;
            $destination = "../" . UPLOAD_VIDEO_PATH . $filename;

            if(move_uploaded_file($file['tmp_name'], $destination)) {
                $db = new Database();
                $conn = $db->getConnection();

                try {
                    $conn->beginTransaction();

                    // Insert video
                    $stmt = $conn->prepare("INSERT INTO videos (user_id, title, description, file_path, created_at) 
                                        VALUES (:user_id, :title, :description, :file_path, NOW())");
                    
                    $filePath = UPLOAD_VIDEO_PATH . $filename;
                    $stmt->bindParam(":user_id", $_SESSION['user_id']);
                    $stmt->bindParam(":title", $title);
                    $stmt->bindParam(":description", $description);
                    $stmt->bindParam(":file_path", $filePath);
                    $stmt->execute();

                    $videoId = $conn->lastInsertId();

                    // Insert categories
                    if(!empty($categories)) {
                        $stmt = $conn->prepare("INSERT INTO video_categories (video_id, category_id) VALUES (:video_id, :category_id)");
                        foreach($categories as $categoryId) {
                            $stmt->bindParam(":video_id", $videoId);
                            $stmt->bindParam(":category_id", $categoryId);
                            $stmt->execute();
                        }
                    }

                    $conn->commit();
                    $success = "Video uploaded successfully!";
                } catch(PDOException $e) {
                    $conn->rollBack();
                    $error = "Error uploading video: " . $e->getMessage();
                    // Delete uploaded file if database insertion fails
                    if(file_exists($destination)) {
                        unlink($destination);
                    }
                }
            } else {
                $error = "Error moving uploaded file";
            }
        }
    }
}

// Fetch categories for the form
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body class="bg-gray-50">
    <?php include '../components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 mt-16">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6" data-aos="fade-up">
                <h1 class="text-2xl font-bold text-center mb-6">Upload Educational Video</h1>

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

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Title</label>
                        <input type="text" name="title" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter video title">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="4"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            placeholder="Describe your video content"></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Categories</label>
                        <select name="categories[]" multiple 
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple categories</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Video File</label>
                        <input type="file" name="video" accept="video/*" required
                            class="mt-1 block w-full file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-500 file:text-white hover:file:bg-blue-600 text-gray-700 cursor-pointer border border-gray-300 rounded-lg bg-white px-4 py-2.5">
                        <p class="text-sm text-gray-500 mt-1">
                            Allowed types: <?php echo implode(', ', ALLOWED_VIDEO_TYPES); ?>
                        </p>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">Back to Dashboard</a>
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                            Upload Video
                        </button>
                    </div>
                </form>
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