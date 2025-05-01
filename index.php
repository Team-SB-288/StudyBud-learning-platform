<?php
require_once 'config/config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Educational Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.0/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.0/ScrollTrigger.min.js"></script>
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        }
        .feature-card {
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
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
<body class="bg-gray-50">
    <?php include 'components/loading.php'; ?>
    <?php include 'components/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-gradient min-h-screen flex items-center pt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="text-white" id="heroContent">
                    <h1 class="text-4xl md:text-6xl font-bold mb-6">
                        Learn, Share, and Grow Together
                    </h1>
                    <p class="text-xl md:text-2xl mb-8 opacity-90">
                        Join our educational community where you can share knowledge through videos, 
                        courses, and study materials.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="pages/register.php" 
                           class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-3 rounded-lg font-semibold transition duration-200">
                            Join Now
                        </a>
                        <a href="#features" 
                           class="border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 rounded-lg font-semibold transition duration-200">
                            Learn More
                        </a>
                    </div>
                </div>
                <div class="relative" id="heroImage">
                    <img src="assets/images/hero-illustration.svg" 
                         alt="Educational Platform" 
                         class="w-full max-w-lg mx-auto"
                         onerror="">
                         <!-- this.src='https://via.placeholder.com/600x400?text=Learning+Platform' -->
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12">Why Choose <?php echo SITE_NAME; ?>?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Video Learning -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Video Learning</h3>
                    <p class="text-gray-600">Access and share educational videos on various topics. Learn at your own pace.</p>
                </div>

                <!-- Online Courses -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Online Courses</h3>
                    <p class="text-gray-600">Create and enroll in comprehensive courses designed by expert educators.</p>
                </div>

                <!-- Study Materials -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Study Materials</h3>
                    <p class="text-gray-600">Share and download study notes, PDFs, and educational resources.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <?php
                $db = new Database();
                $conn = $db->getConnection();
                
                // Get platform statistics
                $stats = [
                    'users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                    'videos' => $conn->query("SELECT COUNT(*) FROM videos")->fetchColumn(),
                    'courses' => $conn->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
                    'notes' => $conn->query("SELECT COUNT(*) FROM notes")->fetchColumn()
                ];
                ?>
                
                <div class="stat-card">
                    <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo number_format($stats['users']); ?>+</div>
                    <div class="text-gray-600">Active Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="text-4xl font-bold text-green-600 mb-2"><?php echo number_format($stats['videos']); ?>+</div>
                    <div class="text-gray-600">Educational Videos</div>
                </div>
                
                <div class="stat-card">
                    <div class="text-4xl font-bold text-purple-600 mb-2"><?php echo number_format($stats['courses']); ?>+</div>
                    <div class="text-gray-600">Online Courses</div>
                </div>
                
                <div class="stat-card">
                    <div class="text-4xl font-bold text-indigo-600 mb-2"><?php echo number_format($stats['notes']); ?>+</div>
                    <div class="text-gray-600">Study Materials</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php' ?>

    <script>
        // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);

        // Hero animations
        gsap.from("#heroContent", {
            duration: 1,
            y: 50,
            opacity: 0,
            delay: 0.2
        });

        gsap.from("#heroImage", {
            duration: 1,
            x: 50,
            opacity: 0,
            delay: 0.5
        });

        // Feature cards animation
        gsap.from(".feature-card", {
            scrollTrigger: {
                trigger: "#features",
                start: "top center"
            },
            duration: 0.8,
            y: 50,
            opacity: 0,
            stagger: 0.2
        });

        // Statistics animation
        gsap.from(".stat-card", {
            scrollTrigger: {
                trigger: ".stat-card",
                start: "top center"
            },
            duration: 1,
            y: 30,
            opacity: 0,
            stagger: 0.2
        });
    </script>
</body>
</html>