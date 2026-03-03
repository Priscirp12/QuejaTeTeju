<?php
// api/quejas/actualizar-estatus.php — Solo admin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
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

// Verificar que es admin
if (!isAdmin($user)) {
    http_response_code(403);
    echo json_encode(array("error" => "Acceso denegado. Solo administradores."));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->queja_id) && !empty($data->estatus)) {

    // Validar estatus
    $estatus_validos = array('Pendiente', 'En Proceso', 'Resuelta', 'Rechazada');
    if (!in_array($data->estatus, $estatus_validos)) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "error" => "Estatus inválido"
        ));
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    $comentario = isset($data->comentario) ? $data->comentario : '';

    try {
        // Usar procedimiento almacenado
        $query = "CALL sp_actualizar_estatus_queja(:queja_id, :estatus, :comentario, :admin_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':queja_id', $data->queja_id);
        $stmt->bindParam(':estatus', $data->estatus);
        $stmt->bindParam(':comentario', $comentario);
        $stmt->bindParam(':admin_id', $user['id']);
        $stmt->execute();

        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Estatus actualizado exitosamente"
        ));
    }
    catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "error" => "Error al actualizar estatus: " . $e->getMessage()
        ));
    }
}
else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "error" => "queja_id y estatus son requeridos"
    ));
}
?>
