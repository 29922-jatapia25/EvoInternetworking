CREATE DATABASE IF NOT EXISTS evosalud_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE evosalud_db;

-- Usuarios base (login)
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('doctor','paciente','admin') NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Datos extra del doctor
CREATE TABLE IF NOT EXISTS doctores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  especialidad VARCHAR(120),
  cedula_profesional VARCHAR(30),
  telefono VARCHAR(30),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Datos extra del paciente
CREATE TABLE IF NOT EXISTS pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  cedula VARCHAR(20),
  fecha_nacimiento DATE,
  telefono VARCHAR(30),
  direccion VARCHAR(200),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- EJEMPLO (después reemplazas password_hash por uno real)
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Dr. Carlos', 'doctor@evosalud.com', '$2y$10$REEMPLAZA_ESTE_HASH', 'doctor'),
('Ana Paciente', 'paciente@evosalud.com', '$2y$10$REEMPLAZA_ESTE_HASH', 'paciente');

INSERT INTO doctores (usuario_id, especialidad, cedula_profesional, telefono)
VALUES ((SELECT id FROM usuarios WHERE email='doctor@evosalud.com'), 'Medicina General', 'MG-12345', '0999999999');

INSERT INTO pacientes (usuario_id, cedula, fecha_nacimiento, telefono, direccion)
VALUES ((SELECT id FROM usuarios WHERE email='paciente@evosalud.com'), '0102030405', '2004-05-10', '0988888888', 'Guayaquil');

ALTER TABLE usuarios
  MODIFY password_hash CHAR(64) NOT NULL;

UPDATE usuarios
SET password_hash = SHA2('Doctor123*', 256)
WHERE email='doctor@evosalud.com';

UPDATE usuarios
SET password_hash = SHA2('Paciente123*', 256)
WHERE email='paciente@evosalud.com';
