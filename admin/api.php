<?php
header("Content-Type: application/json");
require '../config.php'; // Use the same config as before

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getDoctors':
        $stmt = $pdo->query("SELECT * FROM doctors WHERE status='active'");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'getDoctorPerformance':
        $doctorId = $_GET['doctorId'] ?? 0;
        
        // Get basic doctor info
        $doctor = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
        $doctor->execute([$doctorId]);
        $doctorData = $doctor->fetch();
        
        // Get appointment stats
        $appointments = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(status = 'confirmed') as confirmed,
                SUM(status = 'cancelled') as cancelled,
                SUM(status = 'pending') as pending
            FROM appointments 
            WHERE doctor_id = ?
        ");
        $appointments->execute([$doctorId]);
        $appointmentStats = $appointments->fetch();
        
        // Get rating stats
        $ratings = $pdo->prepare("
            SELECT 
                AVG(rating) as avg_rating,
                COUNT(*) as total_ratings
            FROM ratings 
            WHERE doctor_id = ?
        ");
        $ratings->execute([$doctorId]);
        $ratingStats = $ratings->fetch();
        
        // Combine all data
        $performanceData = [
            'doctor' => $doctorData,
            'appointments' => $appointmentStats,
            'ratings' => $ratingStats
        ];
        
        echo json_encode($performanceData);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>