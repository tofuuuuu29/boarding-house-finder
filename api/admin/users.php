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

// GET - Get users with filters
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Get filter parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $user_type = isset($_GET['user_type']) ? $_GET['user_type'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($user_type)) {
            $whereConditions[] = "u.user_type = :user_type";
            $params[':user_type'] = $user_type;
        }
        
        if (!empty($status)) {
            if ($status === 'active') {
                $whereConditions[] = "u.is_approved = 1";
            } elseif ($status === 'pending') {
                $whereConditions[] = "u.is_approved = 0";
            } elseif ($status === 'suspended') {
                $whereConditions[] = "u.is_approved = 0 AND u.user_type = 'landlord'";
            }
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        // Build ORDER BY clause
        $orderBy = '';
        switch ($sort) {
            case 'oldest':
                $orderBy = 'u.created_at ASC';
                break;
            case 'name':
                $orderBy = 'u.name ASC';
                break;
            default: // newest
                $orderBy = 'u.created_at DESC';
        }
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM users u $whereClause";
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get users with property counts
        $query = "SELECT u.*, 
                         COUNT(p.id) as property_count
                  FROM users u 
                  LEFT JOIN properties p ON u.id = p.owner_id 
                  $whereClause 
                  GROUP BY u.id 
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
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stats = [
            'total_users' => $total,
            'total_landlords' => getCount($db, "WHERE user_type = 'landlord' AND is_approved = 1"),
            'total_boarders' => getCount($db, "WHERE user_type = 'boarder'"),
            'pending_landlords' => getCount($db, "WHERE user_type = 'landlord' AND is_approved = 0")
        ];
        
        echo json_encode([
            "success" => true,
            "users" => $users,
            "total" => $total,
            "stats" => $stats,
            "page" => $page,
            "totalPages" => ceil($total / $limit)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// POST - User actions
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
            // Approve landlord
            $query = "UPDATE users SET is_approved = 1, approved_at = NOW() WHERE id = :id AND user_type = 'landlord'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Landlord approved successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to approve landlord"]);
            }
            
        } elseif ($action == 'suspend') {
            // Suspend user (set as not approved)
            $query = "UPDATE users SET is_approved = 0 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "User suspended successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to suspend user"]);
            }
            
        } elseif ($action == 'activate') {
            // Activate user
            $query = "UPDATE users SET is_approved = 1 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "User activated successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to activate user"]);
            }
            
        } elseif ($action == 'reset_password') {
            // Reset user password
            if (!isset($data['new_password'])) {
                echo json_encode(["success" => false, "message" => "New password required"]);
                exit;
            }
            
            $hashed_password = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Password reset successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to reset password"]);
            }
            
        } elseif ($action == 'delete') {
            // Delete user (only if they have no properties)
            $query = "DELETE FROM users WHERE id = :id AND (SELECT COUNT(*) FROM properties WHERE owner_id = :id) = 0";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    echo json_encode(["success" => true, "message" => "User deleted successfully"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Cannot delete user with existing properties"]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Failed to delete user"]);
            }
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// Helper function to get counts
function getCount($db, $where) {
    $query = "SELECT COUNT(*) as total FROM users $where";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
?>