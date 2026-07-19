<?php
session_start(); // Start session at the beginning

// Redirect to login if not logged in as patient
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'hms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

$patient_email = $_SESSION['user']['email'];

// Create tables if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        specialization VARCHAR(100) NOT NULL,
        profile_pic VARCHAR(255),
        gender ENUM('male','female','other'),
        status ENUM('active','inactive') DEFAULT 'active'
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS doctor_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        session_duration INT NOT NULL COMMENT 'Duration in minutes',
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        patient_name VARCHAR(100) NOT NULL,
        patient_email VARCHAR(100) NOT NULL,
        patient_phone VARCHAR(20) NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        reason TEXT,
        status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id),
        FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
    )
");

// Insert sample doctors if none exist
$stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
if ($stmt->fetchColumn() == 0) {
    $sampleDoctors = [
        ['Dr. Sarah Johnson', 'Cardiology', 'female'],
        ['Dr. Michael Chen', 'Neurology', 'male'],
        ['Dr. Priya Patel', 'Pediatrics', 'female']
    ];
    
    foreach ($sampleDoctors as $doctor) {
        $stmt = $pdo->prepare("INSERT INTO doctors (name, specialization, gender) VALUES (?, ?, ?)");
        $stmt->execute([$doctor[0], $doctor[1], $doctor[2]]);
    }
}

// Insert sample availability if none exists
$stmt = $pdo->query("SELECT COUNT(*) FROM doctor_availability");
if ($stmt->fetchColumn() == 0) {
    $sampleAvailability = [
        [1, 'Monday', '09:00:00', '17:00:00', 30],
        [1, 'Wednesday', '09:00:00', '17:00:00', 30],
        [2, 'Tuesday', '10:00:00', '16:00:00', 45],
        [2, 'Thursday', '10:00:00', '16:00:00', 45],
        [3, 'Friday', '08:00:00', '14:00:00', 60]
    ];
    
    foreach ($sampleAvailability as $avail) {
        $stmt = $pdo->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, session_duration) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($avail);
    }
}

