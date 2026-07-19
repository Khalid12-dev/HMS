<?php
include '../config.php';

try {
    // Using PDO connection from config.php
    $sql = "SELECT * FROM doctors";
    $stmt = $pdo->query($sql);
    $doctors = $stmt->fetchAll();
} catch(PDOException $e) {
    die("ERROR: Could not execute query. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Doctors</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .doctor-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .rating-btn {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border: none;
        }
        .rating-btn:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            position: relative;
        }
        .dashboard-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .dashboard-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .no-image {
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header text-center mb-5">
            <a href="patient_dashboard.php" class="btn dashboard-btn text-white">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
            <h1><i class="fas fa-user-md me-2"></i> Our Specialist Doctors</h1>
            <p class="lead">Please rate your experience with our healthcare providers</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($doctors as $doctor): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <?php if(!empty($doctor['profile_pic'])): ?>
                            <img src="../admin/uploads/<?= htmlspecialchars($doctor['profile_pic']) ?>" class="doctor-img mb-3" alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">
                        <?php else: ?>
                            <div class="doctor-img mb-3 no-image">
                                <i class="fas fa-user-md"></i>
                            </div>
                        <?php endif; ?>
                        <h5 class="card-title">Dr. <?= htmlspecialchars($doctor['name']) ?></h5>
                        <p class="text-muted mb-3">
                            <i class="fas fa-stethoscope me-2"></i><?= htmlspecialchars($doctor['specialization']) ?>
                        </p>
                        <a href="rate_doctor.php?doctor_id=<?= $doctor['id'] ?>" class="btn rating-btn text-white">
                            <i class="fas fa-star me-1"></i> Rate Doctor
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <footer class="mt-5 text-center text-muted">
            <p>© <?= date('Y') ?> Hospital Rating System. All rights reserved.</p>
        </footer>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>