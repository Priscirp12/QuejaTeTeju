<?php
// api/quejas/mis-quejas.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../includes/auth.php';

// Verificar autenticación
$user = authenticateUser();
if(!$user) {
    http_response_code(401);
    echo json_encode(array("error" => "No autorizado"));
    exit();
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

if($estatus) {
    $query .= " AND q.estatus = :estatus";
    $params[':estatus'] = $estatus;
}

if($categoria) {
    $query .= " AND q.categoria = :categoria";
    $params[':categoria'] = $categoria;
}

$query .= " ORDER BY q.fecha_creacion DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$quejas = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $quejas[] = $row;
}

http_response_code(200);
echo json_encode(array(
    "success" => true,
    "quejas" => $quejas
));
?>