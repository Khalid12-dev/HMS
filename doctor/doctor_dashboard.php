<?php
session_start();

// Redirect if not logged in as doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

$doctor_id = $_SESSION['user']['id'];

// Fetch doctor profile
$doctor_stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$doctor_stmt->execute([$doctor_id]);
$doctor = $doctor_stmt->fetch();

// Fetch today's appointments
$today = date('Y-m-d');
$appointments_stmt = $pdo->prepare("
    SELECT a.*, d.specialization AS department 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.doctor_id = ? AND a.appointment_date = ?
    ORDER BY a.appointment_time ASC
");
$appointments_stmt->execute([$doctor_id, $today]);
$appointments = $appointments_stmt->fetchAll();

// Count today's appointments
$today_count_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE doctor_id = ? AND appointment_date = ?
");
$today_count_stmt->execute([$doctor_id, $today]);
$today_appointments_count = $today_count_stmt->fetchColumn();

// Count pending appointments
$pending_count_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE doctor_id = ? AND status = 'pending'
");
$pending_count_stmt->execute([$doctor_id]);
$pending_appointments_count = $pending_count_stmt->fetchColumn();

// Count total patients
$patients_count_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT patient_email) FROM appointments 
    WHERE doctor_id = ?
");
$patients_count_stmt->execute([$doctor_id]);
$total_patients_count = $patients_count_stmt->fetchColumn();

// Count recent prescriptions (assuming you have a prescriptions table)
$prescriptions_count = 0; // Default if no prescriptions table
try {
    $prescriptions_count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM prescriptions 
        WHERE doctor_id = ? AND DATE(created_at) = ?
    ");
    $prescriptions_count_stmt->execute([$doctor_id, $today]);
    $prescriptions_count = $prescriptions_count_stmt->fetchColumn();
} catch (PDOException $e) {
    // Prescriptions table might not exist
    error_log("Prescriptions table error: " . $e->getMessage());
}

