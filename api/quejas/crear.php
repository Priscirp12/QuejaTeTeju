<?php
// api/quejas/crear.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Suprimir warnings/errores para evitar que Xdebug o PHP vuelquen HTML al cliente
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ob_start(); // comenzar a capturar cualquier salida accidental

include_once '../../config/configDatabase.php';
include_once '../../includes/auth.php';

// Helper para responder JSON y vaciar cualquier buffer
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

// Obtener datos del formulario
$titulo = isset($_POST['titulo']) ? $_POST['titulo'] : '';
$descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
$prioridad = isset($_POST['prioridad']) ? $_POST['prioridad'] : 'Media';
$ubicacion_direccion = isset($_POST['ubicacion_direccion']) ? $_POST['ubicacion_direccion'] : '';
$ubicacion_latitud = isset($_POST['ubicacion_latitud']) ? $_POST['ubicacion_latitud'] : null;
$ubicacion_longitud = isset($_POST['ubicacion_longitud']) ? $_POST['ubicacion_longitud'] : null;

// Validaciones
if (empty($titulo) || empty($descripcion) || empty($categoria) || empty($tipo) || empty($ubicacion_direccion)) {
    send_json(array(
        "success" => false,
        "error" => "Faltan campos requeridos"
    ), 400);
}

try {
    // Iniciar transacción
    $db->beginTransaction();

    // Insertar queja usando procedimiento almacenado
    $query = "CALL sp_crear_queja(:usuario_id, :titulo, :descripcion, :categoria, :tipo, :prioridad, :ubicacion_direccion, :ubicacion_latitud, :ubicacion_longitud)";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':usuario_id', $user['id']);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':categoria', $categoria);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':prioridad', $prioridad);
    $stmt->bindParam(':ubicacion_direccion', $ubicacion_direccion);
    $stmt->bindParam(':ubicacion_latitud', $ubicacion_latitud);
    $stmt->bindParam(':ubicacion_longitud', $ubicacion_longitud);

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $queja_id = $result['id'];

    // Consume any additional result sets to avoid packet errors
    try {
        while ($stmt->nextRowset()) {}
    } catch (Exception $ex) {}

    $stmt->closeCursor();

    // Procesar archivos subidos
    if (isset($_FILES['archivos'])) {
        $upload_dir = '../../uploads/';
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg', 'application/pdf');
        $max_size = 10 * 1024 * 1024; // 10MB

        if (!is_dir($upload_dir . 'imagenes')) mkdir($upload_dir . 'imagenes', 0777, true);
        if (!is_dir($upload_dir . 'pdfs')) mkdir($upload_dir . 'pdfs', 0777, true);
        if (!is_dir($upload_dir . 'otros')) mkdir($upload_dir . 'otros', 0777, true);

        $files = $_FILES['archivos'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] == 0) {
                if (!in_array($files['type'][$i], $allowed_types)) continue;
                if ($files['size'][$i] > $max_size) continue;

                $tipo_archivo = 'otro';
                $subfolder = 'otros';
                if (strpos($files['type'][$i], 'image') !== false) {
                    $tipo_archivo = 'imagen';
                    $subfolder = 'imagenes';
                } elseif ($files['type'][$i] == 'application/pdf') {
                    $tipo_archivo = 'pdf';
                    $subfolder = 'pdfs';
                }

                $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
                $ruta_completa = $upload_dir . $subfolder . '/' . $nombre_archivo;

                if (move_uploaded_file($files['tmp_name'][$i], $ruta_completa)) {
                    try {
                        $insert_query = "INSERT INTO archivos_quejas 
                                        (queja_id, nombre_original, nombre_archivo, tipo_archivo, ruta_archivo, tamanio_bytes, extension) 
                                        VALUES (:queja_id, :nombre_original, :nombre_archivo, :tipo_archivo, :ruta_archivo, :tamanio, :extension)";
                        $insert_stmt = $db->prepare($insert_query);
                        $insert_stmt->bindParam(':queja_id', $queja_id);
                        $insert_stmt->bindParam(':nombre_original', $files['name'][$i]);
                        $insert_stmt->bindParam(':nombre_archivo', $nombre_archivo);
                        $insert_stmt->bindParam(':tipo_archivo', $tipo_archivo);
                        $insert_stmt->bindParam(':ruta_archivo', $ruta_completa);
                        $insert_stmt->bindParam(':tamanio', $files['size'][$i]);
                        $insert_stmt->bindParam(':extension', $extension);
                        $insert_stmt->execute();
                    } catch (Exception $e) {
                        error_log('Failed to insert archivo_quejas: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    $db->commit();

    send_json(array(
        "success" => true,
        "message" => "Queja creada exitosamente",
        "quejaId" => $queja_id
    ), 201);
}
catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    send_json(array(
        "success" => false,
        "error" => "Error al crear la queja: " . $e->getMessage()
    ), 500);
}
?>
