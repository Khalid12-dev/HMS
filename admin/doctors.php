<?php
$conn = new mysqli("localhost", "root", "", "hms");
if ($conn->connect_error) die("Connection Failed: " . $conn->connect_error);

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM doctors WHERE id = $id");
    echo "<script>alert('Doctor deleted successfully.'); window.location='manage_doctors.php';</script>";
}

// Fetch doctors
$result = $conn->query("SELECT * FROM doctors");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Doctors - HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-hover: #2e59d9;
            --secondary-color: #f8f9fc;
            --accent-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --text-color: #5a5c69;
            --border-radius: 0.35rem;
            --box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-color);
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 15px;
        }
        
        .admin-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background-color: white;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 1rem 1.5rem;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            color: var(--text-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom-width: 1px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: all 0.15s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .table td, .table th {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .profile-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #e3e6f0;
        }
        
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: var(--border-radius);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: #000;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .action-buttons .btn {
            margin-right: 5px;
        }
        
        .action-buttons .btn:last-child {
            margin-right: 0;
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #858796;
        }
        
        .page-title {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                margin: 1rem auto;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .table-responsive {
                border: 0;
            }
            
            .table thead {
                display: none;
            }
            
            .table tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #e3e6f0;
                border-radius: var(--border-radius);
            }
            
            .table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
                padding: 0.75rem;
                border-bottom: 1px solid #e3e6f0;
            }
            
            .table td::before {
                content: attr(data-label);
                font-weight: 600;
                text-transform: uppercase;
                margin-right: auto;
                padding-right: 1rem;
                color: var(--text-color);
            }
            
            .action-buttons {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="admin-card">
        <div class="card-header">
            <h4 class="page-title mb-0"><i class="fas fa-user-md me-2"></i>Manage Doctors</h4>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="btn btn-sm btn-light">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a href="add_doctor.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Doctor
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): 
                            $i = 1;
                            while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td data-label="#"><?= $i++ ?></td>
                                <td data-label="Profile">
                                    <img src="uploads/<?= htmlspecialchars($row['profile_pic']) ?>" class="profile-img" alt="Profile">
                                </td>
                                <td data-label="Name"><?= htmlspecialchars($row['name']) ?></td>
                                <td data-label="Specialization"><?= htmlspecialchars($row['specialization']) ?></td>
                                <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                                <td data-label="Phone"><?= htmlspecialchars($row['phone']) ?></td>
                                <td data-label="Status">
                                    <span class="badge rounded-pill <?= $row['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td data-label="Actions" class="action-buttons">
                                    <a href="update_doctor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="manage_doctors.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this doctor?')" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="8" class="no-data">
                                    <i class="fas fa-user-md fa-2x mb-3" style="color: #dddfeb;"></i>
                                    <h5>No Doctors Found</h5>
                                    <p class="mb-0">Add your first doctor using the "Add Doctor" button</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>