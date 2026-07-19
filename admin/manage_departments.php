<?php
$conn = new mysqli("localhost", "root", "", "hms"); // update db name if needed
if ($conn->connect_error) die("Connection Failed: " . $conn->connect_error);

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM departments WHERE id = $id");
    echo "<script>alert('Department deleted successfully.'); window.location='manage_departments.php';</script>";
}

// Fetch all departments
$result = $conn->query("SELECT * FROM departments ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Departments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-bg: #f8f9fa;
        }
        
        body { 
            background: #f5f7fb; 
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        
        .page-header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 20px;
            overflow-x: auto;
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
            border-top: none;
            font-weight: 500;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            font-size: 0.8rem;
            min-width: 80px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .action-btns .btn {
            margin-right: 5px;
            min-width: 70px;
        }
        
        .empty-state {
            padding: 40px 0;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 2.5rem;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #6c757d;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-dark">
                <i class="bi bi-building me-2"></i>Manage Departments
            </h4>
            <div>
                <a href="admin_dashboard.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <a href="add_department.html" class="btn btn-sm btn-success ms-2">
                    <i class="bi bi-plus me-1"></i> Add Department
                </a>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Head</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): 
                    $i = 1;
                    while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td><?= htmlspecialchars($row['department_code']) ?></td>
                        <td><?= htmlspecialchars($row['department_head']) ?></td>
                        <td><?= htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : '') ?></td>
                        <td>
                            <span class="badge 
                                <?= $row['status'] == 'active' ? 'bg-success' : ($row['status'] == 'inactive' ? 'bg-secondary' : 'bg-warning') ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <a href="update_department.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="manage_departments.php?delete=<?= $row['id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this department?')" 
                               class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-building"></i>
                                <p>No departments found. Add your first department!</p>
                                <a href="add_department.php" class="btn btn-primary">
                                    <i class="bi bi-plus"></i> Add Department
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>