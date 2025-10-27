<?php
session_start();
header("Content-Type: application/json");
include_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Input validation
    if (empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Email and password are required"]);
        exit;
    }
    
    try {
        $query = "SELECT id, name, email, password, is_admin, is_approved 
                  FROM users 
                  WHERE email = :email AND is_admin = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_logged_in'] = true;
                
                echo json_encode([
                    "success" => true, 
                    "message" => "Login successful",
                    "admin_name" => $admin['name']
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Invalid password"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Admin account not found. Make sure this account has is_admin = 1 in the database."]);
        }
    } catch (PDOException $e) {
        error_log("Admin login error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "System error. Please try again."]);
    }
    exit;
}

// Logout endpoint
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    echo json_encode(["success" => true, "message" => "Logged out successfully"]);
    exit;
}

// If not a valid request
echo json_encode(["success" => false, "message" => "Invalid request"]);
?>