<?php
session_start();
require '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit();
}

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;

if ($doctor_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.rating, r.comment, r.created_at, 
                   CONCAT(p.first_name, ' ', p.last_name) as patient_name 
            FROM ratings r
            JOIN patient p ON r.patient_id = p.patient_id
            WHERE r.doctor_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$doctor_id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($reviews);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}