SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `usuario`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `usuario` ;

CREATE TABLE IF NOT EXISTS `usuario` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_usuario` VARCHAR(60) NOT NULL,
  `nombre_usuario_limpio` VARCHAR(60) NOT NULL,
  `usuario_pass` VARCHAR(255) NOT NULL,
  `usuario_salt` VARCHAR(255) NOT NULL,
  `es_admin` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `nombre_usuario_UNIQUE` (`nombre_usuario` ASC),
  UNIQUE INDEX `nombre_usuario_limpio_UNIQUE` (`nombre_usuario_limpio` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sesion`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sesion` ;

CREATE TABLE IF NOT EXISTS `sesion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash_sesion` VARCHAR(255) NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `creada` BIGINT UNSIGNED NOT NULL,
  `ultima_visita` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `usuario_id_fk_idx` (`usuario_id` ASC),
  CONSTRAINT `sesion_usuario_id_fk`
  FOREIGN KEY (`usuario_id`)
  REFERENCES `usuario` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `nivel`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `nivel` ;

CREATE TABLE IF NOT EXISTS `nivel` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `asignatura`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `asignatura` ;

CREATE TABLE IF NOT EXISTS `asignatura` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nivel_id` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`, `nombre`),
  INDEX `nivel_id_fk_idx` (`nivel_id` ASC),
  CONSTRAINT `asignatura_nivel_id_fk`
    FOREIGN KEY (`nivel_id`)
    REFERENCES `nivel` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `alumno`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `alumno` ;

CREATE TABLE IF NOT EXISTS `alumno` (
  `nie` BIGINT UNSIGNED NOT NULL,
  `nombre` VARCHAR(70) NOT NULL,
  `apellidos` VARCHAR(70) NOT NULL DEFAULT '',
  `telefono` VARCHAR(9) NOT NULL DEFAULT '',
  PRIMARY KEY (`nie`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `libro`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `libro` ;

CREATE TABLE IF NOT EXISTS `libro` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `isbn` VARCHAR(13) NOT NULL,
  `titulo` VARCHAR(80) NOT NULL,
  `autor` VARCHAR(85) NOT NULL,
  `anio` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `isbn_UNIQUE` (`isbn` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `libro_asignatura`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `libro_asignatura` ;

CREATE TABLE IF NOT EXISTS `libro_asignatura` (
  `libro_id` INT UNSIGNED NOT NULL,
  `asignatura_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`libro_id`, `asignatura_id`),
  INDEX `libro_asignatura_asignatura_id_idx` (`asignatura_id` ASC),
  CONSTRAINT `libro_asignatura_libro_id`
    FOREIGN KEY (`libro_id`)
    REFERENCES `libro` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `libro_asignatura_asignatura_id`
    FOREIGN KEY (`asignatura_id`)
    REFERENCES `asignatura` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ejemplar`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ejemplar` ;

CREATE TABLE IF NOT EXISTS `ejemplar` (
  `codigo` INT UNSIGNED NOT NULL,
  `libro_id` INT UNSIGNED NOT NULL,
  `estado` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `alumno_nie` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`codigo`),
  INDEX `ejemplar_alumno_nie_fk_idx` (`alumno_nie` ASC),
  CONSTRAINT `ejemplar_alumno_nie_fk`
    FOREIGN KEY (`alumno_nie`)
    REFERENCES `alumno` (`nie`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX `ejemplar_libro_id_fk_idx` (`libro_id` ASC),
  CONSTRAINT `ejemplar_libro_id_fk`
    FOREIGN KEY (`libro_id`)
    REFERENCES `libro` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `historial`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `historial` ;

CREATE TABLE IF NOT EXISTS `historial` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ejemplar_codigo` INT UNSIGNED NOT NULL,
  `alumno_nie` BIGINT UNSIGNED NULL,
  `estado` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `fecha` BIGINT UNSIGNED NOT NULL,
  `anotacion` BLOB NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `historial_ejemplar_codigo_fk_idx` (`ejemplar_codigo` ASC),
  INDEX `historial_alumno_nie_fk_idx` (`alumno_nie` ASC),
  CONSTRAINT `historial_ejemplar_codigo_fk`
    FOREIGN KEY (`ejemplar_codigo`)
    REFERENCES `ejemplar` (`codigo`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `historial_alumno_nie_fk`
    FOREIGN KEY (`alumno_nie`)
    REFERENCES `alumno` (`nie`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `historial_usuario_id_fk`
    FOREIGN KEY (`usuario_id`)
    REFERENCES `usuario` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
