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

// GET - Get properties with filters
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Get filter parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(p.title LIKE :search OR p.address LIKE :search OR p.city LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($status)) {
            $whereConditions[] = "p.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($room_type)) {
            $whereConditions[] = "p.room_type = :room_type";
            $params[':room_type'] = $room_type;
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        // Build ORDER BY clause
        $orderBy = '';
        switch ($sort) {
            case 'oldest':
                $orderBy = 'p.created_at ASC';
                break;
            case 'price_high':
                $orderBy = 'p.price DESC';
                break;
            case 'price_low':
                $orderBy = 'p.price ASC';
                break;
            default: // newest
                $orderBy = 'p.created_at DESC';
        }
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total 
                      FROM properties p 
                      JOIN users u ON p.owner_id = u.id 
                      $whereClause";
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get properties
        $query = "SELECT p.*, u.name as owner_name, u.email as owner_email 
                  FROM properties p 
                  JOIN users u ON p.owner_id = u.id 
                  $whereClause 
                  ORDER BY $orderBy 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "properties" => $properties,
            "total" => $total,
            "page" => $page,
            "totalPages" => ceil($total / $limit)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// POST - Delete property
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['action']) || !isset($data['property_id'])) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }
    
    $property_id = $data['property_id'];
    $action = $data['action'];
    
    try {
        if ($action == 'delete') {
            // Delete the property
            $query = "DELETE FROM properties WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $property_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Property deleted successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to delete property"]);
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