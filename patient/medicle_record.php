<?php
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
require '../config.php';

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user']['id'];
$message = '';

if (isset($_GET['download_medical_history'])) {
    try {
        // Get medical records - CORRECTED VERSION
        $prescriptions_stmt = $pdo->prepare("SELECT p.*, u.name as doctor_name FROM prescriptions p JOIN doctors u ON p.doctor_id = u.id WHERE p.patient_id = ? ORDER BY p.created_at DESC");
        $prescriptions_stmt->execute([$patient_id]);
        $prescriptions = $prescriptions_stmt->fetchAll();

        $lab_tests_stmt = $pdo->prepare("SELECT l.*, u.name as doctor_name FROM lab_tests l JOIN doctors u ON l.doctor_id = u.id WHERE l.patient_id = ? ORDER BY l.created_at DESC");
        $lab_tests_stmt->execute([$patient_id]);
        $lab_tests = $lab_tests_stmt->fetchAll();

        $diagnosis_reports_stmt = $pdo->prepare("SELECT d.*, u.name as doctor_name FROM diagnosis_reports d JOIN doctors u ON d.doctor_id = u.id WHERE d.patient_id = ? ORDER BY d.created_at DESC");
        $diagnosis_reports_stmt->execute([$patient_id]);
        $diagnosis_reports = $diagnosis_reports_stmt->fetchAll();

        $patient_stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
        $patient_stmt->execute([$patient_id]);
        $patient = $patient_stmt->fetch();

        // Generate PDF
        require_once '../dompdf/autoload.inc.php';
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                th, td { border: 1px solid #ddd; padding: 6px; }
                th { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 15px; }
                .section { margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Medical History Report</h2>
                <p>Generated: '.date('Y-m-d').'</p>
            </div>
            
            <div class="section">
                <h3>Patient Information</h3>
                <table>
                    <tr><th>Name</th><td>'.htmlspecialchars($patient['first_name'] ?? 'N/A').'</td></tr>
                    <tr><th>ID</th><td>'.htmlspecialchars($patient['patient_id'] ?? 'N/A').'</td></tr>
                    <tr><th>DOB</th><td>'.htmlspecialchars($patient['dob'] ?? 'N/A').'</td></tr>
                </table>
            </div>';

        if (!empty($prescriptions)) {
            $html .= '<div class="section"><h3>Prescriptions</h3>';
            foreach ($prescriptions as $prescription) {
                $html .= '<table>
                    <tr><th>Date</th><td>'.htmlspecialchars($prescription['created_at']).'</td></tr>
                    <tr><th>Doctor</th><td>'.htmlspecialchars($prescription['doctor_name']).'</td></tr>
                    <tr><th>Instructions</th><td>'.nl2br(htmlspecialchars($prescription['instructions'])).'</td></tr>
                </table>';
            }
            $html .= '</div>';
        }

        if (!empty($lab_tests)) {
            $html .= '<div class="section"><h3>Lab Tests</h3><table>
                <tr><th>Test</th><th>Details</th><th>Status</th><th>Date</th></tr>';
            foreach ($lab_tests as $test) {
                $html .= '<tr>
                    <td>'.htmlspecialchars($test['test_name']).'</td>
                    <td>'.htmlspecialchars($test['test_details']).'</td>
                    <td>'.htmlspecialchars($test['status']).'</td>
                    <td>'.htmlspecialchars($test['created_at']).'</td>
                </tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($diagnosis_reports)) {
            $html .= '<div class="section"><h3>Diagnosis Reports</h3>';
            foreach ($diagnosis_reports as $report) {
                $html .= '<table>
                    <tr><th>Title</th><td>'.htmlspecialchars($report['title']).'</td></tr>
                    <tr><th>Date</th><td>'.htmlspecialchars($report['created_at']).'</td></tr>
                    <tr><th>Details</th><td>'.nl2br(htmlspecialchars($report['details'])).'</td></tr>
                </table>';
            }
            $html .= '</div>';
        }

        $html .= '</body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('medical_history_'.$patient_id.'.pdf', ['Attachment' => true]);
        exit();
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}








// Fetch medical records
try {
    // Get prescriptions
    $prescriptions_stmt = $pdo->prepare("
        SELECT p.*, u.name as doctor_name 
        FROM prescriptions p
        JOIN doctors u ON p.doctor_id = u.id
        WHERE p.patient_id = ?
        ORDER BY p.created_at DESC
    ");
    $prescriptions_stmt->execute([$patient_id]);
    $prescriptions = $prescriptions_stmt->fetchAll();

    // Get lab tests
    $lab_tests_stmt = $pdo->prepare("
        SELECT l.*, u.name as doctor_name 
        FROM lab_tests l
        JOIN doctors u ON l.doctor_id = u.id
        WHERE l.patient_id = ?
        ORDER BY l.created_at DESC
    ");
    $lab_tests_stmt->execute([$patient_id]);
    $lab_tests = $lab_tests_stmt->fetchAll();

    // Get diagnosis reports
    $diagnosis_reports_stmt = $pdo->prepare("
        SELECT d.*, u.name as doctor_name 
        FROM diagnosis_reports d
        JOIN doctors u ON d.doctor_id = u.id
        WHERE d.patient_id = ?
        ORDER BY d.created_at DESC
    ");
    $diagnosis_reports_stmt->execute([$patient_id]);
    $diagnosis_reports = $diagnosis_reports_stmt->fetchAll();

    // Get patient info
    $patient_stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
    $patient_stmt->execute([$patient_id]);
    $patient = $patient_stmt->fetch();

} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Medical Records</title>
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
            width: 100px;
            height: 100px;
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
        
        .download-btn {
            background: var(--success);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .download-btn:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar top-nav">
        <div class="container">
            <div class="d-flex justify-content-between w-100 align-items-center">
                <a class="nav-brand" href="#">
                    <i class="fas fa-heartbeat"></i>My Medical Records
                </a>
                <a href="patient_dashboard.php" class="btn dashboard-btn">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <a href="?download_medical_history=1" class="btn download-btn">
                    <i class="fas fa-file-pdf me-2"></i>Download Complete Medical History
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Patient Information -->
            <div class="col-md-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>My Information</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo !empty($patient['photo']) ? htmlspecialchars($patient['photo']) : '../assets/default-avatar.jpg'; ?>" 
                             class="avatar mb-3" alt="Patient Photo">
                        <h4><?php echo htmlspecialchars($patient['first_name'] ?? 'Not available'); ?></h4>
                        <p class="text-muted">Patient ID: <?php echo htmlspecialchars($patient['patient_id'] ?? 'Not available'); ?></p>
                        
                        <div class="patient-info mt-3 text-start">
                            <p><i class="fas fa-birthday-cake me-2"></i> <strong>Age:</strong> <?php echo (!empty($patient['dob']) ? date_diff(date_create($patient['dob']), date_create('today'))->y . ' years' : 'Not specified'); ?></p>
                            <p><i class="fas fa-venus-mars me-2"></i> <strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender'] ?? 'Not specified'); ?></p>
                            <p><i class="fas fa-tint me-2"></i> <strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['blood_group'] ?? 'Not specified'); ?></p>
                            <p><i class="fas fa-phone me-2"></i> <strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone'] ?? 'Not specified'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Records -->
            <div class="col-md-8">
                <?php if ($message): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Prescriptions -->
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-prescription me-2"></i>My Prescriptions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prescriptions)): ?>
                            <div class="alert alert-info">No prescriptions found</div>
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
                                                    <span class="me-3">Prescription #<?php echo htmlspecialchars($prescription['id']); ?></span>
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
                                                <div class="d-flex justify-content-between mb-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-md me-1"></i> Prescribed by Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-check me-1"></i> Valid for <?php echo htmlspecialchars($prescription['valid_days']); ?> days
                                                    </small>
                                                </div>
                                                
                                                <h6 class="mb-3">Medications:</h6>
                                                <?php if (is_array($medications)): ?>
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
                                                <?php endif; ?>
                                                
                                                <h6 class="mt-4">Instructions:</h6>
                                                <div class="bg-light p-3 rounded mb-3">
                                                    <?php echo nl2br(htmlspecialchars($prescription['instructions'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lab Tests -->
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-flask me-2"></i>My Lab Tests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lab_tests)): ?>
                            <div class="alert alert-info">No lab tests found</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Test Name</th>
                                            <th>Details</th>
                                            <th>Doctor</th>
                                            <th>Status</th>
                                            <th>Date</th>
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
                                                    <small>Dr. <?php echo htmlspecialchars($test['doctor_name']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge-status badge-<?php echo strtolower($test['status']); ?>">
                                                        <?php echo ucfirst($test['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($test['created_at'])); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Diagnosis Reports -->
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-medical me-2"></i>My Diagnosis Reports</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($diagnosis_reports)): ?>
                            <div class="alert alert-info">No diagnosis reports found</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($diagnosis_reports as $report): ?>
                                    <div class="list-group-item mb-2 border-0 shadow-sm">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                                <p class="mb-2"><?php echo htmlspecialchars($report['details']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-md me-1"></i> Dr. <?php echo htmlspecialchars($report['doctor_name']); ?>
                                                    <span class="ms-3"><i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y', strtotime($report['created_at'])); ?></span>
                                                </small>
                                            </div>
                                            <div class="d-flex">
                                                <?php if (!empty($report['report_file'])): ?>
                                                    <a href="../uploads/diagnosis_reports/<?php echo htmlspecialchars($report['report_file']); ?>" 
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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