<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection with correct path
require_once(__DIR__ . '/../includes/db.php');

// Check if user is logged in and is a student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student information
$s_id = $_SESSION['user_id'];
$query = "SELECT s_id, s_fname, s_lname FROM students WHERE s_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $s_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Add user's full name to session
$_SESSION['user_name'] = $student['s_fname'] . ' ' . $student['s_lname'];

// Page routing
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Define the content path based on folder structure
switch($page) {
    case 'dashboard':
        $content = 'dashboard/index.php';
        $title = 'Dashboard';
        break;
    case 'schedule':
        $content = 'schedule/index.php';
        $title = 'My Schedule';
        break;
    case 'profile':
        $content = 'profile/index.php';
        $title = 'My Profile';
        break;
    default:
        $content = 'dashboard/index.php';
        $title = 'Dashboard';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student - <?php echo $title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    
    <style>
        :root {
            --primary: #3d52a0;
            --secondary: #7091E6;
            --tertiary: #8691E6;
            --quaternary: #adbbda;
            --background: #ede8f5;
            --sidebar-width: 250px;
            --card-border-radius: 0.75rem;
            --transition-speed: 0.3s;
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: var(--background);
            min-height: 100vh;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(145deg, var(--primary) 0%, var(--secondary) 100%);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            padding: 1.5rem;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: transform var(--transition-speed) ease;
            color: white;
        }

        .content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            background-color: var(--background);
            position: relative;
        }

        .sidebar-header {
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .student-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: rgba(255,255,255,0.1);
            border-radius: 1rem;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-label {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            margin-bottom: 0.5rem;
        }
        
        .nav-section a {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }
        
        .nav-section a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-section a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .nav-section a i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-footer a {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .sidebar-footer a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-footer a i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .content {
                margin-left: 0;
                width: 100%;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
        
        .card {
            background-color: white;
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 15px rgba(61, 82, 160, 0.1);
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(61, 82, 160, 0.15);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(61, 82, 160, 0.1);
            padding: 1.25rem;
            border-top-left-radius: var(--card-border-radius) !important;
            border-top-right-radius: var(--card-border-radius) !important;
        }

        .card-body {
            padding: 1.5rem;
            background-color: white;
        }

        .card-title {
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        .card-title i {
            font-size: 1.1em;
            margin-right: 0.5rem;
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3><?php echo htmlspecialchars($student['s_fname'] . ' ' . $student['s_lname']); ?></h3>
                <span class="student-badge">Student</span>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-label">Main</div>
                <a href="?page=dashboard" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Academic</div>
                <a href="?page=schedule" class="<?php echo $page === 'schedule' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar3"></i>
                    <span>My Schedule</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Account</div>
                <a href="?page=profile" class="<?php echo $page === 'profile' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i>
                    <span>Profile</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <a href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Content -->
        <div class="content">
            <?php if (file_exists($content)): ?>
                <div class="container-fluid px-0">
                    <?php include $content; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">Page not found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebarToggle = document.createElement('button');
            sidebarToggle.classList.add('btn', 'btn-primary', 'position-fixed', 'top-0', 'start-0', 'm-2', 'd-md-none');
            sidebarToggle.innerHTML = '<i class="bi bi-list"></i>';
            document.body.appendChild(sidebarToggle);

            sidebarToggle.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
        });
    </script>
</body>
</html>