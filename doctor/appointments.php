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

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appointment_id = $_POST['appointment_id'];
    $action = $_POST['action'];
    
    if ($action === 'confirm' || $action === 'cancel') {
        $new_status = $action === 'confirm' ? 'confirmed' : 'cancelled';
        
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$new_status, $appointment_id, $doctor_id]);
            
            if ($stmt->rowCount() > 0) {
                $message = "Appointment " . ($action === 'confirm' ? "confirmed" : "cancelled") . " successfully!";
            } else {
                $message = "Failed to update appointment or appointment not found.";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch appointments for this doctor
try {
    // Pending appointment requests
    $pending_stmt = $pdo->prepare("
        SELECT a.*, patient_name, patient_email, patient_phone 
        FROM appointments a
        JOIN patient p ON patient_id = patient_id
        WHERE a.doctor_id = ? 
        AND a.status = 'pending'
        AND (a.appointment_date >= CURDATE())
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $pending_stmt->execute([$doctor_id]);
    $pending_appointments = $pending_stmt->fetchAll();

    // Confirmed and cancelled appointments (past and future)
    $processed_stmt = $pdo->prepare("
        SELECT a.*, patient_name, patient_email, patient_phone 
        FROM appointments a
        JOIN patient p ON patient_id = patient_id
        WHERE a.doctor_id = ? 
        AND a.status IN ('confirmed', 'cancelled')
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $processed_stmt->execute([$doctor_id]);
    $processed_appointments = $processed_stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background-color: #2c3e50;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .navbar-custom .navbar-brand {
            color: #fff;
            font-weight: 600;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border: none;
            overflow: hidden;
        }
        .card-header {
            border-bottom: none;
            padding: 15px 20px;
        }
        .appointment-card {
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .pending-card {
            border-left: 4px solid #f39c12;
        }
        .cancelled-card {
            border-left: 4px solid #e74c3c;
        }
        .badge-pending {
            background-color: #f39c12;
            color: #fff;
        }
        .badge-confirmed {
            background-color: #2ecc71;
        }
        .badge-cancelled {
            background-color: #e74c3c;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-action {
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
            font-weight: 500;
            margin: 2px;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .table td {
            vertical-align: middle;
        }
        .action-buttons .btn {
            min-width: 90px;
        }
        .page-title {
            color: #2c3e50;
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
            background: #3498db;
            border-radius: 3px;
        }
        .alert {
            border-radius: 8px;
        }
        .dashboard-btn {
            background-color: #3498db;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }
        .dashboard-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-heartbeat me-2"></i>Doctor Portal
            </a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="header-container">
            <h2 class="page-title"><i class="fas fa-calendar-alt me-2"></i>Appointment Management</h2>
            <a href="doctor_dashboard.php" class="dashboard-btn">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Appointments -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-white">
                <h4 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Appointment Requests</h4>
            </div>
            <div class="card-body">
                <?php if (empty($pending_appointments)): ?>
                    <div class="alert alert-info">No pending appointment requests.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_appointments as $appointment): ?>
                                    <tr class="pending-card">
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($appointment['patient_name'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($appointment['patient_email'] ?? 'N/A'); ?></div>
                                            <div><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($appointment['patient_phone'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <i class="far fa-calendar me-2 text-primary"></i>
                                                <?php 
                                                if (!empty($appointment['appointment_date'])) {
                                                    echo date('M j, Y', strtotime($appointment['appointment_date'])); 
                                                } else {
                                                    echo 'Date not set';
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <i class="far fa-clock me-2 text-primary"></i>
                                                <?php echo date('g:i A', strtotime($appointment['appointment_time'] ?? '00:00:00')); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['reason'] ?? 'Not specified'); ?></td>
                                        <td class="action-buttons text-center">
                                            <form method="POST" class="d-inline-block">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" name="action" value="confirm" class="btn btn-success btn-action">
                                                    <i class="fas fa-check me-1"></i> Accept
                                                </button>
                                                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-action">
                                                    <i class="fas fa-times me-1"></i> Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Processed Appointments (Confirmed/Cancelled) -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>All Processed Appointments</h4>
            </div>
            <div class="card-body">
                <?php if (empty($processed_appointments)): ?>
                    <div class="alert alert-info">No processed appointments found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processed_appointments as $appointment): ?>
                                    <tr class="<?php echo $appointment['status'] === 'confirmed' ? 'appointment-card' : 'cancelled-card'; ?>">
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($appointment['patient_name'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($appointment['patient_email'] ?? 'N/A'); ?></div>
                                            <div><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($appointment['patient_phone'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <i class="far fa-calendar me-2 text-primary"></i>
                                                <?php 
                                                if (!empty($appointment['appointment_date'])) {
                                                    echo date('M j, Y', strtotime($appointment['appointment_date'])); 
                                                } else {
                                                    echo 'Date not set';
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <i class="far fa-clock me-2 text-primary"></i>
                                                <?php echo date('g:i A', strtotime($appointment['appointment_time'] ?? '00:00:00')); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['reason'] ?? 'Not specified'); ?></td>
                                        <td class="text-center">
                                            <span class="status-badge badge-<?php echo $appointment['status']; ?>">
                                                <i class="fas fa-<?php echo $appointment['status'] === 'confirmed' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.appointment-card, .pending-card, .cancelled-card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.4s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>
</body>
</html>