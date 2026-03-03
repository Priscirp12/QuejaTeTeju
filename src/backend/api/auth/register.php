<?php
// api/auth/register.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/configDatabase.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->nombre) && !empty($data->email) && !empty($data->password)) {

    // Validaciones
    if (strlen($data->password) < 6) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "error" => "La contraseña debe tener al menos 6 caracteres"
        ));
        exit();
    }

    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "error" => "Email inválido"
        ));
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el email ya existe
    $check_query = "SELECT id FROM usuarios WHERE email = :email LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":email", $data->email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "error" => "El email ya está registrado"
        ));
        exit();
    }

    // Encriptar contraseña
    $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);

    // Insertar usuario
    $telefono = isset($data->telefono) ? $data->telefono : null;
    $query = "INSERT INTO usuarios (nombre, email, password, telefono, rol) VALUES (:nombre, :email, :password, :telefono, 'ciudadano')";
    $stmt = $db->prepare($query);

    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":telefono", $telefono);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Usuario registrado exitosamente",
            "userId" => $db->lastInsertId()
        ));
    }
    else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "error" => "No se pudo registrar el usuario"
        ));
    }
}
else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "error" => "Nombre, email y contraseña son requeridos"
    ));
}
?>
