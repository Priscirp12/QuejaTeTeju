<?php
// api/quejas/todas.php — Solo admin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Suprimir warnings/errores y limpiar buffer
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ob_start();

include_once '../../config/configDatabase.php';
include_once '../../includes/auth.php';

function send_json($data, $status = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}

// Verificar autenticación
$user = authenticateUser();
if (!$user) {
    send_json(array("success" => false, "error" => "No autorizado"), 401);
}

// Verificar que es admin
if (!isAdmin($user)) {
    send_json(array("success" => false, "error" => "Acceso denegado. Solo administradores."), 403);
}

// Verificar que es admin
if (!isAdmin($user)) {
    http_response_code(403);
    echo json_encode(array("error" => "Acceso denegado. Solo administradores."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener filtros opcionales
$estatus = isset($_GET['estatus']) ? $_GET['estatus'] : null;
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;

// Usar la vista completa
$query = "SELECT * FROM vista_quejas_completas WHERE 1=1";
$params = array();

if ($estatus) {
    $query .= " AND estatus = :estatus";
    $params[':estatus'] = $estatus;
}

if ($categoria) {
    $query .= " AND categoria = :categoria";
    $params[':categoria'] = $categoria;
}

$query .= " ORDER BY fecha_creacion DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$quejas = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $quejas[] = $row;
}

http_response_code(200);
echo json_encode(array(
    "success" => true,
    "quejas" => $quejas
));
?>
