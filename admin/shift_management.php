<?php
session_start();
$db_host = 'localhost';
$db_name = 'hms';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to automatically rotate shifts after 24 hours
function rotateShifts($pdo) {
    // Get all active assignments
    $assignments = $pdo->query("SELECT * FROM staff_shifts WHERE status = 'Active'")->fetchAll();
    
    foreach ($assignments as $assignment) {
        $assignmentTime = strtotime($assignment['last_rotation'] ?? date('Y-m-d H:i:s'));
        $currentTime = time();
        $hoursPassed = ($currentTime - $assignmentTime) / 3600;
        
        if ($hoursPassed >= 24) {
            // Get current shift details
            $currentShift = $pdo->query("SELECT * FROM shifts WHERE shift_id = {$assignment['shift_id']}")->fetch();
            
            // Find the opposite shift (morning/night)
            $newShift = $pdo->query("
                SELECT * FROM shifts 
                WHERE department = '{$currentShift['department']}' 
                AND shift_id != {$currentShift['shift_id']}
                LIMIT 1
            ")->fetch();
            
            if ($newShift) {
                // Update to the opposite shift
                $stmt = $pdo->prepare("
                    UPDATE staff_shifts 
                    SET shift_id = ?, last_rotation = NOW() 
                    WHERE assignment_id = ?
                ");
                $stmt->execute([$newShift['shift_id'], $assignment['assignment_id']]);
            }
        }
    }
}

// Call the rotation function on every page load
rotateShifts($pdo);

// Handle all actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_shift':
                $stmt = $pdo->prepare("INSERT INTO shifts (name, start_time, end_time, department) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['start_time'], $_POST['end_time'], $_POST['department']]);
                echo json_encode(['success' => true]);
                exit;
                
            case 'assign_shift':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO staff_shifts (staff_id, shift_id, shift_date, last_rotation, status, assigned_by_admin) 
                        VALUES (?, ?, ?, NOW(), 'Active', ?)
                    ");
                    // Using session admin ID or default to 1 for testing
                    $admin_id = $_SESSION['admin_id'] ?? 1;
                    $stmt->execute([
                        $_POST['staff_id'], 
                        $_POST['shift_id'], 
                        $_POST['shift_date'],
                        $admin_id
                    ]);
                    echo json_encode(['success' => true]);
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                exit;
                
            case 'update_shift_assignment':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE staff_shifts 
                        SET staff_id = ?, shift_id = ?, shift_date = ?, last_rotation = NOW()
                        WHERE assignment_id = ?
                    ");
                    $stmt->execute([
                        $_POST['staff_id'], 
                        $_POST['shift_id'], 
                        $_POST['shift_date'],
                        $_POST['assignment_id']
                    ]);
                    echo json_encode(['success' => true]);
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                exit;
                
            case 'mark_attendance':
                $stmt = $pdo->prepare("INSERT INTO attendance (assignment_id, status) VALUES (?, ?)");
                $stmt->execute([$_POST['assignment_id'], $_POST['status']]);
                echo json_encode(['success' => true]);
                exit;
                
            case 'update_attendance':
                $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE assignment_id = ?");
                $stmt->execute([$_POST['status'], $_POST['assignment_id']]);
                echo json_encode(['success' => true]);
                exit;
                
            case 'end_assignment':
                $stmt = $pdo->prepare("UPDATE staff_shifts SET status = 'Completed' WHERE assignment_id = ?");
                $stmt->execute([$_POST['assignment_id']]);
                echo json_encode(['success' => true]);
                exit;
        }
    }
}

