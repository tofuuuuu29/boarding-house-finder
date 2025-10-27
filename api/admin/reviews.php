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

// GET - Get reviews with filters
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Get filter parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $property_id = isset($_GET['property_id']) ? $_GET['property_id'] : '';
        $rating = isset($_GET['rating']) ? $_GET['rating'] : '';
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE :search OR p.title LIKE :search OR r.comment LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($property_id)) {
            $whereConditions[] = "r.property_id = :property_id";
            $params[':property_id'] = $property_id;
        }
        
        if (!empty($rating)) {
            $whereConditions[] = "r.rating = :rating";
            $params[':rating'] = $rating;
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        // Build ORDER BY clause
        $orderBy = '';
        switch ($sort) {
            case 'oldest':
                $orderBy = 'r.created_at ASC';
                break;
            case 'highest_rating':
                $orderBy = 'r.rating DESC, r.created_at DESC';
                break;
            case 'lowest_rating':
                $orderBy = 'r.rating ASC, r.created_at DESC';
                break;
            default: // newest
                $orderBy = 'r.created_at DESC';
        }
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total 
                      FROM reviews r
                      JOIN users u ON r.user_id = u.id
                      JOIN properties p ON r.property_id = p.id
                      $whereClause";
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get reviews
        $query = "SELECT r.*, 
                         u.name as user_name,
                         p.title as property_title,
                         p.id as property_id
                  FROM reviews r
                  JOIN users u ON r.user_id = u.id
                  JOIN properties p ON r.property_id = p.id
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
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get properties for filter dropdown
        $propertiesQuery = "SELECT id, title FROM properties ORDER BY title";
        $propertiesStmt = $db->prepare($propertiesQuery);
        $propertiesStmt->execute();
        $properties = $propertiesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get review statistics
        $stats = [
            'total_reviews' => $total,
            'average_rating' => getAverageRating($db),
            'reviewed_properties' => getReviewedPropertiesCount($db),
            'active_reviewers' => getActiveReviewersCount($db)
        ];
        
        echo json_encode([
            "success" => true,
            "reviews" => $reviews,
            "properties" => $properties,
            "stats" => $stats,
            "total" => $total,
            "page" => $page,
            "totalPages" => ceil($total / $limit)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// POST - Delete review
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['action']) || !isset($data['review_id'])) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }
    
    $review_id = $data['review_id'];
    $action = $data['action'];
    
    try {
        if ($action == 'delete') {
            // Delete the review
            $query = "DELETE FROM reviews WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $review_id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Review deleted successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to delete review"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// Helper functions for statistics
function getAverageRating($db) {
    $query = "SELECT AVG(rating) as avg_rating FROM reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return round($result['avg_rating'], 1);
}

function getReviewedPropertiesCount($db) {
    $query = "SELECT COUNT(DISTINCT property_id) as count FROM reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

function getActiveReviewersCount($db) {
    $query = "SELECT COUNT(DISTINCT user_id) as count FROM reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
?>