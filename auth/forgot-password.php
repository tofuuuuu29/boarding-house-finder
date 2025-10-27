<?php
header("Content-Type: application/json");
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid email format"
        ]);
        exit;
    }

    // Check if email exists
    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "No account found with this email address"
        ]);
        exit;
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store reset token in database
    $stmt = $db->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$user['id'], $token, $expiry])) {
        // Send reset email
        $resetLink = "http://localhost/boarding%20house%20finder/design/auth/reset-password.html?token=" . $token;
        
        $to = $email;
        $subject = "Password Reset Request - MyHome";
        
        $message = "Hello " . $user['name'] . ",\n\n";
        $message .= "You have requested to reset your password. Click the link below to reset your password:\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you did not request this password reset, please ignore this email.\n\n";
        $message .= "Best regards,\nMyHome Team";
        
        $headers = "From: GHboardinghouse@gmail.com";

        if (mail($to, $subject, $message, $headers)) {
            echo json_encode([
                "success" => true,
                "message" => "Password reset instructions have been sent to your email"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to send reset email. Please try again."
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "An error occurred. Please try again."
        ]);
    }
    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Invalid request method"
]);
?>