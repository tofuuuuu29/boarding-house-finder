<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit();
}

if (!isset($_FILES['profile_image'])) {
    http_response_code(400);
    echo json_encode(array("message" => "No image file uploaded"));
    exit();
}

$file = $_FILES['profile_image'];
$allowed_types = array('image/jpeg', 'image/png', 'image/gif');

if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid file type. Only JPEG, PNG and GIF are allowed."));
    exit();
}

// Create upload directory if it doesn't exist
$upload_dir = "../../images/profile/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid($_SESSION['user_id'] . '_') . '.' . $extension;
$filepath = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $database = new Database();
    $db = $database->getConnection();

    // Update database with new image path
    $relative_path = "images/profile/" . $filename;
    $query = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$relative_path, $_SESSION['user_id']])) {
        http_response_code(200);
        echo json_encode(array(
            "message" => "Profile image updated successfully",
            "image_url" => "../" . $relative_path
        ));
    } else {
        unlink($filepath); // Delete uploaded file if database update fails
        http_response_code(503);
        echo json_encode(array("message" => "Error updating profile image in database"));
    }
} else {
    http_response_code(503);
    echo json_encode(array("message" => "Error uploading file"));
}
?>