<?php
session_start();
require '../config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = $_SESSION['user']['id'];
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add new prescription
        if ($action === 'add_prescription') {
            $patient_id = $_POST['patient_id'];
            $medications = $_POST['medications'];
            $instructions = $_POST['instructions'];
            $valid_days = $_POST['valid_days'];
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO prescriptions 
                    (patient_id, doctor_id, medications, instructions, valid_days, status) 
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([
                    $patient_id,
                    $doctor_id,
                    json_encode($medications),
                    $instructions,
                    $valid_days
                ]);
                
                $message = "Prescription created successfully!";
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
            }
        }
        // Add lab test recommendation
        elseif ($action === 'add_lab_test') {
            $patient_id = $_POST['patient_id'];
            $test_name = $_POST['test_name'];
            $test_details = $_POST['test_details'];
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO lab_tests 
                    (patient_id, doctor_id, test_name, test_details, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $patient_id,
                    $doctor_id,
                    $test_name,
                    $test_details
                ]);
                
                $message = "Lab test recommended successfully!";
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
            }
        }
        // Upload diagnosis report
        elseif ($action === 'upload_diagnosis') {
            $patient_id = $_POST['patient_id'];
            $diagnosis_title = $_POST['diagnosis_title'];
            $diagnosis_details = $_POST['diagnosis_details'];
            
            // Handle file upload
            $report_file = null;
            if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/diagnosis_reports/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $filename = uniqid() . '_' . basename($_FILES['report_file']['name']);
                $targetPath = $uploadDir . $filename;
                
                // Validate file type and size
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (in_array($_FILES['report_file']['type'], $allowedTypes) && 
                    $_FILES['report_file']['size'] <= $maxSize) {
                    if (move_uploaded_file($_FILES['report_file']['tmp_name'], $targetPath)) {
                        $report_file = $filename;
                    }
                }
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO diagnosis_reports 
                    (patient_id, doctor_id, title, details, report_file) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $patient_id,
                    $doctor_id,
                    $diagnosis_title,
                    $diagnosis_details,
                    $report_file
                ]);
                
                $message = "Diagnosis report uploaded successfully!";
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch patients assigned to this doctor
try {
    $patients_stmt = $pdo->prepare("
        SELECT DISTINCT p.patient_id, p.first_name, p.last_name,  p.dob, p.gender 
        FROM patient p
        JOIN appointments a ON p.patient_id = patient_id
        WHERE a.doctor_id = ?
        ORDER BY p.first_name ASC
    ");
    $patients_stmt->execute([$doctor_id]);
    $patients = $patients_stmt->fetchAll();

    // If patient is selected, fetch their records
    $selected_patient = null;
    $prescriptions = [];
    $lab_tests = [];
    $diagnosis_reports = [];
    
    if (isset($_GET['patient_id'])) {
        $patient_id = $_GET['patient_id'];
        
        // Get patient details
        $patient_stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
        $patient_stmt->execute([$patient_id]);
        $selected_patient = $patient_stmt->fetch();
        
        if ($selected_patient) {
            // Get prescriptions
            $prescription_stmt = $pdo->prepare("
                SELECT * FROM prescriptions 
                WHERE patient_id = ? AND doctor_id = ?
                ORDER BY created_at DESC
            ");
            $prescription_stmt->execute([$patient_id, $doctor_id]);
            $prescriptions = $prescription_stmt->fetchAll();
            
            // Get lab tests
            $lab_test_stmt = $pdo->prepare("
                SELECT * FROM lab_tests 
                WHERE patient_id = ? AND doctor_id = ?
                ORDER BY created_at DESC
            ");
            $lab_test_stmt->execute([$patient_id, $doctor_id]);
            $lab_tests = $lab_test_stmt->fetchAll();
            
            // Get diagnosis reports
            $diagnosis_stmt = $pdo->prepare("
                SELECT * FROM diagnosis_reports 
                WHERE patient_id = ? AND doctor_id = ?
                ORDER BY created_at DESC
            ");
            $diagnosis_stmt->execute([$patient_id, $doctor_id]);
            $diagnosis_reports = $diagnosis_stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription & Diagnosis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #1abc9c;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
        }
        
        .top-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-brand {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .nav-brand i {
            margin-right: 10px;
        }
        
        .dashboard-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .dashboard-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: var(--primary);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 25px;
            font-weight: 600;
        }
        
        .patient-card {
            transition: all 0.3s;
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }
        
        .patient-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .patient-card.active {
            border-left: 4px solid var(--primary);
            background-color: #f1f8fe;
        }
        
        .badge-status {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .badge-active { background: var(--success); }
        .badge-pending { background: var(--warning); }
        .badge-completed { background: var(--info); }
        
        .medication-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #e9ecef;
        }
        
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .patient-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9f2fa 100%);
            border-radius: 12px;
            padding: 20px;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary);
        }
        
        .btn-action {
            border-radius: 50px;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .patient-info {
                flex-direction: column;
                text-align: center;
            }
            
            .avatar {
                margin-bottom: 15px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar top-nav">
        <div class="container">
            <div class="d-flex justify-content-between w-100 align-items-center">
                <a class="nav-brand" href="#">
                    <i class="fas fa-prescription-bottle-alt"></i>MediCare
                </a>
                <a href="doctor_dashboard.php" class="btn dashboard-btn">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-12">
                <h2 class="mb-4"><i class="fas fa-prescription me-2"></i>Prescription Management</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Patient List Column -->
            <div class="col-md-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Select Patient</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($patients)): ?>
                            <div class="alert alert-info">No patients found</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($patients as $patient): ?>
                                    <a href="?patient_id=<?php echo $patient['patient_id']; ?>" 
                                       class="list-group-item list-group-item-action patient-card <?php echo (isset($selected_patient)) && $selected_patient['patient_id'] == $patient['patient_id'] ? 'active' : ''; ?>">
                                        <div class="d-flex align-items-center">
                                            
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($patient['first_name'] . ' ' . htmlspecialchars($patient['last_name'])); ?></h6>
                                                <small class="text-muted"><?php echo !empty($patient['dob']) ? date_diff(date_create($patient['dob']), date_create('today'))->y . ' years' : 'Age not specified'; ?></small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Patient Details Column -->
            <div class="col-md-8">
                <?php if ($selected_patient): ?>
                    <!-- Patient Information -->
                    <div class="card mb-4 fade-in">
                        <div class="card-body patient-info">
                            <div class="d-flex align-items-center flex-wrap">
                               
                                <div>
                                    <h3><?php echo htmlspecialchars($selected_patient['first_name'] . ' ' . htmlspecialchars($selected_patient['last_name'])); ?></h3>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <p><i class="fas fa-birthday-cake me-2"></i> <strong>Age:</strong> <?php echo !empty($selected_patient['dob']) ? date_diff(date_create($selected_patient['dob']), date_create('today'))->y . ' years' : 'Not specified'; ?></p>
                                            <p><i class="fas fa-venus-mars me-2"></i> <strong>Gender:</strong> <?php echo htmlspecialchars($selected_patient['gender'] ?? 'Not specified'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><i class="fas fa-tint me-2"></i> <strong>Blood Group:</strong> <?php echo htmlspecialchars($selected_patient['blood_group'] ?? 'Not specified'); ?></p>
                                            <p><i class="fas fa-id-card me-2"></i> <strong>Patient ID:</strong> <?php echo htmlspecialchars($selected_patient['patient_id']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prescriptions Section -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-prescription me-2"></i>Prescriptions</h5>
                            <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                                <i class="fas fa-plus me-1"></i> New
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($prescriptions)): ?>
                                <div class="alert alert-info">No prescriptions found for this patient</div>
                            <?php else: ?>
                                <div class="accordion" id="prescriptionsAccordion">
                                    <?php foreach ($prescriptions as $index => $prescription): 
                                        $medications = json_decode($prescription['medications'], true);
                                        ?>
                                        <div class="accordion-item mb-2 border-0">
                                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                                <button class="accordion-button collapsed shadow-none" type="button" data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false" 
                                                        aria-controls="collapse<?php echo $index; ?>">
                                                    <div class="d-flex align-items-center w-100">
                                                        <span class="me-3">Prescription #<?php echo $prescription['id']; ?></span>
                                                        <span class="badge-status badge-<?php echo strtolower($prescription['status']); ?> me-2">
                                                            <?php echo ucfirst($prescription['status']); ?>
                                                        </span>
                                                        <small class="text-muted ms-auto">
                                                            <?php echo date('M j, Y', strtotime($prescription['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" 
                                                 aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#prescriptionsAccordion">
                                                <div class="accordion-body pt-0">
                                                    <h6 class="mb-3">Medications:</h6>
                                                    <?php foreach ($medications as $med): ?>
                                                        <div class="medication-item">
                                                            <div class="d-flex justify-content-between flex-wrap">
                                                                <div class="mb-2">
                                                                    <strong><?php echo htmlspecialchars($med['name']); ?></strong>
                                                                    <span class="ms-2"><?php echo htmlspecialchars($med['dosage']); ?></span>
                                                                </div>
                                                                <div>
                                                                    <small class="me-3"><i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($med['frequency']); ?></small>
                                                                    <small><i class="fas fa-calendar me-1"></i> <?php echo htmlspecialchars($med['duration']); ?></small>
                                                                </div>
                                                            </div>
                                                            <?php if (!empty($med['notes'])): ?>
                                                                <p class="mt-2 mb-0 text-muted"><small><?php echo htmlspecialchars($med['notes']); ?></small></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    
                                                    <h6 class="mt-4">Instructions:</h6>
                                                    <div class="bg-light p-3 rounded mb-3">
                                                        <?php echo nl2br(htmlspecialchars($prescription['instructions'])); ?>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar-check me-1"></i> Valid for <?php echo $prescription['valid_days']; ?> days
                                                        </small>
                                                        <a href="generate_prescription.php?id=<?php echo $prescription['id']; ?>" 
                                                           class="btn btn-outline-primary btn-action" target="_blank">
                                                            <i class="fas fa-print me-1"></i> Print
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Lab Tests Section -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Lab Tests</h5>
                            <button class="btn btn-success btn-action" data-bs-toggle="modal" data-bs-target="#addLabTestModal">
                                <i class="fas fa-plus me-1"></i> New
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($lab_tests)): ?>
                                <div class="alert alert-info">No lab tests recommended for this patient</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Test Name</th>
                                                <th>Details</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lab_tests as $test): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars($test['test_details']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge-status badge-<?php echo strtolower($test['status']); ?>">
                                                            <?php echo ucfirst($test['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small><?php echo date('M j, Y', strtotime($test['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($test['status'] === 'pending'): ?>
                                                            <a href="cancel_lab_test.php?id=<?php echo $test['id']; ?>&patient_id=<?php echo $selected_patient['patient_id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Diagnosis Reports Section -->
                    <div class="card fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-medical me-2"></i>Diagnosis Reports</h5>
                            <button class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#addDiagnosisModal">
                                <i class="fas fa-plus me-1"></i> New
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($diagnosis_reports)): ?>
                                <div class="alert alert-info">No diagnosis reports for this patient</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($diagnosis_reports as $report): ?>
                                        <div class="list-group-item mb-2 border-0 shadow-sm">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                                    <p class="mb-2"><?php echo htmlspecialchars($report['details']); ?></p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="d-flex">
                                                    <?php if (!empty($report['report_file'])): ?>
                                                        <a href="../uploads/diagnosis_reports/<?php echo $report['report_file']; ?>" 
                                                           class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                                            <i class="fas fa-download me-1"></i> Download
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- No Patient Selected -->
                    <div class="card fade-in">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-user-injured fa-4x text-muted mb-4"></i>
                            <h4>No Patient Selected</h4>
                            <p class="text-muted">Please select a patient from the list to view prescriptions and diagnosis records</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Prescription Modal -->
    <div class="modal fade" id="addPrescriptionModal" tabindex="-1" aria-labelledby="addPrescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="prescriptions.php">
                    <input type="hidden" name="action" value="add_prescription">
                    <input type="hidden" name="patient_id" value="<?php echo $selected_patient['patient_id'] ?? ''; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPrescriptionModalLabel">New Prescription</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="medications-container">
                            <div class="medication-item mb-3 p-3 bg-light rounded">
                                <div class="row">
                                    <div class="col-md-5 mb-3">
                                        <label class="form-label">Medication Name*</label>
                                        <input type="text" class="form-control" name="medications[0][name]" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Dosage*</label>
                                        <input type="text" class="form-control" name="medications[0][dosage]" placeholder="e.g. 500mg" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Frequency*</label>
                                        <input type="text" class="form-control" name="medications[0][frequency]" placeholder="e.g. Twice daily" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Duration*</label>
                                        <input type="text" class="form-control" name="medications[0][duration]" placeholder="e.g. 7 days" required>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Notes</label>
                                        <input type="text" class="form-control" name="medications[0][notes]" placeholder="Optional notes">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="add-medication">
                            <i class="fas fa-plus-circle me-2"></i> Add Another Medication
                        </button>
                        
                        <div class="mb-3 mt-4">
                            <label class="form-label">Instructions*</label>
                            <textarea class="form-control" name="instructions" rows="4" placeholder="Enter detailed instructions for the patient..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Valid For (Days)*</label>
                            <input type="number" class="form-control" name="valid_days" value="7" min="1" max="30" required>
                            <small class="text-muted">Number of days this prescription is valid</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Prescription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Lab Test Modal -->
    <div class="modal fade" id="addLabTestModal" tabindex="-1" aria-labelledby="addLabTestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="prescriptions.php">
                    <input type="hidden" name="action" value="add_lab_test">
                    <input type="hidden" name="patient_id" value="<?php echo $selected_patient['patient_id'] ?? ''; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addLabTestModalLabel">Recommend Lab Test</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Test Name*</label>
                            <input type="text" class="form-control" name="test_name" placeholder="e.g. Complete Blood Count" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Test Details*</label>
                            <textarea class="form-control" name="test_details" rows="4" placeholder="Describe the test requirements..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Recommend Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Diagnosis Modal -->
    <div class="modal fade" id="addDiagnosisModal" tabindex="-1" aria-labelledby="addDiagnosisModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="prescriptions.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_diagnosis">
                    <input type="hidden" name="patient_id" value="<?php echo $selected_patient['patient_id'] ?? ''; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDiagnosisModalLabel">Upload Diagnosis Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title*</label>
                            <input type="text" class="form-control" name="diagnosis_title" placeholder="e.g. Diabetes Diagnosis" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Details*</label>
                            <textarea class="form-control" name="diagnosis_details" rows="4" placeholder="Enter diagnosis details..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Report File (PDF/Image)</label>
                            <input type="file" class="form-control" name="report_file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Max file size: 5MB (PDF, JPG, PNG)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">Upload Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add medication fields dynamically
        document.getElementById('add-medication').addEventListener('click', function() {
            const container = document.getElementById('medications-container');
            const index = container.children.length;
            
            const newMedication = document.createElement('div');
            newMedication.className = 'medication-item mb-3 p-3 bg-light rounded';
            newMedication.innerHTML = `
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Medication Name*</label>
                        <input type="text" class="form-control" name="medications[${index}][name]" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Dosage*</label>
                        <input type="text" class="form-control" name="medications[${index}][dosage]" placeholder="e.g. 500mg" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Frequency*</label>
                        <input type="text" class="form-control" name="medications[${index}][frequency]" placeholder="e.g. Twice daily" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Duration*</label>
                        <input type="text" class="form-control" name="medications[${index}][duration]" placeholder="e.g. 7 days" required>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="medications[${index}][notes]" placeholder="Optional notes">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-medication">
                    <i class="fas fa-trash me-1"></i> Remove Medication
                </button>
            `;
            
            container.appendChild(newMedication);
        });
        
        // Remove medication fields
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-medication')) {
                e.target.closest('.medication-item').remove();
                // Renumber the remaining medication fields
                const containers = document.querySelectorAll('#medications-container .medication-item');
                containers.forEach((container, index) => {
                    const inputs = container.querySelectorAll('input, textarea, select');
                    inputs.forEach(input => {
                        input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                    });
                });
            }
        });

        // Add fade-in animation to elements when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.card, .list-group-item, .accordion-item').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>