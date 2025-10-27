<?php
session_start();
header("Content-Type: application/json");
include_once __DIR__ . '/../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get dashboard statistics
    $stats = [];
    
    // Total users
    $query = "SELECT COUNT(*) as total FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total properties
    $query = "SELECT COUNT(*) as total FROM properties";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_properties'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total landlords (approved)
    $query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'landlord' AND is_approved = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_landlords'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total boarders
    $query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'boarder'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_boarders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending landlords
    $query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'landlord' AND is_approved = FALSE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_landlords'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total reviews
    $query = "SELECT COUNT(*) as total FROM reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_reviews'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Average rating
    $query = "SELECT AVG(rating) as average FROM reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $avgResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['average_rating'] = $avgResult['average'] ? round($avgResult['average'], 1) : 0;
    
    echo json_encode([
        "success" => true,
        "stats" => $stats,
        "admin_name" => $_SESSION['admin_name']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>