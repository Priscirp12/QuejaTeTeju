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

// Suprimir warnings/errores
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ob_start();

include_once '../../config/configDatabase.php';

function send_json($data, $status = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}

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
            send_json(array("success" => false, "error" => "Usuario inactivo. Contacte al administrador"), 403);
        }

        // Asegurar que sólo el admin conocido funcione como rol 'admin'
        // (evita que cuentas nuevas o mal configuradas se vuelvan administradores)
        $adminEmail = 'admin@municipal.gob.mx';
        if (strtolower($row['email']) !== strtolower($adminEmail)) {
            $row['rol'] = 'ciudadano';
        }

        // Verificar contraseña — soportar migración desde contraseñas en texto plano:
        $provided = isset($data->password) ? $data->password : '';
        $stored = $row['password'];
        $login_ok = false;

        if (!empty($stored) && password_verify($provided, $stored)) {
            $login_ok = true;
        } elseif ($provided === $stored) {
            // Legacy plain-text password — migrate to bcrypt hash
            try {
                $new_hash = password_hash($provided, PASSWORD_BCRYPT);
                $update_pw_q = "UPDATE usuarios SET password = :password WHERE id = :id";
                $up = $db->prepare($update_pw_q);
                $up->bindParam(':password', $new_hash);
                $up->bindParam(':id', $row['id']);
                $up->execute();
            } catch (Exception $e) {
                // ignore update failures but allow login to proceed
            }
            $login_ok = true;
        }

        if ($login_ok) {
            // Actualizar última sesión
            $update_query = "UPDATE usuarios SET ultima_sesion = NOW() WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":id", $row['id']);
            $update_stmt->execute();

            // Generar token simple (en producción usar JWT)
            $token = base64_encode($row['id'] . ':' . time() . ':' . md5($row['email']));

            // Respuesta exitosa
            send_json(array(
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
            ), 200);
        } else {
            send_json(array("success" => false, "error" => "Email o contraseña incorrectos"), 401);
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
