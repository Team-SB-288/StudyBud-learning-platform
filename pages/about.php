<?php
require_once '../config/config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <style>
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
    <?php include '../components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 mt-16">
        <!-- About Hero Section -->
        <section class="py-20 bg-blue-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center text-white" data-aos="fade-up">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6">About <?php echo SITE_NAME; ?></h1>
                    <p class="text-xl md:text-2xl opacity-90 max-w-3xl mx-auto">
                        Empowering learners worldwide through accessible, community-driven educational content
                    </p>
                </div>
            </div>
        </section>

        <!-- Mission Section -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div data-aos="fade-right">
                        <h2 class="text-3xl font-bold mb-6">Our Mission</h2>
                        <p class="text-gray-600 mb-4">
                            At <?php echo SITE_NAME; ?>, we believe that knowledge should be accessible to everyone. Our mission 
                            is to create a collaborative learning environment where educators and students can share their 
                            expertise and learn from each other.
                        </p>
                        <p class="text-gray-600">
                            We strive to break down barriers to education by providing a platform where quality educational 
                            content can be freely shared and accessed by anyone, anywhere in the world.
                        </p>
                    </div>
                    <div class="relative" data-aos="fade-left">
                        <img src="../assets/images/mission.svg" 
                             alt="Our Mission" 
                             class="w-full max-w-lg mx-auto rounded-lg shadow-lg"
                             onerror="this.src='https://via.placeholder.com/600x400?text=Our+Mission'">
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">Our Core Values</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Accessibility -->
                    <div class="bg-white p-6 rounded-xl shadow-lg" data-aos="fade-up" data-aos-delay="0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Accessibility</h3>
                        <p class="text-gray-600">Making quality education available to everyone, regardless of their location or background.</p>
                    </div>

                    <!-- Community -->
                    <div class="bg-white p-6 rounded-xl shadow-lg" data-aos="fade-up" data-aos-delay="200">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Community-Driven</h3>
                        <p class="text-gray-600">Building a supportive community of learners and educators who help each other grow.</p>
                    </div>

                    <!-- Innovation -->
                    <div class="bg-white p-6 rounded-xl shadow-lg" data-aos="fade-up" data-aos-delay="400">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Innovation</h3>
                        <p class="text-gray-600">Continuously improving our platform to enhance the learning experience.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">Our Team</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="text-center" data-aos="fade-up">
                        <img src="../assets/images/team/founder.svg"
                             alt="Founder"
                             class="w-32 h-32 rounded-full mx-auto mb-4"
                             onerror="this.src='https://via.placeholder.com/128x128?text=F'">
                        <h3 class="text-lg font-semibold">John Doe</h3>
                        <p class="text-gray-600">Founder & CEO</p>
                    </div>
                    <!-- Add more team members as needed -->
                </div>
            </div>
        </section>

        <!-- Contact CTA -->
        <section class="py-20 bg-blue-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white mb-6" data-aos="fade-up">Join Our Community</h2>
                <p class="text-xl text-white opacity-90 mb-8" data-aos="fade-up" data-aos-delay="200">
                    Be part of our mission to make education accessible to everyone
                </p>
                <a href="register.php" 
                   class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200"
                   data-aos="fade-up" data-aos-delay="400">
                    Get Started Today
                </a>
            </div>
        </section>

        <!-- Footer -->
    <?php include 'components/footer.php' ?>
        
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