// Fetch data for UI
$shifts = $pdo->query("SELECT * FROM shifts")->fetchAll();
$staff = $pdo->query("SELECT * FROM staff WHERE status = 'Active'")->fetchAll();
$assignments = $pdo->query("
    SELECT ss.assignment_id, s.first_name, s.last_name, sh.name, sh.start_time, sh.end_time, 
           ss.shift_date, ss.last_rotation, ss.status
    FROM staff_shifts ss
    JOIN staff s ON ss.staff_id = s.staff_id
    JOIN shifts sh ON ss.shift_id = sh.shift_id
    ORDER BY ss.shift_date DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Shift Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .nav-tabs .nav-link.active { font-weight: bold; background-color: #f8f9fa; }
        .table-responsive { margin-top: 20px; }
        .status-present { background-color: #d4edda !important; }
        .status-absent { background-color: #f8d7da !important; }
        .status-active { background-color: #e7f5ff !important; }
        .status-completed { background-color: #f8f9fa !important; }
        .modal-backdrop { z-index: 1040 !important; }
        .modal { z-index: 1050 !important; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Hospital Staff Shift Management</h3>
                            <a href="admin_dashboard.php" class="btn btn-light">Back to Dashboard</a>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="shifts-tab" data-bs-toggle="tab" data-bs-target="#shifts" type="button">Shifts</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign" type="button">Assign Shifts</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button">Attendance</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content py-4">
                            <!-- Shift Management Tab -->
                            <div class="tab-pane fade show active" id="shifts" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5>Create New Shift</h5>
                                            </div>
                                            <div class="card-body">
                                                <form id="shiftForm">
                                                    <div class="mb-3">
                                                        <label class="form-label">Shift Name</label>
                                                        <input type="text" name="name" class="form-control" required>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col">
                                                            <label class="form-label">Start Time</label>
                                                            <input type="time" name="start_time" class="form-control" required>
                                                        </div>
                                                        <div class="col">
                                                            <label class="form-label">End Time</label>
                                                            <input type="time" name="end_time" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Department</label>
                                                        <select name="department" class="form-select" required>
                                                            <option value="Cleaning">Cleaning</option>
                                                            <option value="Security">Security</option>
                                                            <option value="Administration">Administration</option>
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
                                                    <button type="submit" class="btn btn-primary">Create Shift</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5>Existing Shifts</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>Timing</th>
                                                                <th>Department</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($shifts as $shift): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($shift['name']) ?></td>
                                                                <td><?= date('h:i A', strtotime($shift['start_time'])) ?> - <?= date('h:i A', strtotime($shift['end_time'])) ?></td>
                                                                <td><?= htmlspecialchars($shift['department']) ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Shift Assignment Tab -->
                            <div class="tab-pane fade" id="assign" role="tabpanel">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Assign Staff to Shifts</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="assignForm">
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Staff Member</label>
                                                    <select name="staff_id" class="form-select" required>
                                                        <?php foreach ($staff as $member): ?>
                                                        <option value="<?= $member['staff_id'] ?>"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Shift</label>
                                                    <select name="shift_id" class="form-select" required>
                                                        <?php foreach ($shifts as $shift): ?>
                                                        <option value="<?= $shift['shift_id'] ?>"><?= htmlspecialchars($shift['name']) ?> (<?= $shift['department'] ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Date</label>
                                                    <input type="date" name="shift_date" class="form-control" required>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Assign Shift</button>
                                        </form>
                                        
                                        <hr>
                                        
                                        <h5 class="mt-4">Current Assignments</h5>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Staff Name</th>
                                                        <th>Shift</th>
                                                        <th>Date</th>
                                                        <th>Timing</th>
                                                        <th>Last Rotation</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($assignments as $assignment): ?>
                                                    <tr class="status-<?= strtolower($assignment['status']) ?>">
                                                        <td><?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($assignment['name']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($assignment['shift_date'])) ?></td>
                                                        <td><?= date('h:i A', strtotime($assignment['start_time'])) ?> - <?= date('h:i A', strtotime($assignment['end_time'])) ?></td>
                                                        <td><?= $assignment['last_rotation'] ? date('M d, Y H:i', strtotime($assignment['last_rotation'])) : 'Never' ?></td>
                                                        <td><?= $assignment['status'] ?></td>
                                                        <td>
                                                            <?php if ($assignment['status'] === 'Active'): ?>
                                                            <button class="btn btn-sm btn-info edit-assignment-btn" data-id="<?= $assignment['assignment_id'] ?>">Edit</button>
                                                            <button class="btn btn-sm btn-warning end-btn" data-id="<?= $assignment['assignment_id'] ?>">End</button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Attendance Tab -->
                            <div class="tab-pane fade" id="attendance" role="tabpanel">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Mark Attendance</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Staff Name</th>
                                                        <th>Shift</th>
                                                        <th>Date</th>
                                                        <th>Timing</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($assignments as $assignment): 
                                                        $attendance = $pdo->query("SELECT status FROM attendance WHERE assignment_id = {$assignment['assignment_id']}")->fetch();
                                                    ?>
                                                    <tr class="<?= $attendance ? 'status-' . strtolower($attendance['status']) : '' ?>">
                                                        <td><?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($assignment['name']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($assignment['shift_date'])) ?></td>
                                                        <td><?= date('h:i A', strtotime($assignment['start_time'])) ?> - <?= date('h:i A', strtotime($assignment['end_time'])) ?></td>
                                                        <td><?= $attendance ? $attendance['status'] : 'Pending' ?></td>
                                                        <td>
                                                            <?php if ($assignment['status'] === 'Active'): ?>
                                                                <?php if ($attendance): ?>
                                                                    <button class="btn btn-sm btn-info edit-attendance-btn" 
                                                                            data-id="<?= $assignment['assignment_id'] ?>" 
                                                                            data-status="<?= $attendance['status'] ?>">Edit</button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-success mark-btn" data-id="<?= $assignment['assignment_id'] ?>" data-status="Present">Present</button>
                                                                    <button class="btn btn-sm btn-danger mark-btn" data-id="<?= $assignment['assignment_id'] ?>" data-status="Absent">Absent</button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div class="modal fade" id="editAssignmentModal" tabindex="-1" aria-labelledby="editAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAssignmentModalLabel">Edit Shift Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAssignmentForm">
                        <input type="hidden" name="assignment_id" id="edit_assignment_id">
                        <div class="mb-3">
                            <label class="form-label">Staff Member</label>
                            <select name="staff_id" id="edit_staff_id" class="form-select" required>
                                <?php foreach ($staff as $member): ?>
                                <option value="<?= $member['staff_id'] ?>"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shift</label>
                            <select name="shift_id" id="edit_shift_id" class="form-select" required>
                                <?php foreach ($shifts as $shift): ?>
                                <option value="<?= $shift['shift_id'] ?>"><?= htmlspecialchars($shift['name']) ?> (<?= $shift['department'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="shift_date" id="edit_shift_date" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveAssignmentChanges">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Attendance Modal -->
    <div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-labelledby="editAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAttendanceModalLabel">Edit Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAttendanceForm">
                        <input type="hidden" name="assignment_id" id="edit_attendance_assignment_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_attendance_status" class="form-select" required>
                                <option value="Present">Present</option>
                                <option value="Absent">Absent</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveAttendanceChanges">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Shift Creation
        document.getElementById('shiftForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_shift');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Shift created successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to create shift'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
        
        // Shift Assignment
        document.getElementById('assignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'assign_shift');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Shift assigned successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to assign shift'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
        
        // Edit Assignment Button Click
        document.querySelectorAll('.edit-assignment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const assignmentId = this.dataset.id;
                const row = this.closest('tr');
                
                // Get current values from the row
                const staffName = row.cells[0].textContent;
                const shiftName = row.cells[1].textContent;
                const shiftDate = row.cells[2].textContent;
                
                // Find staff ID
                let staffId = null;
                const staffSelect = document.getElementById('edit_staff_id');
                for (let i = 0; i < staffSelect.options.length; i++) {
                    if (staffSelect.options[i].text === staffName) {
                        staffId = staffSelect.options[i].value;
                        break;
                    }
                }
                
                // Find shift ID
                let shiftId = null;
                const shiftSelect = document.getElementById('edit_shift_id');
                for (let i = 0; i < shiftSelect.options.length; i++) {
                    if (shiftSelect.options[i].text.includes(shiftName.split(' (')[0])) {
                        shiftId = shiftSelect.options[i].value;
                        break;
                    }
                }
                
                // Set form values
                document.getElementById('edit_assignment_id').value = assignmentId;
                document.getElementById('edit_staff_id').value = staffId;
                document.getElementById('edit_shift_id').value = shiftId;
                
                // Convert date format from "MMM d, YYYY" to "YYYY-MM-DD"
                const dateParts = shiftDate.split(' ');
                const month = dateParts[0];
                const day = dateParts[1].replace(',', '');
                const year = dateParts[2];
                const monthMap = {
                    'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06',
                    'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
                };
                const formattedDate = `${year}-${monthMap[month]}-${day.padStart(2, '0')}`;
                document.getElementById('edit_shift_date').value = formattedDate;
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
                modal.show();
            });
        });
        
        // Save Assignment Changes
        document.getElementById('saveAssignmentChanges').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('editAssignmentForm'));
            formData.append('action', 'update_shift_assignment');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Assignment updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to update assignment'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
        
        // Edit Attendance Button Click
        document.querySelectorAll('.edit-attendance-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const assignmentId = this.dataset.id;
                const currentStatus = this.dataset.status;
                
                // Set form values
                document.getElementById('edit_attendance_assignment_id').value = assignmentId;
                document.getElementById('edit_attendance_status').value = currentStatus;
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editAttendanceModal'));
                modal.show();
            });
        });
        
        // Save Attendance Changes
        document.getElementById('saveAttendanceChanges').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('editAttendanceForm'));
            formData.append('action', 'update_attendance');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to update attendance'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
        
        // Mark Attendance
        document.querySelectorAll('.mark-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const formData = new FormData();
                formData.append('action', 'mark_attendance');
                formData.append('assignment_id', this.dataset.id);
                formData.append('status', this.dataset.status);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Attendance marked!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to mark attendance'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });
        });
        
        // End Assignment
        document.querySelectorAll('.end-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to end this assignment?')) {
                    const formData = new FormData();
                    formData.append('action', 'end_assignment');
                    formData.append('assignment_id', this.dataset.id);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Assignment ended!');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.error || 'Failed to end assignment'));
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
                }
            });
        });
    </script>
</body>
</html>