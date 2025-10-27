<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

session_start();

$database = new Database();
$db = $database->getConnection();

// Get property ID from URL parameter
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($property_id === 0) {
    http_response_code(400);
    echo json_encode(array("message" => "Property ID is required."));
    exit();
}

try {
    // Get property details with owner information
    $query = "SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone, 
              u.profile_image as owner_image
              FROM properties p 
              LEFT JOIN users u ON p.owner_id = u.id 
              WHERE p.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $property_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("message" => "Property not found."));
        exit();
    }

    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if property is saved by current user
    $is_saved = false;
    if (isLoggedIn()) {
        $saved_check = $db->prepare("SELECT id FROM saved_properties WHERE boarder_id = ? AND property_id = ?");
        $saved_check->execute([$_SESSION['user_id'], $property_id]);
        $is_saved = $saved_check->rowCount() > 0;
    }

    // Get property reviews
    $reviews_query = "SELECT r.*, u.name as reviewer_name, u.profile_image as reviewer_image 
                     FROM reviews r 
                     LEFT JOIN users u ON r.user_id = u.id 
                     WHERE r.property_id = ? 
                     ORDER BY r.created_at DESC";
    
    $reviews_stmt = $db->prepare($reviews_query);
    $reviews_stmt->bindParam(1, $property_id);
    $reviews_stmt->execute();

    $reviews = [];
    while ($review = $reviews_stmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = array(
            "id" => $review['id'],
            "rating" => $review['rating'],
            "comment" => $review['comment'],
            "reviewer_name" => $review['reviewer_name'],
            "reviewer_image" => $review['reviewer_image'],
            "created_at" => $review['created_at']
        );
    }

    // Calculate average rating
    $avg_rating = 0;
    if (count($reviews) > 0) {
        $avg_rating = array_reduce($reviews, function($carry, $review) {
            return $carry + $review['rating'];
        }, 0) / count($reviews);
    }

    // Get property images
    $images_query = "SELECT image FROM property_images WHERE property_id = ? ORDER BY created_at";
    $images_stmt = $db->prepare($images_query);
    $images_stmt->execute([$property_id]);
    $images = [];
    while ($img = $images_stmt->fetch(PDO::FETCH_ASSOC)) {
        $images[] = "../images/properties/" . $img['image'];
    }

    // Format response data
    $response = array(
        "id" => $property['id'],
        "title" => $property['title'],
        "description" => $property['description'],
        "room_type" => $property['room_type'],
        "price" => $property['price'],
        "address" => $property['address'],
        "city" => $property['city'],
        "capacity" => $property['capacity'],
        "amenities" => json_decode($property['amenities']),
        "images" => $images,
        "rules" => json_decode($property['rules']),
        "status" => $property['status'],
        "owner" => array(
            "name" => $property['owner_name'],
            "email" => $property['owner_email'],
            "phone" => $property['owner_phone'],
            "image" => $property['owner_image']
        ),
        "is_saved" => $is_saved,
        "reviews" => array(
            "average_rating" => round($avg_rating, 1),
            "total_reviews" => count($reviews),
            "items" => $reviews
        ),
        "created_at" => $property['created_at'],
        "updated_at" => $property['updated_at']
    );

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error: " . $e->getMessage()));
}
?>