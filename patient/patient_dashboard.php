<?php
require_once '../config.php';
session_start(); 
// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// Get patient data
$patient_id = $_SESSION['user']['id']; // Assuming user ID is stored in session
try {
    $stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        // Handle case where patient data is not found
        die("Patient data not found");
    }

    // Get upcoming appointments
    $appointments_stmt = $pdo->prepare("
        SELECT a.*, d.name AS doctor_name, d.specialization AS department 
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_email = ? AND a.status IN ('pending', 'confirmed')
        ORDER BY a.appointment_date ASC 
        LIMIT 3
    ");
    $appointments_stmt->execute([$patient['email']]);
    $appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get medical history
    $medical_history_stmt = $pdo->prepare("
        SELECT * FROM medical_history 
        WHERE patient_id = ?
        ORDER BY date_recorded DESC 
        LIMIT 3
    ");
    $medical_history_stmt->execute([$patient_id]);
    $medical_history = $medical_history_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get active prescriptions
    $prescriptions_stmt = $pdo->prepare("
        SELECT p.*, d.name AS doctor_name 
        FROM prescriptions p
        JOIN doctors d ON p.doctor_id = d.id
        WHERE p.patient_id = ? AND p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 3
    ");
    $prescriptions_stmt->execute([$patient_id]);
    $prescriptions = $prescriptions_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count stats (using prepared statements for security)
    $upcoming_appointments_count = 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_email = ? AND status IN ('pending', 'confirmed')");
    $stmt->execute([$patient['email']]);
    $upcoming_appointments_count = $stmt->fetchColumn();

    $active_prescriptions_count = 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ? AND status = 'active'");
    $stmt->execute([$patient_id]);
    $active_prescriptions_count = $stmt->fetchColumn();

    $pending_tests_count = 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_history WHERE patient_id = ? AND title LIKE '%Test%' AND attachment IS NULL");
    $stmt->execute([$patient_id]);
    $pending_tests_count = $stmt->fetchColumn();

    $total_appointments_count = 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_email = ?");
    $stmt->execute([$patient['email']]);
    $total_appointments_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCare | Patient Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/patient-dashboard.css">
 <link rel="stylesheet" href="style1.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                
                <span><?php echo htmlspecialchars($patient['first_name'] . ' ' . htmlspecialchars($patient['last_name'])); ?></span>
            </div>
            
            <div class="sidebar-menu">
                <h3>Menu</h3>
                <ul>
                    <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="appointment.php"><i class="fas fa-calendar-alt"></i> <span>Appointments</span></a></li>
                    <li><a href="medicle_record.php"><i class="fas fa-file-medical"></i> <span>Medical Records</span></a></li>
                             
                
                    <li><a href="beds.php"><i class="fas fa-bed"></i> <span>Bed Availability</span></a></li>
                    <li><a href="services.php"><i class="fas fa-procedures"></i> <span>Medical Services</span></a></li>
                    <li><a href="emergency.html"><i class="fas fa-phone-alt"></i> <span>Emergency Contacts</span></a></li>
                
            
                    <li><a href="doctors.php"><i class="fas fa-user-edit"></i> <span>Feedback</span></a></li>
                </ul>
                
                <h3>Account</h3>
                <ul>
                    <li><a href="profile.php"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Patient Dashboard</h1>
                
                <div class="user-actions">
                    <div class="notification">
                        <i class="fas fa-bell"></i>
                        <span class="badge">2</span>
                    </div>
                    
                    <div class="user-profile">
                        
                        <span><?php echo htmlspecialchars($patient['first_name'] . ' ' . htmlspecialchars($patient['last_name'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="cards">
                <div class="card">
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Upcoming Appointments</h3>
                    <div class="value"><?php echo $upcoming_appointments_count; ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 1 new
                    </div>
                </div>
                
                <div class="card secondary">
                    <div class="icon">
                        <i class="fas fa-file-prescription"></i>
                    </div>
                    <h3>Active Prescriptions</h3>
                    <div class="value"><?php echo $active_prescriptions_count; ?></div>
                    <div class="trend">
                        <i class="fas fa-equals"></i> No change
                    </div>
                </div>
                
                <div class="card accent">
                    <div class="icon">
                        <i class="fas fa-vial"></i>
                    </div>
                    <h3>Pending Tests</h3>
                    <div class="value"><?php echo $pending_tests_count; ?></div>
                    <div class="trend down">
                        <i class="fas fa-arrow-down"></i> 1 completed
                    </div>
                </div>
                
                <div class="card info">
                    <div class="icon">
                        <i class="fas fa-hospital-user"></i>
                    </div>
                    <h3>Total Visits</h3>
                    <div class="value"><?php echo $total_appointments_count; ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 2 this month
                    </div>
                </div>
            </div>
            
            <!-- Main Sections -->
            <div class="main-sections">
                <div class="appointments">
                    <div class="section-header">
                        <h2>Recent Appointments</h2>
                        <a href="appointment.php">View All</a>
                    </div>
                    
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Department</th>
                                <th>Status</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <div class="doctor-info">
                                          <?php if(!empty($doctor['profile_pic'])): ?>
                            <img src="../admin/uploads/<?= htmlspecialchars($doctor['profile_pic']) ?>" class="doctor-img mb-3" alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">
                        <?php else: ?>
                            <div class="doctor-img mb-3 no-image">
                                <i class="fas fa-user-md"></i>
                            </div>
                        <?php endif; ?>
                                        <div class="doctor-details">
                                            <h4><?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($appointment['department']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('M j, g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['department']); ?></td>
                                <td><span class="status <?php echo strtolower($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td>
                                    <?php if ($appointment['status'] == 'confirmed'): ?>
                                        
                                       
                                    <?php elseif ($appointment['status'] == 'pending'): ?>
                                        <button class="action-btn primary"><a href="appointment.php">Reschedule </a></button>
                                        
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="right-column">
                    <div class="quick-actions">
                        <div class="section-header">
                            <h2>Quick Actions</h2>
                        </div>
                        
                        <div class="quick-actions-grid">
                            <a href="appointment.php" class="action-card">
                                <i class="fas fa-calendar-plus"></i>
                                <h3>Book Appointment</h3>
                            </a>
                            <a href="hospital_services.php" class="action-card">
                                <i class="fas fa-prescription-bottle"></i>
                                <h3>Request Prescription</h3>
                            </a>
                            <a href="medicle_record.php" class="action-card">
                                <i class="fas fa-file-download"></i>
                                <h3>Download Records</h3>
                            </a>
                            <a href="doctors.php" class="action-card">
                                <i class="fas fa-comment-medical"></i>
                                <h3>Give Feedback</h3>
                            </a>
                        </div>
                        
                        <div class="emergency-section">
                            <button class="emergency-btn">
                                <i class="fas fa-phone-alt"></i> Emergency Contact
                            </button>
                        </div>
                    </div>
                    
                    <div class="medical-records">
                        <div class="section-header">
                            <h2>Medical History</h2>
                            <a href="medicle_record.php">View All</a>
                        </div>
                        
                        <div class="records-list">
                            <?php foreach ($medical_history as $record): ?>
                            <div class="record-item">
                                <div class="record-icon <?php echo strtolower(str_replace(' ', '-', $record['title'])); ?>">
                                    <?php if (strpos($record['title'], 'Test') !== false): ?>
                                        <i class="fas fa-vial"></i>
                                    <?php elseif (strpos($record['title'], 'Prescription') !== false): ?>
                                        <i class="fas fa-prescription"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file-medical-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="record-details">
                                    <h4><?php echo htmlspecialchars($record['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($record['description']); ?></p>
                                </div>
                                <div class="record-date"><?php echo date('M j, Y', strtotime($record['date_recorded'])); ?></div>
                                <div class="record-download">
                                    <?php if ($record['attachment']): ?>
                                        <a href="download.php?file=<?php echo urlencode($record['attachment']); ?>"><i class="fas fa-download"></i></a>
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="prescriptions">
                        <div class="section-header">
                            <h2>Active Prescriptions</h2>
                            <a href="hospital_services.php">View All</a>
                        </div>
                        
                        <div class="prescriptions-list">
                            <?php foreach ($prescriptions as $prescription): 
                                $medications = json_decode($prescription['medications'], true);
                            ?>
                            <div class="prescription-item">
                                <div class="prescription-header">
                                    <h4>Prescribed by Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></h4>
                                    <span class="expiry-date">
                                        Expires: <?php echo date('M j, Y', strtotime($prescription['created_at'] . " + {$prescription['valid_days']} days")); ?>
                                    </span>
                                </div>
                                
                                <div class="medication-list">
                                    <?php foreach ($medications as $med): ?>
                                    <div class="medication-item">
                                        <span class="med-name"><?php echo htmlspecialchars($med['name']); ?></span>
                                        <span class="med-dosage"><?php echo htmlspecialchars($med['dosage']); ?></span>
                                        <span class="med-frequency"><?php echo htmlspecialchars($med['frequency']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="prescription-footer">
                                    <div class="instructions">
                                        <p><?php echo htmlspecialchars($prescription['instructions']); ?></p>
                                    </div>
                                    <button class="action-btn primary">Download</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>