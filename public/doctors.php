<?php
require_once '../config/db-config.php';
require_once '../app/includes/FileCache.php';

$cache    = new FileCache();
$cacheKey = 'doctors_page_list';
$doctors  = $cache->get($cacheKey);

if ($doctors === null) {
    // Cache miss — query the database
    $conn = getDBConnection();

    $query = "SELECT d.id, d.full_name, d.qualification, d.experience_years, d.specialty, d.department, d.photo
              FROM doctors d
              WHERE d.status = 'active'
              ORDER BY d.full_name";

    $result = $conn->query($query);
    $doctors = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
    $conn->close();

    // Cache for 1 hour
    $cache->set($cacheKey, $doctors, 3600);
}

// Color palette for doctor cards
$colors = ['blue', 'green', 'purple', 'red', 'yellow', 'indigo', 'pink', 'teal'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #1d4ed8 100%);
        }
    </style>
    <link rel="stylesheet" href="assets/css/responsive-sidebar.css">
</head>

<body class="font-sans antialiased">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-blue-600"></i>
                        <span class="ml-2 text-xl font-bold text-blue-600">MediCare</span>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="index.html" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Home</a>
                    <a href="services.html" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Services</a>
                    <a href="doctors.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Doctors</a>
                    <a href="about.html" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">About</a>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <a href="login.html" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50">Login</a>
                    <a href="register.html" class="ml-4 px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">Register</a>
                </div>
                <div class="-mr-2 flex items-center sm:hidden">
                    <button type="button" id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <i data-feather="menu" class="block h-6 w-6" id="menu-icon"></i>
                        <i data-feather="x" class="hidden h-6 w-6" id="close-icon"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="hidden sm:hidden" id="mobile-menu">
            <div class="pt-2 pb-3 space-y-1">
                <a href="index.html" class="border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Home</a>
                <a href="services.html" class="border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Services</a>
                <a href="doctors.php" class="bg-blue-50 border-blue-500 text-blue-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Doctors</a>
                <a href="about.html" class="border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">About</a>
            </div>
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="space-y-1 px-2">
                    <a href="login.html" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Login</a>
                    <a href="register.html" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-blue-600 hover:bg-blue-700">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-gradient text-white">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold sm:text-5xl md:text-6xl">
                    Meet Our Doctors
                </h1>
                <p class="mt-3 max-w-md mx-auto text-base text-blue-100 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                    Expert physicians dedicated to providing exceptional care
                </p>
            </div>
        </div>
    </div>

    <!-- Doctors Grid -->
    <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
        <?php if (empty($doctors)): ?>
            <div class="text-center text-gray-600">
                <p class="text-lg">No doctors available at the moment.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($doctors as $index => $doctor):
                    $colorClass = $colors[$index % count($colors)];
                    $colorMap = [
                        'blue' => 'bg-blue-600',
                        'green' => 'bg-green-600',
                        'purple' => 'bg-purple-600',
                        'red' => 'bg-red-600',
                        'yellow' => 'bg-yellow-600',
                        'indigo' => 'bg-indigo-600',
                        'pink' => 'bg-pink-600',
                        'teal' => 'bg-teal-600'
                    ];
                    $colorTextMap = [
                        'blue' => 'text-blue-600',
                        'green' => 'text-green-600',
                        'purple' => 'text-purple-600',
                        'red' => 'text-red-600',
                        'yellow' => 'text-yellow-600',
                        'indigo' => 'text-indigo-600',
                        'pink' => 'text-pink-600',
                        'teal' => 'text-teal-600'
                    ];
                    $colorBgMap = [
                        'blue' => 'bg-blue-600',
                        'green' => 'bg-green-600',
                        'purple' => 'bg-purple-600',
                        'red' => 'bg-red-600',
                        'yellow' => 'bg-yellow-600',
                        'indigo' => 'bg-indigo-600',
                        'pink' => 'bg-pink-600',
                        'teal' => 'bg-teal-600'
                    ];
                    $colorHoverMap = [
                        'blue' => 'hover:bg-blue-700',
                        'green' => 'hover:bg-green-700',
                        'purple' => 'hover:bg-purple-700',
                        'red' => 'hover:bg-red-700',
                        'yellow' => 'hover:bg-yellow-700',
                        'indigo' => 'hover:bg-indigo-700',
                        'pink' => 'hover:bg-pink-700',
                        'teal' => 'hover:bg-teal-700'
                    ];
                ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="<?php echo $colorMap[$colorClass]; ?> h-32"></div>
                        <div class="relative px-6 pb-6">
                            <div class="absolute -top-16 left-1/2 transform -translate-x-1/2">
                                <?php if (!empty($doctor['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($doctor['photo']); ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" class="h-32 w-32 rounded-full border-4 border-white object-cover">
                                <?php else: ?>
                                    <div class="h-32 w-32 rounded-full bg-gray-300 border-4 border-white flex items-center justify-center">
                                        <i data-feather="user" class="h-16 w-16 text-gray-600"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-20 text-center">
                                <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                                <p class="<?php echo $colorTextMap[$colorClass]; ?> font-medium mt-1">
                                    <?php echo htmlspecialchars($doctor['department']); ?>
                                </p>
                                <p class="text-gray-600 mt-2 text-sm">
                                    <?php
                                    $expText = $doctor['experience_years'] ?? 0;
                                    if ($expText > 0) {
                                        echo $expText . "+ years of experience";
                                    } else {
                                        echo "Medical specialist";
                                    }
                                    ?>
                                </p>
                                <?php if (!empty($doctor['specialty'])): ?>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <p class="font-medium text-gray-700">Specialty:</p>
                                        <p><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="flex justify-center space-x-4 text-sm text-gray-600">
                                        <div class="flex items-center">
                                            <i data-feather="award" class="h-4 w-4 mr-1"></i>
                                            <span><?php echo htmlspecialchars($doctor['qualification'] ?? 'Certified'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <a href="patient-book.php" class="mt-4 inline-block px-6 py-2 <?php echo $colorBgMap[$colorClass]; ?> text-white rounded-md <?php echo $colorHoverMap[$colorClass]; ?> transition-colors">Book Appointment</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- CTA Section -->
    <div class="bg-blue-600">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Ready to get started?</span>
                <span class="block text-blue-200">Book an appointment today.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="patient-book.php"
                        class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50">
                        Book Now
                    </a>
                </div>
                <div class="ml-3 inline-flex rounded-md shadow">
                    <a href="services.html"
                        class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-500 hover:bg-blue-400">
                        View Services
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white">
        <div class="max-w-7xl mx-auto py-12 px-4 overflow-hidden sm:px-6 lg:px-8">
            <nav class="-mx-5 -my-2 flex flex-wrap justify-center" aria-label="Footer">
                <div class="px-5 py-2">
                    <a href="index.html" class="text-base text-gray-500 hover:text-gray-900">Home</a>
                </div>
                <div class="px-5 py-2">
                    <a href="services.html" class="text-base text-gray-500 hover:text-gray-900">Services</a>
                </div>
                <div class="px-5 py-2">
                    <a href="doctors.php" class="text-base text-gray-500 hover:text-gray-900">Doctors</a>
                </div>
                <div class="px-5 py-2">
                    <a href="about.html" class="text-base text-gray-500 hover:text-gray-900">About</a>
                </div>
                <div class="px-5 py-2">
                    <a href="privacy.html" class="text-base text-gray-500 hover:text-gray-900">Privacy Policy</a>
                </div>
                <div class="px-5 py-2">
                    <a href="terms.html" class="text-base text-gray-500 hover:text-gray-900">Terms</a>
                </div>
            </nav>
            <div class="mt-8 flex justify-center space-x-6">
                <a href="#" class="text-gray-400 hover:text-gray-500">
                    <i data-feather="facebook" class="h-6 w-6"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-gray-500">
                    <i data-feather="instagram" class="h-6 w-6"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-gray-500">
                    <i data-feather="twitter" class="h-6 w-6"></i>
                </a>
            </div>
            <p class="mt-8 text-center text-base text-gray-400">
                &copy; 2026 MediCare Clinic. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        feather.replace();

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            menuIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');
        });
    </script>
</body>

</html>