<?php
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

require '../config.php';

// Get staff details
$staff_id = $_SESSION['user']['id'];
$current_date = date('Y-m-d');

// Get current shift assignment
$shift_query = "SELECT ss.*, s.name, s.start_time, s.end_time 
                FROM staff_shifts ss
                JOIN shifts s ON ss.shift_id = s.shift_id
                WHERE ss.staff_id = ? AND ss.shift_date = ? AND ss.status = 'Active'";
$shift_stmt = $pdo->prepare($shift_query);
$shift_stmt->execute([$staff_id, $current_date]);
$current_shift = $shift_stmt->fetch();

// Get staff profile details
$profile_query = "SELECT * FROM staff WHERE staff_id = ?";
$profile_stmt = $pdo->prepare($profile_query);
$profile_stmt->execute([$staff_id]);
$profile = $profile_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - MediCare Pro HMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #0d9c6e;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
        }
        
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu .nav-link {
            color: #495057;
            border-radius: 0;
            margin-bottom: 5px;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .sidebar-menu .nav-link:hover, 
        .sidebar-menu .nav-link.active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--primary-color);
        }
        
        .sidebar-menu .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .shift-card {
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
        }
        
        .profile-card .card-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                left: -var(--sidebar-width);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header text-center">
            <img src="<?php echo !empty($profile['photo']) ? '../admin/uploads/' . $profile['photo'] : '../../assets/default-profile.jpg'; ?>" 
                 alt="Profile Image" class="profile-img mb-3">
            <h5><?php echo htmlspecialchars($_SESSION['user']['full_name']); ?></h5>
            <p class="mb-0"><?php echo htmlspecialchars($profile['role']); ?></p>
            <small><?php echo htmlspecialchars($profile['department']); ?></small>
        </div>
        
        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shifts.php">
                        <i class="fas fa-calendar-alt"></i> My Shifts
                    </a>
                </li>
              
               
            </ul>
        </div>
        
        <div class="logout-btn">
            <a href="../logout.php" class="btn btn-danger btn-block">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Staff Dashboard</h2>
                <button class="btn btn-primary d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Current Shift Card -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shift-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Today's Shift</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($current_shift): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4><?php echo htmlspecialchars($current_shift['name']); ?></h4>
                                        <p class="mb-1">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('h:i A', strtotime($current_shift['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($current_shift['end_time'])); ?>
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-calendar-day"></i> 
                                            <?php echo date('F j, Y', strtotime($current_shift['shift_date'])); ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h5>No Shift Assigned Today</h5>
                                    <p class="text-muted">You don't have any shift scheduled for today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
               
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Summary -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card profile-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Profile Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <img src="<?php echo !empty($profile['photo']) ? '../../uploads/staff/' . $profile['photo'] : '../../assets/default-profile.jpg'; ?>" 
                                         alt="Profile Image" class="img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <h5><?php echo htmlspecialchars($_SESSION['user']['full_name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($profile['role']); ?></p>
                                </div>
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
                                            <p><strong>Department:</strong> <?php echo htmlspecialchars($profile['department']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status:</strong> 
                                                <span class="badge <?php echo $profile['status'] === 'Active' ? 'bg-success' : ($profile['status'] === 'On Leave' ? 'bg-warning' : 'bg-secondary'); ?>">
                                                    <?php echo htmlspecialchars($profile['status']); ?>
                                                </span>
                                            </p>
                                            <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($profile['created_at'])); ?></p>
                                            <p><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($profile['updated_at'])); ?></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <a href="profile.php" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });
    </script>
</body>
</html>