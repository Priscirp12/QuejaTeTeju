<?php
// api/quejas/archivos.php - Get files for a specific complaint
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// Verify authentication
$user = authenticateUser();
if (!$user) {
    send_json(array("success" => false, "error" => "No autorizado"), 401);
}

$queja_id = isset($_GET['queja_id']) ? intval($_GET['queja_id']) : 0;

if ($queja_id <= 0) {
    send_json(array("success" => false, "error" => "queja_id requerido"), 400);
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get all files for the complaint
    $query = "SELECT id, nombre_original, nombre_archivo, tipo_archivo, ruta_archivo, tamanio_bytes, extension, fecha_subida 
              FROM archivos_quejas 
              WHERE queja_id = :queja_id 
              ORDER BY fecha_subida DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':queja_id', $queja_id);
    $stmt->execute();
    
    $archivos = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Convert relative path to web URL
        // Stored as: ../../uploads/imagenes/archivo.jpg
        // Convert to: /PETICIONES/uploads/imagenes/archivo.jpg
        $ruta_relativa = $row['ruta_archivo'];
        $ruta_web = str_replace('../../', '/PETICIONES/', $ruta_relativa);
        $row['ruta_archivo'] = $ruta_web;
        $archivos[] = $row;
    }
    
    send_json(array(
        "success" => true,
        "archivos" => $archivos
    ), 200);
    
} catch (Exception $e) {
    send_json(array(
        "success" => false,
        "error" => "Error al obtener archivos"
    ), 500);
}
?>
