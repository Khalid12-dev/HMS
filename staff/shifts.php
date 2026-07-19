<?php
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

require '../config.php';

$staff_id = $_SESSION['user']['id'];

// Get all shifts assigned to this staff member
$shifts_query = "SELECT ss.*, s.name, s.start_time, s.end_time, 
                a.full_name as assigned_by_name
                FROM staff_shifts ss
                JOIN shifts s ON ss.shift_id = s.shift_id
                JOIN admin a ON ss.assigned_by_admin = a.admin_id
                WHERE ss.staff_id = ?
                ORDER BY ss.shift_date DESC";
$shifts_stmt = $pdo->prepare($shifts_query);
$shifts_stmt->execute([$staff_id]);
$shifts = $shifts_stmt->fetchAll();

// Get today's date for highlighting current shift
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shifts - MediCare Pro HMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include the same CSS as dashboard -->
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #0d9c6e;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        .current-shift {
            border-left: 4px solid var(--primary-color);
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        .shift-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .badge-completed {
            background-color: #6c757d;
        }
        
        .badge-active {
            background-color: var(--primary-color);
        }
        
        .dashboard-btn {
            position: fixed;
            top: 20px;
            left: 1000px;
            z-index: 1000;
            
        }
    </style>
</head>
<body>
    
    <!-- Dashboard Button -->
    <a href="staff_dashboard.php" class="btn btn-primary dashboard-btn">
        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
    </a>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Shifts</h2>
                <button class="btn btn-primary d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Shift Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="shiftFilterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="all">All Shifts</option>
                                    <option value="active">Active Shifts</option>
                                    <option value="completed">Completed Shifts</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="dateFrom" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                            <div class="col-md-3">
                                <label for="dateTo" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="applyFilters">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Shifts List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Shift Assignments</h5>
                </div>
                <div class="card-body">
                    <?php if (count($shifts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Shift</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Assigned By</th>
                                        <th>Last Rotation</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shifts as $shift): ?>
                                        <tr class="shift-row <?php echo $shift['shift_date'] == $today ? 'current-shift' : ''; ?>" 
                                            data-status="<?php echo strtolower($shift['status']); ?>"
                                            data-date="<?php echo $shift['shift_date']; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($shift['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($shift['shift_date'])); ?>
                                                <?php if ($shift['shift_date'] == $today): ?>
                                                    <span class="badge bg-info">Today</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('h:i A', strtotime($shift['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($shift['end_time'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $shift['status'] == 'Active' ? 'badge-active' : 'badge-completed'; ?>">
                                                    <?php echo htmlspecialchars($shift['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($shift['assigned_by_name']); ?></td>
                                            <td>
                                                <?php echo $shift['last_rotation'] ? date('M j, Y h:i A', strtotime($shift['last_rotation'])) : 'Never'; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary view-shift" 
                                                        data-shift-id="<?php echo $shift['assignment_id']; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                            <h4>No Shifts Assigned Yet</h4>
                            <p class="text-muted">You don't have any shift assignments yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Shift Details Modal -->
            <div class="modal fade" id="shiftDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Shift Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="shiftDetailsContent">
                            <!-- Content will be loaded via AJAX -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Filter shifts
        $('#applyFilters').click(function() {
            const status = $('#statusFilter').val();
            const dateFrom = $('#dateFrom').val();
            const dateTo = $('#dateTo').val();
            
            $('.shift-row').each(function() {
                const rowStatus = $(this).data('status');
                const rowDate = $(this).data('date');
                let showRow = true;
                
                // Apply status filter
                if (status !== 'all' && rowStatus !== status) {
                    showRow = false;
                }
                
                // Apply date range filter
                if (dateFrom && rowDate < dateFrom) {
                    showRow = false;
                }
                
                if (dateTo && rowDate > dateTo) {
                    showRow = false;
                }
                
                $(this).toggle(showRow);
            });
        });

        // View shift details
        $('.view-shift').click(function() {
            const assignmentId = $(this).data('shift-id');
            
            // Load shift details via AJAX
            $.get('get_shift_details.php', { assignment_id: assignmentId }, function(data) {
                $('#shiftDetailsContent').html(data);
                $('#shiftDetailsModal').modal('show');
            });
        });
    </script>
</body>
</html>