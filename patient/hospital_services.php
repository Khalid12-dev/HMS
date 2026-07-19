<?php
session_start();

// Redirect if not logged in as patient
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// Database Configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "hms";

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$bedMessage = $serviceMessage = '';

// Handle Bed Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_bed'])) {
    $patientName = $conn->real_escape_string($_POST['patient_name']);
    $patientAge = $conn->real_escape_string($_POST['patient_age']);
    $patientGender = $conn->real_escape_string($_POST['patient_gender']);
    $ward = $conn->real_escape_string($_POST['ward']);
    $contact = $conn->real_escape_string($_POST['contact']);
    
    // Insert bed request
    $sql = "INSERT INTO service_requests (patient_name, patient_age, patient_gender, ward, contact, status) 
            VALUES ('$patientName', '$patientAge', '$patientGender', '$ward', '$contact', 'Pending')";
    
    if ($conn->query($sql)) {
        $bedMessage = "Bed request submitted successfully for $patientName in $ward ward (Status: Pending)";
    } else {
        $bedMessage = "Error: " . $conn->error;
    }
}

// Handle Service Application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_service'])) {
    $servicePatientName = $conn->real_escape_string($_POST['service_patient_name']);
    $servicePatientAge = $conn->real_escape_string($_POST['service_patient_age']);
    $serviceName = $conn->real_escape_string($_POST['service_name']);
    $serviceDepartment = $conn->real_escape_string($_POST['service_department']);
    $serviceDate = $conn->real_escape_string($_POST['service_date']);
    
    // Insert service application
    $sql = "INSERT INTO service_requests (patient_name, patient_age, service_name, department, service_date, status) 
            VALUES ('$servicePatientName', '$servicePatientAge', '$serviceName', '$serviceDepartment', '$serviceDate', 'Pending')";
    
    if ($conn->query($sql)) {
        $serviceMessage = "Service application submitted successfully for $servicePatientName (Service: $serviceName)";
    } else {
        $serviceMessage = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Services Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .service-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
            height: 100%;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .bed-card {
            border-left: 5px solid #0d6efd;
        }
        .emergency-card {
            border-left: 5px solid #dc3545;
        }
        .available-bed {
            background-color: #d4edda;
        }
        .occupied-bed {
            background-color: #f8d7da;
        }
        .pending-bed {
            background-color: #fff3cd;
        }
        .tab-content {
            padding: 20px;
            background: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        .nav-tabs .nav-link {
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="text-center mb-5">
            <h1 class="display-4">🏥 Hospital Services Portal</h1>
            <p class="lead">Comprehensive healthcare management system</p>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bed-tab" data-bs-toggle="tab" data-bs-target="#bed-tab-pane" type="button" role="tab" aria-controls="bed-tab-pane" aria-selected="true">Bed Allocation</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services-tab-pane" type="button" role="tab" aria-controls="services-tab-pane" aria-selected="false">Department Services</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="emergency-tab" data-bs-toggle="tab" data-bs-target="#emergency-tab-pane" type="button" role="tab" aria-controls="emergency-tab-pane" aria-selected="false">Emergency Contacts</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Bed Allocation Tab -->
            <div class="tab-pane fade show active" id="bed-tab-pane" role="tabpanel" aria-labelledby="bed-tab" tabindex="0">
                <?php if (!empty($bedMessage)): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($bedMessage); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bed-card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h3>Request In-Patient Bed</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="patient_name" class="form-label">Patient Name</label>
                                        <input type="text" class="form-control" id="patient_name" name="patient_name" required>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="patient_age" class="form-label">Age</label>
                                            <input type="number" class="form-control" id="patient_age" name="patient_age" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="patient_gender" class="form-label">Gender</label>
                                            <select class="form-select" id="patient_gender" name="patient_gender" required>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact" name="contact" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="ward" class="form-label">Preferred Ward</label>
                                        <select class="form-select" id="ward" name="ward" required>
                                            <?php
                                            $wards = $conn->query("SELECT ward, COUNT(*) as total, 
                                                SUM(status = 'Available') as available 
                                                FROM beds GROUP BY ward");
                                            while($ward = $wards->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($ward['ward']) . "'>
                                                    " . htmlspecialchars($ward['ward']) . " (" . htmlspecialchars($ward['available']) . "/" . htmlspecialchars($ward['total']) . " available)
                                                </option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="request_bed" class="btn btn-primary">Request Bed</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card bed-card">
                            <div class="card-header bg-info text-white">
                                <h3>Bed Requests Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Ward</th>
                                                <th>Contact</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $requests = $conn->query("SELECT * FROM patient_requests ORDER BY request_date DESC");
                                            while($request = $requests->fetch_assoc()) {
                                                $statusClass = "status-" . strtolower($request['status']);
                                                echo "<tr>
                                                        <td>" . htmlspecialchars($request['patient_name']) . " (" . htmlspecialchars($request['patient_age']) . ", " . htmlspecialchars($request['patient_gender']) . ")</td>
                                                        <td>" . htmlspecialchars($request['ward']) . "</td>
                                                        <td>" . htmlspecialchars($request['contact']) . "</td>
                                                        <td class='" . htmlspecialchars($statusClass) . "'>" . htmlspecialchars($request['status']) . "</td>
                                                    </tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bed-card">
                            <div class="card-header bg-info text-white">
                                <h3>Current Bed Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Ward</th>
                                                <th>Bed No.</th>
                                                <th>Status</th>
                                                <th>Patient</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $beds = $conn->query("SELECT b.*, r.patient_name 
                                                FROM beds b 
                                                LEFT JOIN patient_requests r ON b.patient_id = r.id 
                                                ORDER BY ward, bed_number");
                                            while($bed = $beds->fetch_assoc()) {
                                                $statusClass = strtolower($bed['status']) . '-bed';
                                                $patientName = isset($bed['patient_name']) ? htmlspecialchars($bed['patient_name']) : 'N/A';
                                                echo "<tr class='" . htmlspecialchars($statusClass) . "'>
                                                        <td>" . htmlspecialchars($bed['ward']) . "</td>
                                                        <td>" . htmlspecialchars($bed['bed_number']) . "</td>
                                                        <td>" . htmlspecialchars($bed['status']) . "</td>
                                                        <td>" . $patientName . "</td>
                                                    </tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Services Tab -->
            <div class="tab-pane fade" id="services-tab-pane" role="tabpanel" aria-labelledby="services-tab" tabindex="0">
                <?php if (!empty($serviceMessage)): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($serviceMessage); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card service-card mb-4">
                            <div class="card-header bg-success text-white">
                                <h3>Apply for Service</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="service_patient_name" class="form-label">Patient Name</label>
                                        <input type="text" class="form-control" id="service_patient_name" name="service_patient_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="service_patient_age" class="form-label">Patient Age</label>
                                        <input type="number" class="form-control" id="service_patient_age" name="service_patient_age" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="service_name" class="form-label">Service</label>
                                        <select class="form-select" id="service_name" name="service_name" required>
                                            <?php
                                            $services = $conn->query("SELECT * FROM services WHERE status = 'Active' ORDER BY name");
                                            while($service = $services->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($service['name']) . "' data-department='" . htmlspecialchars($service['department']) . "'>
                                                    " . htmlspecialchars($service['name']) . " (₹" . number_format($service['cost'], 2) . ")
                                                </option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="service_department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="service_department" name="service_department" readonly required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="service_date" class="form-label">Preferred Date</label>
                                        <input type="date" class="form-control" id="service_date" name="service_date" required>
                                    </div>
                                    <button type="submit" name="apply_service" class="btn btn-success">Apply for Service</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card service-card">
                            <div class="card-header bg-warning text-dark">
                                <h3>Service Applications</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Service</th>
                                                <th>Department</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $applications = $conn->query("SELECT * FROM service_requests ORDER BY application_date DESC");
                                            while($app = $applications->fetch_assoc()) {
                                                $statusClass = "status-" . strtolower($app['status']);
                                                echo "<tr>
                                                        <td>" . htmlspecialchars($app['patient_name']) . " (" . htmlspecialchars($app['patient_age']) . ")</td>
                                                        <td>" . htmlspecialchars($app['service_name']) . "</td>
                                                        <td>" . htmlspecialchars($app['department']) . "</td>
                                                        <td>" . htmlspecialchars($app['service_date']) . "</td>
                                                        <td class='" . htmlspecialchars($statusClass) . "'>" . htmlspecialchars($app['status']) . "</td>
                                                    </tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <?php
                            $services = $conn->query("SELECT * FROM services ORDER BY department, name");
                            while($service = $services->fetch_assoc()) {
                                echo "<div class='col-md-6'>
                                        <div class='card service-card'>
                                            <div class='card-header bg-success text-white'>
                                                <h5>" . htmlspecialchars($service['name']) . "</h5>
                                            </div>
                                            <div class='card-body'>
                                                <p><strong>Department:</strong> " . htmlspecialchars($service['department']) . "</p>
                                                <p><strong>Cost:</strong> ₹" . number_format($service['cost'], 2) . "</p>
                                                <p>" . htmlspecialchars($service['description']) . "</p>
                                                <span class='badge " . ($service['status'] == 'Active' ? 'bg-success' : 'bg-secondary') . "'>
                                                    " . htmlspecialchars($service['status']) . "
                                                </span>
                                            </div>
                                        </div>
                                    </div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contacts Tab -->
            <div class="tab-pane fade" id="emergency-tab-pane" role="tabpanel" aria-labelledby="emergency-tab" tabindex="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card emergency-card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h3>Emergency Contacts</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-danger text-white rounded-circle p-3 me-3">
                                        <h2 class="mb-0">☎️</h2>
                                    </div>
                                    <div>
                                        <h5>Emergency Helpline</h5>
                                        <p class="mb-0"><strong>108 / +92-341-7269059</strong></p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-danger text-white rounded-circle p-3 me-3">
                                        <h2 class="mb-0">🚑</h2>
                                    </div>
                                    <div>
                                        <h5>Ambulance Request</h5>
                                        <p class="mb-0"><strong><a href="tel:+911234567890" class="text-danger">Call Now</a></strong></p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="bg-danger text-white rounded-circle p-3 me-3">
                                        <h2 class="mb-0">🩸</h2>
                                    </div>
                                    <div>
                                        <h5>Blood Bank</h5>
                                        <p class="mb-0"><strong>+92-301-1277269</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card emergency-card">
                            <div class="card-header bg-warning text-dark">
                                <h3>Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-danger btn-lg mb-3">
                                        <h4 class="mb-0">🚨 Emergency Button</h4>
                                    </button>
                                    <button class="btn btn-warning btn-lg mb-3">
                                        <h4 class="mb-0">🆘 SOS Alert</h4>
                                    </button>
                                    <button class="btn btn-info btn-lg">
                                        <h4 class="mb-0">🏥 Nearest Hospital</h4>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Service department auto-fill
            document.getElementById('service_name')?.addEventListener('change', function() {
                const dept = this.options[this.selectedIndex].getAttribute('data-department');
                document.getElementById('service_department').value = dept;
            });
            
            // Tab switching test
            var tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabElms.forEach(function(tabEl) {
                tabEl.addEventListener('shown.bs.tab', function (event) {
                    console.log('Tab shown:', event.target.id);
                });
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>