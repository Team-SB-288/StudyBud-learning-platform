<?php
require_once '../config/config.php';
require_once '../includes/User.php';
session_start();

if(!isset($_GET['id'])) {
    header("Location: browse.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get note details
$stmt = $conn->prepare("
    SELECT 
        n.*,
        u.name as author_name,
        u.profile_picture as author_profile,
        u.gender as author_gender,
        GROUP_CONCAT(c.name) as categories
    FROM notes n
    LEFT JOIN users u ON n.user_id = u.id
    LEFT JOIN note_categories nc ON n.id = nc.note_id
    LEFT JOIN categories c ON nc.category_id = c.id
    WHERE n.id = :id
    GROUP BY n.id
");
$stmt->bindParam(":id", $_GET['id']);
$stmt->execute();
$note = $stmt->fetch();

if(!$note) {
    header("Location: browse.php");
    exit();
}

// Update view count
if(!isset($_SESSION['viewed_notes']) || !in_array($note['id'], $_SESSION['viewed_notes'])) {
    $stmt = $conn->prepare("UPDATE notes SET views = views + 1 WHERE id = :id");
    $stmt->bindParam(":id", $note['id']);
    $stmt->execute();
    
    if(!isset($_SESSION['viewed_notes'])) {
        $_SESSION['viewed_notes'] = [];
    }
    $_SESSION['viewed_notes'][] = $note['id'];
}

// Get related notes
$stmt = $conn->prepare("
    SELECT 
        n.*,
        u.name as author_name,
        u.profile_picture as author_profile
    FROM notes n
    JOIN users u ON n.user_id = u.id
    WHERE n.id != :id
    ORDER BY n.views DESC
    LIMIT 5
");
$stmt->bindParam(":id", $note['id']);
$stmt->execute();
$relatedNotes = $stmt->fetchAll();

// Handle bookmarking
if(isset($_POST['toggle_bookmark']) && isset($_SESSION['user_id'])) {
    $isBookmarked = false;
    
    // Check if already bookmarked
    $stmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = :user_id AND note_id = :note_id");
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->bindParam(":note_id", $note['id']);
    $stmt->execute();
    $bookmark = $stmt->fetch();
    
    if($bookmark) {
        // Remove bookmark
        $stmt = $conn->prepare("DELETE FROM bookmarks WHERE id = :id");
        $stmt->bindParam(":id", $bookmark['id']);
        $stmt->execute();
    } else {
        // Add bookmark
        $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, note_id, created_at) VALUES (:user_id, :note_id, NOW())");
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":note_id", $note['id']);
        $stmt->execute();
        $isBookmarked = true;
    }
    
    // Return JSON response for AJAX
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'isBookmarked' => $isBookmarked]);
        exit();
    }
    
    // Redirect for non-AJAX requests
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Check if note is bookmarked by current user
$isBookmarked = false;
if(isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = :user_id AND note_id = :note_id");
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->bindParam(":note_id", $note['id']);
    $stmt->execute();
    $isBookmarked = (bool)$stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($note['title']); ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';</script>
</head>
<body class="bg-gray-50">
    <?php include '../components/loading.php'; ?>
    <?php include '../components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 mt-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    You're browsing as a guest. <a href="register.php" class="font-medium underline hover:text-blue-600">Create an account</a> to bookmark notes and track your downloads!
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Note Header -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($note['title']); ?></h1>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <form method="POST" class="inline" id="bookmarkForm">
                                <input type="hidden" name="toggle_bookmark" value="1">
                                <button type="submit" class="text-gray-500 hover:text-yellow-500 transition-colors">
                                    <svg class="w-6 h-6 <?php echo $isBookmarked ? 'text-yellow-500 fill-current' : ''; ?>" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-sm text-gray-500">
                                <a href="login.php" class="text-blue-500 hover:text-blue-600">Log in</a> to bookmark this note
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center space-x-4 mb-6">
                        <img src="<?php echo !empty($note['author_profile']) ? BASE_URL . '/' . $note['author_profile'] : BASE_URL . '/assets/images/' . ($note['author_gender'] === 'female' ? 'female.png' : 'male.png'); ?>" 
                             alt="<?php echo htmlspecialchars($note['author_name']); ?>" 
                             class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($note['author_name']); ?></h3>
                            <p class="text-sm text-gray-500">Published on <?php echo date('M d, Y', strtotime($note['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <img src="<?php echo !empty($note['author_profile']) ? BASE_URL . '/' . $note['author_profile'] : 'https://via.placeholder.com/40' ?>"
                                alt="Author"
                                class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($note['author_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($note['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="text-gray-500">
                            <?php echo number_format($note['views']); ?> views
                        </div>
                    </div>

                    <div class="prose max-w-none mb-6">
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($note['description'])); ?></p>
                    </div>

                    <?php if(!empty($note['categories'])): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach(explode(',', $note['categories']) as $category): ?>
                                <span class="bg-gray-100 text-gray-800 text-sm font-medium px-3 py-1 rounded">
                                    <?php echo htmlspecialchars($category); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PDF Preview -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold mb-6">Preview</h2>
                    <div class="border rounded-lg overflow-hidden">
                        <div id="pdfViewer" class="w-full" style="height: 800px;"></div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <a href="<?php echo BASE_URL . '/' . $note['file_path']; ?>" 
                           class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200"
                           download>
                            Download PDF
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Related Notes -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">Related Notes</h2>
                    <div class="space-y-4">
                        <?php foreach($relatedNotes as $relatedNote): ?>
                            <a href="?id=<?php echo $relatedNote['id']; ?>" class="block group">
                                <div class="flex space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-24 h-16 bg-gray-100 rounded overflow-hidden">
                                            <?php if($relatedNote['thumbnail']): ?>
                                                <img src="<?php echo BASE_URL . '/' . $relatedNote['thumbnail']; ?>"
                                                    alt="Note thumbnail"
                                                    class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold group-hover:text-blue-600 line-clamp-2">
                                            <?php echo htmlspecialchars($relatedNote['title']); ?>
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo htmlspecialchars($relatedNote['author_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo number_format($relatedNote['views']); ?> views
                                        </p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Note Information -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Note Information</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold">File Type</h3>
                            <p class="text-gray-600"><?php echo strtoupper(pathinfo($note['file_path'], PATHINFO_EXTENSION)); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold">File Size</h3>
                            <p class="text-gray-600">
                                <?php
                                $filesize = filesize(BASE_PATH . '/' . $note['file_path']);
                                echo $filesize > 1048576 ? 
                                    round($filesize/1048576, 2) . ' MB' : 
                                    round($filesize/1024, 2) . ' KB';
                                ?>
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold">Last Updated</h3>
                            <p class="text-gray-600"><?php echo date('M d, Y', strtotime($note['updated_at'])); ?></p>
                        </div>

                        <?php if($note['subject']): ?>
                            <div>
                                <h3 class="font-semibold">Subject</h3>
                                <p class="text-gray-600"><?php echo htmlspecialchars($note['subject']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if($note['school_year']): ?>
                            <div>
                                <h3 class="font-semibold">School Year</h3>
                                <p class="text-gray-600"><?php echo htmlspecialchars($note['school_year']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <p class="text-blue-800 mb-4">Sign up to access more study materials!</p>
                            <a href="register.php" 
                               class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center font-bold py-2 px-4 rounded-lg transition duration-200">
                                Get Started
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS animations
        AOS.init({
            duration: 1000,
            once: true
        });

        // Initialize PDF viewer
        const pdfUrl = '<?php echo BASE_URL . '/' . $note['file_path']; ?>';
        const container = document.getElementById('pdfViewer');

        // Load the PDF
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            // Load the first page
            pdf.getPage(1).then(function(page) {
                const viewport = page.getViewport({scale: 1.5});
                
                // Prepare canvas using PDF page dimensions
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                container.appendChild(canvas);

                // Render PDF page into canvas context
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                page.render(renderContext);
            });
        });

        // Handle bookmarking with AJAX
        const bookmarkForm = document.getElementById('bookmarkForm');
        if(bookmarkForm) {
            bookmarkForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch(window.location.href, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const icon = bookmarkForm.querySelector('svg');
                        if(data.isBookmarked) {
                            icon.classList.add('text-yellow-500', 'fill-current');
                        } else {
                            icon.classList.remove('text-yellow-500', 'fill-current');
                        }
                    }
                });
            });
        }
    </script>
</body>
</html>