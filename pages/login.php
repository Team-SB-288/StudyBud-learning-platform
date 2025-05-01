<?php
require_once '../config/config.php';
require_once '../includes/User.php';

session_start();

if(User::isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = new User();
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if($user->login($login, $password)) {
        header("Location: " . BASE_URL . "/pages/dashboard.php");
        exit();
    } else {
        $error = "Invalid username/email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudyBud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        ::-webkit-scrollbar {
            display: none;
        }
        
        /* Hide scrollbar for IE, Edge and Firefox */
        body {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        main {
            flex: 1;
        }

        footer {
            margin-top: auto;
        }

        .form__group {
            position: relative;
            padding: 20px 0 0;
            width: 100%;
        }

        .form__field {
            font-family: inherit;
            width: 100%;
            border: none;
            border-bottom: 2px solid #9b9b9b;
            outline: 0;
            font-size: 17px;
            color: #000;
            padding: 7px 0;
            background: transparent;
            transition: border-color 0.2s;
        }

        .form__field::placeholder {
            color: transparent;
        }

        .form__field:placeholder-shown ~ .form__label {
            font-size: 17px;
            cursor: text;
            top: 20px;
        }

        .form__label {
            position: absolute;
            top: 0;
            display: block;
            transition: 0.2s;
            font-size: 17px;
            color: #9b9b9b;
            pointer-events: none;
        }

        .form__field:focus {
            padding-bottom: 6px;
            font-weight: 700;
            border-width: 3px;
            border-image: linear-gradient(to right, #116399, #38caef);
            border-image-slice: 1;
        }

        .form__field:focus ~ .form__label {
            position: absolute;
            top: 0;
            display: block;
            transition: 0.2s;
            font-size: 17px;
            color: #38caef;
            font-weight: 700;
        }

        .form__field:required, .form__field:invalid {
            box-shadow: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../components/loading.php'; ?>
    <!-- Navigation Bar -->
    <?php include '../components/navbar.php'; ?>

    <main>
        <div class="container mx-auto px-4 py-8 mt-16">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold text-center mb-6">Welcome Back</h1>
                
                <?php if($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <div class="form__group field">
                            <input type="text" name="login" class="form__field" placeholder="Username or Email" required>
                            <label for="login" class="form__label">Username or Email</label>
                        </div>
                    </div>

                    <div>
                        <div class="form__group field">
                            <input type="password" name="password" class="form__field" placeholder="Password" required>
                            <label for="password" class="form__label">Password</label>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        Login
                    </button>
                </form>

                <p class="mt-4 text-center text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="text-blue-500 hover:text-blue-600">Register here</a>
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../components/footer.php' ?>

</body>
</html>