<?php
// api/quejas/historial.php — devuelve comentarios del historial de una queja
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

$queja_id = isset($_GET['queja_id']) ? intval($_GET['queja_id']) : 0;
if (!$queja_id) {
    http_response_code(400);
    echo json_encode(array("error" => "queja_id es requerido"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Validar que el usuario pueda ver esta queja (propietario o admin)
$query = "SELECT usuario_id FROM quejas WHERE id = :queja_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':queja_id', $queja_id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(array("error" => "Queja no encontrada"));
    exit();
}

$esPropietario = $row['usuario_id'] == $user['id'];

if (!$esPropietario && $user['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(array("error" => "Acceso denegado"));
    exit();
}

// Obtener historial con comentarios
$query = "SELECT h.id, h.comentario, h.estatus_anterior, h.estatus_nuevo, h.fecha_cambio, u.nombre AS admin_nombre
          FROM historial_quejas h
          LEFT JOIN usuarios u ON u.id = h.admin_id
          WHERE h.queja_id = :queja_id
            AND h.comentario IS NOT NULL
            AND TRIM(h.comentario) <> ''
          ORDER BY h.fecha_cambio DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':queja_id', $queja_id);
$stmt->execute();

$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode(array(
    "success" => true,
    "comentarios" => $comentarios
));
?>