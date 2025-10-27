<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

session_start();

// Check if user is logged in and is a landlord
if (!isLoggedIn() || $_SESSION['user_type'] !== 'landlord') {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized access. Please login as landlord."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if(
    !empty($data->title) &&
    !empty($data->address) &&
    !empty($data->room_type) &&
    !empty($data->price) &&
    !empty($data->capacity)
) {
    $title = sanitize($data->title);
    $address = sanitize($data->address);
    $room_type = $data->room_type;
    $price = $data->price;
    $capacity = $data->capacity;
    $beds = !empty($data->beds) ? $data->beds : NULL;
    $description = !empty($data->description) ? sanitize($data->description) : '';
    $amenities = !empty($data->amenities) ? json_encode($data->amenities) : '[]';
    $contact_phone = !empty($data->contact_phone) ? sanitize($data->contact_phone) : $_SESSION['user_phone'];

    // Insert new property
    $query = "INSERT INTO properties SET 
        owner_id=:owner_id, 
        title=:title, 
        description=:description, 
        room_type=:room_type, 
        capacity=:capacity, 
        price=:price, 
        address=:address, 
        beds=:beds, 
        amenities=:amenities, 
        contact_phone=:contact_phone,
        status='Available'";

    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(":owner_id", $_SESSION['user_id']);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":room_type", $room_type);
    $stmt->bindParam(":capacity", $capacity);
    $stmt->bindParam(":price", $price);
    $stmt->bindParam(":address", $address);
    $stmt->bindParam(":beds", $beds);
    $stmt->bindParam(":amenities", $amenities);
    $stmt->bindParam(":contact_phone", $contact_phone);

    if($stmt->execute()) {
        $property_id = $db->lastInsertId();
        http_response_code(201);
        echo json_encode(array(
            "message" => "Property added successfully.", 
            "property_id" => $property_id
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to add property."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to add property. Required data is missing."));
}
?>