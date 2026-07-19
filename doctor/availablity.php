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

// Handle availability form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    try {
        // Delete existing availability
        $delete_stmt = $pdo->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?");
        $delete_stmt->execute([$doctor_id]);

        // Insert new availability
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            $insert_stmt = $pdo->prepare("
                INSERT INTO doctor_availability 
                (doctor_id, day_of_week, start_time, end_time, session_duration, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($_POST['days'] as $day) {
                $start_time = $_POST['start_time'][$day];
                $end_time = $_POST['end_time'][$day];
                $session_duration = $_POST['session_duration'][$day];
                $status = isset($_POST['status'][$day]) ? 'active' : 'inactive';

                if (!empty($start_time) && !empty($end_time)) {
                    $insert_stmt->execute([
                        $doctor_id,
                        $day,
                        $start_time,
                        $end_time,
                        $session_duration,
                        $status
                    ]);
                }
            }
        }

        $message = "Availability updated successfully!";
    } catch (PDOException $e) {
        $message = "Error updating availability: " . $e->getMessage();
    }
}

// Fetch current availability
try {
    $availability_stmt = $pdo->prepare("
        SELECT * FROM doctor_availability 
        WHERE doctor_id = ? 
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
    ");
    $availability_stmt->execute([$doctor_id]);
    $current_availability = $availability_stmt->fetchAll();

    // Create a map of availability by day for easy access
    $availability_map = [];
    foreach ($current_availability as $slot) {
        $availability_map[$slot['day_of_week']] = $slot;
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
    <title>Doctor Availability Management</title>
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
        
        .day-card {
            border-left: 3px solid var(--primary);
            margin-bottom: 15px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
        }
        
        .day-label {
            font-weight: 600;
            color: var(--dark);
        }
        
        .time-input {
            max-width: 120px;
            display: inline-block;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .badge-active {
            background-color: var(--success);
            color: white;
        }
        
        .badge-inactive {
            background-color: var(--danger);
            color: white;
        }
        
        .availability-table th {
            background-color: var(--primary);
            color: white;
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
                    <i class="fas fa-calendar-alt me-2"></i>Availability Management
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
                <h2 class="page-title"><i class="fas fa-clock me-2"></i>Manage Your Availability</h2>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Availability Form -->
            <div class="col-md-6">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Set Availability</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            
                            foreach ($days as $day): 
                                $existing = $availability_map[$day] ?? null;
                            ?>
                                <div class="day-card mb-3">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input day-toggle" type="checkbox" 
                                               id="day-<?php echo strtolower($day); ?>" 
                                               name="days[]" value="<?php echo $day; ?>"
                                               <?php echo $existing ? 'checked' : ''; ?>>
                                        <label class="form-check-label day-label" for="day-<?php echo strtolower($day); ?>">
                                            <?php echo $day; ?>
                                        </label>
                                    </div>
                                    
                                    <div class="row g-2 timing-fields" style="<?php echo !$existing ? 'display: none;' : ''; ?>">
                                        <div class="col-md-4">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control time-input" 
                                                   name="start_time[<?php echo $day; ?>]" 
                                                   value="<?php echo $existing ? substr($existing['start_time'], 0, 5) : '09:00'; ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control time-input" 
                                                   name="end_time[<?php echo $day; ?>]" 
                                                   value="<?php echo $existing ? substr($existing['end_time'], 0, 5) : '17:00'; ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Duration (mins)</label>
                                            <input type="number" class="form-control" 
                                                   name="session_duration[<?php echo $day; ?>]" 
                                                   value="<?php echo $existing ? $existing['session_duration'] : '30'; ?>" 
                                                   min="10" max="120" step="5" required>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="status-<?php echo strtolower($day); ?>" 
                                                       name="status[<?php echo $day; ?>]"
                                                       <?php echo !$existing || $existing['status'] === 'active' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="status-<?php echo strtolower($day); ?>">
                                                    Available for appointments
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-end mt-4">
                                <button type="submit" name="update_availability" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Availability
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Current Availability Table -->
            <div class="col-md-6">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Current Availability</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($current_availability)): ?>
                            <div class="alert alert-info">No availability set. Please set your availability using the form.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover availability-table">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Session</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($current_availability as $slot): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($slot['day_of_week']); ?></td>
                                                <td><?php echo date('h:i A', strtotime($slot['start_time'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($slot['end_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($slot['session_duration']); ?> mins</td>
                                                <td>
                                                    <span class="status-badge badge-<?php echo strtolower($slot['status']); ?>">
                                                        <?php echo ucfirst($slot['status']); ?>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle timing fields when day is enabled/disabled
        document.querySelectorAll('.day-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const timingFields = this.closest('.day-card').querySelector('.timing-fields');
                timingFields.style.display = this.checked ? 'flex' : 'none';
                
                // Toggle required attribute on inputs
                const inputs = timingFields.querySelectorAll('input');
                inputs.forEach(input => {
                    input.required = this.checked;
                });
            });
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