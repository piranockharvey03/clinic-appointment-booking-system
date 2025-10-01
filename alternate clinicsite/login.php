<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required.'; }
    if ($password === '') { $errors[] = 'Password is required.'; }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            redirect('patient-dashboard.php');
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MediCare Clinic</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .login-bg {
            background-image: linear-gradient(rgba(59, 130, 246, 0.8), rgba(29, 78, 216, 0.8)), url('http://static.photos/medical/1200x630/1');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex">
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-blue-600"></i>
                        <span class="ml-2 text-xl font-bold text-blue-600">MediCare</span>
                    </div>
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                        Sign in to your account
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Or <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">register for a new account</a>
                    </p>
                </div>

                <?php if ($errors): ?>
                <div class="mt-6 bg-red-50 border border-red-200 text-red-700 p-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        <?php foreach ($errors as $e): ?>
                        <li><?php echo h($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="mt-8">
                    <div class="mt-6">
                        <form action="" method="POST" class="space-y-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                                <div class="mt-1">
                                    <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo h($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <div class="mt-1">
                                    <input id="password" name="password" type="password" autocomplete="current-password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                                </div>
                                <div class="text-sm">
                                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Forgot your password?</a>
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden lg:block relative w-0 flex-1 login-bg">
            <div class="absolute inset-0 flex items-center justify-center p-12">
                <div class="bg-white bg-opacity-90 p-8 rounded-lg max-w-md">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Patient Portal</h3>
                    <p class="text-gray-600 mb-6">Access your medical records, book appointments, and communicate with your healthcare providers.</p>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i data-feather="check-circle" class="h-5 w-5 text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-700">View upcoming appointments</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i data-feather="check-circle" class="h-5 w-5 text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-700">Access test results</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i data-feather="check-circle" class="h-5 w-5 text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-700">Message your doctor</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>


