<?php
header("Content-Type: application/json");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_beds':
        getBeds($conn);
        break;
    case 'add_bed':
        addBed($conn);
        break;
    case 'update_bed':
        updateBed($conn);
        break;
    case 'delete_bed':
        deleteBed($conn);
        break;
    case 'get_services':
        getServices($conn);
        break;
    case 'add_service':
        addService($conn);
        break;
    case 'update_service':
        updateService($conn);
        break;
    case 'delete_service':
        deleteService($conn);
        break;
    case 'get_staff':
        getStaff($conn);
        break;
    case 'add_staff':
        addStaff($conn);
        break;
    case 'update_staff':
        updateStaff($conn);
        break;
    case 'delete_staff':
        deleteStaff($conn);
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

function getBeds($conn) {
    $sql = "SELECT b.*, p.first_name, p.last_name 
            FROM beds b
            LEFT JOIN patient p ON b.patient_id = p.patient_id
            ORDER BY b.ward, b.bed_number";
    $result = $conn->query($sql);
    
    $beds = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $beds[] = $row;
        }
    }
    
    echo json_encode(["status" => "success", "data" => $beds]);
}

function addBed($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $bed_number = $data['bed_number'];
    $ward = $data['ward'];
    $bed_type = $data['bed_type'];
    $status = $data['status'];
    
    $stmt = $conn->prepare("INSERT INTO beds (bed_number, ward, bed_type, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $bed_number, $ward, $bed_type, $status);
    
    if ($stmt->execute()) {
        $bed_id = $stmt->insert_id;
        echo json_encode(["status" => "success", "message" => "Bed added successfully", "bed_id" => $bed_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding bed"]);
    }
}

function updateBed($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $bed_id = $data['bed_id'];
    $bed_number = $data['bed_number'];
    $ward = $data['ward'];
    $bed_type = $data['bed_type'];
    $status = $data['status'];
    $patient_id = $data['patient_id'] ?? null;
    
    $stmt = $conn->prepare("UPDATE beds SET bed_number=?, ward=?, bed_type=?, status=?, patient_id=? WHERE bed_id=?");
    $stmt->bind_param("ssssii", $bed_number, $ward, $bed_type, $status, $patient_id, $bed_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Bed updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating bed"]);
    }
}

function deleteBed($conn) {
    $bed_id = $_GET['bed_id'];
    
    $stmt = $conn->prepare("DELETE FROM beds WHERE bed_id=?");
    $stmt->bind_param("i", $bed_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Bed deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error deleting bed"]);
    }
}

function getServices($conn) {
    $sql = "SELECT * FROM services ORDER BY name";
    $result = $conn->query($sql);
    
    $services = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    
    echo json_encode(["status" => "success", "data" => $services]);
}

function addService($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'];
    $department = $data['department'];
    $description = $data['description'];
    $cost = $data['cost'];
    $status = $data['status'];
    
    $stmt = $conn->prepare("INSERT INTO services (name, department, description, cost, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssds", $name, $department, $description, $cost, $status);
    
    if ($stmt->execute()) {
        $service_id = $stmt->insert_id;
        echo json_encode(["status" => "success", "message" => "Service added successfully", "service_id" => $service_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding service"]);
    }
}

function updateService($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'];
    $name = $data['name'];
    $department = $data['department'];
    $description = $data['description'];
    $cost = $data['cost'];
    $status = $data['status'];
    
    $stmt = $conn->prepare("UPDATE services SET name=?, department=?, description=?, cost=?, status=? WHERE id=?");
    $stmt->bind_param("sssdsi", $name, $department, $description, $cost, $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Service updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating service"]);
    }
}

function deleteService($conn) {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Service deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error deleting service"]);
    }
}

function getStaff($conn) {
    $sql = "SELECT * FROM staff ORDER BY first_name, last_name";
    $result = $conn->query($sql);
    
    $staff = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
    }
    
    echo json_encode(["status" => "success", "data" => $staff]);
}

function addStaff($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $role = $data['role'];
    $department = $data['department'];
    $email = $data['email'];
    $phone = $data['phone'];
    $status = $data['status'];
    
    $stmt = $conn->prepare("INSERT INTO staff (first_name, last_name, role, department, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $first_name, $last_name, $role, $department, $email, $phone, $status);
    
    if ($stmt->execute()) {
        $staff_id = $stmt->insert_id;
        echo json_encode(["status" => "success", "message" => "Staff added successfully", "staff_id" => $staff_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding staff"]);
    }
}

function updateStaff($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $role = $data['role'];
    $department = $data['department'];
    $email = $data['email'];
    $phone = $data['phone'];
    $status = $data['status'];
    
    $stmt = $conn->prepare("UPDATE staff SET first_name=?, last_name=?, role=?, department=?, email=?, phone=?, status=? WHERE id=?");
    $stmt->bind_param("sssssssi", $first_name, $last_name, $role, $department, $email, $phone, $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Staff updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating staff"]);
    }
}

function deleteStaff($conn) {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM staff WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Staff deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error deleting staff"]);
    }
}

$conn->close();
?>