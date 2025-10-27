<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

session_start();

// Check if user is logged in and is a boarder
if (!isLoggedIn() || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'boarder') {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized access. Please login as boarder."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        // Create new review
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->property_id) || !isset($data->rating) || !isset($data->comment)) {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields."));
            exit();
        }

        // Validate rating
        if ($data->rating < 1 || $data->rating > 5) {
            http_response_code(400);
            echo json_encode(array("message" => "Rating must be between 1 and 5."));
            exit();
        }

        // Check if user has already reviewed this property
        $check_query = "SELECT id FROM reviews WHERE user_id = ? AND property_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $_SESSION['user_id']);
        $check_stmt->bindParam(2, $data->property_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(array("message" => "You have already reviewed this property."));
            exit();
        }

        // Insert review
        $query = "INSERT INTO reviews (user_id, property_id, rating, comment, images, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($query);
        $images = isset($data->images) ? json_encode($data->images) : null;
        
        $stmt->bindParam(1, $_SESSION['user_id']);
        $stmt->bindParam(2, $data->property_id);
        $stmt->bindParam(3, $data->rating);
        $stmt->bindParam(4, $data->comment);
        $stmt->bindParam(5, $images);

        if ($stmt->execute()) {
            // Update property average rating
            updatePropertyRating($db, $data->property_id);
            
            http_response_code(201);
            echo json_encode(array("message" => "Review created successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create review."));
        }
        break;

    case 'GET':
        // Get reviews for a property
        $property_id = isset($_GET['property_id']) ? $_GET['property_id'] : null;
        
        if (!$property_id) {
            http_response_code(400);
            echo json_encode(array("message" => "Property ID is required."));
            exit();
        }

        $query = "SELECT r.*, u.name as reviewer_name, u.profile_image as reviewer_image 
                 FROM reviews r 
                 LEFT JOIN users u ON r.user_id = u.id 
                 WHERE r.property_id = ? 
                 ORDER BY r.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $property_id);
        $stmt->execute();

        $reviews = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reviews[] = array(
                "id" => $row['id'],
                "rating" => $row['rating'],
                "comment" => $row['comment'],
                "images" => json_decode($row['images']),
                "reviewer_name" => $row['reviewer_name'],
                "reviewer_image" => $row['reviewer_image'],
                "created_at" => $row['created_at']
            );
        }

        // Get average rating and total count
        $stats_query = "SELECT COUNT(*) as total, AVG(rating) as average 
                       FROM reviews WHERE property_id = ?";
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(1, $property_id);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(array(
            "reviews" => $reviews,
            "total" => (int)$stats['total'],
            "average_rating" => round($stats['average'], 1)
        ));
        break;

    case 'DELETE':
        // Delete a review
        $review_id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$review_id) {
            http_response_code(400);
            echo json_encode(array("message" => "Review ID is required."));
            exit();
        }

        // Check if the review belongs to the user
        $check_query = "SELECT property_id FROM reviews WHERE id = ? AND user_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $review_id);
        $check_stmt->bindParam(2, $_SESSION['user_id']);
        $check_stmt->execute();

        if ($check_stmt->rowCount() === 0) {
            http_response_code(403);
            echo json_encode(array("message" => "You can only delete your own reviews."));
            exit();
        }

        $property_id = $check_stmt->fetch(PDO::FETCH_COLUMN);

        $query = "DELETE FROM reviews WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $review_id);

        if ($stmt->execute()) {
            // Update property average rating
            updatePropertyRating($db, $property_id);
            
            http_response_code(200);
            echo json_encode(array("message" => "Review deleted successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete review."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function updatePropertyRating($db, $property_id) {
    $query = "UPDATE properties p 
              SET rating = (SELECT AVG(rating) FROM reviews WHERE property_id = ?)
              WHERE p.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $property_id);
    $stmt->bindParam(2, $property_id);
    $stmt->execute();
}
?>