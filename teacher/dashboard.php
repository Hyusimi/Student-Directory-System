<?php
session_start();
require_once('../includes/db.php');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Get teacher information
$t_id = $_SESSION['user_id'];
$query = "SELECT t_id, t_fname, t_lname FROM teachers WHERE t_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $t_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

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
    case 'subjects':
        $content = 'subjects/index.php';
        $title = 'My Subjects';
        break;
    case 'records':
        $content = 'records/index.php';
        $title = 'Class Records';
        break;
    case 'profile':
        $content = 'profile/index.php';
        $title = 'Profile';
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
    <title>Teacher - <?php echo $title; ?></title>
    
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
        
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(145deg, var(--primary) 0%, var(--secondary) 100%);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: transform var(--transition-speed) ease;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .teacher-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        
        .nav-section {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-section-label {
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .nav-section a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-section a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-section a.active {
            background: rgba(255,255,255,0.2);
            font-weight: 500;
        }
        
        .nav-section a i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            background-color: var(--background);
            transition: margin-left var(--transition-speed) ease;
        }

        .content .container-fluid {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }

        .content .row {
            margin-right: -15px;
            margin-left: -15px;
            display: flex;
            flex-wrap: wrap;
        }

        /* Card styles */
        .card {
            margin-bottom: 1.5rem;
            border: none;
            border-radius: var(--card-border-radius);
            background: white;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        /* Dashboard specific fixes */
        [data-page="dashboard"] .container-fluid {
            width: calc(100% - 30px);
            max-width: 100%;
            overflow-x: hidden;
        }

        [data-page="dashboard"] .row {
            margin-right: -10px;
            margin-left: -10px;
            width: 100%;
        }

        [data-page="dashboard"] .col-md-4,
        [data-page="dashboard"] .col-lg-3,
        [data-page="dashboard"] .col-xl-3 {
            padding-right: 10px;
            padding-left: 10px;
            position: relative;
            width: 100%;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content.sidebar-shown {
                margin-left: var(--sidebar-width);
            }

            [data-page="dashboard"] .container-fluid {
                width: calc(100% - 20px);
            }

            [data-page="dashboard"] .row {
                margin-right: -10px;
                margin-left: -10px;
            }

            [data-page="dashboard"] .col-md-4,
            [data-page="dashboard"] .col-lg-3,
            [data-page="dashboard"] .col-xl-3 {
                padding-right: 5px;
                padding-left: 5px;
            }
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-footer a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-footer a:hover {
            opacity: 1;
        }
        
        .sidebar-footer i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <h3><?php echo $teacher['t_fname'] . ' ' . $teacher['t_lname']; ?></h3>
                    <div class="teacher-badge">Teacher</div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-label">Navigation</div>
                    <a href="?page=dashboard" class="<?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                    <a href="?page=schedule" class="<?php echo ($page === 'schedule') ? 'active' : ''; ?>">
                        <i class="bi bi-calendar3"></i>
                        My Schedule
                    </a>
                    <a href="?page=subjects" class="<?php echo ($page === 'subjects') ? 'active' : ''; ?>">
                        <i class="bi bi-book"></i>
                        My Subjects
                    </a>
                    <a href="?page=records" class="<?php echo ($page === 'records') ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-text"></i>
                        Records
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-label">Account</div>
                    <a href="?page=profile" class="<?php echo ($page === 'profile') ? 'active' : ''; ?>">
                        <i class="bi bi-person"></i>
                        Profile
                    </a>
                </div>
                
                <div class="sidebar-footer">
                    <a href="../logout.php">
                        <i class="bi bi-box-arrow-left"></i>
                        Logout
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <?php include $content; ?>
            </div>
        </div>
    </div>
</body>
</html>