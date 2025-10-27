<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get search parameters
$data = json_decode(file_get_contents("php://input"));

$location = !empty($data->location) ? sanitize($data->location) : '';
$room_type = !empty($data->room_type) ? $data->room_type : '';
$capacity = !empty($data->capacity) ? $data->capacity : '';
$min_price = !empty($data->min_price) ? $data->min_price : 0;
$max_price = !empty($data->max_price) ? $data->max_price : 100000;

// Build search query
$query = "SELECT p.*, u.name as owner_name, u.phone as owner_phone 
          FROM properties p 
          LEFT JOIN users u ON p.owner_id = u.id 
          WHERE p.status = 'Available'";

$params = [];

// Add filters
if (!empty($location)) {
    $query .= " AND (p.address LIKE ? OR p.city LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
}

if (!empty($room_type)) {
    $query .= " AND p.room_type = ?";
    $params[] = $room_type;
}

if (!empty($capacity)) {
    $query .= " AND p.capacity = ?";
    $params[] = $capacity;
}

$query .= " AND p.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;

$query .= " ORDER BY p.created_at DESC";

// Execute query
$stmt = $db->prepare($query);

// Bind parameters using bindValue (better for dynamic loops)
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}

$stmt->execute();

$properties = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $properties[] = array(
        "id" => $row['id'],
        "title" => $row['title'],
        "description" => $row['description'],
        "room_type" => $row['room_type'],
        "capacity" => $row['capacity'],
        "price" => $row['price'],
        "address" => $row['address'],
        "city" => $row['city'],
        "beds" => $row['beds'],
        "baths" => $row['baths'],
        "amenities" => $row['amenities'] ? json_decode($row['amenities']) : [],
        "contact_phone" => $row['contact_phone'],
        "owner_name" => $row['owner_name'],
        "created_at" => $row['created_at']
    );
}

http_response_code(200);
echo json_encode(array(
    "message" => "Search completed successfully.",
    "count" => count($properties),
    "properties" => $properties
));
?>