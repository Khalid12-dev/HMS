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

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM patient WHERE patient_id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success_msg = "Patient deleted successfully!";
    } else {
        $error_msg = "Error deleting patient: " . $stmt->error;
    }
    $stmt->close();
}

// Handle filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';
if ($filter === 'male') {
    $where_clause = " WHERE gender = 'Male'";
} elseif ($filter === 'female') {
    $where_clause = " WHERE gender = 'Female'";
} elseif ($filter === 'other') {
    $where_clause = " WHERE gender = 'Other'";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Pro HMS - Manage Patients</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #1a76d1;
            --secondary-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
        }
        
        .table tbody tr:hover {
            background-color: rgba(26, 118, 209, 0.05);
        }
        
        .action-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 3px;
        }
        
        .edit-btn {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .edit-btn:hover {
            background-color: rgba(40, 167, 69, 0.2);
        }
        
        .delete-btn {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.2);
        }
        
        .search-box {
            position: relative;
            max-width: 300px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 30px;
        }
        
        .gender-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .gender-male {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        
        .gender-female {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .gender-other {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        .filter-active {
            background-color: #e9ecef;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="mb-0">Manage Patients</h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="admin_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search patients...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-2"></i> Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item <?php echo $filter === 'all' ? 'filter-active' : ''; ?>" href="?filter=all">All Patients</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'male' ? 'filter-active' : ''; ?>" href="?filter=male">Male</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'female' ? 'filter-active' : ''; ?>" href="?filter=female">Female</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'other' ? 'filter-active' : ''; ?>" href="?filter=other">Other</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="patientsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>DOB</th>
                                <th>Gender</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch patients from database with filter
                            $sql = "SELECT * FROM patient" . $where_clause . " ORDER BY created_at DESC";
                            $result = $conn->query($sql);
                            $total_patients = $result->num_rows;
                            
                            if ($total_patients > 0) {
                                while($row = $result->fetch_assoc()) {
                                    // Calculate age from DOB
                                    $dob = new DateTime($row['dob']);
                                    $today = new DateTime();
                                    $age = $today->diff($dob)->y;
                                    
                                    echo "<tr>
                                            <td>PT-" . $row['patient_id'] . "</td>
                                            <td>" . $row['first_name'] . " " . $row['last_name'] . "</td>
                                            <td>" . $row['email'] . "</td>
                                            <td>" . $row['phone'] . "</td>
                                            <td>" . date('d M Y', strtotime($row['dob'])) . " (" . $age . " yrs)</td>
                                            <td><span class='gender-badge gender-" . strtolower($row['gender']) . "'>" . ucfirst($row['gender']) . "</span></td>
                                            <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                            <td>
                                                <a href='edit_patient.php?id=" . $row['patient_id'] . "' class='action-btn edit-btn' title='Edit'>
                                                    <i class='fas fa-edit'></i>
                                                </a>
                                                <a href='manage_patients.php?delete_id=" . $row['patient_id'] . "' class='action-btn delete-btn' title='Delete' onclick='return confirm(\"Are you sure you want to delete this patient?\")'>
                                                    <i class='fas fa-trash'></i>
                                                </a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center py-4'>No patients found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p class="text-muted">Showing <span id="showingCount"><?php echo $total_patients; ?></span> of <?php echo $total_patients; ?> entries</p>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Page navigation" class="float-end">
                            <ul class="pagination">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Live search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const input = this.value.toLowerCase();
            const rows = document.querySelectorAll('#patientsTable tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(input)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.getElementById('showingCount').textContent = visibleCount;
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>