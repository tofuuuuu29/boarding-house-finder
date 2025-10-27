<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

session_start();

// Provide a fallback isLoggedIn() if it's not defined in includes/functions.php
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Check if user is logged in and is a landlord
if (!isLoggedIn() || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'landlord') {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized access. Please login as landlord."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$landlord_id = $_SESSION['user_id'];

// Get landlord's properties
$query = "SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $landlord_id);
$stmt->execute();

$properties = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $properties[] = array(
        "id" => $row['id'],
        "title" => $row['title'],
        "description" => $row['description'],
        "room_type" => $row['room_type'],
        "capacity" => $row['capacity'],
        "status" => $row['status'],
        "price" => $row['price'],
        "address" => $row['address'],
        "city" => $row['city'],
        "beds" => $row['beds'],
        "baths" => $row['baths'],
        "amenities" => $row['amenities'] ? json_decode($row['amenities']) : [],
        "contact_phone" => $row['contact_phone'],
        "created_at" => $row['created_at']
    );
}

http_response_code(200);
echo json_encode(array(
    "message" => "Properties retrieved successfully.",
    "count" => count($properties),
    "properties" => $properties
));
?>