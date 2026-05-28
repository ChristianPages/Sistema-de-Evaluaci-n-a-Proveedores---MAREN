-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-05-2026 a las 21:11:42
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u699112877_edms_app`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones`
--

CREATE TABLE `evaluaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `representante` varchar(255) DEFAULT NULL,
  `email_contacto` varchar(255) DEFAULT NULL,
  `departamento` varchar(255) DEFAULT NULL,
  `servicio` varchar(255) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `puntaje` decimal(5,2) DEFAULT NULL,
  `nivel` varchar(20) DEFAULT NULL,
  `respuestas_json` text DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_completas`
--

CREATE TABLE `evaluaciones_completas` (
  `id` int(11) NOT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `representante` varchar(255) DEFAULT NULL,
  `servicio` varchar(255) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `puntaje_decimal` decimal(5,2) DEFAULT NULL,
  `nivel_resultado` varchar(20) DEFAULT NULL,
  `archivo_ruta` varchar(255) DEFAULT NULL,
  `comentarios_adicionales` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','usuario','proveedor') NOT NULL DEFAULT 'proveedor',
  `puede_editar` tinyint(1) DEFAULT 0,
  `empresa_nombre` varchar(255) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `ultima_actividad` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_usuario`, `email`, `password_hash`, `rol`, `puede_editar`, `empresa_nombre`, `empresa_id`, `ultima_actividad`) VALUES
(1, 'Christian', NULL, '$2y$10$PmJ57x/S6vNXhXF0EeYHke9ZQlBFIvomgbNgQJeRHN/anDVhmpzXO', 'admin', 0, NULL, NULL, '2026-05-28 12:14:49'),
(4, 'especialista.qhse@energydrilling.com.mx', NULL, '$2y$10$AfnnKt1m3a7/S6Vob2rEVOkdHtXB3sGiAnLnDpSJ.WkQi/p6xLGpa', 'admin', 0, NULL, NULL, '2026-05-28 00:56:53');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `evaluaciones_completas`
--
ALTER TABLE `evaluaciones_completas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_completas`
--
ALTER TABLE `evaluaciones_completas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
