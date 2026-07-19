<?php
require 'config.php';

// Fetch departments
$departmentsQuery = "SELECT * FROM departments WHERE status = 'active'";
$departmentsStmt = $pdo->query($departmentsQuery);
$departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctors with their average ratings
$doctorsQuery = "SELECT d.*, 
                COALESCE(AVG(r.rating), 0) as average_rating,
                COUNT(r.id) as rating_count
                FROM doctors d
                LEFT JOIN ratings r ON d.id = r.doctor_id
                WHERE d.status = 'active'
                GROUP BY d.id";
$doctorsStmt = $pdo->query($doctorsQuery);
$doctors = $doctorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff members
$staffQuery = "SELECT * FROM staff WHERE status = 'Active'";
$staffStmt = $pdo->query($staffQuery);
$staff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Pro HMS - Hospital Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #10b981;
            --accent-dark: #0d9488;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --admin-color: #7c3aed;
            --doctor-color: #f59e0b;
            --patient-color: #14b8a6;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            color: #334155;
            line-height: 1.6;
        }
        
        .navbar {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            padding: 12px 0;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.98);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s;
            color: var(--dark-color);
        }
        
        .nav-link:hover {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.92), rgba(16, 185, 129, 0.88)), 
                        url('https://images.unsplash.com/photo-1579684385127-1ef15d508118?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 160px 0 120px;
            position: relative;
        }
        
        .feature-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border-bottom: 4px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
            border-bottom-color: var(--primary-color);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            transition: all 0.3s;
        }
        
        .stats-section {
            background-color: white;
            position: relative;
            z-index: 3;
            margin-top: -60px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px 15px;
            position: relative;
        }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .testimonial-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .contact-section {
            background-color: var(--dark-color);
            color: white;
            position: relative;
        }
        
        .btn-appointment {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
            border-radius: 8px;
        }
        
        .btn-appointment:hover {
            background-color: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }
        
        .section-title {
            position: relative;
            margin-bottom: 50px;
            text-align: center;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .department-card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.4s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            text-decoration: none;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 999;
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }
        
        /* Doctor Cards */
        .doctor-card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .doctor-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .rating-stars {
            color: #f59e0b;
        }
        
        .staff-card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .staff-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .staff-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .badge-role {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding: 20px;
                background-color: white;
                border-radius: 12px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
                margin-top: 15px;
            }
            
            .hero-section {
                padding: 120px 0 80px;
                text-align: center;
            }
            
            .stat-item {
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 767.98px) {
            .section-title {
                font-size: 1.8rem;
            }
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 50px 0 20px;
        }

        .footer h5 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1rem;
        }

        .footer ul {
            padding-left: 0;
            list-style: none;
        }

        .footer ul li {
            margin-bottom: 0.8rem;
        }

        .footer ul li a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer ul li a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-icons {
            margin-top: 1.5rem;
        }

        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .social-icons a:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer hr {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 2rem 0;
        }

        .footer .form-control {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            border-radius: 4px;
        }

        .footer .form-control:focus {
            box-shadow: none;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .footer .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .footer .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .footer .form-check-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .footer .text-muted {
            color: rgba(255, 255, 255, 0.5) !important;
        }

        .footer .text-decoration-none {
            color: white;
        }

        .footer .text-decoration-none:hover {
            color: var(--primary-color);
        }
 @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        body {
            animation: fadeIn 0.8s ease-out;
            overflow-x: hidden;
        }
        
        /* Slide-up animation for sections */
        .section-animate {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease-out;
        }
        
        .section-animate.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Hover animations for cards */
        .doctor-card, .staff-card, .department-card {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .doctor-card:hover, .staff-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 14px 28px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.08);
        }
        
        .department-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.08);
        }
        
        /* Pulse animation for buttons */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .btn-primary, .btn-outline-primary {
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover, .btn-outline-primary:hover {
            animation: pulse 1s infinite;
        }
        
        /* Floating animation for hero image */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        
        .hero-image {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Rotate animation for icons */
        .feature-icon {
            transition: all 0.4s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: rotateY(180deg);
            color: var(--accent-color);
        }
        
        /* Bounce animation for stats */
        .stat-item {
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
        }
        
        /* Wave animation for section titles */
        .section-title:after {
            transition: all 0.6s ease;
        }
        
        .section-title:hover:after {
            width: 120px;
            background-color: var(--accent-color);
        }
        
        /* Social media icons animation */
        .social-icons a {
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            transform: translateY(-5px) rotate(10deg);
        }
        
        /* Navbar link animation */
        .nav-link {
            position: relative;
        }
        
        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover:after {
            width: 100%;
        }
        
        /* Back to top button animation */
        .back-to-top.active {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-15px);}
            60% {transform: translateY(-7px);}
        }
        @media (max-width: 767.98px) {
            .footer {
                text-align: center;
            }
            
            .footer .col-md-6.text-md-end {
                text-align: center !important;
                margin-top: 1rem;
            }
            
            .social-icons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital me-2"></i>MediCare <span class="fw-bold">Pro</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#doctors">Doctors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#departments">Departments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#staff">Staff</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <div class="ms-lg-3 mt-3 mt-lg-0 d-flex align-items-center">
                    <a href="login.php" class="btn btn-primary me-2">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-user-plus me-1"></i> Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Advanced Hospital Management System</h1>
                    <p class="lead mb-4">Streamline your healthcare operations with our comprehensive solution for patient care, doctor management, and administrative tasks.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#doctors" class="btn btn-light btn-lg px-4">Our Doctors</a>
                        <a href="#departments" class="btn btn-outline-light btn-lg px-4">Departments</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="https://images.unsplash.com/photo-1581595219315-a187dd40c322?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Hospital Management System" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section py-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($doctors); ?>+</div>
                        <div class="stat-label">Expert Doctors</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Emergency Service</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($staff); ?>+</div>
                        <div class="stat-label">Dedicated Staff</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($departments); ?>+</div>
                        <div class="stat-label">Specialized Departments</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section class="py-5" id="doctors">
        <div class="container">
            <h2 class="section-title">Our Expert Doctors</h2>
            <p class="text-center mb-5">Meet our team of highly qualified and experienced medical professionals</p>
            <div class="row g-4">
                <?php foreach ($doctors as $doctor): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="doctor-card card h-100">
                 <?php
// Set the profile picture path
$profilePic = !empty($doctor['profile_pic']) && file_exists('./admin/uploads/' . $doctor['profile_pic'])
    ? './admin/uploads/' . $doctor['profile_pic']
    : './admin/uploads/default.png';
?>

<img 
    src="<?php echo $profilePic; ?>" 
    class="doctor-img card-img-top" 
    alt="<?php echo htmlspecialchars($doctor['name'] ?? 'Doctor'); ?>">


                        <div class="card-body text-center">
                            <h4 class="card-title"><?php echo htmlspecialchars($doctor['name']); ?></h4>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                            <div class="rating-stars mb-2">
                                <?php
                                $rating = round($doctor['average_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span class="ms-2">(<?php echo $doctor['rating_count']; ?>)</span>
                            </div>
                            <p class="card-text">
                                <i class="fas fa-envelope me-2 text-primary"></i> <?php echo htmlspecialchars($doctor['email']); ?><br>
                                <i class="fas fa-phone me-2 text-primary"></i> <?php echo htmlspecialchars($doctor['phone']); ?>
                            </p>
                            
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Departments Section -->
    <section class="py-5 bg-light" id="departments">
        <div class="container">
            <h2 class="section-title">Our Departments</h2>
            <p class="text-center mb-5">Explore our specialized medical departments offering comprehensive care</p>
            <div class="row g-4">
                <?php foreach ($departments as $department): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="department-card card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="feature-icon me-3">
                                    <i class="fas fa-hospital-user"></i>
                                </div>
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($department['department_name']); ?></h5>
                            </div>
                            <?php if ($department['department_head']): ?>
                            <p class="text-muted"><i class="fas fa-user-md me-2"></i> Head: <?php echo htmlspecialchars($department['department_head']); ?></p>
                            <?php endif; ?>
                            <p class="card-text"><?php echo htmlspecialchars($department['description'] ?: 'Comprehensive care for all related medical conditions.'); ?></p>
                            <a href="#" class="btn btn-sm btn-outline-primary">Learn More</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Staff Section -->
    <section class="py-5" id="staff">
        <div class="container">
            <h2 class="section-title">Our Dedicated Staff</h2>
            <p class="text-center mb-5">Meet our team of healthcare professionals who ensure smooth hospital operations</p>
            <div class="row g-4">
                <?php foreach ($staff as $staffMember): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="staff-card card h-100">
                        <?php
// Set the photo path
$photoPath = !empty($staffMember['photo']) && file_exists('./admin/uploads/' . $staffMember['photo'])
    ? './admin/uploads/' . $staffMember['photo']
    : './admin/uploads/default.png'; // Default image if no photo
?>

<img 
    src="<?php echo $photoPath; ?>" 
    class="staff-img card-img-top" 
    alt="<?php echo htmlspecialchars($staffMember['first_name'] . ' ' . $staffMember['last_name']); ?>">

                        <div class="card-body text-center">
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($staffMember['first_name'] . ' ' . $staffMember['last_name']); ?></h5>
                            <span class="badge badge-role mb-2"><?php echo htmlspecialchars($staffMember['role']); ?></span>
                            <p class="text-muted mb-2"><i class="fas fa-clinic-medical me-1"></i> <?php echo htmlspecialchars($staffMember['department']); ?></p>
                            <p class="card-text small">
                                <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($staffMember['email']); ?><br>
                                <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($staffMember['phone']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section py-5" id="contact">
        <div class="container">
            <h2 class="section-title text-white">Contact Us</h2>
            <p class="text-center text-white mb-5">Get in touch with our team for more information or support</p>
            <div class="row g-4">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h4 class="card-title">Our Location</h4>
                            <p class="card-text">123 Medical Plaza, Health District<br>Samundri, Faisalabad<br>Pakistan</p>
                            <a href="#" class="btn btn-sm btn-outline-primary mt-2">Get Directions</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h4 class="card-title">Call Us</h4>
                            <p class="card-text">
                                <a href="tel:+923046951550" class="text-decoration-none d-block">+92 304 6951550</a>
                                <a href="tel:+923217943960" class="text-decoration-none d-block">+92 321 7943960</a>
                            </p>
                            <p class="mt-2">Emergency: <a href="tel:911" class="text-decoration-none">911</a></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4 class="card-title">Email Us</h4>
                            <p class="card-text">
                                <a href="mailto:info@medicarehms.com" class="text-decoration-none d-block">info@medicarehms.com</a>
                                <a href="mailto:support@medicarehms.com" class="text-decoration-none d-block">support@medicarehms.com</a>
                            </p>
                            <a href="#" class="btn btn-sm btn-outline-primary mt-2">Send Message</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-3"><i class="fas fa-hospital me-2"></i>MediCare Pro HMS</h5>
                    <p>A comprehensive Hospital Management System designed to streamline healthcare operations and improve patient care.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="#doctors" class="text-white text-decoration-none">Doctors</a></li>
                        <li class="mb-2"><a href="#departments" class="text-white text-decoration-none">Departments</a></li>
                        <li class="mb-2"><a href="#staff" class="text-white text-decoration-none">Staff</a></li>
                        <li><a href="#contact" class="text-white text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="mb-3">Services</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Emergency Care</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Chamber Service</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Expert Doctors</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Diagnostics</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Pharmacy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3">Newsletter</h5>
                    <p>Subscribe to our newsletter for the latest updates.</p>
                    <form class="mt-3">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="privacyCheck" required>
                            <label class="form-check-label small" for="privacyCheck">
                                I agree to the privacy policy
                            </label>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; 2025 MediCare Pro HMS. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">Developed by <a href="#" class="text-white text-decoration-none">Salman Haider & Abdullah Saeed</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top"><i class="fas fa-arrow-up"></i></a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Back to top button
        const backToTopButton = document.querySelector('.back-to-top');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.style.opacity = '1';
                backToTopButton.style.visibility = 'visible';
                backToTopButton.classList.add('active');
            } else {
                backToTopButton.style.opacity = '0';
                backToTopButton.style.visibility = 'hidden';
                backToTopButton.classList.remove('active');
            }
        });
        
        backToTopButton.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Navbar background change on scroll
        const navbar = document.querySelector('.navbar');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 50) {
                navbar.classList.add('shadow-sm');
            } else {
                navbar.classList.remove('shadow-sm');
            }
        });
        
        // Animation on scroll
        const animateOnScroll = () => {
            const sections = document.querySelectorAll('.section-animate');
            
            sections.forEach(section => {
                const sectionTop = section.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (sectionTop < windowHeight - 100) {
                    section.classList.add('animated');
                }
            });
        };
        
        // Add section-animate class to all sections that should animate
        document.querySelectorAll('section').forEach(section => {
            section.classList.add('section-animate');
        });
        
        // Run once on page load
        animateOnScroll();
        
        // Run on scroll
        window.addEventListener('scroll', animateOnScroll);
        
        // Add hover class to stats on page load for demo purposes
        setTimeout(() => {
            document.querySelectorAll('.stat-item').forEach(item => {
                item.classList.add('hover');
                setTimeout(() => {
                    item.classList.remove('hover');
                }, 2000);
            });
        }, 1500);
    </script>
</body>
</html>