<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// FIXED PATH - goes up two levels to reach config folder
include_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getReviews($db);
        break;
    case 'POST':
        addReview($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}

function getReviews($db) {
    $property_id = isset($_GET['property_id']) ? $_GET['property_id'] : '';
    
    if (empty($property_id)) {
        echo json_encode(["success" => false, "message" => "Property ID is required"]);
        return;
    }

    try {
        $query = "SELECT r.*, u.name as user_name 
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.property_id = :property_id 
                  ORDER BY r.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':property_id', $property_id);
        $stmt->execute();
        
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "reviews" => $reviews]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}

function addReview($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->property_id) && !empty($data->user_id) && !empty($data->rating)) {
        // Validate rating
        if ($data->rating < 1 || $data->rating > 5) {
            echo json_encode(["success" => false, "message" => "Rating must be between 1 and 5"]);
            return;
        }

        try {
            $query = "INSERT INTO reviews (property_id, user_id, rating, comment) 
                      VALUES (:property_id, :user_id, :rating, :comment)";
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':property_id', $data->property_id);
            $stmt->bindParam(':user_id', $data->user_id);
            $stmt->bindParam(':rating', $data->rating);
            $stmt->bindParam(':comment', $data->comment);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Review added successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to add review"]);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(["success" => false, "message" => "You have already reviewed this property"]);
            } else {
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            }
        }
    } else {
        echo json_encode(["success" => false, "message" => "Missing required fields: property_id, user_id, and rating are required"]);
    }
}
?>