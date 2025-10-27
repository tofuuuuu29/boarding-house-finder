<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['property_id'])) {
    http_response_code(400);
    echo json_encode(array("message" => "Property ID is required"));
    exit();
}

$property_id = $_GET['property_id'];

// Get all images for the property
$query = "SELECT * FROM property_images WHERE property_id = ? ORDER BY created_at";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $property_id);
$stmt->execute();

$images = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $images[] = array(
        "id" => $row['id'],
        "url" => "../images/properties/" . $row['image'],
        "created_at" => $row['created_at']
    );
}

http_response_code(200);
echo json_encode(array("images" => $images));
?>