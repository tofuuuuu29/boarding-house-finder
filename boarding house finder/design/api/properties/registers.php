<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->username) &&
    !empty($data->email) &&
    !empty($data->password) &&
    !empty($data->user_type) &&
    !empty($data->full_name)
) {
    $username = sanitize($data->username);
    $email = sanitize($data->email);
    $password = password_hash($data->password, PASSWORD_DEFAULT);
    $user_type = $data->user_type;
    $full_name = sanitize($data->full_name);
    $phone = !empty($data->phone) ? sanitize($data->phone) : '';

    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $username);
    $check_stmt->bindParam(2, $email);
    $check_stmt->execute();

    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Username or email already exists."));
        exit();
    }

    // Insert new user
    $query = "INSERT INTO users SET 
        username=:username, 
        email=:email, 
        password=:password, 
        user_type=:user_type, 
        full_name=:full_name, 
        phone=:phone";

    $stmt = $db->prepare($query);

    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password);
    $stmt->bindParam(":user_type", $user_type);
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":phone", $phone);

    if($stmt->execute()) {
        $user_id = $db->lastInsertId();
        http_response_code(201);
        echo json_encode(array(
            "message" => "User registered successfully.", 
            "user_id" => $user_id
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to register user."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to register. Required data is missing."));
}
?>