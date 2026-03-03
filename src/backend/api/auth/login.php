<?php
// api/auth/login.php
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

// Obtener datos del POST
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {

    $database = new Database();
    $db = $database->getConnection();

    // Buscar usuario por email
    $query = "SELECT id, nombre, email, password, telefono, rol, activo FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    $num = $stmt->rowCount();

    if ($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si el usuario está activo
        if ($row['activo'] != 1) {
            http_response_code(403);
            echo json_encode(array(
                "success" => false,
                "error" => "Usuario inactivo. Contacte al administrador"
            ));
            exit();
        }

        // Verificar contraseña
        if (password_verify($data->password, $row['password'])) {

            // Actualizar última sesión
            $update_query = "UPDATE usuarios SET ultima_sesion = NOW() WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":id", $row['id']);
            $update_stmt->execute();

            // Generar token simple (en producción usar JWT)
            $token = base64_encode($row['id'] . ':' . time() . ':' . md5($row['email']));

            // Respuesta exitosa
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Login exitoso",
                "token" => $token,
                "user" => array(
                    "id" => $row['id'],
                    "nombre" => $row['nombre'],
                    "email" => $row['email'],
                    "telefono" => $row['telefono'],
                    "rol" => $row['rol']
                )
            ));
        }
        else {
            http_response_code(401);
            echo json_encode(array(
                "success" => false,
                "error" => "Email o contraseña incorrectos"
            ));
        }
    }
    else {
        http_response_code(401);
        echo json_encode(array(
            "success" => false,
            "error" => "Email o contraseña incorrectos"
        ));
    }
}
else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "error" => "Email y contraseña son requeridos"
    ));
}
?>
