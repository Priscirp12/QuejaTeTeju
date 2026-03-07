<?php
// api/estadisticas/usuario.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/configDatabase.php';
include_once '../../includes/auth.php';

// Verificar autenticación
$user = authenticateUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(array("error" => "No autorizado"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Si es administrador, devolver estadísticas globales (todas las quejas)
if ($user['rol'] === 'admin') {
    $query = "SELECT * FROM vista_estadisticas_generales";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Obtener estadísticas del usuario
    $query = "SELECT * FROM vista_estadisticas_usuario WHERE usuario_id = :usuario_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario_id', $user['id']);
    $stmt->execute();
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$estadisticas) {
    $estadisticas = array(
        'total_quejas' => 0,
        'pendientes' => 0,
        'en_proceso' => 0,
        'resueltas' => 0,
        'rechazadas' => 0
    );
}

http_response_code(200);
echo json_encode(array(
    "success" => true,
    "estadisticas" => $estadisticas
));
?>
