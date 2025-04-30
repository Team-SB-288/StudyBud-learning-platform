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

// Get course details
$stmt = $conn->prepare("
    SELECT 
        c.*,
        u.name as author_name,
        u.profile_picture as author_profile,
        u.gender as author_gender,
        GROUP_CONCAT(DISTINCT cat.name) as categories
    FROM courses c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN course_categories cc ON c.id = cc.course_id
    LEFT JOIN categories cat ON cc.category_id = cat.id
    WHERE c.id = :id
    GROUP BY c.id
");
$stmt->bindParam(":id", $_GET['id']);
$stmt->execute();
$course = $stmt->fetch();

if(!$course) {
    header("Location: browse.php");
    exit();
}

// Get course sections and lessons
$stmt = $conn->prepare("
    SELECT 
        s.id as section_id,
        s.title as section_title,
        s.description as section_description,
        l.id as lesson_id,
        l.title as lesson_title,
        l.type as lesson_type,
        l.duration as lesson_duration,
        l.file_path as lesson_file_path
    FROM course_sections s
    LEFT JOIN course_lessons l ON s.id = l.section_id
    WHERE s.course_id = :course_id
    ORDER BY s.order_index, l.order_index
");
$stmt->bindParam(":course_id", $course['id']);
$stmt->execute();
$sections = [];
while($row = $stmt->fetch()) {
    if(!isset($sections[$row['section_id']])) {
        $sections[$row['section_id']] = [
            'title' => $row['section_title'],
            'description' => $row['section_description'],
            'lessons' => []
        ];
    }
    if($row['lesson_id']) {
        $sections[$row['section_id']]['lessons'][] = [
            'id' => $row['lesson_id'],
            'title' => $row['lesson_title'],
            'type' => $row['lesson_type'],
            'duration' => $row['lesson_duration'],
            'file_path' => $row['lesson_file_path']
        ];
    }
}

// Get user's progress if logged in
$userProgress = [];
if(isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT lesson_id, completed_at
        FROM user_lesson_progress
        WHERE user_id = :user_id AND course_id = :course_id
    ");
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->bindParam(":course_id", $course['id']);
    $stmt->execute();
    while($row = $stmt->fetch()) {
        $userProgress[$row['lesson_id']] = $row['completed_at'];
    }
}

// Handle lesson completion
if(isset($_POST['complete_lesson']) && isset($_SESSION['user_id'])) {
    $lessonId = $_POST['lesson_id'];
    $stmt = $conn->prepare("
        INSERT INTO user_lesson_progress (user_id, course_id, lesson_id, completed_at)
        VALUES (:user_id, :course_id, :lesson_id, NOW())
        ON DUPLICATE KEY UPDATE completed_at = NOW()
    ");
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->bindParam(":course_id", $course['id']);
    $stmt->bindParam(":lesson_id", $lessonId);
    $stmt->execute();
    
    // Refresh progress
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Handle comment submission, editing, and deletion
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    if(isset($_POST['comment'])) {
        // Add new comment
        $comment = trim($_POST['comment']);
        if(!empty($comment)) {
            $stmt = $conn->prepare("INSERT INTO course_comments (user_id, course_id, content) VALUES (:user_id, :course_id, :content)");
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->bindParam(":course_id", $course['id']);
            $stmt->bindParam(":content", $comment);
            $stmt->execute();
        }
    } elseif(isset($_POST['edit_comment'])) {
        // Edit existing comment
        $commentId = $_POST['comment_id'];
        $newContent = trim($_POST['edit_comment']);
        
        // Verify comment belongs to current user or course owner
        $stmt = $conn->prepare("
            SELECT c.* FROM course_comments c 
            WHERE c.id = :comment_id 
            AND (c.user_id = :user_id OR :user_id = :course_owner_id)
        ");
        $stmt->bindParam(":comment_id", $commentId);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":course_owner_id", $course['user_id']);
        $stmt->execute();
        
        if($stmt->fetch() && !empty($newContent)) {
            $stmt = $conn->prepare("UPDATE course_comments SET content = :content WHERE id = :id");
            $stmt->bindParam(":content", $newContent);
            $stmt->bindParam(":id", $commentId);
            $stmt->execute();
        }
    } elseif(isset($_POST['delete_comment'])) {
        // Delete comment
        $commentId = $_POST['comment_id'];
        
        // Verify comment belongs to current user or course owner
        $stmt = $conn->prepare("
            SELECT c.* FROM course_comments c 
            WHERE c.id = :comment_id 
            AND (c.user_id = :user_id OR :user_id = :course_owner_id)
        ");
        $stmt->bindParam(":comment_id", $commentId);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":course_owner_id", $course['user_id']);
        $stmt->execute();
        
        if($stmt->fetch()) {
            $stmt = $conn->prepare("DELETE FROM course_comments WHERE id = :id");
            $stmt->bindParam(":id", $commentId);
            $stmt->execute();
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Get comments
$stmt = $conn->prepare("
    SELECT c.*, u.name as author_name, u.profile_picture as author_profile, u.gender as author_gender
    FROM course_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.course_id = :course_id
    ORDER BY c.created_at DESC
");
$stmt->bindParam(":course_id", $course['id']);
$stmt->execute();
$comments = $stmt->fetchAll();

// Update view count
if(!isset($_SESSION['viewed_courses']) || !in_array($course['id'], $_SESSION['viewed_courses'])) {
    $stmt = $conn->prepare("UPDATE courses SET views = views + 1 WHERE id = :id");
    $stmt->bindParam(":id", $course['id']);
    $stmt->execute();
    
    if(!isset($_SESSION['viewed_courses'])) {
        $_SESSION['viewed_courses'] = [];
    }
    $_SESSION['viewed_courses'][] = $course['id'];
}

// Calculate progress percentage
$totalLessons = 0;
$completedLessons = 0;
foreach($sections as $section) {
    $totalLessons += count($section['lessons']);
    foreach($section['lessons'] as $lesson) {
        if(isset($userProgress[$lesson['id']])) {
            $completedLessons++;
        }
    }
}
$progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/plyr/3.7.8/plyr.min.css">
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
                                    You're browsing as a guest. <a href="register.php" class="font-medium underline hover:text-blue-600">Create an account</a> to track your progress and earn completion certificates!
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Course Header -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($course['title']); ?></h1>
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <img src="<?php echo !empty($course['author_profile']) ? BASE_URL . '/' . $course['author_profile'] : BASE_URL . '/assets/images/' . ($course['author_gender'] === 'female' ? 'female.png' : 'male.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($course['author_name']); ?>" 
                                 class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($course['author_name']); ?></p>
                                <p class="text-sm text-gray-500">
                                    Last updated: <?php echo date('M d, Y', strtotime($course['updated_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-gray-500">
                            <span class="mr-4">
                                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <?php echo number_format($course['views']); ?> views
                            </span>
                        </div>
                    </div>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold">Your Progress</span>
                                <span class="text-sm text-gray-500"><?php echo $completedLessons; ?>/<?php echo $totalLessons; ?> lessons completed</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $progressPercentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="prose max-w-none">
                        <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <?php 
                        if($course['categories']) {
                            foreach(explode(',', $course['categories']) as $category): ?>
                                <span class="bg-gray-100 text-gray-800 text-sm font-medium px-3 py-1 rounded">
                                    <?php echo htmlspecialchars($category); ?>
                                </span>
                            <?php endforeach;
                        } ?>
                    </div>
                </div>

                <!-- Course Content -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold mb-6">Course Content</h2>

                    <div class="space-y-6">
                        <?php foreach($sections as $sectionId => $section): ?>
                            <div class="border rounded-lg overflow-hidden">
                                <div class="bg-gray-50 p-4">
                                    <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($section['title']); ?></h3>
                                    <?php if($section['description']): ?>
                                        <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($section['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="divide-y">
                                    <?php foreach($section['lessons'] as $lesson): ?>
                                        <div class="p-4 flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <?php if($lesson['type'] == 'video'): ?>
                                                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                <?php endif; ?>
                                                <div>
                                                    <h4 class="font-medium"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                                    <?php if($lesson['duration']): ?>
                                                        <p class="text-sm text-gray-500"><?php echo $lesson['duration']; ?> minutes</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <?php if(isset($userProgress[$lesson['id']])): ?>
                                                    <span class="text-green-500 flex items-center">
                                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Completed
                                                    </span>
                                                <?php elseif(isset($_SESSION['user_id'])): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                                                        <button type="submit" name="complete_lesson" 
                                                            class="text-blue-500 hover:text-blue-600">
                                                            Mark as Complete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="<?php echo BASE_URL . '/' . $lesson['file_path']; ?>" 
                                                   class="inline-flex items-center text-blue-500 hover:text-blue-600"
                                                   <?php echo $lesson['type'] == 'video' ? 'data-video="true"' : 'download'; ?>>
                                                    <?php echo $lesson['type'] == 'video' ? 'Watch' : 'Download'; ?>
                                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
                    <h2 class="text-2xl font-bold mb-6">Comments</h2>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form method="POST" class="mb-6">
                            <textarea name="comment" rows="3" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add a comment..."></textarea>
                            <button type="submit" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Submit</button>
                        </form>
                    <?php else: ?>
                        <p class="text-gray-600 mb-6">Please <a href="login.php" class="text-blue-500 hover:underline">log in</a> to leave a comment.</p>
                    <?php endif; ?>

                    <div class="space-y-4">
                        <?php foreach($comments as $comment): ?>
                            <div class="border rounded-lg p-4" id="comment-<?php echo $comment['id']; ?>">
                                <div class="flex items-start space-x-3">
                                    <img src="<?php echo !empty($comment['author_profile']) ? BASE_URL . '/' . $comment['author_profile'] : BASE_URL . '/assets/images/' . ($comment['author_gender'] === 'female' ? 'female.png' : 'male.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($comment['author_name']); ?>" 
                                         class="w-8 h-8 rounded-full">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <p class="font-semibold"><?php echo htmlspecialchars($comment['author_name']); ?></p>
                                                <span class="text-gray-500 text-sm">•</span>
                                                <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></p>
                                            </div>
                                            <?php if(isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['user_id'] == $course['user_id'])): ?>
                                                <div class="relative" data-comment-actions>
                                                    <button class="p-1 hover:bg-gray-200 rounded-full" onclick="toggleCommentMenu(<?php echo $comment['id']; ?>)">
                                                        <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="12" cy="12" r="2" />
                                                            <circle cx="12" cy="5" r="2" />
                                                            <circle cx="12" cy="19" r="2" />
                                                        </svg>
                                                    </button>
                                                    <div class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden" data-comment-menu="<?php echo $comment['id']; ?>">
                                                        <button class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100" onclick="editCommentStart(<?php echo $comment['id']; ?>)">Edit</button>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="delete_comment" value="1">
                                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                            <button type="submit" class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div data-comment-content="<?php echo $comment['id']; ?>">
                                            <p class="text-gray-800 mt-1"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        </div>
                                        <div class="hidden" data-comment-edit="<?php echo $comment['id']; ?>">
                                            <form method="POST">
                                                <textarea name="edit_comment" rows="3" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2"><?php echo htmlspecialchars($comment['content']); ?></textarea>
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <div class="flex justify-end space-x-2">
                                                    <button type="button" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800" onclick="cancelEdit(<?php echo $comment['id']; ?>)">Cancel</button>
                                                    <button type="submit" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-6">
                    <h2 class="text-xl font-bold mb-4">Course Information</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold">Duration</h3>
                            <p class="text-gray-600"><?php echo $course['duration']; ?> hours</p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold">Level</h3>
                            <p class="text-gray-600"><?php echo ucfirst($course['level']); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold">Requirements</h3>
                            <ul class="list-disc list-inside text-gray-600">
                                <?php 
                                $requirements = json_decode($course['requirements'], true);
                                foreach($requirements as $requirement): ?>
                                    <li><?php echo htmlspecialchars($requirement); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold">What You'll Learn</h3>
                            <ul class="list-disc list-inside text-gray-600">
                                <?php 
                                $objectives = json_decode($course['learning_objectives'], true);
                                foreach($objectives as $objective): ?>
                                    <li><?php echo htmlspecialchars($objective); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <p class="text-blue-800 mb-4">Sign up to track your progress and earn certificates!</p>
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

    <!-- Video Modal -->
    <div id="videoModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-75"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl">
                <div class="relative">
                    <button id="closeModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="aspect-w-16 aspect-h-9">
                        <video id="lessonPlayer" playsinline controls></video>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/plyr/3.7.8/plyr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS animations
        AOS.init({
            duration: 1000,
            once: true
        });

        // Video modal functionality
        const modal = document.getElementById('videoModal');
        const player = new Plyr('#lessonPlayer', {
            controls: [
                'play-large',
                'play',
                'progress',
                'current-time',
                'mute',
                'volume',
                'captions',
                'settings',
                'pip',
                'airplay',
                'fullscreen'
            ]
        });

        document.querySelectorAll('a[data-video="true"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const video = document.getElementById('lessonPlayer');
                video.src = link.href;
                modal.classList.remove('hidden');
                player.play();
            });
        });

        document.getElementById('closeModal').addEventListener('click', () => {
            modal.classList.add('hidden');
            player.stop();
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if(e.key === 'Escape' && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                player.stop();
            }
        });

        // Edit comment functionality
        function editComment(button) {
            const commentDiv = button.closest('.border');
            const commentText = commentDiv.querySelector('p.text-gray-800').innerText;
            const commentId = commentDiv.querySelector('input[name="comment_id"]').value;

            const editForm = document.createElement('form');
            editForm.method = 'POST';
            editForm.classList.add('mt-2');

            const textarea = document.createElement('textarea');
            textarea.name = 'edit_comment';
            textarea.rows = 3;
            textarea.classList.add('w-full', 'p-3', 'border', 'rounded-lg', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500');
            textarea.value = commentText;

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'comment_id';
            hiddenInput.value = commentId;

            const submitButton = document.createElement('button');
            submitButton.type = 'submit';
            submitButton.classList.add('mt-2', 'bg-blue-500', 'hover:bg-blue-600', 'text-white', 'font-bold', 'py-2', 'px-4', 'rounded-lg');
            submitButton.innerText = 'Save';

            editForm.appendChild(textarea);
            editForm.appendChild(hiddenInput);
            editForm.appendChild(submitButton);

            commentDiv.querySelector('p.text-gray-800').replaceWith(editForm);
            button.remove();
        }

        function toggleCommentMenu(commentId) {
            const allMenus = document.querySelectorAll('[data-comment-menu]');
            allMenus.forEach(menu => {
                if (menu.getAttribute('data-comment-menu') != commentId) {
                    menu.classList.add('hidden');
                }
            });
            
            const menu = document.querySelector(`[data-comment-menu="${commentId}"]`);
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[data-comment-actions]')) {
                document.querySelectorAll('[data-comment-menu]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        function editCommentStart(commentId) {
            // Hide the menu
            document.querySelector(`[data-comment-menu="${commentId}"]`).classList.add('hidden');
            
            // Show edit form and hide content
            document.querySelector(`[data-comment-content="${commentId}"]`).classList.add('hidden');
            document.querySelector(`[data-comment-edit="${commentId}"]`).classList.remove('hidden');
        }

        function cancelEdit(commentId) {
            // Hide edit form and show content
            document.querySelector(`[data-comment-content="${commentId}"]`).classList.remove('hidden');
            document.querySelector(`[data-comment-edit="${commentId}"]`).classList.add('hidden');
        }
    </script>
</body>
</html>