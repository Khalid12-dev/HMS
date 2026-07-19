<?php
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'staff') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

require '../config.php';

if (isset($_GET['assignment_id'])) {
    $assignment_id = $_GET['assignment_id'];
    $staff_id = $_SESSION['user']['id'];
    
    // Get shift details
    $shift_query = "SELECT ss.*, s.name, s.start_time, s.end_time, 
                    a.full_name as assigned_by_name, a.email as assigned_by_email,
                    st.first_name, st.last_name, st.email as staff_email, st.phone as staff_phone
                    FROM staff_shifts ss
                    JOIN shifts s ON ss.shift_id = s.shift_id
                    JOIN admin a ON ss.assigned_by_admin = a.admin_id
                    JOIN staff st ON ss.staff_id = st.staff_id
                    WHERE ss.assignment_id = ? AND ss.staff_id = ?";
    $shift_stmt = $pdo->prepare($shift_query);
    $shift_stmt->execute([$assignment_id, $staff_id]);
    $shift = $shift_stmt->fetch();
    
    if ($shift) {
        ?>
        <div class="row">
            <div class="col-md-6">
                <h4><?php echo htmlspecialchars($shift['name']); ?></h4>
               
                
                <div class="mb-4">
                    <h5>Shift Timing</h5>
                    <p>
                        <i class="fas fa-calendar-day text-primary"></i> 
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($shift['shift_date'])); ?>
                    </p>
                    <p>
                        <i class="fas fa-clock text-primary"></i> 
                        <strong>Time:</strong> <?php echo date('h:i A', strtotime($shift['start_time'])); ?> - 
                        <?php echo date('h:i A', strtotime($shift['end_time'])); ?>
                    </p>
                    <p>
                        <i class="fas fa-info-circle text-primary"></i> 
                        <strong>Status:</strong> 
                        <span class="badge <?php echo $shift['status'] == 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo htmlspecialchars($shift['status']); ?>
                        </span>
                    </p>
                </div>
                
                <div class="mb-4">
                    <h5>Assignment Details</h5>
                    <p>
                        <i class="fas fa-user-shield text-primary"></i> 
                        <strong>Assigned By:</strong> <?php echo htmlspecialchars($shift['assigned_by_name']); ?>
                    </p>
                    <p>
                        <i class="fas fa-envelope text-primary"></i> 
                        <strong>Admin Email:</strong> <?php echo htmlspecialchars($shift['assigned_by_email']); ?>
                    </p>
                    <p>
                        <i class="fas fa-calendar-check text-primary"></i> 
                        <strong>Last Rotation:</strong> 
                        <?php echo $shift['last_rotation'] ? date('M j, Y h:i A', strtotime($shift['last_rotation'])) : 'Never'; ?>
                    </p>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Staff Information</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            <i class="fas fa-user text-primary"></i> 
                            <strong>Name:</strong> <?php echo htmlspecialchars($shift['first_name'].' '.$shift['last_name']); ?>
                        </p>
                        <p>
                            <i class="fas fa-envelope text-primary"></i> 
                            <strong>Email:</strong> <?php echo htmlspecialchars($shift['staff_email']); ?>
                        </p>
                        <p>
                            <i class="fas fa-phone text-primary"></i> 
                            <strong>Phone:</strong> <?php echo htmlspecialchars($shift['staff_phone']); ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($shift['status'] == 'Active' && $shift['shift_date'] == date('Y-m-d')): ?>
                    <div class="card mt-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Shift Actions</h5>
                        </div>
                        <div class="card-body text-center">
                            <button class="btn btn-success me-2">
                                <i class="fas fa-check-circle"></i> Start Shift
                            </button>
                            <button class="btn btn-warning">
                                <i class="fas fa-flag"></i> Report Issue
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Shift not found or you don\'t have permission to view it.</div>';
    }
} else {
    echo '<div class="alert alert-danger">Invalid request.</div>';
}
?>