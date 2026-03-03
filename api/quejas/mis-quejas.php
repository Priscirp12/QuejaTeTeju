<?php
// api/quejas/mis-quejas.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Suprimir warnings/errores para evitar HTML inesperado
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

$database = new Database();
$db = $database->getConnection();

// Obtener filtros opcionales
$estatus = isset($_GET['estatus']) ? $_GET['estatus'] : null;
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;

// Construir query
$query = "SELECT q.*, 
          (SELECT COUNT(*) FROM archivos_quejas WHERE queja_id = q.id) as total_archivos
          FROM quejas q
          WHERE q.usuario_id = :usuario_id";

$params = array(':usuario_id' => $user['id']);

if ($estatus) {
    $query .= " AND q.estatus = :estatus";
    $params[':estatus'] = $estatus;
}

if ($categoria) {
    $query .= " AND q.categoria = :categoria";
    $params[':categoria'] = $categoria;
}

$query .= " ORDER BY q.fecha_creacion DESC";

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