// Function to get doctor availability
function getDoctorAvailability($pdo, $doctor_id) {
    $stmt = $pdo->prepare("
        SELECT day_of_week, start_time, end_time, session_duration 
        FROM doctor_availability 
        WHERE doctor_id = ? AND status = 'active'
    ");
    $stmt->execute([$doctor_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get booked slots
function getBookedSlots($pdo, $doctor_id, $date) {
    $stmt = $pdo->prepare("
        SELECT appointment_time 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'
    ");
    $stmt->execute([$doctor_id, $date]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Handle AJAX request for time slots
if (isset($_GET['action']) && $_GET['action'] == 'get_time_slots') {
    header('Content-Type: application/json');
    
    $doctor_id = $_GET['doctor_id'];
    $date = $_GET['date'];
    
    // Get day of week from date
    $dayOfWeek = date('l', strtotime($date));
    
    // Get doctor availability
    $availability = getDoctorAvailability($pdo, $doctor_id);
    
    // Find availability for this day
    $dayAvailability = null;
    foreach ($availability as $avail) {
        if ($avail['day_of_week'] == $dayOfWeek) {
            $dayAvailability = $avail;
            break;
        }
    }
    
    if (!$dayAvailability) {
        echo json_encode(['error' => 'Doctor not available on this day']);
        exit;
    }
    
    // Get booked slots
    $bookedSlots = getBookedSlots($pdo, $doctor_id, $date);
    
    // Generate time slots
    $startTime = strtotime($dayAvailability['start_time']);
    $endTime = strtotime($dayAvailability['end_time']);
    $duration = $dayAvailability['session_duration'] * 60; // in seconds
    
    $timeSlots = [];
    $currentTime = $startTime;
    
    while ($currentTime + $duration <= $endTime) {
        $timeFormatted = date('H:i:s', $currentTime);
        $displayTime = date('h:i A', $currentTime);
        
        $isBooked = in_array($timeFormatted, $bookedSlots);
        
        $timeSlots[] = [
            'time' => $timeFormatted,
            'display' => $displayTime,
            'booked' => $isBooked
        ];
        
        $currentTime += $duration;
    }
    
    echo json_encode([
        'slots' => $timeSlots,
        'day' => $dayOfWeek,
        'start' => date('h:i A', $startTime),
        'end' => date('h:i A', $endTime)
    ]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['book_appointment'])) {
        $doctor_id = $_POST['doctor_id'];
        $patient_name = $_POST['patient_name'];
        $patient_email = $_POST['patient_email'];
        $patient_phone = $_POST['patient_phone'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $reason = $_POST['reason'];
        
        try {
            // Check if slot is available
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND appointment_time = ?
                AND status != 'cancelled'
            ");
            $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
            
            if ($stmt->fetchColumn() > 0) {
                $error_message = "This time slot is no longer available. Please choose another time.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO appointments (doctor_id, patient_name, patient_email, patient_phone, appointment_date, appointment_time, reason) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$doctor_id, $patient_name, $patient_email, $patient_phone, $appointment_date, $appointment_time, $reason]);
                
                $appointment_id = $pdo->lastInsertId();
                $success_message = "Appointment booked successfully! Your ID: $appointment_id";
                
                // Reset form fields after successful submission
                $_POST = array();
            }
        } catch (PDOException $e) {
            $error_message = "Error booking appointment: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['cancel_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_email = ?");
            $stmt->execute([$appointment_id, $patient_email]);
            
            if ($stmt->rowCount() > 0) {
                $success_message = "Appointment cancelled successfully!";
            } else {
                $error_message = "Failed to cancel appointment or appointment not found.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['reschedule_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $new_date = $_POST['new_date'];
        $new_time = $_POST['new_time']; // This was the issue - changed from $_POST['new_time'][0] to $_POST['new_time']
        
        try {
            // Check if the new slot is available
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE doctor_id = (SELECT doctor_id FROM appointments WHERE id = ?)
                AND appointment_date = ? 
                AND appointment_time = ?
                AND status != 'cancelled'
                AND id != ?
            ");
            $stmt->execute([$appointment_id, $new_date, $new_time, $appointment_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $error_message = "This time slot is already booked. Please choose another time.";
            } else {
                // Update appointment
                $stmt = $pdo->prepare("
                    UPDATE appointments 
                    SET appointment_date = ?, appointment_time = ?, status = 'pending'
                    WHERE id = ? AND patient_email = ?
                ");
                $stmt->execute([$new_date, $new_time, $appointment_id, $patient_email]);
                
                if ($stmt->rowCount() > 0) {
                    $success_message = "Appointment rescheduled successfully!";
                } else {
                    $error_message = "Failed to reschedule appointment or appointment not found.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all active doctors with their ratings
$doctors = [];
try {
    $stmt = $pdo->query("
        SELECT d.*, 
               COALESCE(AVG(r.rating), 0) as avg_rating, 
               COUNT(r.id) as total_reviews
        FROM doctors d
        LEFT JOIN ratings r ON d.id = r.doctor_id
        WHERE d.status = 'active'
        GROUP BY d.id
    ");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching doctors: " . $e->getMessage();
}

// Fetch user appointments
$appointments = [];
if (isset($_GET['tab']) && $_GET['tab'] === 'manage') {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, d.name as doctor_name, d.specialization 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.patient_email = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$patient_email]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching appointments: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCare | Book Appointment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --danger: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--gray-100);
            color: var(--gray-800);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }

        .btn-outline:hover {
            background-color: var(--gray-100);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 30px;
        }

        .tab {
            padding: 12px 20px;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-500);
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .tab.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        .tab:hover:not(.active) {
            color: var(--gray-700);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .doctor-card {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .doctor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .doctor-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 50px;
        }

        .doctor-info {
            padding: 15px;
        }

        .doctor-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .doctor-specialty {
            color: var(--gray-500);
            margin-bottom: 10px;
            font-size: 14px;
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
            color: var(--gray-600);
        }

        .stars {
            color: #f59e0b;
        }

        .doctor-actions {
            display: flex;
            justify-content: space-between;
        }

        .schedule-container {
            display: flex;
            gap: 30px;
        }

        .calendar {
            flex: 1;
        }

        .time-slots {
            flex: 1;
        }

        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .time-slot {
            padding: 10px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .time-slot:hover {
            background-color: var(--gray-100);
        }

        .time-slot.selected {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .time-slot.booked {
            background-color: var(--gray-100);
            color: var(--gray-400);
            cursor: not-allowed;
            text-decoration: line-through;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .appointment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            background-color: white;
        }

        .appointment-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .appointment-info p {
            color: var(--gray-500);
            font-size: 14px;
        }

        .appointment-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .review-item {
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stars {
            color: #f59e0b;
            margin-bottom: 5px;
        }

        .far.fa-star {
            color: #f59e0b;
        }

        .time-slot-radio {
            display: block;
            padding: 10px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0;
        }

        .time-slot-radio:hover {
            background-color: var(--gray-100);
        }

        .time-slot-radio input[type="radio"] {
            display: none;
        }

        .time-slot-radio.selected {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .time-slot-radio.booked {
            background-color: var(--gray-100);
            color: var(--gray-400);
            cursor: not-allowed;
            text-decoration: line-through;
        }

        .reschedule-time-slots {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
        }

        .reschedule-time-slots .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .availability-info {
            margin-top: 10px;
            font-size: 14px;
            color: var(--gray-500);
        }
        
        .time-slot-radio input[type="radio"]:checked + span {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .time-slot-radio input[type="radio"]:disabled + span {
            background-color: var(--gray-100);
            color: var(--gray-400);
            cursor: not-allowed;
            text-decoration: line-through;
        }
        @media (max-width: 768px) {
            .schedule-container {
                flex-direction: column;
            }
            
            .time-slots-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .doctors-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-heartbeat"></i>
                <span>MedCare</span>
            </div>
            <div class="user-actions">
                <button class="btn btn-outline" onclick="window.location.href='?tab=manage'">
                    <i class="fas fa-calendar"></i>
                    My Appointments
                </button>
                <button class="btn btn-outline">
                   <a href="patient_dashboard.php"> <i class="fas fa-dashboard"></i>
                    Dashboard
                   </a>
                </button>
            </div>
        </header>

        <div class="tabs">
            <div class="tab <?= (!isset($_GET['tab'])) || $_GET['tab'] === 'book' ? 'active' : '' ?>" data-tab="book">Book Appointment</div>
            <div class="tab <?= (isset($_GET['tab'])) && $_GET['tab'] === 'manage' ? 'active' : '' ?>" data-tab="manage">My Appointments</div>
        </div>

        <!-- Book Appointment Tab -->
        <div class="tab-content <?= (!isset($_GET['tab'])) || $_GET['tab'] === 'book' ? 'active' : '' ?>" id="book">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Select Doctor</h2>
                </div>
                <div class="doctors-grid">
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card">
                            <div class="doctor-img">
                                <img src="../admin/uploads/<?= htmlspecialchars($doctor['profile_pic']) ?>" 
                                     alt="<?= htmlspecialchars($doctor['name']) ?>" 
                                     class="doctor-img">
                            </div>
                            <div class="doctor-info">
                                <h3 class="doctor-name"><?= htmlspecialchars($doctor['name']) ?></h3>
                                <p class="doctor-specialty"><?= htmlspecialchars($doctor['specialization']) ?></p>
                                <div class="doctor-rating">
                                    <div class="stars">
                                        <?php
                                        $avgRating = round($doctor['avg_rating'], 1);
                                        $fullStars = floor($avgRating);
                                        $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $fullStars) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span><?= $avgRating ?> (<?= $doctor['total_reviews'] ?> reviews)</span>
                                </div>
                                <div class="doctor-actions">
                                    <button class="btn btn-outline view-reviews" data-doctor-id="<?= $doctor['id'] ?>">
                                        <i class="fas fa-info-circle"></i>
                                        Reviews
                                    </button>
                                    <button class="btn btn-primary select-doctor" data-doctor-id="<?= $doctor['id'] ?>">
                                        <i class="fas fa-calendar-check"></i>
                                        Select
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Schedule Section (hidden by default) -->
            <div class="card schedule-section" style="display: none;">
                <div class="card-header">
                    <h2 class="card-title">Select Date & Time</h2>
                    <button class="btn btn-outline change-doctor">
                        <i class="fas fa-arrow-left"></i>
                        Change Doctor
                    </button>
                </div>
                <div class="schedule-container">
                    <div class="calendar">
                        <h3>Select Date</h3>
                        <div class="form-group">
                            <input type="date" id="appointment-date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="time-slots">
                        <h3>Available Time Slots</h3>
                        <div class="time-slots-grid">
                            <div class="text-center py-4">
                                <p>Please select a date to see available time slots</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient Details Section (hidden by default) -->
            <div class="card patient-details-section" style="display: none;">
                <div class="card-header">
                    <h2 class="card-title">Patient Information</h2>
                </div>
                <form id="appointment-form" method="post">
                    <input type="hidden" name="doctor_id" id="selected-doctor-id">
                    <input type="hidden" name="appointment_date" id="selected-appointment-date">
                    <input type="hidden" name="appointment_time" id="selected-appointment-time">
                    
                    <div class="form-group">
                        <label for="patient-name" class="form-label">Full Name</label>
                        <input type="text" id="patient-name" name="patient_name" class="form-control" 
                               value="<?= htmlspecialchars($_SESSION['user']['full_name']) ?>" 
                               required>
                    </div>
                    <div class="form-group">
                        <label for="patient-email" class="form-label">Email Address</label>
                        <input type="email" id="patient-email" name="patient_email" class="form-control" 
                               value="<?= htmlspecialchars($_SESSION['user']['email']) ?>" 
                               required readonly>
                    </div>
                    <div class="form-group">
                        <label for="patient-phone" class="form-label">Phone Number</label>
                        <input type="tel" id="patient-phone" name="patient_phone" class="form-control" 
                               value="<?= isset($_POST['patient_phone']) ? htmlspecialchars($_POST['patient_phone']) : '' ?>" 
                               required>
                    </div>
                    <div class="form-group">
                        <label for="appointment-reason" class="form-label">Reason for Appointment</label>
                        <textarea id="appointment-reason" name="reason" class="form-control" rows="3"><?= isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '' ?></textarea>
                    </div>
                    <button type="submit" name="book_appointment" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-check-circle"></i>
                        Confirm Appointment
                    </button>
                </form>
            </div>
        </div>

        <!-- Manage Appointments Tab -->
        <div class="tab-content <?= (isset($_GET['tab'])) && $_GET['tab'] === 'manage' ? 'active' : '' ?>" id="manage">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Your Appointments</h2>
                    <button class="btn btn-outline" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
                <div class="appointments-list">
                    <?php if (empty($appointments)): ?>
                        <div class="alert alert-info">
                            You don't have any appointments yet. <a href="?tab=book" class="alert-link">Book an appointment</a>.
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="appointment-info">
                                    <h4><?= htmlspecialchars($appointment['doctor_name']) ?></h4>
                                    <p><?= htmlspecialchars($appointment['specialization']) ?></p>
                                    <p><?= date('F j, Y', strtotime($appointment['appointment_date'])) ?> at <?= date('h:i A', strtotime($appointment['appointment_time'])) ?></p>
                                    <p>Status: <span class="appointment-status status-<?= $appointment['status'] ?>">
                                        <?= ucfirst($appointment['status']) ?>
                                    </span></p>
                                    <?php if ($appointment['reason']): ?>
                                        <p>Reason: <?= htmlspecialchars($appointment['reason']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="appointment-actions">
                                    <?php if ($appointment['status'] !== 'cancelled'): ?>
                                        <button class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#rescheduleModal<?= $appointment['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                            Reschedule
                                        </button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                            <button type="submit" name="cancel_appointment" class="btn btn-outline-danger">
                                                <i class="fas fa-times"></i>
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-primary" onclick="window.location.href='?tab=book'">
                                            <i class="fas fa-calendar-plus"></i>
                                            Book New
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Reschedule Modal -->
                            <div class="modal fade" id="rescheduleModal<?= $appointment['id'] ?>" tabindex="-1" aria-labelledby="rescheduleModalLabel<?= $appointment['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="rescheduleModalLabel<?= $appointment['id'] ?>">Reschedule Appointment</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" id="rescheduleForm<?= $appointment['id'] ?>">
                                            <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                            <input type="hidden" name="doctor_id" value="<?= $appointment['doctor_id'] ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">New Date</label>
                                                    <input type="date" name="new_date" class="form-control reschedule-date" 
                                                           min="<?= date('Y-m-d') ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">New Time</label>
                                                    <div class="reschedule-time-slots">
                                                        <div class="text-center py-3">
                                                            <div class="spinner-border text-primary" role="status"></div>
                                                            <p class="mt-2">Select a date first</p>
                                                        </div>
                                                    </div>
                                                    <div class="invalid-feedback" id="timeSlotError">
                                                        Please select a time slot
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="reschedule_appointment" class="btn btn-primary">
                                                    Request Reschedule
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Appointment Confirmation</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="confirmation-message">
                <h3>Your appointment has been booked!</h3>
                <p>You will receive a confirmation email and SMS shortly.</p>
            </div>
            <button class="btn btn-primary" style="width: 100%;" id="modal-close-btn">
                <i class="fas fa-calendar"></i>
                View My Appointments
            </button>
        </div>
    </div>

    <!-- Reviews Modal -->
    <div class="modal" id="reviews-modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Patient Reviews</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                <div id="reviews-container">
                    <!-- Reviews will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Update URL without reloading
                history.pushState(null, null, `?tab=${tabId}`);
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Update active content
                tabContents.forEach(content => content.classList.remove('active'));
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Doctor selection functionality
        const doctorCards = document.querySelectorAll('.select-doctor');
        const scheduleSection = document.querySelector('.schedule-section');
        const patientDetailsSection = document.querySelector('.patient-details-section');
        const changeDoctorBtn = document.querySelector('.change-doctor');
        const selectedDoctorId = document.getElementById('selected-doctor-id');
        const doctorsGrid = document.querySelector('#book .doctors-grid');

        doctorCards.forEach(card => {
            card.addEventListener('click', () => {
                const doctorId = card.getAttribute('data-doctor-id');
                selectedDoctorId.value = doctorId;
                
                doctorsGrid.style.display = 'none';
                scheduleSection.style.display = 'block';
                patientDetailsSection.style.display = 'none';
                
                // Scroll to schedule section
                scheduleSection.scrollIntoView({ behavior: 'smooth' });
            });
        });

        changeDoctorBtn.addEventListener('click', () => {
            doctorsGrid.style.display = 'grid';
            scheduleSection.style.display = 'none';
            patientDetailsSection.style.display = 'none';
            
            // Reset selections
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Date selection with availability check
        const appointmentDate = document.getElementById('appointment-date');
        const selectedAppointmentDate = document.getElementById('selected-appointment-date');
        const timeSlotsGrid = document.querySelector('.time-slots-grid');

        appointmentDate.addEventListener('change', async () => {
            const date = appointmentDate.value;
            const doctorId = selectedDoctorId.value;
            
            if (!date || !doctorId) return;
            
            selectedAppointmentDate.value = date;
            patientDetailsSection.style.display = 'none';
            
            // Show loading state
            timeSlotsGrid.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading available slots...</p></div>';
            
            try {
                // Fetch available time slots from server
                const response = await fetch(`?action=get_time_slots&doctor_id=${doctorId}&date=${date}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    timeSlotsGrid.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    return;
                }
                
                // Display time slots
                let html = '';
                data.slots.forEach(slot => {
                    const slotClass = slot.booked ? 'booked' : '';
                    html += `
                        <div class="time-slot ${slotClass}" data-time="${slot.time}" ${slot.booked ? 'title="Already booked"' : ''}>
                            ${slot.display}
                        </div>
                    `;
                });
                
                timeSlotsGrid.innerHTML = html;
                
                // Add click event to new time slots
                document.querySelectorAll('.time-slot:not(.booked)').forEach(slot => {
                    slot.addEventListener('click', () => {
                        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                        slot.classList.add('selected');
                        selectedAppointmentTime.value = slot.getAttribute('data-time');
                        patientDetailsSection.style.display = 'block';
                        patientDetailsSection.scrollIntoView({ behavior: 'smooth' });
                    });
                });
                
                // Show availability info
                const availabilityInfo = document.createElement('div');
                availabilityInfo.className = 'availability-info mt-3 small text-muted';
                availabilityInfo.innerHTML = `<i class="fas fa-info-circle me-1"></i> Doctor available ${data.day}s from ${data.start} to ${data.end}`;
                timeSlotsGrid.after(availabilityInfo);
                
            } catch (error) {
                console.error('Error fetching time slots:', error);
                timeSlotsGrid.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading time slots. Please try again.
                    </div>
                `;
            }
        });

        // Time slot selection
        const selectedAppointmentTime = document.getElementById('selected-appointment-time');
        
        // Form submission
        const appointmentForm = document.getElementById('appointment-form');
        const confirmationModal = document.getElementById('confirmation-modal');
        const modalCloseBtn = document.querySelector('.modal-close');
        const modalCloseBtn2 = document.getElementById('modal-close-btn');

        appointmentForm.addEventListener('submit', (e) => {
            // Validate all required fields
            if (!selectedAppointmentTime.value) {
                e.preventDefault();
                alert('Please select a time slot');
                return;
            }
            
            const patientName = document.getElementById('patient-name').value;
            const patientEmail = document.getElementById('patient-email').value;
            const patientPhone = document.getElementById('patient-phone').value;
            
            if (!patientName || !patientEmail || !patientPhone) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
        });

        // Close modal
        modalCloseBtn.addEventListener('click', () => {
            confirmationModal.classList.remove('active');
        });

        modalCloseBtn2.addEventListener('click', () => {
            confirmationModal.classList.remove('active');
            // Switch to manage appointments tab
            window.location.href = '?tab=manage';
        });

        // Close modal when clicking outside
        confirmationModal.addEventListener('click', (e) => {
            if (e.target === confirmationModal) {
                confirmationModal.classList.remove('active');
            }
        });

        // View Reviews functionality
        const viewReviewsBtns = document.querySelectorAll('.view-reviews');
        const reviewsModal = document.getElementById('reviews-modal');
        const reviewsContainer = document.getElementById('reviews-container');

        viewReviewsBtns.forEach(btn => {
            btn.addEventListener('click', async () => {
                const doctorId = btn.getAttribute('data-doctor-id');
                
                try {
                    // Show loading message
                    reviewsContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading reviews...</p></div>';
                    reviewsModal.classList.add('active');
                    
                    // Fetch reviews from server
                    const response = await fetch(`get_reviews.php?doctor_id=${doctorId}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const reviews = await response.json();
                    
                    // Display reviews
                    if (reviews.error) {
                        reviewsContainer.innerHTML = `<div class="alert alert-danger">${reviews.error}</div>`;
                        return;
                    }
                    
                    if (reviews.length > 0) {
                        let html = '';
                        reviews.forEach(review => {
                            html += `
                                <div class="review-item mb-4 p-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="stars text-warning">
                                            ${'<i class="fas fa-star"></i>'.repeat(review.rating)}
                                            ${'<i class="far fa-star"></i>'.repeat(5 - review.rating)}
                                        </div>
                                        <small class="text-muted">${new Date(review.created_at).toLocaleDateString('en-US', { 
                                            year: 'numeric', 
                                            month: 'short', 
                                            day: 'numeric' 
                                        })}</small>
                                    </div>
                                    ${review.comment ? `<div class="review-comment mb-2">${review.comment}</div>` : ''}
                                    <div class="reviewer text-muted small">
                                        Reviewed by: ${review.patient_name || 'Anonymous'}
                                    </div>
                                </div>
                            `;
                        });
                        reviewsContainer.innerHTML = html;
                    } else {
                        reviewsContainer.innerHTML = '<div class="text-center py-4"><i class="far fa-folder-open fa-3x text-muted mb-3"></i><p>No reviews yet for this doctor.</p></div>';
                    }
                } catch (error) {
                    console.error('Error fetching reviews:', error);
                    reviewsContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error loading reviews. Please try again later.
                        </div>
                    `;
                }
            });
        });

        // Close reviews modal
        document.querySelector('#reviews-modal .modal-close').addEventListener('click', () => {
            reviewsModal.classList.remove('active');
        });

       // Reschedule modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const rescheduleModals = document.querySelectorAll('[id^="rescheduleModal"]');
    
    rescheduleModals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const appointmentItem = button.closest('.appointment-item');
            const doctorId = appointmentItem.querySelector('input[name="doctor_id"]')?.value || 
                            modal.querySelector('input[name="doctor_id"]')?.value;
            const appointmentId = modal.id.replace('rescheduleModal', '');
            const form = modal.querySelector('form');
            const dateInput = modal.querySelector('.reschedule-date');
            const timeSlotsContainer = modal.querySelector('.reschedule-time-slots');
            const timeSlotError = modal.querySelector('#timeSlotError');
            
            // Set minimum date to today
            dateInput.min = new Date().toISOString().split('T')[0];
            
            // Handle date change
            dateInput.addEventListener('change', async function() {
                const date = this.value;
                
                if (!date) return;
                
                timeSlotsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading available slots...</p></div>';
                
                try {
                    // Fetch available time slots from server
                    const response = await fetch(`?action=get_time_slots&doctor_id=${doctorId}&date=${date}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.error) {
                        timeSlotsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }
                    
                    let html = '<div class="time-slots-grid">';
                    if (data.slots.length === 0) {
                        html = '<div class="alert alert-warning">No available time slots for this date</div>';
                    } else {
                        data.slots.forEach(slot => {
                            const slotClass = slot.booked ? 'booked' : '';
                            html += `
                                <label class="time-slot-radio ${slotClass}">
                                    <input type="radio" name="new_time" value="${slot.time}" required 
                                           ${slot.booked ? 'disabled' : ''}>
                                    <span>${slot.display}</span>
                                </label>
                            `;
                        });
                    }
                    html += '</div>';
                    
                    timeSlotsContainer.innerHTML = html;
                    
                    // Add click handler for radio buttons
                    timeSlotsContainer.querySelectorAll('.time-slot-radio input[type="radio"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            timeSlotError.style.display = 'none';
                        });
                    });
                    
                } catch (error) {
                    console.error('Error fetching time slots:', error);
                    timeSlotsContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error loading time slots. Please try again.
                        </div>
                    `;
                }
            });
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                const timeSelected = form.querySelector('input[name="new_time"]:checked');
                if (!timeSelected) {
                    e.preventDefault();
                    timeSlotError.style.display = 'block';
                    return false;
                }
                return true;
            });
        });
    });
});

        // Initialize the date picker with today's date
        window.addEventListener('DOMContentLoaded', () => {
            const today = new Date().toISOString().split('T')[0];
            appointmentDate.value = today;
            selectedAppointmentDate.value = today;
            
            // Check URL for tab parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam === 'manage') {
                document.querySelector('.tab[data-tab="manage"]').click();
            }
            
            // Show confirmation modal if we have a success message
            <?php if (isset($success_message)): ?>
                confirmationModal.classList.add('active');
            <?php endif; ?>
        });
    </script>
</body>
</html>