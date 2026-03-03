<?php
// includes/auth.php
// Funciones de autenticación

function authenticateUser() {
    $headers = getallheaders();
    
    if(!isset($headers['Authorization'])) {
        return false;
    }
    
    $auth_header = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $auth_header);
    
    // Decodificar token simple
    $decoded = base64_decode($token);
    $parts = explode(':', $decoded);
    
    if(count($parts) >= 1) {
        $user_id = $parts[0];
        
        // Verificar que el usuario existe
        include_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, nombre, email, rol FROM usuarios WHERE id = :id AND activo = 1 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    return false;
}

function isAdmin($user) {
    return $user && $user['rol'] === 'admin';
}
?>