<?php
// api/quejas/crear.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
if(empty($titulo) || empty($descripcion) || empty($categoria) || empty($tipo) || empty($ubicacion_direccion)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "error" => "Faltan campos requeridos"
    ));
    exit();
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
    
    // Procesar archivos subidos
    if(isset($_FILES['archivos'])) {
        $upload_dir = '../../uploads/';
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg', 'application/pdf');
        $max_size = 10 * 1024 * 1024; // 10MB
        
        // Crear directorios si no existen
        if(!is_dir($upload_dir . 'imagenes')) mkdir($upload_dir . 'imagenes', 0777, true);
        if(!is_dir($upload_dir . 'pdfs')) mkdir($upload_dir . 'pdfs', 0777, true);
        if(!is_dir($upload_dir . 'otros')) mkdir($upload_dir . 'otros', 0777, true);
        
        $files = $_FILES['archivos'];
        $file_count = count($files['name']);
        
        for($i = 0; $i < $file_count; $i++) {
            if($files['error'][$i] == 0) {
                // Validar tipo
                if(!in_array($files['type'][$i], $allowed_types)) {
                    continue;
                }
                
                // Validar tamaño
                if($files['size'][$i] > $max_size) {
                    continue;
                }
                
                // Determinar carpeta de destino
                $tipo_archivo = 'otro';
                $subfolder = 'otros';
                
                if(strpos($files['type'][$i], 'image') !== false) {
                    $tipo_archivo = 'imagen';
                    $subfolder = 'imagenes';
                } elseif($files['type'][$i] == 'application/pdf') {
                    $tipo_archivo = 'pdf';
                    $subfolder = 'pdfs';
                }
                
                // Generar nombre único
                $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
                $ruta_completa = $upload_dir . $subfolder . '/' . $nombre_archivo;
                
                // Mover archivo
                if(move_uploaded_file($files['tmp_name'][$i], $ruta_completa)) {
                    // Guardar en base de datos
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
                }
            }
        }
    }
    
    // Confirmar transacción
    $db->commit();
    
    http_response_code(201);
    echo json_encode(array(
        "success" => true,
        "message" => "Queja creada exitosamente",
        "quejaId" => $queja_id
    ));
    
} catch(Exception $e) {
    // Revertir transacción en caso de error
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "error" => "Error al crear la queja: " . $e->getMessage()
    ));
}
?>