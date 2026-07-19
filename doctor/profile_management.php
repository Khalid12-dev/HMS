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

// Fetch doctor's current profile data
try {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    // Fetch consultation timings
    $timings_stmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? ORDER BY day_of_week, start_time");
    $timings_stmt->execute([$doctor_id]);
    $consultation_timings = $timings_stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $specialization = $_POST['specialization'];
    

    try {
        $update_stmt = $pdo->prepare("
            UPDATE doctors 
            SET name = ?, email = ?, phone = ?, specialization = ?
            WHERE id = ?
        ");
        $update_stmt->execute([$name, $email, $phone, $specialization,  $doctor_id]);

        // Handle profile photo upload
        if (!empty($_FILES['photo']['name'])) {
            $target_dir = "../admin/uploads/";
            $target_file = $target_dir . basename($_FILES["photo"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if image file is valid
            $check = getimagesize($_FILES["photo"]["tmp_name"]);
            if ($check !== false) {
                // Generate unique filename
                $new_filename = "doctor_" . $doctor_id . "." . $imageFileType;
                $target_file = $target_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                    // Update photo path in database
                    $photo_stmt = $pdo->prepare("UPDATE doctors SET photo = ? WHERE id = ?");
                    $photo_stmt->execute([$new_filename, $doctor_id]);
                }
            }
        }

        $message = "Profile updated successfully!";
        // Refresh doctor data
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "Error updating profile: " . $e->getMessage();
    }
}

// Handle consultation timings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_timings'])) {
    try {
        // First delete all existing timings
        $delete_stmt = $pdo->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?");
        $delete_stmt->execute([$doctor_id]);

        // Insert new timings
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            $insert_stmt = $pdo->prepare("
                INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, session_duration)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($_POST['days'] as $day) {
                $start_time = $_POST['start_time'][$day];
                $end_time = $_POST['end_time'][$day];
                $session_duration = $_POST['session_duration'][$day];

                if (!empty($start_time) && !empty($end_time)) {
                    $insert_stmt->execute([
                        $doctor_id,
                        $day,
                        $start_time,
                        $end_time,
                        $session_duration
                    ]);
                }
            }
        }

        $message = "Consultation timings updated successfully!";
        // Refresh timings data
        $timings_stmt->execute([$doctor_id]);
        $consultation_timings = $timings_stmt->fetchAll();
    } catch (PDOException $e) {
        $message = "Error updating consultation timings: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile Management</title>
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
        
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .profile-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9f2fa 100%);
            border-radius: 12px;
            padding: 20px;
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
                    <i class="fas fa-user-md me-2"></i>Doctor Profile
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
                <h2 class="page-title"><i class="fas fa-user-cog me-2"></i>Profile Management</h2>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Personal Details -->
            <div class="col-md-6">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Personal Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-4">
                                <img src="<?php echo !empty($doctor['profile_pic']) ? '../admin/uploads/' . htmlspecialchars($doctor['profile_pic']) : '../assets/default-avatar.jpg'; ?>" 
                                     class="avatar mb-3" alt="Doctor Photo">
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Change Profile Photo</label>
                                    <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($doctor['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($doctor['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="specialization" class="form-label">Specialization</label>
                                <input type="text" class="form-control" id="specialization" name="specialization" 
                                       value="<?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?>" required>
                            </div>
                            
                          
                            
                            <div class="text-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Consultation Timings -->
            <div class="col-md-6">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Consultation Timings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php
                            $days = [
                                'Monday', 'Tuesday', 'Wednesday', 
                                'Thursday', 'Friday', 'Saturday', 'Sunday'
                            ];
                            
                            foreach ($days as $day): 
                                // Find existing timing for this day
                                $existing_timing = null;
                                foreach ($consultation_timings as $timing) {
                                    if ($timing['day_of_week'] === $day) {
                                        $existing_timing = $timing;
                                        break;
                                    }
                                }
                            ?>
                                <div class="day-card mb-3">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input day-toggle" type="checkbox" 
                                               id="day-<?php echo strtolower($day); ?>" 
                                               name="days[]" value="<?php echo $day; ?>"
                                               <?php echo $existing_timing ? 'checked' : ''; ?>>
                                        <label class="form-check-label day-label" for="day-<?php echo strtolower($day); ?>">
                                            <?php echo $day; ?>
                                        </label>
                                    </div>
                                    
                                    <div class="row g-2 timing-fields" style="<?php echo !$existing_timing ? 'display: none;' : ''; ?>">
                                        <div class="col-md-4">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control time-input" 
                                                   name="start_time[<?php echo $day; ?>]" 
                                                   value="<?php echo $existing_timing ? substr($existing_timing['start_time'], 0, 5) : '09:00'; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control time-input" 
                                                   name="end_time[<?php echo $day; ?>]" 
                                                   value="<?php echo $existing_timing ? substr($existing_timing['end_time'], 0, 5) : '17:00'; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Session Duration (mins)</label>
                                            <input type="number" class="form-control" 
                                                   name="session_duration[<?php echo $day; ?>]" 
                                                   value="<?php echo $existing_timing ? $existing_timing['session_duration'] : '30'; ?>" 
                                                   min="10" max="120" step="5">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-end mt-4">
                                <button type="submit" name="update_timings" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Timings
                                </button>
                            </div>
                        </form>
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