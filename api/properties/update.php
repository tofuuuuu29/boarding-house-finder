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
if(!empty($data->id) && !empty($data->title) && !empty($data->address) && !empty($data->room_type) && !empty($data->price) && !empty($data->capacity)) {
    $property_id = $data->id;
    $title = sanitize($data->title);
    $address = sanitize($data->address);
    $room_type = $data->room_type;
    $price = $data->price;
    $capacity = $data->capacity;
    $beds = !empty($data->beds) ? $data->beds : NULL;
    $baths = !empty($data->baths) ? $data->baths : NULL;
    $description = !empty($data->description) ? sanitize($data->description) : '';
    $amenities = !empty($data->amenities) ? json_encode($data->amenities) : '[]';
    $contact_phone = !empty($data->contact_phone) ? sanitize($data->contact_phone) : $_SESSION['user_phone'];
    $status = !empty($data->status) ? $data->status : 'Available';

    // Verify property belongs to this landlord
    $check_query = "SELECT id FROM properties WHERE id = ? AND owner_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $property_id);
    $check_stmt->bindParam(2, $_SESSION['user_id']);
    $check_stmt->execute();

    if($check_stmt->rowCount() > 0) {
        // Update property
        $query = "UPDATE properties SET 
            title=:title, 
            description=:description, 
            room_type=:room_type, 
            capacity=:capacity, 
            price=:price, 
            address=:address, 
            beds=:beds, 
            baths=:baths,
            amenities=:amenities, 
            contact_phone=:contact_phone,
            status=:status
            WHERE id=:id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $property_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":room_type", $room_type);
        $stmt->bindParam(":capacity", $capacity);
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":address", $address);
        $stmt->bindParam(":beds", $beds);
        $stmt->bindParam(":baths", $baths);
        $stmt->bindParam(":amenities", $amenities);
        $stmt->bindParam(":contact_phone", $contact_phone);
        $stmt->bindParam(":status", $status);

        if($stmt->execute()) {
            // Handle new image uploads if present
            if (!empty($data->images)) {
                $images_query = "INSERT INTO property_images (property_id, image) VALUES (?, ?)";
                $images_stmt = $db->prepare($images_query);

                foreach ($data->images as $image) {
                    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                    
                    // Validate image size (max 5MB)
                    if (strlen($image_data) > 5242880) {
                        continue;
                    }
                    
                    // Validate image type
                    $image_info = @getimagesizefromstring($image_data);
                    if ($image_info === false || !in_array($image_info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                        continue;
                    }
                    
                    $filename = uniqid() . '.jpg';
                    $filepath = "../../images/properties/" . $filename;

                    if (!file_exists("../../images/properties/")) {
                        mkdir("../../images/properties/", 0777, true);
                    }

                    if (file_put_contents($filepath, $image_data)) {
                        $images_stmt->execute([$property_id, $filename]);
                    }
                }
            }

            http_response_code(200);
            echo json_encode(array("message" => "Property updated successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update property."));
        }
    } else {
        http_response_code(403);
        echo json_encode(array("message" => "You don't have permission to update this property."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to update property. Required data is missing."));
}
?>

