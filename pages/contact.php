<?php
require_once '../config/config.php';
require_once '../includes/User.php';
session_start();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if(empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Email headers
        $to = "admin@studybud.com"; // Replace with your admin email
        $headers = "From: $name <$email>" . "\r\n";
        $headers .= "Reply-To: $email" . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $emailBody = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";

        // Send email
        if(mail($to, "Contact Form: $subject", $emailBody, $headers)) {
            $success = "Thank you for your message. We'll get back to you soon!";
        } else {
            $error = "Sorry, there was an error sending your message. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo SITE_NAME; ?></title>
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
    <!-- Contact Hero Section -->
    <section class="py-20 bg-blue-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center text-white" data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Get in Touch</h1>
                <p class="text-xl md:text-2xl opacity-90 max-w-3xl mx-auto">
                    Have questions or feedback? We'd love to hear from you.
                </p>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg p-8" data-aos="fade-up">
                <?php if($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Name</label>
                        <input type="text" name="name" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Subject</label>
                        <input type="text" name="subject" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Message</label>
                        <textarea name="message" rows="6" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-700 shadow-sm hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Location -->
                <div class="text-center" data-aos="fade-up">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Our Location</h3>
                    <p class="text-gray-600">123 Education Street<br>Learning City, ED 12345</p>
                </div>

                <!-- Email -->
                <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Email Us</h3>
                    <p class="text-gray-600">info@studybud.com<br>support@studybud.com</p>
                </div>

                <!-- Phone -->
                <div class="text-center" data-aos="fade-up" data-aos-delay="400">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Call Us</h3>
                    <p class="text-gray-600">+1 (555) 123-4567<br>+1 (555) 987-6543</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php' ?>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>