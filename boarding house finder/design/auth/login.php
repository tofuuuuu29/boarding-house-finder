<?php
// ADD THIS AT THE VERY TOP - TURN OFF ERROR DISPLAY
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password) && !empty($data->user_type)) {
    $email = sanitize($data->email);
    $password = $data->password;
    $user_type = $data->user_type;

    // Check if user exists with this email and user type
    $query = "SELECT id, name, password, phone FROM users WHERE email = ? AND user_type = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->bindParam(2, $user_type);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hashed_password = $row['password'];
        
        // Verify password
        if(password_verify($password, $hashed_password)) {
            session_start();
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_phone'] = $row['phone'];
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "Login successful.",
                "user" => array(
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "email" => $email,
                    "user_type" => $user_type,
                    "phone" => $row['phone']
                ),
               "redirect" => $user_type === 'landlord' ? '/boarding%20house%20finder/design/landlord/dashboard.html' : '/boarding%20house%20finder/design/home.html'
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Login failed. Invalid credentials."));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Login failed. No user found with these credentials."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to login. Data is incomplete."));
}
?>