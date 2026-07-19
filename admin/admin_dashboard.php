<?php
session_start();
// Database connection
$db_host = 'localhost';
$db_name = 'hms';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$admin_id = $_SESSION['user']['id'];
$admin_stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = ?");
$admin_stmt->execute([$admin_id]);
$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
// Fetch all dashboard data
$patients_count = $pdo->query("SELECT COUNT(*) FROM patient")->fetchColumn();
$doctors_count = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'Active'")->fetchColumn();
$appointments_count = $pdo->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()")->fetchColumn();
$staff_count = $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 'Active'")->fetchColumn();

// Fetch recent activities
$activities = $pdo->query("
    SELECT 'appointment' as type, id, CONCAT('New Appointment: ', patient_name, ' with Dr. ', doctor_id) as description, created_at 
    FROM appointments 
    ORDER BY created_at DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch department distribution
$departments = $pdo->query("
    SELECT 
        d.department_name as name, 
        COUNT(patient_id) as patient_count,
        COUNT(DISTINCT doc.id) as doctor_count
    FROM departments d
    LEFT JOIN patient p ON d.id = id
    LEFT JOIN doctors doc ON d.id = doc.id
    WHERE d.status = 'active'
    GROUP BY d.id, d.department_name
    ORDER BY patient_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$appointments_data = $pdo->query("
    SELECT 
        MONTHNAME(appointment_date) as month, 
        COUNT(*) as count 
    FROM appointments 
    WHERE YEAR(appointment_date) = YEAR(CURDATE())
    GROUP BY MONTH(appointment_date), MONTHNAME(appointment_date)
    ORDER BY MONTH(appointment_date)
")->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$appointment_counts = [];
foreach ($appointments_data as $data) {
    $months[] = substr($data['month'], 0, 3);
    $appointment_counts[] = $data['count'];
}

$dept_labels = [];
$dept_patient_counts = [];
$dept_doctor_counts = [];
foreach ($departments as $dept) {
    $dept_labels[] = $dept['name'];
    $dept_patient_counts[] = $dept['patient_count'];
    $dept_doctor_counts[] = $dept['doctor_count'];
}

// Helper function to display time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Pro HMS - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="logo.png" alt="MediCare HMS Logo" class="logo">MediCare HMS 
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="manage_patients.php" class="menu-item">
                <i class="fas fa-user-injured"></i>
                <span>Patients</span>
                <span class="badge rounded-pill bg-primary"><?= $patients_count ?></span>
            </a>
            
            <!-- Doctors Dropdown -->
            <div class="department-dropdown">
                <a href="#" class="menu-item dropdown-toggle" id="doctorsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-md"></i>
                    <span>Doctors</span>
                </a>
                <ul class="dropdown-menu" aria-labelledby="doctorsDropdown">
                    <li><a class="dropdown-item" href="add_doctor.php"><i class="fas fa-plus-circle me-2"></i> Add Doctor</a></li>
                    <li><a class="dropdown-item" href="doctors.php"><i class="fas fa-list me-2"></i> Manage Doctors</a></li>
                </ul>
            </div>
            
            <a href="appointment.php" class="menu-item">
                <i class="fas fa-calendar-check"></i>
                <span>Appointments</span>
                <span class="badge rounded-pill bg-danger"><?= $appointments_count ?></span>
            </a>
            
            <!-- Department Dropdown -->
            <div class="department-dropdown">
                <a href="#" class="menu-item dropdown-toggle" id="departmentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-procedures"></i>
                    <span>Departments</span>
                </a>
                <ul class="dropdown-menu" aria-labelledby="departmentDropdown">
                    <li><a class="dropdown-item" href="manage_departments.php"><i class="fas fa-list me-2"></i> Manage Departments</a></li>
                    <li><a class="dropdown-item" href="add_department.html"><i class="fas fa-plus-circle me-2"></i> Add New Department</a></li>
                </ul>
            </div>
            
            <a href="Hospital Management.php" class="menu-item">
                <i class="fas fa-pills"></i>
                <span>Hospital Management</span>
            </a>
            
            <a href="shift_management.php" class="menu-item">
                <i class="fas fa-pills"></i>
                <span>Shift Management</span>
            </a>
            
            <a href="performance.html" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Performance</span>
            </a>
            
            <a href="profile.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            
            <div class="mt-4 px-3">
                <a href="../logout.php" class="btn btn-danger w-100">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav">
            <div class="d-flex align-items-center">
                <button class="btn btn-link sidebar-toggle d-none me-3">
                    <i class="fas fa-bars"></i>
                </button>
               
            </div>
            
           <div class="user-menu d-flex align-items-center">
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
            <img src="<?= !empty($admin['profile_pic']) ? htmlspecialchars($admin['profile_pic']) : 'https://randomuser.me/api/portraits/men/32.jpg' ?>" alt="User" class="me-2">
            <div class="d-none d-md-block">
                <h6 class="mb-0"><?= htmlspecialchars($admin['full_name'] ?? 'Admin User') ?></h6>
                <small class="text-muted">Administrator</small>
            </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
           
        </ul>
    </div>
</div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-icon patients">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <h3><?= number_format($patients_count) ?></h3>
                    <p>Total Patients</p>
                    <div class="text-success small mt-2">
                        <i class="fas fa-caret-up me-1"></i> 
                        <?php 
                        $last_month = $pdo->query("SELECT COUNT(*) FROM patient WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
                        $increase = $last_month > 0 ? round(($patients_count - $last_month) / $last_month * 100, 1) : 0;
                        echo $increase > 0 ? $increase.'% increase' : 'No change';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-icon doctors">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3><?= number_format($doctors_count) ?></h3>
                    <p>Active Doctors</p>
                    <div class="text-success small mt-2">
                        <i class="fas fa-caret-up me-1"></i> 
                        <?php 
                        $new_doctors = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'Active' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
                        echo $new_doctors > 0 ? $new_doctors.' new this month' : 'No new doctors';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-icon appointments">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3><?= number_format($appointments_count) ?></h3>
                    <p>Today's Appointments</p>
                    <div class="text-danger small mt-2">
                        <i class="fas fa-caret-down me-1"></i> 
                        <?php 
                        $cancelled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE() AND status = 'Cancelled'")->fetchColumn();
                        echo $cancelled > 0 ? $cancelled.' cancellations' : 'No cancellations';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-icon staff">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?= number_format($staff_count) ?></h3>
                    <p>Active Staff</p>
                    <div class="text-success small mt-2">
                        <i class="fas fa-caret-up me-1"></i> 
                        <?php 
                        $new_staff = $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 'Active' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
                        echo $new_staff > 0 ? $new_staff.' new hires' : 'No new staff';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5>Appointments Overview (<?= date('Y') ?>)</h5>
                    <?php if(!empty($appointment_counts)): ?>
                        <canvas id="appointmentsChart" height="250"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">No appointment data available for this year</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5>Departments Distribution</h5>
                    <?php if(!empty($dept_patient_counts)): ?>
                        <canvas id="departmentsChart" height="250"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">No department data available</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity and Quick Actions -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Recent Activity</h5>
                        <a href="appointment.php" class="btn btn-sm btn-link">View All</a>
                    </div>
                    
                    <div class="activity-list">
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?= 
                                    $activity['type'] == 'appointment' ? 'calendar-plus' : 
                                    ($activity['type'] == 'patient' ? 'user-plus' : 'file-alt') 
                                ?>"></i>
                            </div>
                            <div class="activity-details flex-grow-1">
                                <h6><?= 
                                    $activity['type'] == 'appointment' ? 'New Appointment' : 
                                    ($activity['type'] == 'patient' ? 'New Patient' : 'System Activity')
                                ?></h6>
                                <p><?= htmlspecialchars($activity['description']) ?></p>
                            </div>
                            <div class="activity-time">
                                <?= time_elapsed_string($activity['created_at']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5>Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-12">
                            <a href="add_patient.php" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i> Add Patient
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-12">
                            <a href="create_appointment.php" class="btn btn-success w-100 mb-3">
                                <i class="fas fa-calendar-plus me-2"></i> Create Appointment
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-12">
                            <a href="staff_management.php" class="btn btn-warning w-100 mb-3">
                                <i class="fas fa-users me-2"></i> Manage Staff
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-12">
                            <a href="reports.php" class="btn btn-info w-100 mb-3">
                                <i class="fas fa-chart-line me-2"></i> View Reports
                            </a>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">System Status</h5>
                    <div class="list-group">
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between">
                                <span>Database</span>
                                <span class="badge bg-success">Online</span>
                            </div>
                        </div>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between">
                                <span>Server Load</span>
                                <span class="badge bg-success">24%</span>
                            </div>
                        </div>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between">
                                <span>Storage</span>
                                <span class="badge bg-warning">78% used</span>
                            </div>
                        </div>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between">
                                <span>Last Backup</span>
                                <span class="badge bg-danger">2 days ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        <?php if(!empty($appointment_counts)): ?>
        // Initialize appointments chart
        const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
        const appointmentsChart = new Chart(appointmentsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?= json_encode($appointment_counts) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if(!empty($dept_patient_counts)): ?>
        // Initialize departments chart - Bar chart version
        const departmentsCtx = document.getElementById('departmentsChart').getContext('2d');
        const departmentsChart = new Chart(departmentsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($dept_labels) ?>,
                datasets: [
                    {
                        label: 'Patients',
                        data: <?= json_encode($dept_patient_counts) ?>,
                        backgroundColor: '#3b82f6',
                        borderColor: '#3b82f6',
                        borderWidth: 1
                    },
                    {
                        label: 'Doctors',
                        data: <?= json_encode($dept_doctor_counts) ?>,
                        backgroundColor: '#10b981',
                        borderColor: '#10b981',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Patients and Doctors by Department'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Departments'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>