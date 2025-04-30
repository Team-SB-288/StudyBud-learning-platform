<?php
require_once '../config/config.php';
require_once '../includes/User.php';
session_start();

$db = new Database();
$conn = $db->getConnection();

// Get all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Handle search and filters
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build the query
$query = "SELECT 
    CASE 
        WHEN content_type = 'video' THEN 'video'
        WHEN content_type = 'course' THEN 'course'
        ELSE 'note'
    END as type,
    content.*,
    users.name as author_name,
    users.profile_picture as author_profile,
    GROUP_CONCAT(categories.name) as categories
FROM (
    SELECT 'video' as content_type, id, user_id, title, description, file_path, views, created_at FROM videos
    UNION ALL
    SELECT 'course' as content_type, id, user_id, title, description, file_path, views, created_at FROM courses
    UNION ALL
    SELECT 'note' as content_type, id, user_id, title, description, file_path, views, created_at FROM notes
) as content
LEFT JOIN users ON content.user_id = users.id
LEFT JOIN video_categories ON content.content_type = 'video' AND content.id = video_categories.video_id
LEFT JOIN course_categories ON content.content_type = 'course' AND content.id = course_categories.course_id
LEFT JOIN note_categories ON content.content_type = 'note' AND content.id = note_categories.note_id
LEFT JOIN categories ON 
    video_categories.category_id = categories.id OR 
    course_categories.category_id = categories.id OR 
    note_categories.category_id = categories.id
WHERE 1=1";

$params = [];

if(!empty($search)) {
    $query .= " AND (content.title LIKE :search OR content.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if(!empty($category)) {
    $query .= " AND categories.id = :category_id";
    $params[':category_id'] = $category;
}

if(!empty($type)) {
    $query .= " AND content.content_type = :type";
    $params[':type'] = $type;
}

$query .= " GROUP BY content.content_type, content.id";

// Add sorting
switch($sort) {
    case 'views':
        $query .= " ORDER BY content.views DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY content.created_at ASC";
        break;
    default: // newest
        $query .= " ORDER BY content.created_at DESC";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $type ? ucfirst($type) . 's' : 'Browse'; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body class="bg-gray-50">
    <?php include '../components/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-16">
        <!-- Content Type Header -->
        <div class="mb-8 text-center">
            <?php if($type == 'video'): ?>
                <h1 class="text-3xl font-bold mb-2">Educational Videos</h1>
                <p class="text-gray-600">Watch and learn from our collection of educational videos</p>
            <?php elseif($type == 'course'): ?>
                <h1 class="text-3xl font-bold mb-2">Online Courses</h1>
                <p class="text-gray-600">Comprehensive courses to help you master new skills</p>
            <?php elseif($type == 'note'): ?>
                <h1 class="text-3xl font-bold mb-2">Study Library</h1>
                <p class="text-gray-600">Access study materials, notes, and educational resources</p>
            <?php else: ?>
                <h1 class="text-3xl font-bold mb-2">Browse All Content</h1>
                <p class="text-gray-600">Discover videos, courses, and study materials</p>
            <?php endif; ?>
        </div>

        <!-- Search and Filters -->
        <div class="mb-8">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-2">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search content..."
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <select name="category" 
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="type" 
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="">All Types</option>
                            <option value="video" <?php echo $type == 'video' ? 'selected' : ''; ?>>Videos</option>
                            <option value="course" <?php echo $type == 'course' ? 'selected' : ''; ?>>Courses</option>
                            <option value="note" <?php echo $type == 'note' ? 'selected' : ''; ?>>Notes</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <select name="sort" 
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="views" <?php echo $sort == 'views' ? 'selected' : ''; ?>>Most Viewed</option>
                    </select>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-6 rounded-lg transition duration-200">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach($results as $item): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden" data-aos="fade-up">
                    <!-- Content Type Header -->
                    <div class="relative p-4 <?php 
                        echo $item['type'] == 'video' ? 'bg-blue-50' : 
                            ($item['type'] == 'course' ? 'bg-green-50' : 'bg-purple-50'); 
                    ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <?php if($item['type'] == 'video'): ?>
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                <?php elseif($item['type'] == 'course'): ?>
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                <?php endif; ?>
                                <span class="font-medium <?php 
                                    echo $item['type'] == 'video' ? 'text-blue-800' : 
                                        ($item['type'] == 'course' ? 'text-green-800' : 'text-purple-800'); 
                                ?>"><?php echo ucfirst($item['type']); ?></span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo number_format($item['views']); ?> views
                            </div>
                        </div>
                    </div>

                    <!-- Content Info -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                        
                        <!-- Author Info -->
                        <div class="flex items-center mb-4">
                            <img src="<?php echo !empty($item['author_profile']) ? BASE_URL . '/' . $item['author_profile'] : 'https://via.placeholder.com/40' ?>"
                                alt="Author"
                                class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($item['author_name']); ?></p>
                                <p class="text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Categories -->
                        <?php if($item['categories']): ?>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php foreach(array_slice(explode(',', $item['categories']), 0, 3) as $cat): ?>
                                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- View Button -->
                        <a href="view-<?php echo $item['type']; ?>.php?id=<?php echo $item['id']; ?>"
                           class="block w-full text-center font-bold py-2 px-4 rounded-lg transition duration-200 <?php 
                           echo $item['type'] == 'video' ? 'bg-blue-500 hover:bg-blue-600 text-white' : 
                               ($item['type'] == 'course' ? 'bg-green-500 hover:bg-green-600 text-white' : 
                               'bg-purple-500 hover:bg-purple-600 text-white'); 
                           ?>">
                            <?php 
                            echo $item['type'] == 'video' ? 'Watch Video' : 
                                ($item['type'] == 'course' ? 'View Course' : 'View Notes'); 
                            ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if(empty($results)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-2xl font-bold text-gray-700 mb-4">No results found</h3>
                <?php if(!empty($search) || !empty($category) || !empty($type)): ?>
                    <p class="text-gray-500 mb-4">Try adjusting your search filters:</p>
                    <ul class="text-gray-500 mb-6">
                        <?php if(!empty($search)): ?>
                            <li>• Use fewer or different search terms</li>
                        <?php endif; ?>
                        <?php if(!empty($category)): ?>
                            <li>• Try a different category</li>
                        <?php endif; ?>
                        <?php if(!empty($type)): ?>
                            <li>• Check other content types</li>
                        <?php endif; ?>
                    </ul>
                    <a href="browse.php" class="inline-flex items-center text-blue-500 hover:text-blue-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Clear all filters
                    </a>
                <?php else: ?>
                    <p class="text-gray-500">Be the first to share educational content!</p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="mt-6 flex flex-wrap justify-center gap-4">
                            <a href="upload-video.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                Upload Video
                            </a>
                            <a href="upload-course.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                Create Course
                            </a>
                            <a href="upload-note.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                                Share Notes
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="mt-6">
                            <a href="login.php" class="text-blue-500 hover:text-blue-600">Log in</a>
                            <span class="text-gray-500 mx-2">or</span>
                            <a href="register.php" class="text-blue-500 hover:text-blue-600">create an account</a>
                            <span class="text-gray-500">to start sharing!</span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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