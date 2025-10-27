<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
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
$boarder_id = $_SESSION['user_id'];

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all saved properties for the boarder
        $query = "SELECT p.*, sp.saved_at 
                 FROM properties p 
                 INNER JOIN saved_properties sp ON p.id = sp.property_id 
                 WHERE sp.boarder_id = ? 
                 ORDER BY sp.saved_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $boarder_id);
        $stmt->execute();

        $properties = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $properties[] = array(
                "id" => $row['id'],
                "title" => $row['title'],
                "description" => $row['description'],
                "room_type" => $row['room_type'],
                "price" => $row['price'],
                "address" => $row['address'],
                "city" => $row['city'],
                "capacity" => $row['capacity'],
                "amenities" => $row['amenities'] ? json_decode($row['amenities']) : [],
                "saved_at" => $row['saved_at']
            );
        }

        http_response_code(200);
        echo json_encode(array("properties" => $properties));
        break;

    case 'POST':
        // Save a new property
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->property_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Property ID is required."));
            exit();
        }

        // Check if property exists
        $check_query = "SELECT id FROM properties WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $data->property_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Property not found."));
            exit();
        }

        // Check if already saved
        $check_saved = "SELECT id FROM saved_properties WHERE boarder_id = ? AND property_id = ?";
        $saved_stmt = $db->prepare($check_saved);
        $saved_stmt->bindParam(1, $boarder_id);
        $saved_stmt->bindParam(2, $data->property_id);
        $saved_stmt->execute();

        if ($saved_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Property already saved."));
            exit();
        }

        // Save the property
        $query = "INSERT INTO saved_properties (boarder_id, property_id, saved_at) VALUES (?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $boarder_id);
        $stmt->bindParam(2, $data->property_id);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Property saved successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to save property."));
        }
        break;

    case 'DELETE':
        // Remove a saved property
        $property_id = isset($_GET['property_id']) ? $_GET['property_id'] : null;
        
        if (!$property_id) {
            http_response_code(400);
            echo json_encode(array("message" => "Property ID is required."));
            exit();
        }

        $query = "DELETE FROM saved_properties WHERE boarder_id = ? AND property_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $boarder_id);
        $stmt->bindParam(2, $property_id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Property removed from saved list."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to remove property."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>