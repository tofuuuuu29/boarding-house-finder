<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $property_id = isset($_GET['property_id']) ? $_GET['property_id'] : '';
    
    if (empty($property_id)) {
        echo json_encode(["success" => false, "message" => "Property ID is required"]);
        exit;
    }

    try {
        $query = "SELECT 
                  COUNT(*) as total_reviews,
                  AVG(rating) as average_rating,
                  SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                  SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                  SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                  SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                  SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                  FROM reviews WHERE property_id = :property_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':property_id', $property_id);
        $stmt->execute();
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format average rating
        if ($stats['total_reviews'] > 0) {
            $stats['average_rating'] = round(floatval($stats['average_rating']), 1);
        } else {
            $stats['average_rating'] = 0;
        }
        
        echo json_encode(["success" => true, "stats" => $stats]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
?>