<?php
session_start();
include '../config.php';

// Check if user is logged in as patient
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['doctor_id'])) {
    $doctor_id = $_GET['doctor_id'];
    $patient_id = $_SESSION['user']['id']; // Get logged-in patient's ID
    
    try {
        // Fetch doctor details using PDO
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();

        if (!$doctor) {
            die("ERROR: Doctor not found.");
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $rating = (int)$_POST['rating'];
            $comment = $_POST['comment'];

            // Validate rating
            if ($rating < 1 || $rating > 5) {
                $error_message = "Please select a valid rating (1-5 stars).";
            } else {
                // Check if patient already rated this doctor
                $stmt = $pdo->prepare("SELECT id FROM ratings WHERE doctor_id = ? AND patient_id = ?");
                $stmt->execute([$doctor_id, $patient_id]);
                
                if ($stmt->rowCount() > 0) {
                    $error_message = "You have already rated this doctor.";
                } else {
                    // Insert rating into database using PDO
                    $stmt = $pdo->prepare("INSERT INTO ratings (doctor_id, patient_id, rating, comment) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$doctor_id, $patient_id, $rating, $comment]);

                    $success_message = "Rating submitted successfully!";
                }
            }
        }
    } catch(PDOException $e) {
        die("ERROR: Database operation failed. " . $e->getMessage());
    }
} else {
    die("ERROR: Doctor ID not specified.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Dr. <?= htmlspecialchars($doctor['name'] ?? '') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .rating-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .doctor-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .doctor-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
        }
        .rating-stars {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .rating-stars .star {
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s;
        }
        .rating-stars .star.active, .rating-stars .star.hover {
            color: #ffc107;
        }
        .btn-submit {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .back-btn {
            color: #6c757d;
            transition: color 0.2s;
        }
        .back-btn:hover {
            color: #0d6efd;
            text-decoration: none;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="rating-container">
            <a href="javascript:history.back()" class="back-btn mb-3 d-inline-block">
                <i class="fas fa-arrow-left me-1"></i> Back to Doctors
            </a>
            
            <div class="doctor-header text-center">
                <?php if(!empty($doctor['profile_pic'])): ?>
                    <img src="../admin/uploads/<?= htmlspecialchars($doctor['profile_pic']) ?>" class="doctor-img mb-3" alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">
                <?php else: ?>
                    <div class="doctor-img mb-3 mx-auto bg-secondary d-flex align-items-center justify-content-center">
                        <i class="fas fa-user-md text-white" style="font-size: 2rem;"></i>
                    </div>
                <?php endif; ?>
                <h2>Rate Dr. <?= htmlspecialchars($doctor['name'] ?? '') ?></h2>
                <p class="mb-0"><i class="fas fa-stethoscope me-2"></i><?= htmlspecialchars($doctor['specialization'] ?? '') ?></p>
            </div>

            <?php if(isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="ratingForm">
                <div class="mb-4 text-center">
                    <label class="form-label fw-bold mb-3">How would you rate your experience?</label>
                    <div class="rating-stars" id="ratingStars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star star mx-1" data-rating="<?= $i ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="selectedRating" required>
                    <div id="ratingError" class="error-message"></div>
                </div>

                <div class="mb-4">
                    <label for="comment" class="form-label fw-bold">Your Feedback</label>
                    <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Share your experience (optional)"></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-submit text-white btn-lg">
                        <i class="fas fa-paper-plane me-2"></i> Submit Rating
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Star Rating Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('selectedRating');
            const ratingForm = document.getElementById('ratingForm');
            const ratingError = document.getElementById('ratingError');
            
            // Initialize star rating functionality
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    ratingInput.value = rating;
                    
                    stars.forEach((s, index) => {
                        s.classList.toggle('active', index < rating);
                    });
                    
                    // Clear error when rating is selected
                    ratingError.textContent = '';
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = this.getAttribute('data-rating');
                    
                    stars.forEach((s, index) => {
                        s.classList.toggle('hover', index < rating);
                    });
                });
                
                star.addEventListener('mouseout', function() {
                    stars.forEach(s => s.classList.remove('hover'));
                });
            });
            
            // Form validation
            ratingForm.addEventListener('submit', function(e) {
                if (!ratingInput.value) {
                    e.preventDefault();
                    ratingError.textContent = 'Please select a rating';
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>