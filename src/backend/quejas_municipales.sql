-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 28-02-2026 a las 22:48:46
-- Versión del servidor: 9.0.1
-- Versión de PHP: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `quejas_municipales`
--

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `sp_actualizar_estatus_queja`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_actualizar_estatus_queja` (IN `p_queja_id` INT, IN `p_nuevo_estatus` VARCHAR(20), IN `p_comentario` TEXT, IN `p_admin_id` INT)  BEGIN
    DECLARE v_estatus_anterior VARCHAR(20);
    DECLARE v_usuario_id INT;
    
    -- Obtener estatus anterior y usuario
    SELECT estatus, usuario_id INTO v_estatus_anterior, v_usuario_id
    FROM quejas WHERE id = p_queja_id;
    
    -- Actualizar la queja
    UPDATE quejas 
    SET estatus = p_nuevo_estatus, 
        respuesta_admin = p_comentario,
        admin_id = p_admin_id
    WHERE id = p_queja_id;
    
    -- Registrar en historial
    INSERT INTO historial_quejas (queja_id, estatus_anterior, estatus_nuevo, comentario, admin_id)
    VALUES (p_queja_id, v_estatus_anterior, p_nuevo_estatus, p_comentario, p_admin_id);
    
    -- Crear notificación para el ciudadano
    INSERT INTO notificaciones (usuario_id, queja_id, titulo, mensaje, tipo)
    VALUES (
        v_usuario_id, 
        p_queja_id, 
        CONCAT('Tu queja cambió a: ', p_nuevo_estatus),
        p_comentario,
        CASE p_nuevo_estatus
            WHEN 'Resuelta' THEN 'success'
            WHEN 'Rechazada' THEN 'error'
            ELSE 'info'
        END
    );
END$$

DROP PROCEDURE IF EXISTS `sp_crear_queja`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_queja` (IN `p_usuario_id` INT, IN `p_titulo` VARCHAR(200), IN `p_descripcion` TEXT, IN `p_categoria` VARCHAR(50), IN `p_tipo` VARCHAR(50), IN `p_prioridad` VARCHAR(10), IN `p_ubicacion_direccion` VARCHAR(255), IN `p_ubicacion_latitud` DECIMAL(10,8), IN `p_ubicacion_longitud` DECIMAL(11,8))  BEGIN
    DECLARE nueva_queja_id INT;
    
    INSERT INTO quejas (
        usuario_id, titulo, descripcion, categoria, tipo, prioridad,
        ubicacion_direccion, ubicacion_latitud, ubicacion_longitud
    ) VALUES (
        p_usuario_id, p_titulo, p_descripcion, p_categoria, p_tipo, p_prioridad,
        p_ubicacion_direccion, p_ubicacion_latitud, p_ubicacion_longitud
    );
    
    SET nueva_queja_id = LAST_INSERT_ID();
    
    -- Crear registro en historial
    INSERT INTO historial_quejas (queja_id, estatus_anterior, estatus_nuevo, comentario)
    VALUES (nueva_queja_id, NULL, 'Pendiente', 'Queja registrada en el sistema');
    
    -- Crear notificación para el usuario
    INSERT INTO notificaciones (usuario_id, queja_id, titulo, mensaje, tipo)
    VALUES (p_usuario_id, nueva_queja_id, 'Queja registrada', 'Tu reporte ha sido registrado exitosamente.', 'success');
    
    SELECT nueva_queja_id as id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_quejas`
--

DROP TABLE IF EXISTS `archivos_quejas`;
CREATE TABLE IF NOT EXISTS `archivos_quejas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `queja_id` int NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_archivo` enum('imagen','pdf','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanio_bytes` int NOT NULL,
  `extension` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_queja` (`queja_id`),
  KEY `idx_tipo` (`tipo_archivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_quejas`
--

DROP TABLE IF EXISTS `comentarios_quejas`;
CREATE TABLE IF NOT EXISTS `comentarios_quejas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `queja_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_publico` tinyint(1) DEFAULT '1',
  `fecha_comentario` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_queja` (`queja_id`),
  KEY `idx_fecha` (`fecha_comentario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comentarios_quejas`
--

INSERT INTO `comentarios_quejas` (`id`, `queja_id`, `usuario_id`, `comentario`, `es_publico`, `fecha_comentario`) VALUES
(1, 2, 1, 'Hemos recibido su reporte. El equipo de mantenimiento visitará la zona en los próximos días.', 1, '2026-02-24 17:49:50'),
(2, 4, 1, 'Gracias por su sugerencia. Estamos analizando el tráfico vehicular y peatonal de la zona.', 1, '2026-02-24 17:49:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_quejas`
--

