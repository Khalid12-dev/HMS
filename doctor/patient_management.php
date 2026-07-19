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

// Fetch all patients assigned to this doctor
try {
    $patients_stmt = $pdo->prepare("
        SELECT DISTINCT p.* 
        FROM patient p
        JOIN appointments a ON patient_id = patient_id
        WHERE a.doctor_id = ?
        ORDER BY p.first_name ASC
    ");
    $patients_stmt->execute([$doctor_id]);
    $patients = $patients_stmt->fetchAll();

    // If specific patient is selected, get their details
    $selected_patient = null;
    $medical_history = [];
    $ongoing_treatments = [];
    
    if (isset($_GET['patient_id'])) {
        $patient_id = $_GET['patient_id'];
        
        // Get patient details
        $patient_stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
        $patient_stmt->execute([$patient_id]);
        $selected_patient = $patient_stmt->fetch();
        
        if ($selected_patient) {
            // Get medical history
            $history_stmt = $pdo->prepare("
                SELECT * FROM medical_history 
                WHERE patient_id = ?
                ORDER BY date_recorded DESC
            ");
            $history_stmt->execute([$patient_id]);
            $medical_history = $history_stmt->fetchAll();
            
            // Get ongoing treatments
            $treatment_stmt = $pdo->prepare("
                SELECT * FROM treatments 
                WHERE patient_id = ? AND status = 'ongoing'
                ORDER BY start_date DESC
            ");
            $treatment_stmt->execute([$patient_id]);
            $ongoing_treatments = $treatment_stmt->fetchAll();
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
    <title>Patient Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
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
            cursor: pointer;
            border-left: 3px solid transparent;
        }
        
        .patient-card:hover, .patient-card.active {
            border-left: 3px solid var(--primary);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .badge-treatment {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .badge-ongoing { background: var(--warning); }
        .badge-completed { background: var(--success); }
        
        .history-item {
            border-left: 3px solid var(--primary);
            padding-left: 15px;
            margin-bottom: 15px;
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
        
        .action-btn {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            margin-right: 10px;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-title {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar top-nav">
        <div class="container">
            <div class="d-flex justify-content-between w-100 align-items-center">
                <a class="nav-brand" href="#">
                    <i class="fas fa-user-md me-2"></i>Patient Management
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
            <div class="col-12">
                <h2 class="page-title"><i class="fas fa-user-injured me-2"></i>My Patients</h2>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Patient List -->
            <div class="col-md-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Patient List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($patients)): ?>
                            <div class="alert alert-info">No patients found.</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($patients as $patient): ?>
                                    <a href="?patient_id=<?php echo $patient['patient_id']; ?>" 
                                       class="list-group-item list-group-item-action patient-card <?php echo (isset($selected_patient)) && $selected_patient['patient_id'] == $patient['patient_id'] ? 'active' : ''; ?>">
                                        <div class="d-flex align-items-center">
                                            
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['last_name'] ?? '')); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($patient['email'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Patient Details -->
            <div class="col-md-8">
                <?php if ($selected_patient): ?>
                    <!-- Patient Information -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Patient Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    
                                </div>
                                <div class="col-md-9">
                                    <div class="patient-info">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($selected_patient['first_name'] . ' ' . ($selected_patient['last_name'] ?? '')); ?></p>
                                                <p><strong>Gender:</strong> <?php echo htmlspecialchars($selected_patient['gender'] ?? 'Not specified'); ?></p>
                                                <p><strong>Date of Birth:</strong> <?php echo !empty($selected_patient['dob']) ? date('M j, Y', strtotime($selected_patient['dob'])) : 'Not specified'; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($selected_patient['email']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($selected_patient['phone'] ?? 'Not specified'); ?></p>
                                                <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($selected_patient['blood_group'] ?? 'Not specified'); ?></p>
                                            </div>
                                        </div>
                                        <?php if (!empty($selected_patient['address'])): ?>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($selected_patient['address']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($selected_patient['allergies'])): ?>
                                            <div class="alert alert-warning p-2 mt-2">
                                                <strong><i class="fas fa-exclamation-triangle me-2"></i>Allergies:</strong> 
                                                <?php echo htmlspecialchars($selected_patient['allergies']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button class="btn btn-primary action-btn" data-bs-toggle="modal" data-bs-target="#addTreatmentModal">
                                            <i class="fas fa-plus me-2"></i>Add Treatment
                                        </button>
                                        <button class="btn btn-success action-btn" data-bs-toggle="modal" data-bs-target="#addHistoryModal">
                                            <i class="fas fa-plus me-2"></i>Add Medical History
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ongoing Treatments -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-prescription-bottle-alt me-2"></i>Ongoing Treatments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($ongoing_treatments)): ?>
                                <div class="alert alert-info">No ongoing treatments found.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Treatment</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ongoing_treatments as $treatment): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($treatment['name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($treatment['description']); ?></small>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($treatment['start_date'])); ?></td>
                                                    <td><?php echo !empty($treatment['end_date']) ? date('M j, Y', strtotime($treatment['end_date'])) : 'Ongoing'; ?></td>
                                                    <td>
                                                        <span class="badge-treatment badge-<?php echo $treatment['status'] === 'ongoing' ? 'ongoing' : 'completed'; ?>">
                                                            <?php echo ucfirst($treatment['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editTreatmentModal"
                                                                data-id="<?php echo $treatment['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($treatment['name']); ?>"
                                                                data-desc="<?php echo htmlspecialchars($treatment['description']); ?>"
                                                                data-start="<?php echo $treatment['start_date']; ?>"
                                                                data-end="<?php echo $treatment['end_date']; ?>"
                                                                data-status="<?php echo $treatment['status']; ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Medical History -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Medical History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($medical_history)): ?>
                                <div class="alert alert-info">No medical history found.</div>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($medical_history as $history): ?>
                                        <div class="history-item mb-4">
                                            <div class="d-flex justify-content-between">
                                                <h6><?php echo htmlspecialchars($history['title']); ?></h6>
                                                <small class="text-muted"><?php echo date('M j, Y', strtotime($history['date_recorded'])); ?></small>
                                            </div>
                                            <p class="mt-2"><?php echo htmlspecialchars($history['description']); ?></p>
                                            <?php if (!empty($history['attachment'])): ?>
                                                <a href="../uploads/medical_history/<?php echo $history['attachment']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i class="fas fa-paperclip me-1"></i> View Attachment
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card fade-in">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-user-injured fa-4x text-muted mb-4"></i>
                            <h4>No Patient Selected</h4>
                            <p class="text-muted">Please select a patient from the list to view details</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Treatment Modal -->
    <div class="modal fade" id="addTreatmentModal" tabindex="-1" aria-labelledby="addTreatmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="add_treatment.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTreatmentModalLabel">Add New Treatment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient['id'] ?? ''; ?>">
                        <div class="mb-3">
                            <label for="treatmentName" class="form-label">Treatment Name</label>
                            <input type="text" class="form-control" id="treatmentName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="treatmentDesc" class="form-label">Description</label>
                            <textarea class="form-control" id="treatmentDesc" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="endDate" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="endDate" name="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Treatment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Medical History Modal -->
    <div class="modal fade" id="addHistoryModal" tabindex="-1" aria-labelledby="addHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="add_history.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addHistoryModalLabel">Add Medical History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient['id'] ?? ''; ?>">
                        <div class="mb-3">
                            <label for="historyTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="historyTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="historyDesc" class="form-label">Description</label>
                            <textarea class="form-control" id="historyDesc" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="historyDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="historyDate" name="date_recorded" required>
                        </div>
                        <div class="mb-3">
                            <label for="historyAttachment" class="form-label">Attachment (Optional)</label>
                            <input type="file" class="form-control" id="historyAttachment" name="attachment">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save History</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Treatment Modal -->
    <div class="modal fade" id="editTreatmentModal" tabindex="-1" aria-labelledby="editTreatmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="update_treatment.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTreatmentModalLabel">Edit Treatment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="treatment_id" id="editTreatmentId">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient['id'] ?? ''; ?>">
                        <div class="mb-3">
                            <label for="editTreatmentName" class="form-label">Treatment Name</label>
                            <input type="text" class="form-control" id="editTreatmentName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTreatmentDesc" class="form-label">Description</label>
                            <textarea class="form-control" id="editTreatmentDesc" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="editStartDate" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEndDate" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="editEndDate" name="end_date">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editTreatmentStatus" class="form-label">Status</label>
                            <select class="form-select" id="editTreatmentStatus" name="status">
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Treatment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Treatment Modal Handler
        const editTreatmentModal = document.getElementById('editTreatmentModal');
        if (editTreatmentModal) {
            editTreatmentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const desc = button.getAttribute('data-desc');
                const start = button.getAttribute('data-start');
                const end = button.getAttribute('data-end');
                const status = button.getAttribute('data-status');
                
                document.getElementById('editTreatmentId').value = id;
                document.getElementById('editTreatmentName').value = name;
                document.getElementById('editTreatmentDesc').value = desc;
                document.getElementById('editStartDate').value = start;
                document.getElementById('editEndDate').value = end;
                document.getElementById('editTreatmentStatus').value = status;
            });
        }

        // Set default dates in modals
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').value = today;
            document.getElementById('historyDate').value = today;
        });

        // Add fade-in animation to elements when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>