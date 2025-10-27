<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

session_start();

// Check if user is logged in and is a landlord
if (!isLoggedIn() || !isLandlord()) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized access. Please login as landlord."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->property_id)) {
    $property_id = $data->property_id;
    $landlord_id = $_SESSION['user_id'];

    // Verify that the property belongs to the logged-in landlord
    $check_query = "SELECT id FROM properties WHERE id = ? AND owner_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $property_id);
    $check_stmt->bindParam(2, $landlord_id);
    $check_stmt->execute();

    if($check_stmt->rowCount() > 0) {
        // Delete the property
        $delete_query = "DELETE FROM properties WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(1, $property_id);

        if($delete_stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Property deleted successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete property."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Property not found or you don't have permission to delete it."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to delete property. Property ID is missing."));
}
?>