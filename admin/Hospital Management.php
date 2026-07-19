
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Pro HMS - Hospital Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #1a76d1;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
        
        .badge-available {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .badge-occupied {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .badge-maintenance {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .badge-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .badge-approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .badge-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .badge-completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .badge-cancelled {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .staff-card {
            border-left: 4px solid var(--primary-color);
        }
        
        .emergency-card {
            border-left: 4px solid var(--danger-color);
        }
        
        .service-card {
            border-left: 4px solid var(--success-color);
        }
        
        .staff-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "hms";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_bed'])) {
            $bed_number = $_POST['bed_number'];
            $ward = $_POST['ward'];
            $bed_type = $_POST['bed_type'];
            $status = $_POST['status'];
            
            $sql = "INSERT INTO beds (bed_number, ward, bed_type, status) 
                    VALUES ('$bed_number', '$ward', '$bed_type', '$status')";
            $conn->query($sql);
        }
        
        if (isset($_POST['add_service'])) {
            $name = $_POST['name'];
            $department = $_POST['department'];
            $description = $_POST['description'];
            $cost = $_POST['cost'];
            $status = $_POST['status'];
            
            $sql = "INSERT INTO services (name, department, description, cost, status) 
                    VALUES ('$name', '$department', '$description', '$cost', '$status')";
            $conn->query($sql);
        }
        
        if (isset($_POST['add_staff'])) {
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $role = $_POST['role'];
            $department = $_POST['department'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $phone = $_POST['phone'];
            $status = $_POST['status'];
            
            // Handle file upload
            $photo_name = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                $file_name = basename($_FILES['photo']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
                
                if (in_array($file_ext, $allowed_ext)) {
                    // Generate unique filename
                    $photo_name = uniqid('staff_', true) . '.' . $file_ext;
                    $upload_path = $upload_dir . $photo_name;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                        // File uploaded successfully
                    } else {
                        $photo_name = ''; // Reset if upload failed
                    }
                }
            }
            
            $sql = "INSERT INTO staff (first_name, last_name, role, department, email, password, phone, photo, status) 
                    VALUES ('$first_name', '$last_name', '$role', '$department', '$email', '$password', '$phone', '$photo_name', '$status')";
            $conn->query($sql);
        }
        
        // Handle bed request actions
        if (isset($_POST['bed_request_action'])) {
            $request_id = $_POST['request_id'];
            $action = $_POST['action'];
            
            if ($action === 'approve') {
                $sql = "UPDATE patient_requests SET status='Approved' WHERE id=$request_id";
                $conn->query($sql);
                
                // Assign bed to patient
                if (isset($_POST['bed_id'])) {
                    $bed_id = $_POST['bed_id'];
                    $sql = "UPDATE beds SET status='Occupied', patient_id=(SELECT patient_id FROM patient_requests WHERE id=$request_id) WHERE bed_id=$bed_id";
                    $conn->query($sql);
                }
            } elseif ($action === 'reject') {
                $sql = "UPDATE patient_requests SET status='Rejected' WHERE id=$request_id";
                $conn->query($sql);
            }
        }
        
        // Handle service request actions
        if (isset($_POST['service_request_action'])) {
            $request_id = $_POST['request_id'];
            $action = $_POST['action'];
            
            if ($action === 'complete') {
                $sql = "UPDATE service_requests SET status='Completed' WHERE patient_id=$request_id";
            } elseif ($action === 'cancel') {
                $sql = "UPDATE service_requests SET status='Cancelled' WHERE patient_id=$request_id";
            }
            $conn->query($sql);
        }
    }
    
    // Handle deletions
    if (isset($_GET['delete'])) {
        $type = $_GET['delete'];
        $id = $_GET['id'];
        
        if ($type === 'bed') {
            $sql = "DELETE FROM beds WHERE bed_id=$id";
        } elseif ($type === 'service') {
            $sql = "DELETE FROM services WHERE service_id=$id";
        } elseif ($type === 'staff') {
            // First get photo name to delete the file
            $result = $conn->query("SELECT photo FROM staff WHERE staff_id=$id");
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (!empty($row['photo']) && file_exists('uploads/' . $row['photo'])) {
                    unlink('uploads/' . $row['photo']);
                }
            }
            $sql = "DELETE FROM staff WHERE staff_id=$id";
        }
        
        $conn->query($sql);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
    ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-hospital me-2"></i> Hospital Management</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hospital Management</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <ul class="nav nav-pills mb-4" id="hospitalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="beds-tab" data-bs-toggle="pill" data-bs-target="#beds" type="button">
                    <i class="fas fa-bed me-2"></i>Bed Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="services-tab" data-bs-toggle="pill" data-bs-target="#services" type="button">
                    <i class="fas fa-procedures me-2"></i>Services
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="staff-tab" data-bs-toggle="pill" data-bs-target="#staff" type="button">
                    <i class="fas fa-user-md me-2"></i>Staff Management
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="hospitalTabsContent">
            <!-- Bed Management Tab -->
            <div class="tab-pane fade show active" id="beds" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Bed Availability</h5>
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addBedModal">
                                    <i class="fas fa-plus me-1"></i> Add Bed
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="bedsTable">
                                        <thead>
                                            <tr>
                                                <th>Bed ID</th>
                                                <th>Ward/Unit</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                               
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT b.*, p.first_name, p.last_name 
                                                    FROM beds b 
                                                    LEFT JOIN patient p ON b.patient_id = p.patient_id";
                                            $result = $conn->query($sql);
                                            
                                            if ($result->num_rows > 0) {
                                                while($bed = $result->fetch_assoc()) {
                                                    $status_class = $bed['status'] === 'Available' ? 'badge-available' : 
                                                                  ($bed['status'] === 'Occupied' ? 'badge-occupied' : 'badge-maintenance');
                                                    $patientName = $bed['first_name'] ? $bed['first_name'].' '.$bed['last_name'] : '-';
                                                    echo "<tr id='bed-{$bed['bed_id']}'>
                                                            <td>{$bed['bed_number']}</td>
                                                            <td>{$bed['ward']}</td>
                                                            <td>{$bed['bed_type']}</td>
                                                            <td><span class='badge {$status_class}'>{$bed['status']}</span></td>
                                                            
                                                            <td>
                                                                <a href='edit_bed.php?id={$bed['bed_id']}' class='btn btn-sm btn-outline-primary me-1'>
                                                                    <i class='fas fa-edit'></i>
                                                                </a>
                                                                <a href='?delete=bed&id={$bed['bed_id']}' class='btn btn-sm btn-outline-danger delete-bed'>
                                                                    <i class='fas fa-trash'></i>
                                                                </a>
                                                            </td>
                                                        </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='6' class='text-center py-4'>No beds found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bed Requests Section -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Bed Requests</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Patient Name</th>
                                                <th>Age/Gender</th>
                                                <th>Ward</th>
                                                <th>Status</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT * FROM patient_requests ORDER BY request_date DESC";
                                            $result = $conn->query($sql);
                                            
                                            if ($result->num_rows > 0) {
                                                while($request = $result->fetch_assoc()) {
                                                    $status_class = $request['status'] === 'Pending' ? 'badge-pending' : 
                                                                  ($request['status'] === 'Approved' ? 'badge-approved' : 'badge-rejected');
                                                    
                                                    echo "<tr>
                                                            <td>PR-{$request['id']}</td>
                                                            <td>{$request['patient_name']}</td>
                                                            <td>{$request['patient_age']}/{$request['patient_gender']}</td>
                                                            <td>{$request['ward']}</td>
                                                            <td><span class='badge {$status_class}'>{$request['status']}</span></td>
                                                            <td>" . date('M d, Y H:i', strtotime($request['request_date'])) . "</td>
                                                            <td>";
                                                    
                                                    if ($request['status'] === 'Pending') {
                                                        // Get available beds for this ward
                                                        $ward = $conn->real_escape_string($request['ward']);
                                                        $beds_sql = "SELECT * FROM beds WHERE ward='$ward' AND status='Available'";
                                                        $beds_result = $conn->query($beds_sql);
                                                        
                                                        echo "<form method='POST' action='' class='d-inline'>
                                                                <input type='hidden' name='request_id' value='{$request['id']}'>
                                                                <input type='hidden' name='action' value='approve'>";
                                                                
                                                        if ($beds_result->num_rows > 0) {
                                                            echo "<select name='bed_id' class='form-select form-select-sm d-inline-block w-auto me-2' required>
                                                                    <option value=''>Select Bed</option>";
                                                            while($bed = $beds_result->fetch_assoc()) {
                                                                echo "<option value='{$bed['bed_id']}'>{$bed['bed_number']} ({$bed['bed_type']})</option>";
                                                            }
                                                            echo "</select>";
                                                        }
                                                        
                                                        echo "<button type='submit' name='bed_request_action' class='btn btn-sm btn-success me-1'>
                                                                <i class='fas fa-check'></i> Approve
                                                              </button>
                                                              </form>";
                                                        
                                                        echo "<form method='POST' action='' class='d-inline'>
                                                                <input type='hidden' name='request_id' value='{$request['id']}'>
                                                                <input type='hidden' name='action' value='reject'>
                                                                <button type='submit' name='bed_request_action' class='btn btn-sm btn-danger'>
                                                                    <i class='fas fa-times'></i> Reject
                                                                </button>
                                                              </form>";
                                                    } 
                                                    
                                                    echo "</td>
                                                        </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center py-4'>No bed requests found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Bed Statistics</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="bedStatusChart" height="200"></canvas>
                                <div class="mt-3">
                                    <?php
                                    $sql = "SELECT 
                                            COUNT(*) as total,
                                            SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
                                            SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied,
                                            SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance
                                            FROM beds";
                                    $result = $conn->query($sql);
                                    $stats = $result->fetch_assoc();
                                    ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Beds:</span>
                                        <strong id="totalBeds"><?php echo $stats['total']; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Available:</span>
                                        <strong id="availableBeds"><?php echo $stats['available']; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Occupied:</span>
                                        <strong id="occupiedBeds"><?php echo $stats['occupied']; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Under Maintenance:</span>
                                        <strong id="maintenanceBeds"><?php echo $stats['maintenance']; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Tab -->
            <div class="tab-pane fade" id="services" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card service-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Hospital Services</h5>
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                    <i class="fas fa-plus me-1"></i> Add Service
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="list-group" id="servicesList">
                                    <?php
                                    $sql = "SELECT * FROM services ORDER BY name";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while($service = $result->fetch_assoc()) {
                                            $status_class = $service['status'] == 'Active' ? 'text-success' : 'text-danger';
                                            echo "<div class='list-group-item d-flex justify-content-between align-items-center' id='service-{$service['service_id']}'>
                                                    <div>
                                                        <h6 class='mb-1'>{$service['name']}</h6>
                                                        <small class='text-muted'>{$service['department']}</small>
                                                    </div>
                                                    <div>
                                                        <span class='badge {$status_class} me-2'>{$service['status']}</span>
                                                        <a href='edit_service.php?id={$service['service_id']}' class='btn btn-sm btn-outline-primary me-1'>
                                                            <i class='fas fa-edit'></i>
                                                        </a>
                                                        <a href='?delete=service&id={$service['service_id']}' class='btn btn-sm btn-outline-danger delete-service'>
                                                            <i class='fas fa-trash'></i>
                                                        </a>
                                                    </div>
                                                </div>";
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Service Requests</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Patient Name</th>
                                                <th>Service</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT sr.*, s.name as service_name, s.department 
                                                    FROM service_requests sr
                                                    JOIN services s ON sr.service_id = s.service_id
                                                    ORDER BY sr.service_date DESC";
                                            $result = $conn->query($sql);

                                            if ($result->num_rows > 0) {
                                                while($request = $result->fetch_assoc()) {
                                                    $status_class = $request['status'] === 'Pending' ? 'badge-pending' : 
                                                                  ($request['status'] === 'Completed' ? 'badge-completed' : 'badge-cancelled');
                                                    
                                                    echo "<tr>
                                                            <td>SR-{$request['patient_id']}</td>
                                                            <td>{$request['patient_name']}</td>
                                                            <td>{$request['service_name']}</td>
                                                            <td>{$request['department']}</td>
                                                            <td><span class='badge {$status_class}'>{$request['status']}</span></td>
                                                            <td>" . date('M d, Y H:i', strtotime($request['service_date'])) . "</td>
                                                            <td>";
                                                    
                                                    if ($request['status'] === 'Pending') {
                                                        echo "<form method='POST' action='' class='d-inline'>
                                                                <input type='hidden' name='request_id' value='{$request['patient_id']}'>
                                                                <input type='hidden' name='action' value='complete'>
                                                                <button type='submit' name='service_request_action' class='btn btn-sm btn-success me-1'>
                                                                    <i class='fas fa-check'></i> Complete
                                                                </button>
                                                              </form>";
                                                        
                                                        echo "<form method='POST' action='' class='d-inline'>
                                                                <input type='hidden' name='request_id' value='{$request['patient_id']}'>
                                                                <input type='hidden' name='action' value='cancel'>
                                                                <button type='submit' name='service_request_action' class='btn btn-sm btn-danger'>
                                                                    <i class='fas fa-times'></i> Cancel
                                                                </button>
                                                              </form>";
                                                    } 
                                                    
                                                    echo "</td>
                                                        </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center py-4'>No service requests found</td></tr>";
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
            
            <!-- Staff Management Tab -->
            <div class="tab-pane fade" id="staff" role="tabpanel">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Staff Members</h5>
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                    <i class="fas fa-plus me-1"></i> Add Staff
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row" id="staffMembersContainer">
                                    <?php
                                    $sql = "SELECT * FROM staff ORDER BY first_name, last_name";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while($staff = $result->fetch_assoc()) {
                                            $status_class = 'text-danger'; // default
                                            if ($staff['status'] == 'Active') {
                                                $status_class = 'text-success';
                                            } elseif ($staff['status'] == 'On Leave') {
                                                $status_class = 'text-warning';
                                            }
                                            
                                            echo "<div class='col-md-4 mb-3 staff-member' id='staff-{$staff['staff_id']}'>
                                                    <div class='card staff-card h-100'>
                                                        <div class='card-body'>
                                                            <div class='d-flex align-items-center mb-3'>";
                                            
                                            if (!empty($staff['photo']) && file_exists('uploads/' . $staff['photo'])) {
                                                echo "<img src='uploads/{$staff['photo']}' 
                                                     class='staff-photo me-3' 
                                                     alt='{$staff['first_name']} {$staff['last_name']}'>";
                                            } else {
                                                // Display a default avatar if no photo is available
                                                $gender = ($staff['staff_id'] % 2 == 0) ? 'women' : 'men';
                                                echo "<img src='https://randomuser.me/api/portraits/{$gender}/{$staff['staff_id']}.jpg' 
                                                     class='staff-photo me-3' 
                                                     alt='{$staff['first_name']} {$staff['last_name']}'>";
                                            }

                                            echo "          <div>
                                                                <h5 class='mb-0'>{$staff['first_name']} {$staff['last_name']}</h5>
                                                                <small class='text-muted'>{$staff['role']}</small>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='mb-2'>
                                                            <i class='fas fa-hospital me-2 text-muted'></i>
                                                            <span>{$staff['department']}</span>
                                                        </div>
                                                        
                                                        <div class='mb-3'>
                                                            <i class='fas fa-circle me-2 {$status_class}'></i>
                                                            <span>{$staff['status']}</span>
                                                        </div>
                                                        
                                                        <div class='d-flex justify-content-between'>
                                                            <a href='edit_staff.php?id={$staff['staff_id']}' 
                                                               class='btn btn-sm btn-outline-primary edit-staff'>
                                                                <i class='fas fa-edit'></i> Edit
                                                            </a>
                                                            <a href='?delete=staff&id={$staff['staff_id']}' 
                                                               class='btn btn-sm btn-outline-danger delete-staff'
                                                               onclick='return confirm(\"Are you sure you want to delete this staff member?\");'>
                                                                <i class='fas fa-trash'></i> Remove
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>";
                                        }
                                    } else {
                                        echo "<div class='col-12 text-center py-4'>
                                                <div class='alert alert-info'>No staff members found</div>
                                              </div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bed Modal -->
    <div class="modal fade" id="addBedModal" tabindex="-1" aria-labelledby="addBedModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBedModalLabel">Add New Bed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="bedNumber" class="form-label">Bed Number *</label>
                            <input type="text" class="form-control" id="bedNumber" name="bed_number" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ward" class="form-label">Ward/Unit *</label>
                            <select class="form-select" id="ward" name="ward" required>
                                <option value="">Select Ward</option>
                                <option value="General">General Ward</option>
                                <option value="ICU">ICU</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Maternity">Maternity</option>
                                <option value="Pediatric">Pediatric</option>
                                <option value="Surgical">Surgical</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bedType" class="form-label">Bed Type *</label>
                            <select class="form-select" id="bedType" name="bed_type" required>
                                <option value="">Select Type</option>
                                <option value="Regular">Regular</option>
                                <option value="ICU">ICU</option>
                                <option value="Ventilator">Ventilator</option>
                                <option value="Pediatric">Pediatric</option>
                                <option value="Bariatric">Bariatric</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bedStatus" class="form-label">Status *</label>
                            <select class="form-select" id="bedStatus" name="status" required>
                                <option value="Available">Available</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_bed">Add Bed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStaffModalLabel">Add New Staff Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staffFirstName" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="staffFirstName" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="staffLastName" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="staffLastName" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staffRole" class="form-label">Role *</label>
                                <select class="form-select" id="staffRole" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Nurse">Nurse</option>
                                    <option value="Technician">Technician</option>
                                    <option value="Administrator">Administrator</option>
                                    <option value="Receptionist">Receptionist</option>
                                    <option value="Pharmacist">Pharmacist</option>
                                    <option value="Therapist">Therapist</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="staffDepartment" class="form-label">Department *</label>
                                <select class="form-select" id="staffDepartment" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="Cardiology">Cardiology</option>
                                    <option value="Neurology">Neurology</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Surgery">Surgery</option>
                                    <option value="Pediatrics">Pediatrics</option>
                                    <option value="Radiology">Radiology</option>
                                    <option value="Laboratory">Laboratory</option>
                                    <option value="Pharmacy">Pharmacy</option>
                                    <option value="Physiotherapy">Physiotherapy</option>
                                    <option value="Administration">Administration</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staffEmail" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="staffEmail" name="email" required>
                            </div>
                               <div class="col-md-6 mb-3">
                                <label for="staffPassword" class="form-label">Password *</label>
                                <input type="password" class="form-control "  id="staffPassword" name="password"required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="staffPhone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="staffPhone" name="phone" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staffStatus" class="form-label">Status *</label>
                                <select class="form-select" id="staffStatus" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="On Leave">On Leave</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="staffPhoto" class="form-label">Photo</label>
                                <input type="file" class="form-control" id="staffPhoto" name="photo" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_staff">Add Staff</button>
                                        </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="serviceName" class="form-label">Service Name *</label>
                            <input type="text" class="form-control" id="serviceName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="serviceDepartment" class="form-label">Department *</label>
                            <select class="form-select" id="serviceDepartment" name="department" required>
                                <option value="">Select Department</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Surgery">Surgery</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Radiology">Radiology</option>
                                <option value="Laboratory">Laboratory</option>
                                <option value="Pharmacy">Pharmacy</option>
                                <option value="Physiotherapy">Physiotherapy</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="serviceDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="serviceDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="serviceCost" class="form-label">Cost *</label>
                            <input type="number" class="form-control" id="serviceCost" name="cost" required step="0.01">
                        </div>
                        
                        <div class="mb-3">
                            <label for="serviceStatus" class="form-label">Status *</label>
                            <select class="form-select" id="serviceStatus" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_service">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize bed status chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('bedStatusChart').getContext('2d');
            const available = parseInt(document.getElementById('availableBeds').textContent);
            const occupied = parseInt(document.getElementById('occupiedBeds').textContent);
            const maintenance = parseInt(document.getElementById('maintenanceBeds').textContent);
            
            const bedStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Occupied', 'Maintenance'],
                    datasets: [{
                        data: [available, occupied, maintenance],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
            
            // Confirm before deleting
            document.querySelectorAll('.delete-bed, .delete-service, .delete-staff').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>