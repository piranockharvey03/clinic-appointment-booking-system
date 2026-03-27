<?php
require_once '../../config/session-config.php';

// Redirect to login if not authenticated or not a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
    exit;
}

// Database connection
try {
    $host = "localhost";
    $dbname = "medicare";
    $username = "root";
    $password = "";
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed. Please try again later.");
    }
} catch (Exception $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("<div style='padding: 20px; margin: 20px; background: #fee; border-left: 4px solid #c33; color: #c33;'>Unable to connect to the database. Please contact support.</div>");
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../../public/login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$success = false;
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    try {
        // Get all available fields from the form
        $updates = [];
        $types = '';
        $values = [];

        // Check each field and add to updates if it exists in the form
        $fields = [
            'phone' => 's',
            'gender' => 's',
            'address' => 's',
            'insurance' => 's',
            'date_of_birth' => 's'
        ];

        foreach ($fields as $field => $type) {
            if ($field === 'date_of_birth' && isset($_POST['dob'])) {
                // Map 'dob' form field to 'date_of_birth' database field
                $updates[] = "date_of_birth = ?";
                $types .= $type;
                $values[] = trim($_POST['dob']);
            } elseif ($field !== 'date_of_birth' && isset($_POST[$field])) {
                $updates[] = "$field = ?";
                $types .= $type;
                $values[] = trim($_POST[$field]);
            }
        }

        if (!empty($updates)) {
            // Add user ID to values
            $types .= 'i';
            $values[] = $userId;

            // Build and execute the update query
            $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt === false) {
                throw new Exception("Database error: " . $conn->error);
            }

            // Bind parameters dynamically
            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                $success = true;
                // Refresh the page to show updated data
                header("Location: patient-profile.php");
                exit();
            } else {
                throw new Exception("Failed to update profile: " . $stmt->error);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Profile update error: " . $error);
    }
}

// Initialize profile variables with default values
$fullName = '';
$email = '';
$phone = '';
$gender = '';
$address = '';
$insurance = '';
$dob = '';

// Fetch user profile from database
try {
    // First, get the list of columns that exist in the users table
    $result = $conn->query("SHOW COLUMNS FROM users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    // Build the query with only existing columns
    $selectFields = [];
    $selectFields[] = in_array('full_name', $columns) ? 'full_name' : "'' as full_name";
    $selectFields[] = in_array('email', $columns) ? 'email' : "'' as email";
    $selectFields[] = in_array('phone', $columns) ? 'phone' : "'' as phone";
    $selectFields[] = in_array('gender', $columns) ? 'gender' : "'' as gender";
    $selectFields[] = in_array('address', $columns) ? 'address' : "'' as address";
    $selectFields[] = in_array('insurance', $columns) ? 'insurance' : "'' as insurance";
    $selectFields[] = in_array('date_of_birth', $columns) ? 'date_of_birth' : "'' as date_of_birth";

    $query = "SELECT " . implode(', ', $selectFields) . " FROM users WHERE id=?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to fetch profile: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        $fullName = $userData['full_name'] ?? '';
        $email = $userData['email'] ?? '';
        $phone = $userData['phone'] ?? '';
        $gender = $userData['gender'] ?? '';
        $address = $userData['address'] ?? '';
        $insurance = $userData['insurance'] ?? '';
        $dob = $userData['date_of_birth'] ?? '';
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error loading profile: " . $e->getMessage();
    error_log($error);
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
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
                            <a href="patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="../../public/patient-book.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="how-appointments-work.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="book-open" class="mr-3 h-5 w-5"></i>
                                How It Works
                            </a>
                            <a href="patient-messages.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-circle" class="mr-3 h-5 w-5"></i>
                                Messages
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="patient-profile.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Profile
                            </a>
                            <a href="patient-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="../auth/logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
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
                            <button class="flex items-center space-x-2">
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

                    <!-- Profile Information Display -->
                    <div class="mb-8 pb-8 border-b border-gray-200">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($fullName); ?></h2>
                        <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($email); ?></p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php if (!empty($gender)): ?>
                                <div class="bg-blue-50 p-3 rounded">
                                    <p class="text-xs font-semibold text-gray-600">Gender</p>
                                    <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars(ucfirst($gender)); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($dob)): ?>
                                <div class="bg-blue-50 p-3 rounded">
                                    <p class="text-xs font-semibold text-gray-600">Date of Birth</p>
                                    <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($dob); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($phone)): ?>
                                <div class="bg-blue-50 p-3 rounded">
                                    <p class="text-xs font-semibold text-gray-600">Phone</p>
                                    <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($phone); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($address)): ?>
                                <div class="bg-blue-50 p-3 rounded">
                                    <p class="text-xs font-semibold text-gray-600">Address</p>
                                    <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($address); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($insurance)): ?>
                                <div class="bg-blue-50 p-3 rounded">
                                    <p class="text-xs font-semibold text-gray-600">Insurance</p>
                                    <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($insurance); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Edit Profile</h3>
                    <form method="post" class="space-y-8">
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
                                    <option value="male" <?php if (strtolower($gender) == 'male') echo 'selected'; ?>>Male</option>
                                    <option value="female" <?php if (strtolower($gender) == 'female') echo 'selected'; ?>>Female</option>
                                    <option value="other" <?php if (strtolower($gender) == 'other') echo 'selected'; ?>>Other</option>
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

        // Add spinner animation
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);

        // Handle form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('profileBtn');
            const spinner = document.getElementById('profileSpinner');
            const text = document.getElementById('profileText');

            if (!this.checkValidity()) return;

            btn.disabled = true;
            spinner.classList.remove('hidden');
            text.textContent = 'Saving...';
        });
    </script>
</body>

</html>