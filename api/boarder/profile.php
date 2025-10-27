<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
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

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get user profile data
        $query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM saved_properties WHERE user_id = u.id) as saved_count,
                 (SELECT COUNT(*) FROM reviews WHERE user_id = u.id) as review_count
                 FROM users u 
                 WHERE u.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $_SESSION['user_id']);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Remove sensitive information
            unset($row['password']);
            
            http_response_code(200);
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "User not found"));
        }
        break;

    case 'POST':
        // Update user profile
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate current password if trying to change password
        if (!empty($data->current_password)) {
            $verify_query = "SELECT password FROM users WHERE id = ?";
            $verify_stmt = $db->prepare($verify_query);
            $verify_stmt->bindParam(1, $_SESSION['user_id']);
            $verify_stmt->execute();
            
            $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            if (!password_verify($data->current_password, $user['password'])) {
                http_response_code(400);
                echo json_encode(array("message" => "Current password is incorrect"));
                exit();
            }
        }

        // Start building the update query
        $update_fields = array();
        $params = array();

        if (isset($data->name)) {
            $update_fields[] = "name = ?";
            $params[] = $data->name;
        }
        if (isset($data->email)) {
            $update_fields[] = "email = ?";
            $params[] = $data->email;
        }
        if (isset($data->phone)) {
            $update_fields[] = "phone = ?";
            $params[] = $data->phone;
        }
        if (!empty($data->new_password)) {
            $update_fields[] = "password = ?";
            $params[] = password_hash($data->new_password, PASSWORD_DEFAULT);
        }

        if (empty($update_fields)) {
            http_response_code(400);
            echo json_encode(array("message" => "No fields to update"));
            exit();
        }

        $query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $params[] = $_SESSION['user_id'];

        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            http_response_code(200);
            echo json_encode(array("message" => "Profile updated successfully"));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update profile"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>