// Fetch recent patients (last 5 seen)
$recent_patients_stmt = $pdo->prepare("
    SELECT patient_name, patient_email, MAX(appointment_date) AS last_visit 
    FROM appointments
    WHERE doctor_id = ? AND status = 'confirmed'
    GROUP BY patient_email
    ORDER BY last_visit DESC
    LIMIT 5
");
$recent_patients_stmt->execute([$doctor_id]);
$recent_patients = $recent_patients_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCare | Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
           <div class="sidebar-header">
    <?php
        $defaultImage = '../admin/uploads/default.png'; // Default image if none provided
        $profilePic = !empty($doctor['profile_pic']) ? '../admin/uploads/' . htmlspecialchars($doctor['profile_pic']) : $defaultImage;
    ?>
    <img src="<?= $profilePic ?>" alt="Doctor" style="width: 60px; height: 60px; border-radius: 50%;">
    <span>Dr. <?= htmlspecialchars($doctor['name'] ?? 'User') ?></span>
</div>

            
            <div class="sidebar-menu">
                <h3>Menu</h3>
                <ul>
                    <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="availablity.php"><i class="fas fa-vial"></i> <span>Availability</span></a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> <span>Appointments</span></a></li>
                    <li><a href="patient_management.php"><i class="fas fa-users"></i> <span>Patients</span></a></li>
                    <li><a href="Prescription & Diagnosis.php"><i class="fas fa-prescription"></i> <span>Prescriptions</span></a></li>
                    <li><a href="#"><i class="fas fa-flask"></i> <span>Lab Tests</span></a></li>
                </ul>
                
                <h3>Settings</h3>
                <ul>
                    <li><a href="profile_management.php"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
                    <li><a href="change_password.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Doctor Dashboard</h1>
                
                <div class="user-actions">
                    <div class="notification">
                        <i class="fas fa-bell"></i>
                        <span class="badge"><?= $pending_appointments_count ?></span>
                    </div>
                    
                  <div class="sidebar-header">
    <?php
        $defaultImage = '../admin/uploads/default.png'; // Default image if none provided
        $profilePic = !empty($doctor['profile_pic']) ? '../admin/uploads/' . htmlspecialchars($doctor['profile_pic']) : $defaultImage;
    ?>
    <img src="<?= $profilePic ?>" alt="Doctor" style="width: 60px; height: 60px; border-radius: 50%;">
    <span>Dr. <?= htmlspecialchars($doctor['name'] ?? 'User') ?></span>
</div>

                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="cards">
                <div class="card">
                    <h3>Today's Appointments</h3>
                    <div class="value"><?= $today_appointments_count ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 
                        <?php 
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        $yesterday_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
                        $yesterday_stmt->execute([$doctor_id, $yesterday]);
                        $yesterday_count = $yesterday_stmt->fetchColumn();
                        echo abs($today_appointments_count - $yesterday_count) . ' from yesterday';
                        ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Pending Requests</h3>
                    <div class="value"><?= $pending_appointments_count ?></div>
                    <div class="trend down">
                        <i class="fas fa-arrow-down"></i> 
                        <?php
                        $last_week_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending' AND appointment_date BETWEEN ? AND ?");
                        $last_week_start = date('Y-m-d', strtotime('-7 days'));
                        $last_week_stmt->execute([$doctor_id, $last_week_start, $today]);
                        $last_week_count = $last_week_stmt->fetchColumn();
                        echo abs($pending_appointments_count - $last_week_count) . ' from last week';
                        ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Total Patients</h3>
                    <div class="value"><?= $total_patients_count ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 
                        <?php
                        $new_this_week_stmt = $pdo->prepare("
                            SELECT COUNT(DISTINCT patient_email) FROM appointments 
                            WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?
                        ");
                        $week_start = date('Y-m-d', strtotime('-7 days'));
                        $new_this_week_stmt->execute([$doctor_id, $week_start, $today]);
                        $new_this_week = $new_this_week_stmt->fetchColumn();
                        echo $new_this_week . ' this week';
                        ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Prescriptions</h3>
                    <div class="value"><?= $prescriptions_count ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 
                        <?php
                        $yesterday_prescriptions = 0;
                        try {
                            $yesterday_prescriptions_stmt = $pdo->prepare("
                                SELECT COUNT(*) FROM prescriptions 
                                WHERE doctor_id = ? AND DATE(created_at) = ?
                            ");
                            $yesterday_prescriptions_stmt->execute([$doctor_id, $yesterday]);
                            $yesterday_prescriptions = $yesterday_prescriptions_stmt->fetchColumn();
                            echo abs($prescriptions_count - $yesterday_prescriptions) . ' from yesterday';
                        } catch (PDOException $e) {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Sections -->
            <div class="main-sections">
                <div class="appointments">
                    <div class="section-header">
                        <h2>Upcoming Appointments</h2>
                        <a href="appointments.php">View All</a>
                    </div>
                    
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No appointments today</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td>
                                            <div class="patient-info">
                                                
                                                <div class="patient-details">
                                                    <h4><?= htmlspecialchars($appointment['patient_name']) ?></h4>
                                                    <p><?= htmlspecialchars($appointment['department']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                                        <td>
                                            <span class="status <?= $appointment['status'] ?>">
                                                <?= ucfirst($appointment['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <button class="action-btn accept">Accept</button>
                                                <button class="action-btn reject">Reject</button>
                                            <?php else: ?>
                                                <button class="action-btn accept">Start</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="right-column">
                    <div class="quick-actions">
                        <div class="section-header">
                            <h2>Quick Actions</h2>
                        </div>
                        
                        <div class="quick-actions-grid">
                            <a href="Prescription & Diagnosis.php" class="action-card">
                                <i class="fas fa-prescription"></i>
                                <h3>New Prescription</h3>
                            </a>
                            <a href="#" class="action-card">
                                <i class="fas fa-flask"></i>
                                <h3>Lab Test</h3>
                            </a>
                            <a href="#" class="action-card">
                                <i class="fas fa-file-medical"></i>
                                <h3>Diagnosis Report</h3>
                            </a>
                            <a href="availablity.php" class="action-card">
                                <i class="fas fa-calendar-plus"></i>
                                <h3>Add Availability</h3>
                            </a>
                        </div>
                    </div>
                    
                    <div class="patient-history">
                        <div class="section-header">
                            <h2>Recent Patients</h2>
                            <a href="patient_management.php">View All</a>
                        </div>
                        
                        <?php if (empty($recent_patients)): ?>
                            <p style="text-align: center; padding: 20px;">No recent patients</p>
                        <?php else: ?>
                            <?php foreach ($recent_patients as $patient): ?>
                                <div class="patient-history-item">
                                   
                                    <div class="patient-history-details">
                                        <h4><?= htmlspecialchars($patient['patient_name']) ?></h4>
                                        <p><?= htmlspecialchars($patient['patient_email']) ?></p>
                                    </div>
                                    <div class="patient-history-date">
                                        <?= date('M j', strtotime($patient['last_visit'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add functionality for action buttons
        document.querySelectorAll('.action-btn.accept').forEach(btn => {
            btn.addEventListener('click', function() {
                const appointmentRow = this.closest('tr');
                const patientName = appointmentRow.querySelector('.patient-details h4').textContent;
                
                if (this.textContent === 'Accept') {
                    if (confirm(`Accept appointment with ${patientName}?`)) {
                        // In a real app, you would make an AJAX call here
                        appointmentRow.querySelector('td:nth-child(3) span').textContent = 'Confirmed';
                        appointmentRow.querySelector('td:nth-child(3) span').className = 'status confirmed';
                        this.textContent = 'Start';
                        this.nextElementSibling.remove(); // Remove reject button
                    }
                } else {
                    // Start appointment
                    alert(`Starting appointment with ${patientName}`);
                }
            });
        });

        document.querySelectorAll('.action-btn.reject').forEach(btn => {
            btn.addEventListener('click', function() {
                const appointmentRow = this.closest('tr');
                const patientName = appointmentRow.querySelector('.patient-details h4').textContent;
                
                if (confirm(`Reject appointment with ${patientName}?`)) {
                    // In a real app, you would make an AJAX call here
                    appointmentRow.querySelector('td:nth-child(3) span').textContent = 'Cancelled';
                    appointmentRow.querySelector('td:nth-child(3) span').className = 'status cancelled';
                    this.closest('td').innerHTML = '<button class="action-btn accept">Reschedule</button>';
                }
            });
        });
    </script>
</body>
</html>