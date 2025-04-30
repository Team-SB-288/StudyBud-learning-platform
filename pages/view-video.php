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

// Get video details
$stmt = $conn->prepare("
    SELECT 
        v.*,
        u.name as author_name,
        u.profile_picture as author_profile,
        u.gender as author_gender,
        GROUP_CONCAT(c.name) as categories
    FROM videos v
    LEFT JOIN users u ON v.user_id = u.id
    LEFT JOIN video_categories vc ON v.id = vc.video_id
    LEFT JOIN categories c ON vc.category_id = c.id
    WHERE v.id = :id
    GROUP BY v.id
");
$stmt->bindParam(":id", $_GET['id']);
$stmt->execute();
$video = $stmt->fetch();

if(!$video) {
    header("Location: browse.php");
    exit();
}

// Update view count
if(!isset($_SESSION['viewed_videos']) || !in_array($video['id'], $_SESSION['viewed_videos'])) {
    $stmt = $conn->prepare("UPDATE videos SET views = views + 1 WHERE id = :id");
    $stmt->bindParam(":id", $video['id']);
    $stmt->execute();
    
    if(!isset($_SESSION['viewed_videos'])) {
        $_SESSION['viewed_videos'] = [];
    }
    $_SESSION['viewed_videos'][] = $video['id'];
}

// Handle comment submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $comment = trim($_POST['comment'] ?? '');
    if(!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, video_id, content, created_at) VALUES (:user_id, :video_id, :content, NOW())");
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":video_id", $video['id']);
        $stmt->bindParam(":content", $comment);
        $stmt->execute();
    }
}

// Get comments
$stmt = $conn->prepare("
    SELECT c.*, u.name as author_name, u.profile_picture as author_profile, u.gender as author_gender
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.video_id = :video_id
    ORDER BY c.created_at DESC
");
$stmt->bindParam(":video_id", $video['id']);
$stmt->execute();
$comments = $stmt->fetchAll();

// Get related videos
$stmt = $conn->prepare("
    SELECT 
        v.*,
        u.name as author_name,
        u.profile_picture as author_profile
    FROM videos v
    JOIN users u ON v.user_id = u.id
    WHERE v.id != :id
    ORDER BY v.views DESC
    LIMIT 5
");
$stmt->bindParam(":id", $video['id']);
$stmt->execute();
$relatedVideos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - <?php echo SITE_NAME; ?></title>
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
                                    You're browsing as a guest. <a href="register.php" class="font-medium underline hover:text-blue-600">Create an account</a> to comment and track your learning progress!
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Video Player -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <div class="aspect-w-16 aspect-h-9 mb-6">
                        <video id="player" playsinline controls data-poster="<?php echo !empty($video['thumbnail']) ? BASE_URL . '/' . $video['thumbnail'] : ''; ?>">
                            <source src="<?php echo BASE_URL . '/' . $video['file_path']; ?>" type="video/mp4" />
                        </video>
                    </div>

                    <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($video['title']); ?></h1>
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <img src="<?php echo !empty($video['author_profile']) ? BASE_URL . '/' . $video['author_profile'] : BASE_URL . '/assets/images/' . ($video['author_gender'] === 'female' ? 'female.png' : 'male.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($video['author_name']); ?>" 
                                 class="w-12 h-12 rounded-full object-cover">
                            <div>
                                <h3 class="font-semibold"><?php echo htmlspecialchars($video['author_name']); ?></h3>
                                <p class="text-sm text-gray-500">Published on <?php echo date('M d, Y', strtotime($video['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="text-gray-500">
                            <?php echo number_format($video['views']); ?> views
                        </div>
                    </div>

                    <div class="prose max-w-none">
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    </div>

                    <?php if(!empty($video['categories'])): ?>
                        <div class="flex flex-wrap gap-2 mt-6">
                            <?php foreach(explode(',', $video['categories']) as $category): ?>
                                <span class="bg-gray-100 text-gray-800 text-sm font-medium px-3 py-1 rounded">
                                    <?php echo htmlspecialchars($category); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
                    <h2 class="text-xl font-bold mb-4">Comments</h2>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form method="POST" class="mb-6">
                            <textarea name="comment" rows="3" 
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 focus:border-blue-500 focus:outline-none"
                                placeholder="Add a comment..."></textarea>
                            <button type="submit" 
                                class="mt-2 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                                Post Comment
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="space-y-6">
                        <?php foreach($comments as $comment): ?>
                            <div class="flex space-x-3">
                                <div class="flex-shrink-0">
                                    <img src="<?php echo !empty($comment['author_profile']) ? BASE_URL . '/' . $comment['author_profile'] : BASE_URL . '/assets/images/' . ($comment['author_gender'] === 'female' ? 'female.png' : 'male.png'); ?>"
                                        alt="<?php echo htmlspecialchars($comment['author_name']); ?>"
                                        class="w-10 h-10 rounded-full">
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <p class="font-semibold"><?php echo htmlspecialchars($comment['author_name']); ?></p>
                                        <span class="text-gray-500 text-sm">•</span>
                                        <p class="text-gray-500 text-sm">
                                            <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                                        </p>
                                    </div>
                                    <p class="mt-1 text-gray-700"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Related Videos</h2>
                    <div class="space-y-4">
                        <?php foreach($relatedVideos as $relatedVideo): ?>
                            <a href="?id=<?php echo $relatedVideo['id']; ?>" class="block group">
                                <div class="flex space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-24 h-16 bg-gray-100 rounded overflow-hidden">
                                            <!-- Video thumbnail -->
                                            <img src="<?php echo !empty($relatedVideo['thumbnail']) ? BASE_URL . '/' . $relatedVideo['thumbnail'] : 'https://via.placeholder.com/96x64' ?>"
                                                alt="Video thumbnail"
                                                class="w-full h-full object-cover">
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold group-hover:text-blue-600 line-clamp-2">
                                            <?php echo htmlspecialchars($relatedVideo['title']); ?>
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo htmlspecialchars($relatedVideo['author_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo number_format($relatedVideo['views']); ?> views
                                        </p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/plyr/3.7.8/plyr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize Plyr video player
        const player = new Plyr('#player', {
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

        // Initialize AOS animations
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>