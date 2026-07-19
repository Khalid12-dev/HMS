<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

session_start();

// Database configuration
$db_host = 'localhost';
$db_name = 'hms';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// Load libraries
require_once '../dompdf/vendor/autoload.php';
use Twilio\Rest\Client;
use Dompdf\Dompdf;

// Handle data export
if (isset($_GET['export'])) {
    try {
        $type = $_GET['export'];
        $filename = "export_" . date('Y-m-d') . ".csv";
        $data = [];
        
        switch ($type) {
            case 'visits':
                $data = $pdo->query("
                    SELECT v.visit_id, p.patient_name, d.doctor_name, 
                           v.visit_date, v.diagnosis, v.treatment, 
                           v.follow_up_date, v.status
                    FROM patient_visits v
                    JOIN patients p ON v.patient_id = p.patient_id
                    JOIN doctors d ON v.doctor_id = d.doctor_id
                    ORDER BY v.visit_date DESC
                ")->fetchAll();
                $filename = "patient_visits_" . date('Y-m-d') . ".csv";
                break;
                
            case 'performance':
                $data = $pdo->query("
                    SELECT d.doctor_id, d.doctor_name, d.specialization,
                           COUNT(v.visit_id) as total_visits,
                           AVG(v.rating) as avg_rating,
                           SUM(CASE WHEN v.status = 'Completed' THEN 1 ELSE 0 END) as completed_visits
                    FROM doctors d
                    LEFT JOIN patient_visits v ON d.doctor_id = v.doctor_id
                    GROUP BY d.doctor_id
                    ORDER BY avg_rating DESC
                ")->fetchAll();
                $filename = "doctor_performance_" . date('Y-m-d') . ".csv";
                break;
                
            case 'shifts':
                $data = $pdo->query("
                    SELECT s.staff_id, s.first_name, s.last_name,
                           sh.name as shift_name, sh.department,
                           ss.shift_date, a.status as attendance_status
                    FROM staff_shifts ss
                    JOIN staff s ON ss.staff_id = s.staff_id
                    JOIN shifts sh ON ss.shift_id = sh.shift_id
                    LEFT JOIN attendance a ON ss.assignment_id = a.assignment_id
                    ORDER BY ss.shift_date DESC
                ")->fetchAll();
                $filename = "shift_assignments_" . date('Y-m-d') . ".csv";
                break;
        }

        // Output CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Add data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        die("Export failed: " . $e->getMessage());
    }
}

// Handle all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        try {
            switch ($_POST['action']) {
                // ... (previous cases remain the same) ...
                
                case 'record_visit':
                    $required = ['patient_id', 'doctor_id', 'visit_date', 'diagnosis'];
                    foreach ($required as $field) {
                        if (empty($_POST[$field])) {
                            echo json_encode(['success' => false, 'error' => "Field $field is required"]);
                            exit;
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO patient_visits 
                        (patient_id, doctor_id, visit_date, diagnosis, treatment, follow_up_date, status, rating)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        (int)$_POST['patient_id'],
                        (int)$_POST['doctor_id'],
                        $_POST['visit_date'],
                        htmlspecialchars($_POST['diagnosis']),
                        htmlspecialchars($_POST['treatment'] ?? ''),
                        $_POST['follow_up_date'] ?? null,
                        $_POST['status'] ?? 'Scheduled',
                        isset($_POST['rating']) ? min(5, max(1, (int)$_POST['rating'])) : null
                    ]);
                    
                    echo json_encode(['success' => true]);
                    exit;
                    
                case 'update_visit_status':
                    $stmt = $pdo->prepare("
                        UPDATE patient_visits 
                        SET status = ?
                        WHERE visit_id = ?
                    ");
                    
                    $stmt->execute([
                        in_array($_POST['status'], ['Scheduled', 'In Progress', 'Completed', 'Cancelled']) 
                            ? $_POST['status'] 
                            : 'Scheduled',
                        (int)$_POST['visit_id']
                    ]);
                    
                    echo json_encode(['success' => true]);
                    exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit;
        }
    }
}

// Fetch all data for UI
try {
    // Shift data
    $shifts = $pdo->query("SELECT * FROM shifts ORDER BY name")->fetchAll();
    $staff = $pdo->query("SELECT staff_id, first_name, last_name FROM staff WHERE status = 'Active' ORDER BY first_name")->fetchAll();
    
    $assignments = $pdo->query("
        SELECT ss.assignment_id, s.first_name, s.last_name, sh.name, sh.start_time, sh.end_time, ss.shift_date
        FROM staff_shifts ss
        JOIN staff s ON ss.staff_id = s.staff_id
        JOIN shifts sh ON ss.shift_id = sh.shift_id
        ORDER BY ss.shift_date DESC, sh.start_time
    ")->fetchAll();
    
    // Patient visit data
    $patients = $pdo->query("SELECT patient_id, first_name FROM patient ORDER BY first_name")->fetchAll();
    $doctors = $pdo->query("SELECT id, name, specialization FROM doctors ORDER BY name")->fetchAll();
    
    $visits = $pdo->query("
        SELECT v.visit_id, p.patient_name, d.doctor_name, 
               v.visit_date, v.diagnosis, v.status, v.rating
        FROM patient_visits v
        JOIN patients p ON v.patient_id = p.patient_id
        JOIN doctors d ON v.doctor_id = d.doctor_id
        ORDER BY v.visit_date DESC
        LIMIT 50
    ")->fetchAll();
    
    // Doctor performance data
    $performance = $pdo->query("
        SELECT d.doctor_id, d.doctor_name, d.specialization,
               COUNT(v.visit_id) as total_visits,
               AVG(v.rating) as avg_rating,
               SUM(CASE WHEN v.status = 'Completed' THEN 1 ELSE 0 END) as completed_visits
        FROM doctors d
        LEFT JOIN patient_visits v ON d.doctor_id = v.doctor_id
        GROUP BY d.doctor_id
        ORDER BY avg_rating DESC
    ")->fetchAll();
    
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* ... (previous styles remain the same) ... */
        .performance-card { transition: transform 0.3s; }
        .performance-card:hover { transform: translateY(-5px); }
        .rating-stars { color: gold; }
        .export-btn { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="text-center"><i class="bi bi-hospital"></i> Hospital Management System</h2>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="shifts-tab" data-bs-toggle="tab" data-bs-target="#shifts" type="button">
                            <i class="bi bi-clock"></i> Shifts
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="visits-tab" data-bs-toggle="tab" data-bs-target="#visits" type="button">
                            <i class="bi bi-journal-medical"></i> Patient Visits
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button">
                            <i class="bi bi-graph-up"></i> Doctor Performance
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content py-4">
                    <!-- Shift Management Tab (previous content remains the same) -->
                    
                    <!-- Patient Visits Tab -->
                    <div class="tab-pane fade" id="visits" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="bi bi-plus-circle"></i> Record Patient Visit</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="visitForm">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Patient</label>
                                                    <select name="patient_id" class="form-select" required>
                                                        <option value="">Select Patient</option>
                                                        <?php foreach ($patients as $patient): ?>
                                                        <option value="<?= $patient['patient_id'] ?>">
                                                            <?= htmlspecialchars($patient['patient_name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Doctor</label>
                                                    <select name="doctor_id" class="form-select" required>
                                                        <option value="">Select Doctor</option>
                                                        <?php foreach ($doctors as $doctor): ?>
                                                        <option value="<?= $doctor['doctor_id'] ?>">
                                                            <?= htmlspecialchars($doctor['doctor_name']) ?> (<?= $doctor['specialization'] ?>)
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Visit Date</label>
                                                    <input type="datetime-local" name="visit_date" class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select">
                                                        <option value="Scheduled">Scheduled</option>
                                                        <option value="In Progress">In Progress</option>
                                                        <option value="Completed">Completed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Diagnosis</label>
                                                <textarea name="diagnosis" class="form-control" rows="2" required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Treatment</label>
                                                <textarea name="treatment" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Follow-up Date</label>
                                                    <input type="date" name="follow_up_date" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Rating (1-5)</label>
                                                    <input type="number" name="rating" class="form-control" min="1" max="5">
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save"></i> Record Visit
                                            </button>
                                            <a href="?export=visits" class="btn btn-success export-btn">
                                                <i class="bi bi-download"></i> Export Visits
                                            </a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5><i class="bi bi-list-ul"></i> Recent Visits</h5>
                                        <span class="badge bg-secondary"><?= count($visits) ?> visits</span>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($visits)): ?>
                                            <div class="alert alert-info">No visits found.</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Patient</th>
                                                            <th>Doctor</th>
                                                            <th>Date</th>
                                                            <th>Status</th>
                                                            <th>Rating</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($visits as $visit): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($visit['patient_name']) ?></td>
                                                            <td><?= htmlspecialchars($visit['doctor_name']) ?></td>
                                                            <td><?= date('M d, Y h:i A', strtotime($visit['visit_date'])) ?></td>
                                                            <td>
                                                                <span class="badge <?= 
                                                                    $visit['status'] == 'Completed' ? 'bg-success' : 
                                                                    ($visit['status'] == 'In Progress' ? 'bg-primary' : 'bg-secondary')
                                                                ?>">
                                                                    <?= $visit['status'] ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($visit['rating']): ?>
                                                                    <span class="rating-stars">
                                                                        <?= str_repeat('★', $visit['rating']) . str_repeat('☆', 5 - $visit['rating']) ?>
                                                                    </span>
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
                            </div>
                        </div>
                    </div>
                    
                    <!-- Doctor Performance Tab -->
                    <div class="tab-pane fade" id="performance" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5><i class="bi bi-graph-up"></i> Doctor Performance Metrics</h5>
                                        <a href="?export=performance" class="btn btn-success export-btn">
                                            <i class="bi bi-download"></i> Export Performance Data
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($performance)): ?>
                                            <div class="alert alert-info">No performance data available.</div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($performance as $doctor): ?>
                                                <div class="col-md-4 mb-4">
                                                    <div class="card performance-card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?= htmlspecialchars($doctor['doctor_name']) ?></h5>
                                                            <h6 class="card-subtitle mb-2 text-muted">
                                                                <?= htmlspecialchars($doctor['specialization']) ?>
                                                            </h6>
                                                            <div class="mt-3">
                                                                <p class="mb-1">
                                                                    <strong>Total Visits:</strong> <?= $doctor['total_visits'] ?>
                                                                </p>
                                                                <p class="mb-1">
                                                                    <strong>Completed:</strong> <?= $doctor['completed_visits'] ?>
                                                                </p>
                                                                <p class="mb-1">
                                                                    <strong>Avg Rating:</strong> 
                                                                    <?php if ($doctor['avg_rating']): ?>
                                                                        <span class="rating-stars">
                                                                            <?= round($doctor['avg_rating'], 1) ?> 
                                                                            <?= str_repeat('★', round($doctor['avg_rating'])) ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        N/A
                                                                    <?php endif; ?>
                                                                </p>
                                                            </div>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toast-message"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize toast
        const toastEl = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        const toast = new bootstrap.Toast(toastEl);
        
        // Show toast notification
        function showToast(message, type = 'success') {
            toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
            toastMessage.textContent = message;
            toast.show();
        }

        // Record Patient Visit
        document.getElementById('visitForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'record_visit');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }
                throw new Error('Invalid server response');
            })
            .then(data => {
                if (data.success) {
                    showToast('Visit recorded successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.error || 'Error recording visit', 'danger');
                }
            })
            .catch(error => {
                showToast('Error: ' + error.message, 'danger');
                console.error('Error:', error);
            });
        });

        // ... (previous JavaScript remains the same) ...
    </script>
</body>
</html>
<?php ob_end_flush(); ?>