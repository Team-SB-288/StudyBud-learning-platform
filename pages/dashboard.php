<?php
require_once '../config/config.php';
require_once '../includes/User.php';

session_start();

if(!User::isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit();
}

$user = new User();
$userData = $user->getUserById($_SESSION['user_id']);

// Get user's content counts using PDO
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM videos WHERE user_id = :uid) as video_count,
    (SELECT COUNT(*) FROM courses WHERE user_id = :uid) as course_count,
    (SELECT COUNT(*) FROM notes WHERE user_id = :uid) as note_count,
    (SELECT SUM(views) FROM videos WHERE user_id = :uid) as total_video_views,
    (SELECT SUM(views) FROM courses WHERE user_id = :uid) as total_course_views,
    (SELECT SUM(views) FROM notes WHERE user_id = :uid) as total_note_views");
$stmt->bindParam(":uid", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->fetch();

// Get user's recent content
$stmt = $conn->prepare("SELECT 'video' as type, title, created_at, views FROM videos WHERE user_id = :uid
                    UNION ALL
                    SELECT 'course' as type, title, created_at, views FROM courses WHERE user_id = :uid
                    UNION ALL
                    SELECT 'note' as type, title, created_at, views FROM notes WHERE user_id = :uid
                    ORDER BY created_at DESC LIMIT 5");
$stmt->bindParam(":uid", $_SESSION['user_id']);
$stmt->execute();
$recentContent = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <?php include '../components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 mt-16">
        <!-- Profile Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6" data-aos="fade-right">
                    <div class="text-center">
                        <img src="<?php 
                            if (!empty($userData['profile_picture'])) {
                                // For uploaded profile pictures
                                echo str_starts_with($userData['profile_picture'], 'http') 
                                    ? $userData['profile_picture'] 
                                    : BASE_URL . '/' . $userData['profile_picture'];
                            } else {
                                // For default gender-based images
                                echo BASE_URL . '/assets/images/' . ($userData['gender'] === 'female' ? 'female.png' : 'male.png');
                            }
                        ?>" 
                             alt="Profile" 
                             class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($userData['name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($userData['email']); ?></p>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-700"><?php echo htmlspecialchars($userData['bio'] ?? 'No bio added yet.'); ?></p>
                    </div>
                    <div class="mt-4">
                        <a href="edit-profile.php" class="block text-center bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Content Stats -->
                    <div class="bg-white rounded-lg shadow-lg p-6" data-aos="fade-up">
                        <h3 class="text-lg font-bold mb-4">Your Content</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-500"><?php echo $stats['video_count']; ?></div>
                                <div class="text-gray-600">Videos</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-500"><?php echo $stats['course_count']; ?></div>
                                <div class="text-gray-600">Courses</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-500"><?php echo $stats['note_count']; ?></div>
                                <div class="text-gray-600">Notes</div>
                            </div>
                        </div>
                    </div>

                    <!-- Views Stats -->
                    <div class="bg-white rounded-lg shadow-lg p-6" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="text-lg font-bold mb-4">Total Views</h3>
                        <canvas id="viewsChart"></canvas>
                    </div>
                </div>

                <!-- Recent Content -->
                <div class="mt-6 bg-white rounded-lg shadow-lg p-6" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-lg font-bold mb-4">Recent Content</h3>
                    <div class="space-y-4">
                        <?php foreach($recentContent as $content): ?>
                            <div class="flex items-center justify-between border-b pb-2">
                                <div>
                                    <h4 class="font-semibold"><?php echo htmlspecialchars($content['title']); ?></h4>
                                    <p class="text-sm text-gray-600">
                                        Type: <?php echo ucfirst($content['type']); ?> | 
                                        Views: <?php echo $content['views']; ?>
                                    </p>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($content['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <a href="upload-video.php" class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-6 text-center transition duration-200" data-aos="fade-up">
                <h3 class="text-xl font-bold">Upload Video</h3>
                <p class="mt-2">Share your educational videos</p>
            </a>
            <a href="upload-course.php" class="bg-green-500 hover:bg-green-600 text-white rounded-lg p-6 text-center transition duration-200" data-aos="fade-up" data-aos-delay="100">
                <h3 class="text-xl font-bold">Create Course</h3>
                <p class="mt-2">Start a new educational course</p>
            </a>
            <a href="upload-note.php" class="bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-6 text-center transition duration-200" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-xl font-bold">Share Notes</h3>
                <p class="mt-2">Upload study materials</p>
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Initialize views chart
        const ctx = document.getElementById('viewsChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Videos', 'Courses', 'Notes'],
                datasets: [{
                    data: [
                        <?php echo (int)$stats['total_video_views']; ?>,
                        <?php echo (int)$stats['total_course_views']; ?>,
                        <?php echo (int)$stats['total_note_views']; ?>
                    ],
                    backgroundColor: [
                        '#3B82F6',
                        '#10B981',
                        '#8B5CF6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>