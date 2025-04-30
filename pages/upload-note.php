<?php
require_once '../config/config.php';
require_once '../includes/User.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$uploadMessage = '';
$errorMessage = '';

// Get all categories
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject = trim($_POST['subject']);
    $schoolYear = trim($_POST['school_year']);
    $selectedCategories = isset($_POST['categories']) ? $_POST['categories'] : [];

    // Validate input
    if (empty($title) || empty($description)) {
        $errorMessage = "Title and description are required.";
    } else if (!isset($_FILES['note_file']) || $_FILES['note_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Please select a valid file to upload.";
    } else {
        $file = $_FILES['note_file'];
        $fileName = $file['name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx'];

        if (!in_array($fileType, $allowedTypes)) {
            $errorMessage = "Only PDF, DOC, DOCX, TXT, PPT, and PPTX files are allowed.";
        } else {
            // Generate unique filename
            $newFileName = uniqid('note_') . '.' . $fileType;
            $uploadPath = '../uploads/notes/' . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                try {
                    $conn->beginTransaction();

                    // Insert note
                    $stmt = $conn->prepare("
                        INSERT INTO notes (
                            title, description, file_path, user_id, subject, 
                            school_year, created_at, updated_at
                        ) VALUES (
                            :title, :description, :file_path, :user_id, :subject,
                            :school_year, NOW(), NOW()
                        )
                    ");

                    $filePath = 'uploads/notes/' . $newFileName;
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':file_path', $filePath);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->bindParam(':subject', $subject);
                    $stmt->bindParam(':school_year', $schoolYear);
                    $stmt->execute();

                    $noteId = $conn->lastInsertId();

                    // Insert categories
                    if (!empty($selectedCategories)) {
                        $stmt = $conn->prepare("
                            INSERT INTO note_categories (note_id, category_id)
                            VALUES (:note_id, :category_id)
                        ");

                        foreach ($selectedCategories as $categoryId) {
                            $stmt->bindParam(':note_id', $noteId);
                            $stmt->bindParam(':category_id', $categoryId);
                            $stmt->execute();
                        }
                    }

                    $conn->commit();
                    $uploadMessage = "Note uploaded successfully!";
                    
                    // Redirect to view page
                    header("Location: view-note.php?id=" . $noteId);
                    exit();

                } catch (Exception $e) {
                    $conn->rollBack();
                    $errorMessage = "An error occurred while uploading the note. Please try again.";
                    // Delete uploaded file if database insertion fails
                    if (file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                }
            } else {
                $errorMessage = "Failed to upload file. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Note - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body class="bg-gray-50">
    <?php include '../components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 mt-16">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6" data-aos="fade-up">
                <h1 class="text-3xl font-bold mb-6">Upload Note</h1>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($uploadMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <?php echo htmlspecialchars($uploadMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label for="title" class="block text-gray-700 mb-2">Title</label>
                        <input type="text" name="title" id="title" required
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                               class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label for="description" class="block text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="description" rows="4" required
                                  class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label for="subject" class="block text-gray-700 mb-2">Subject</label>
                        <input type="text" name="subject" id="subject"
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                               class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label for="school_year" class="block text-gray-700 mb-2">School Year</label>
                        <input type="text" name="school_year" id="school_year"
                               value="<?php echo isset($_POST['school_year']) ? htmlspecialchars($_POST['school_year']) : ''; ?>"
                               class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Categories</label>
                        <div class="mt-2 grid grid-cols-2 gap-4 p-4 border border-gray-300 rounded-lg bg-white">
                            <?php foreach ($categories as $category): ?>
                                <div class="flex items-center">
                                    <input type="checkbox" name="categories[]"
                                           value="<?php echo $category['id']; ?>"
                                           id="category_<?php echo $category['id']; ?>"
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-200"
                                           <?php
                                           if (isset($_POST['categories']) && in_array($category['id'], $_POST['categories'])) {
                                               echo 'checked';
                                           }
                                           ?>>
                                    <label for="category_<?php echo $category['id']; ?>"
                                           class="ml-2 text-sm text-gray-700">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label for="note_file" class="block text-gray-700 mb-2">Note File</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                            <div class="space-y-2 text-center">
                                <div class="flex text-sm text-gray-600">
                                    <label for="note_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a file</span>
                                        <input id="note_file" name="note_file" type="file" class="sr-only" required
                                               accept=".pdf,.doc,.docx,.txt,.ppt,.pptx">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    PDF, DOC, DOCX, TXT, PPT, PPTX up to 10MB
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Upload Note
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

        // File upload preview
        const fileInput = document.getElementById('note_file');
        const fileLabel = fileInput.nextElementSibling;
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                fileLabel.textContent = `Selected file: ${fileName}`;
            }
        });

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-blue-500');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-blue-500');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            
            if (files.length > 0) {
                fileLabel.textContent = `Selected file: ${files[0].name}`;
            }
        }
    </script>
</body>
</html>