DROP TABLE IF EXISTS `historial_quejas`;
CREATE TABLE IF NOT EXISTS `historial_quejas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `queja_id` int NOT NULL,
  `estatus_anterior` enum('Pendiente','En Proceso','Resuelta','Rechazada') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estatus_nuevo` enum('Pendiente','En Proceso','Resuelta','Rechazada') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `admin_id` int DEFAULT NULL,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_queja` (`queja_id`),
  KEY `idx_fecha` (`fecha_cambio`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_quejas`
--

INSERT INTO `historial_quejas` (`id`, `queja_id`, `estatus_anterior`, `estatus_nuevo`, `comentario`, `admin_id`, `fecha_cambio`) VALUES
(1, 1, NULL, 'Pendiente', 'Queja recibida y registrada en el sistema', NULL, '2026-02-24 17:49:34'),
(2, 2, NULL, 'Pendiente', 'Queja recibida y registrada en el sistema', NULL, '2026-02-24 17:49:34'),
(3, 2, 'Pendiente', 'En Proceso', 'Se ha asignado el caso al departamento de obras públicas', 1, '2026-02-24 17:49:34'),
(4, 3, NULL, 'Pendiente', 'Queja recibida y registrada en el sistema', NULL, '2026-02-24 17:49:34'),
(5, 4, NULL, 'Pendiente', 'Queja recibida y registrada en el sistema', NULL, '2026-02-24 17:49:34'),
(6, 4, 'Pendiente', 'En Proceso', 'Se está evaluando la viabilidad de la propuesta', 1, '2026-02-24 17:49:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `queja_id` int DEFAULT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('info','success','warning','error') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `leida` tinyint(1) DEFAULT '0',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `queja_id` (`queja_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_leida` (`leida`),
  KEY `idx_fecha` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quejas`
--

DROP TABLE IF EXISTS `quejas`;
CREATE TABLE IF NOT EXISTS `quejas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('Servicios Públicos','Infraestructura','Seguridad','Medio Ambiente','Salud','Transporte','Otros') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('Queja','Sugerencia','Petición') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estatus` enum('Pendiente','En Proceso','Resuelta','Rechazada') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  `prioridad` enum('Baja','Media','Alta') COLLATE utf8mb4_unicode_ci DEFAULT 'Media',
  `ubicacion_direccion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion_latitud` decimal(10,8) DEFAULT NULL,
  `ubicacion_longitud` decimal(11,8) DEFAULT NULL,
  `respuesta_admin` text COLLATE utf8mb4_unicode_ci,
  `admin_id` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_estatus` (`estatus`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_usuario_estatus` (`usuario_id`,`estatus`),
  KEY `idx_categoria_estatus` (`categoria`,`estatus`),
  KEY `idx_fecha_estatus` (`fecha_creacion`,`estatus`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `quejas`
--

INSERT INTO `quejas` (`id`, `usuario_id`, `titulo`, `descripcion`, `categoria`, `tipo`, `estatus`, `prioridad`, `ubicacion_direccion`, `ubicacion_latitud`, `ubicacion_longitud`, `respuesta_admin`, `admin_id`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 2, 'Bache peligroso en calle principal', 'Hay un bache muy grande en la calle 5 de Mayo esquina con Hidalgo que está causando accidentes. Es urgente que lo reparen antes de que ocurra algo grave.', 'Infraestructura', 'Queja', 'Pendiente', 'Alta', 'Calle 5 de Mayo #123, Centro', '19.43260800', '-99.13320900', NULL, NULL, '2026-02-24 17:49:09', '2026-02-24 17:49:09'),
(2, 2, 'Falta de alumbrado público', 'La zona del parque central está completamente oscura en las noches. Los vecinos tenemos miedo de transitar por ahí. Necesitamos más luminarias.', 'Servicios Públicos', 'Petición', 'En Proceso', 'Media', 'Parque Central, Col. Centro', '19.43327000', '-99.13320000', NULL, NULL, '2026-02-24 17:49:09', '2026-02-24 17:49:09'),
(3, 3, 'Basura acumulada en esquina', 'Lleva más de una semana acumulada la basura en la esquina de Juárez y Morelos. Está generando malos olores y atrayendo plagas.', 'Medio Ambiente', 'Queja', 'Pendiente', 'Alta', 'Esquina Juárez y Morelos', '19.43458000', '-99.13410000', NULL, NULL, '2026-02-24 17:49:09', '2026-02-24 17:49:09'),
(4, 4, 'Solicitud de semáforo peatonal', 'Es muy peligroso cruzar la avenida principal. Sugiero instalar un semáforo peatonal para mayor seguridad de niños y adultos mayores.', 'Seguridad', 'Sugerencia', 'En Proceso', 'Media', 'Av. Principal #456', '19.43589000', '-99.13520000', NULL, NULL, '2026-02-24 17:49:09', '2026-02-24 17:49:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('ciudadano','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'ciudadano',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_sesion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `telefono`, `rol`, `activo`, `fecha_registro`, `ultima_sesion`) VALUES
(1, 'Administrador Municipal', 'admin@municipal.gob.mx', '$2b$10$rN9QzJX8Hq5vF3YZkKh9OeX9WJKzJXhF.KxF9WJKzJXhF.KxF9W', '555-0000', 'admin', 1, '2026-02-24 17:47:55', NULL),
(2, 'Juan Pérez García', 'juan@example.com', '$2b$10$rN9QzJX8Hq5vF3YZkKh9OeX9WJKzJXhF.KxF9WJKzJXhF.KxF9W', '555-1234', 'ciudadano', 1, '2026-02-24 17:48:35', NULL),
(3, 'María López Martínez', 'maria@example.com', '$2b$10$rN9QzJX8Hq5vF3YZkKh9OeX9WJKzJXhF.KxF9WJKzJXhF.KxF9W', '555-5678', 'ciudadano', 1, '2026-02-24 17:48:35', NULL),
(4, 'Carlos Rodríguez Sánchez', 'carlos@example.com', '$2b$10$rN9QzJX8Hq5vF3YZkKh9OeX9WJKzJXhF.KxF9WJKzJXhF.KxF9W', '555-9012', 'ciudadano', 1, '2026-02-24 17:48:35', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_estadisticas_generales`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_estadisticas_generales`;
CREATE TABLE IF NOT EXISTS `vista_estadisticas_generales` (
`en_proceso` decimal(23,0)
,`infraestructura` decimal(23,0)
,`medio_ambiente` decimal(23,0)
,`pendientes` decimal(23,0)
,`rechazadas` decimal(23,0)
,`resueltas` decimal(23,0)
,`seguridad` decimal(23,0)
,`servicios_publicos` decimal(23,0)
,`total_peticiones` decimal(23,0)
,`total_quejas` bigint
,`total_quejas_tipo` decimal(23,0)
,`total_sugerencias` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_estadisticas_usuario`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_estadisticas_usuario`;
CREATE TABLE IF NOT EXISTS `vista_estadisticas_usuario` (
`email` varchar(100)
,`en_proceso` decimal(23,0)
,`nombre` varchar(100)
,`pendientes` decimal(23,0)
,`rechazadas` decimal(23,0)
,`resueltas` decimal(23,0)
,`total_quejas` bigint
,`usuario_id` int
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_quejas_completas`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_quejas_completas`;
CREATE TABLE IF NOT EXISTS `vista_quejas_completas` (
`admin_nombre` varchar(100)
,`categoria` enum('Servicios Públicos','Infraestructura','Seguridad','Medio Ambiente','Salud','Transporte','Otros')
,`descripcion` text
,`estatus` enum('Pendiente','En Proceso','Resuelta','Rechazada')
,`fecha_actualizacion` timestamp
,`fecha_creacion` timestamp
,`id` int
,`prioridad` enum('Baja','Media','Alta')
,`tipo` enum('Queja','Sugerencia','Petición')
,`titulo` varchar(200)
,`total_archivos` bigint
,`total_comentarios` bigint
,`ubicacion_direccion` varchar(255)
,`usuario_email` varchar(100)
,`usuario_id` int
,`usuario_nombre` varchar(100)
,`usuario_telefono` varchar(20)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_estadisticas_generales`
--
DROP TABLE IF EXISTS `vista_estadisticas_generales`;

DROP VIEW IF EXISTS `vista_estadisticas_generales`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_estadisticas_generales`  AS SELECT count(0) AS `total_quejas`, sum((case when (`quejas`.`estatus` = 'Pendiente') then 1 else 0 end)) AS `pendientes`, sum((case when (`quejas`.`estatus` = 'En Proceso') then 1 else 0 end)) AS `en_proceso`, sum((case when (`quejas`.`estatus` = 'Resuelta') then 1 else 0 end)) AS `resueltas`, sum((case when (`quejas`.`estatus` = 'Rechazada') then 1 else 0 end)) AS `rechazadas`, sum((case when (`quejas`.`categoria` = 'Servicios Públicos') then 1 else 0 end)) AS `servicios_publicos`, sum((case when (`quejas`.`categoria` = 'Infraestructura') then 1 else 0 end)) AS `infraestructura`, sum((case when (`quejas`.`categoria` = 'Seguridad') then 1 else 0 end)) AS `seguridad`, sum((case when (`quejas`.`categoria` = 'Medio Ambiente') then 1 else 0 end)) AS `medio_ambiente`, sum((case when (`quejas`.`tipo` = 'Queja') then 1 else 0 end)) AS `total_quejas_tipo`, sum((case when (`quejas`.`tipo` = 'Sugerencia') then 1 else 0 end)) AS `total_sugerencias`, sum((case when (`quejas`.`tipo` = 'Petición') then 1 else 0 end)) AS `total_peticiones` FROM `quejas` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_estadisticas_usuario`
--
DROP TABLE IF EXISTS `vista_estadisticas_usuario`;

DROP VIEW IF EXISTS `vista_estadisticas_usuario`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_estadisticas_usuario`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`nombre` AS `nombre`, `u`.`email` AS `email`, count(`q`.`id`) AS `total_quejas`, sum((case when (`q`.`estatus` = 'Pendiente') then 1 else 0 end)) AS `pendientes`, sum((case when (`q`.`estatus` = 'En Proceso') then 1 else 0 end)) AS `en_proceso`, sum((case when (`q`.`estatus` = 'Resuelta') then 1 else 0 end)) AS `resueltas`, sum((case when (`q`.`estatus` = 'Rechazada') then 1 else 0 end)) AS `rechazadas` FROM (`usuarios` `u` left join `quejas` `q` on((`u`.`id` = `q`.`usuario_id`))) WHERE (`u`.`rol` = 'ciudadano') GROUP BY `u`.`id`, `u`.`nombre`, `u`.`email` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_quejas_completas`
--
DROP TABLE IF EXISTS `vista_quejas_completas`;

DROP VIEW IF EXISTS `vista_quejas_completas`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_quejas_completas`  AS SELECT `q`.`id` AS `id`, `q`.`titulo` AS `titulo`, `q`.`descripcion` AS `descripcion`, `q`.`categoria` AS `categoria`, `q`.`tipo` AS `tipo`, `q`.`estatus` AS `estatus`, `q`.`prioridad` AS `prioridad`, `q`.`ubicacion_direccion` AS `ubicacion_direccion`, `q`.`fecha_creacion` AS `fecha_creacion`, `q`.`fecha_actualizacion` AS `fecha_actualizacion`, `u`.`id` AS `usuario_id`, `u`.`nombre` AS `usuario_nombre`, `u`.`email` AS `usuario_email`, `u`.`telefono` AS `usuario_telefono`, `a`.`nombre` AS `admin_nombre`, (select count(0) from `archivos_quejas` where (`archivos_quejas`.`queja_id` = `q`.`id`)) AS `total_archivos`, (select count(0) from `comentarios_quejas` where (`comentarios_quejas`.`queja_id` = `q`.`id`)) AS `total_comentarios` FROM ((`quejas` `q` join `usuarios` `u` on((`q`.`usuario_id` = `u`.`id`))) left join `usuarios` `a` on((`q`.`admin_id` = `a`.`id`))) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_quejas`
--
ALTER TABLE `archivos_quejas`
  ADD CONSTRAINT `archivos_quejas_ibfk_1` FOREIGN KEY (`queja_id`) REFERENCES `quejas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comentarios_quejas`
--
ALTER TABLE `comentarios_quejas`
  ADD CONSTRAINT `comentarios_quejas_ibfk_1` FOREIGN KEY (`queja_id`) REFERENCES `quejas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_quejas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_quejas`
--
ALTER TABLE `historial_quejas`
  ADD CONSTRAINT `historial_quejas_ibfk_1` FOREIGN KEY (`queja_id`) REFERENCES `quejas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_quejas_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificaciones_ibfk_2` FOREIGN KEY (`queja_id`) REFERENCES `quejas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `quejas`
--
ALTER TABLE `quejas`
  ADD CONSTRAINT `quejas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quejas_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
