-- phpMyAdmin SQL Dump mejorado con datos de prueba
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Elimnar o crear base de datos bdgestion
-- --------------------------------------------------------
drop database if exists `bdgestion`;

CREATE DATABASE IF NOT EXISTS `bdgestion`
  CHARACTER SET utf8
  COLLATE utf8_spanish2_ci;
USE `bdgestion`;

-- --------------------------------------------------------
-- Tabla usuarios
-- --------------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `id_rol` varchar(20) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- Datos de prueba usuarios
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellidos`, `email`, `contrasena`, `telefono`, `id_rol`, `fecha_registro`) VALUES
(1, 'Adam', 'El Bakkali', 'adam@mail.com', '$2y$10$NkkAGk2RiEHGKB1ilf.DR.OxtdkmSuVas61GrHZlcFzNSKZzPXSX.', '631694288', 'Mecanico', NOW()),
(NULL, 'Carlos', 'Ruiz Gómez', 'carlos@mail.com', '$2y$10$Oq/wArQlen53gVtzG9VtuOaTKla2O9nDqb/xFGUCp28ofhidFZaCq', '600111222', 'Cliente', NOW()),
(NULL, 'María', 'López Sánchez', 'maria@mail.com', '$2y$10$7/IcXEd2jtLnm1u70.0aI.Epq3R45ELABR.ucACzSjALoISjvWOk2', '600333444', 'Cliente', NOW());

-- --------------------------------------------------------
-- Tabla vehiculos
-- --------------------------------------------------------
CREATE TABLE `vehiculos` (
  `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `anio` int(11) NOT NULL,
  `matricula` varchar(10) NOT NULL,
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `vehiculos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- Vehículos de prueba
INSERT INTO `vehiculos` (`id_vehiculo`, `id_usuario`, `marca`, `modelo`, `anio`, `matricula`) VALUES
(NULL, 2, 'Seat', 'Ibiza', 2018, '3242HJK'),
(NULL, 3, 'Honda', 'Civic', 2016, '2187GPS'),
(NULL, 2, 'Volkswagen', 'Golf', 2019, '4521KLM'),
(NULL, 3, 'Ford', 'Focus', 2017, '8894JTR');

-- --------------------------------------------------------
-- Tabla estados_vehiculo
-- --------------------------------------------------------
CREATE TABLE `estados_vehiculo` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `id_vehiculo` int(11) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_actualizacion` datetime NOT NULL,
  PRIMARY KEY (`id_estado`),
  KEY `id_vehiculo` (`id_vehiculo`),
  CONSTRAINT `estados_vehiculo_ibfk_1`
  FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`)
  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- Estados de prueba
INSERT INTO `estados_vehiculo` (`id_estado`, `id_vehiculo`, `estado`, `descripcion`, `fecha_actualizacion`) VALUES
(NULL, 1, 'Pendiente', 'Vehículo pendiente de diagnóstico', NOW()),
(NULL, 2, 'En proceso', 'Cambio de embrague en curso', NOW()),
(NULL, 3, 'Finalizado', 'Revisión completa finalizada', NOW()),
(NULL, 4, 'En proceso', 'Sustitución de frenos delanteros', NOW());

-- --------------------------------------------------------
-- Tabla reparaciones (incluye horas_mano_obra y precio_hora)
-- --------------------------------------------------------
CREATE TABLE `reparaciones` (
  `id_reparacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_vehiculo` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_estimada` date DEFAULT NULL,
  `costo_estimado` decimal(10,2) DEFAULT NULL,
  `costo_final` decimal(10,2) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `horas_mano_obra` decimal(5,2) DEFAULT NULL,
  `precio_hora` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id_reparacion`),
  KEY `id_vehiculo` (`id_vehiculo`),
  CONSTRAINT `reparaciones_ibfk_1`
  FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`)
  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- Reparaciones de prueba
INSERT INTO `reparaciones` (`id_reparacion`, `id_vehiculo`, `titulo`, `descripcion`, `fecha_estimada`, `costo_estimado`, `estado`, `horas_mano_obra`, `precio_hora`) VALUES
(NULL, 1, 'Diagnóstico motor', 'Revisión general por fallo de potencia', '2026-01-25', 120.00, 'Pendiente', NULL, NULL),
(NULL, 2, 'Cambio embrague', 'Sustitución completa del kit de embrague', '2026-01-26', 850.00, 'En proceso', NULL, NULL),
(NULL, 3, 'Mantenimiento anual', 'Aceite, filtros y revisión general', '2026-01-27', 180.00, 'Finalizado', 2.00, 45.00),
(NULL, 4, 'Cambio frenos', 'Discos y pastillas delanteras', '2026-01-28', 300.00, 'En proceso', NULL, NULL);

-- --------------------------------------------------------
-- Tabla productos
-- --------------------------------------------------------
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad_disponible` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- Productos de prueba
INSERT INTO `productos` (`id_producto`, `nombre`, `descripcion`, `cantidad_disponible`, `precio_unitario`) VALUES
(NULL, 'Aceite 5W30', 'Aceite sintético motor', 50, 9.99),
(NULL, 'Filtro de aceite', 'Filtro compatible multimarca', 30, 6.50),
(NULL, 'Kit embrague', 'Kit completo embrague', 5, 320.00),
(NULL, 'Pastillas de freno', 'Pastillas delanteras', 20, 45.00),
(NULL, 'Discos de freno', 'Discos ventilados', 10, 85.00);

-- --------------------------------------------------------
-- Tabla productos_reparacion
-- --------------------------------------------------------
CREATE TABLE `productos_reparacion` (
  `id_producto_reparacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_reparacion` int(11) NOT NULL,
  `cantidad_usada` int(11) NOT NULL,
  PRIMARY KEY (`id_producto_reparacion`),
  KEY `id_producto` (`id_producto`),
  KEY `id_reparacion` (`id_reparacion`),
  CONSTRAINT `productos_reparacion_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `productos_reparacion_ibfk_2` FOREIGN KEY (`id_reparacion`) REFERENCES `reparaciones` (`id_reparacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

COMMIT;
