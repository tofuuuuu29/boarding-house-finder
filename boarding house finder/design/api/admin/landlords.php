<?php
session_start();
header("Content-Type: application/json");
include_once __DIR__ . '/../../config/database.php';

// Check admin authentication
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// GET - Get pending landlords
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $query = "SELECT id, name, email, phone, created_at 
                  FROM users 
                  WHERE user_type = 'landlord' AND is_approved = FALSE 
                  ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $landlords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "landlords" => $landlords]);
        
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// POST - Approve/Reject landlord
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['action']) || !isset($data['user_id'])) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }
    
    $user_id = $data['user_id'];
    $action = $data['action'];
    
    try {
        if ($action == 'approve') {
            // Approve the landlord
            $query = "UPDATE users SET is_approved = TRUE, approved_at = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Landlord approved successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to approve landlord"]);
            }
            
        } elseif ($action == 'reject') {
            // Reject and delete the landlord
            $query = "DELETE FROM users WHERE id = :id AND user_type = 'landlord' AND is_approved = FALSE";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Landlord rejected and removed"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to reject landlord"]);
            }
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
?>