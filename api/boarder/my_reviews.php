<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

session_start();

// Check if user is logged in and is a boarder
if (!isLoggedIn() || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'boarder') {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized access"));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user's reviews with property information
$query = "SELECT r.*, p.title as property_name, p.location as property_location, 
          (SELECT image FROM property_images WHERE property_id = p.id LIMIT 1) as property_image
          FROM reviews r 
          JOIN properties p ON r.property_id = p.id 
          WHERE r.user_id = ? 
          ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $_SESSION['user_id']);
$stmt->execute();

$reviews = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Add complete image URL
    $row['property_image'] = $row['property_image'] ? "../images/properties/" . $row['property_image'] : "../images/default-property.jpg";
    
    $reviews[] = $row;
}

http_response_code(200);
echo json_encode(array("reviews" => $reviews));
?>