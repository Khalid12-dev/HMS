<?php
$conn = new mysqli("localhost", "root", "", "hms");
if ($conn->connect_error) die("Connection Failed: " . $conn->connect_error);

// Validate and fetch department by ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid department ID.");
}
$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM departments WHERE id = $id");

if ($result->num_rows === 0) {
    die("Department not found.");
}

$department = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $department_name = $conn->real_escape_string($_POST['department_name']);
    $department_code = $conn->real_escape_string($_POST['department_code']);
    $department_head = $conn->real_escape_string($_POST['department_head']);
    $description = $conn->real_escape_string($_POST['description']);
    $status = $conn->real_escape_string($_POST['status']);

    $sql = "UPDATE departments SET 
                department_name='$department_name',
                department_code='$department_code',
                department_head='$department_head',
                description='$description',
                status='$status'
            WHERE id=$id";

    if ($conn->query($sql)) {
        echo "<script>alert('Department updated successfully.'); window.location='manage_departments.php';</script>";
        exit;
    } else {
        echo "<script>alert('Update failed: " . $conn->error . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
        }
        
        body {
            background: #f5f7fa;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
            position: relative;
            padding-bottom: 15px;
        }
        
        .form-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25%;
            width: 50%;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--success-color));
            border-radius: 3px;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select, .form-textarea {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-submit {
            background-color: var(--success-color);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .action-buttons .btn {
            flex: 1;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-inactive {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        .status-construction {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h3 class="form-header">
            <i class="bi bi-building-gear me-2"></i>Update Department Details
        </h3>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label for="department_name" class="form-label">Department Name</label>
                    <input type="text" name="department_name" class="form-control" id="department_name" 
                           value="<?= htmlspecialchars($department['department_name']) ?>" required>
                </div>

                <div class="col-md-6 mb-4">
                    <label for="department_code" class="form-label">Department Code</label>
                    <input type="text" name="department_code" class="form-control" id="department_code" 
                           value="<?= htmlspecialchars($department['department_code']) ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="department_head" class="form-label">Department Head</label>
                <input type="text" name="department_head" class="form-control" id="department_head" 
                       value="<?= htmlspecialchars($department['department_head']) ?>">
            </div>

            <div class="mb-4">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control form-textarea"><?= htmlspecialchars($department['description']) ?></textarea>
            </div>

            <div class="mb-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="active" <?= $department['status'] === 'active' ? 'selected' : '' ?>>
                        Active
                    </option>
                    <option value="inactive" <?= $department['status'] === 'inactive' ? 'selected' : '' ?>>
                        Inactive
                    </option>
                    <option value="under-construction" <?= $department['status'] === 'under-construction' ? 'selected' : '' ?>>
                        Under Construction
                    </option>
                </select>
                <div class="mt-2">
                    Current Status: 
                    <span class="status-badge 
                        <?= $department['status'] === 'active' ? 'status-active' : 
                           ($department['status'] === 'inactive' ? 'status-inactive' : 'status-construction') ?>">
                        <?= ucfirst(str_replace('-', ' ', $department['status'])) ?>
                    </span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="manage_departments.php" class="btn btn-cancel">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit">
                    <i class="bi bi-check-circle"></i> Update Department
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>