<?php
session_start();
header("Content-Type: application/json");
include_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = $data['password'];
    $user_type = $data['user_type'];
    $phone = isset($data['phone']) ? trim($data['phone']) : '';
    
    // Input validation
    if (empty($name) || empty($email) || empty($password) || empty($user_type)) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format"]);
        exit;
    }
    
    // Check if email already exists
    try {
        $check_query = "SELECT id FROM users WHERE email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Email already registered"]);
            exit;
        }
        
        // Set approval status based on user type
        // Boarders are auto-approved, landlords require admin approval
        $is_approved = ($user_type == 'boarder') ? 1 : 0;
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users (name, email, password, user_type, phone, is_approved) 
                  VALUES (:name, :email, :password, :user_type, :phone, :is_approved)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':user_type', $user_type);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':is_approved', $is_approved);
        
        if ($stmt->execute()) {
            // Registration successful
            if ($user_type == 'landlord') {
                // Landlord needs approval - redirect to pending page
                echo json_encode([
                    "success" => true, 
                    "message" => "Registration successful! Your landlord account is pending admin approval.",
                    "redirect" => "pending-approval.html",
                    "requires_approval" => true
                ]);
            } else {
                // Boarder is auto-approved - redirect to login
                echo json_encode([
                    "success" => true, 
                    "message" => "Registration successful! You can now login.",
                    "redirect" => "login.html",
                    "requires_approval" => false
                ]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Registration failed. Please try again."]);
        }
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "System error. Please try again."]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
?>