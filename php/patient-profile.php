<?php
session_start();

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login if not authenticated or not a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../html/login.html');
    exit;
}

// Database connection (copied from register.php)
$host = "localhost";
$dbname = "medicare";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../html/login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$success = false;
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $insurance = trim($_POST['insurance'] ?? '');

    $stmt = $conn->prepare("UPDATE users SET phone=?, dob=?, gender=?, address=?, insurance=? WHERE id=?");
    $stmt->bind_param("sssssi", $phone, $dob, $gender, $address, $insurance, $userId);
    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "Failed to update profile.";
    }
    $stmt->close();
}

// Fetch user profile from database
$stmt = $conn->prepare("SELECT full_name, email, phone, dob, gender, address, insurance FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($fullName, $email, $phone, $dob, $gender, $address, $insurance);
$stmt->fetch();
$stmt->close();
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s;
        }

        .sidebar-collapsed {
            width: 5rem;
        }

        .sidebar-expanded {
            width: 16rem;
        }

        .main-content {
            transition: margin-left 0.3s;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #374151;
        }

        .form-input:focus,
        .form-select:focus {
            outline: 2px solid #2563eb;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar bg-blue-800 text-white" id="sidebar">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-blue-700">
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-white"></i>
                        <span class="ml-2 text-xl font-bold">MediCare</span>
                    </div>
                    <button class="text-blue-200 hover:text-white md:hidden" id="menuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <nav class="p-4">
                        <div class="space-y-1">
                            <a href="../html/patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="../html/patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="../html/patient-book.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="../html/patient-records.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-text" class="mr-3 h-5 w-5"></i>
                                Medical Records
                            </a>
                            <a href="../html/patient-prescriptions.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-plus" class="mr-3 h-5 w-5"></i>
                                Prescriptions
                            </a>
                            <a href="../html/patient-messages.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-square" class="mr-3 h-5 w-5"></i>
                                Messages
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="../html/patient-profile.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Profile
                            </a>
                            <a href="../html/patient-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="../php/logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="log-out" class="mr-3 h-5 w-5"></i>
                                Logout
                            </a>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto w-full">
            <!-- Top navigation -->
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">My Profile</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-1 text-gray-400 hover:text-gray-500">
                            <i data-feather="bell" class="h-6 w-6"></i>
                        </button>
                        <div class="relative">
                                <img class="h-8 w-8 rounded-full" src="http://static.photos/people/200x200/1" alt="User profile">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($fullName); ?></span>
                                <i data-feather="chevron-down" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
                    <?php if ($success): ?>
                        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">Profile updated successfully.</div>
                    <?php elseif ($error): ?>
                        <div class="mb-4 p-3 rounded bg-red-100 text-red-800"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="post" class="space-y-8">
                        <div class="flex items-center space-x-6 mb-8">
                            <img class="h-24 w-24 rounded-full object-cover border-2 border-blue-600" src="http://static.photos/people/200x200/1" alt="Profile photo">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($fullName); ?></h2>
                                <p class="text-gray-500"><?php echo htmlspecialchars($email); ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label mb-1">Phone</label>
                                <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            <div>
                                <label class="form-label mb-1">Date of Birth</label>
                                <input type="date" name="dob" class="form-input" value="<?php echo htmlspecialchars($dob); ?>">
                            </div>
                            <div>
                                <label class="form-label mb-1">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Male" <?php if ($gender == 'Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if ($gender == 'Female') echo 'selected'; ?>>Female</option>
                                    <option value="Other" <?php if ($gender == 'Other') echo 'selected'; ?>>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label mb-1">Address</label>
                                <input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($address); ?>">
                            </div>
                            <div>
                                <label class="form-label mb-1">Insurance</label>
                                <input type="text" name="insurance" class="form-input" value="<?php echo htmlspecialchars($insurance); ?>">
                            </div>
                            <div>
                                <label class="form-label mb-1">Patient ID</label>
                                <input type="text" class="form-input bg-gray-100" value="<?php echo htmlspecialchars($userId); ?>" disabled>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" name="edit_profile" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i data-feather="save" class="h-4 w-4 mr-2"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        feather.replace();
    </script>
</body>

</html>

