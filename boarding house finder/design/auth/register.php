<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// CORRECT PATHS FOR YOUR STRUCTURE
include_once '../config/database.php';
include_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->email) &&
    !empty($data->password) &&
    !empty($data->user_type) &&
    !empty($data->name)
) {
    // ===== ADD PASSWORD VALIDATION HERE =====
    if (strlen($data->password) < 8) {
        http_response_code(400);
        echo json_encode(array("message" => "Password must be at least 8 characters long."));
        exit();
    }

    // Check for special characters
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]+/', $data->password)) {
        http_response_code(400);
        echo json_encode(array("message" => "Password should not contain special characters."));
        exit();
    }
 $full_name = sanitize($data->name);
    $email = sanitize($data->email);
    $password = $data->password;
    $user_type = $data->user_type;
    $phone = !empty($data->phone) ? sanitize($data->phone) : '';

    // Check if email already exists
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Email already exists. Please use a different email."));
    } else {
        // Insert new user
        $query = "INSERT INTO users SET name=:name, email=:email, password=:password, user_type=:user_type, phone=:phone";
        $stmt = $db->prepare($query);

        // Hash password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(":name", $full_name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":user_type", $user_type);
        $stmt->bindParam(":phone", $phone);

        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array(
                "message" => "User registered successfully.", 
                "user_type" => $user_type,
                "redirect" => $user_type === 'landlord' ? 'landlord/dashboard.html' : 'home.html'
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to register user."));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to register user. Data is incomplete."));
}
?>