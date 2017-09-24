-- MySQL Script generated by MySQL Workbench
-- sam 23 sep 2017 19:14:36 CEST
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

-- -----------------------------------------------------
-- Schema pcea
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS pcea ;

-- -----------------------------------------------------
-- Schema pcea
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS pcea DEFAULT CHARACTER SET utf8 ;
USE pcea ;

-- -----------------------------------------------------
-- Table pcea.users
-- -----------------------------------------------------
DROP TABLE IF EXISTS pcea.users ;

CREATE TABLE IF NOT EXISTS pcea.users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(45) NOT NULL,
  password VARCHAR(45) NOT NULL,
  PRIMARY KEY (id))
ENGINE = InnoDB
DEFAULT CHARACTER SET = big5;


-- -----------------------------------------------------
-- Table pcea.groups
-- -----------------------------------------------------
DROP TABLE IF EXISTS pcea.groups ;

CREATE TABLE IF NOT EXISTS pcea.groups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(45) NULL,
  PRIMARY KEY (id))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table pcea.events
-- -----------------------------------------------------
DROP TABLE IF EXISTS pcea.events ;

CREATE TABLE IF NOT EXISTS pcea.events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(45) NOT NULL,
  groups_id INT UNSIGNED NOT NULL,
  currency VARCHAR(45) NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_events_groups1_idx (groups_id ASC),
  CONSTRAINT fk_events_groups1
    FOREIGN KEY (groups_id)
    REFERENCES pcea.groups (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table pcea.spents
-- -----------------------------------------------------
DROP TABLE IF EXISTS pcea.spents ;

CREATE TABLE IF NOT EXISTS pcea.spents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(45) NOT NULL,
  amount DECIMAL NOT NULL,
  date TIMESTAMP NOT NULL,
  buyer INT UNSIGNED NOT NULL,
  events_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_buyer (buyer ASC),
  INDEX fk_spents_events1_idx (events_id ASC),
  CONSTRAINT fk_buyer
    FOREIGN KEY (buyer)
    REFERENCES pcea.users (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_spents_events1
    FOREIGN KEY (events_id)
    REFERENCES pcea.events (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table pcea.users_has_groups
-- -----------------------------------------------------
DROP TABLE IF EXISTS pcea.users_has_groups ;

CREATE TABLE IF NOT EXISTS pcea.users_has_groups (
  users_id INT UNSIGNED NOT NULL,
  groups_id INT UNSIGNED NOT NULL,
  user_weight INT NOT NULL DEFAULT 1,
  PRIMARY KEY (users_id, groups_id),
  INDEX fk_users_has_groups_groups1_idx (groups_id ASC),
  INDEX fk_users_has_groups_users1_idx (users_id ASC),
  CONSTRAINT fk_users_has_groups_users1
    FOREIGN KEY (users_id)
    REFERENCES pcea.users (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_users_has_groups_groups1
    FOREIGN KEY (groups_id)
    REFERENCES pcea.groups (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = big5;


-- -----------------------------------------------------
-- Table pcea.users_has_spents
-- -----------------------------------------------------
DROP TABLE IF EXISTS pcea.users_has_spents ;

CREATE TABLE IF NOT EXISTS pcea.users_has_spents (
  users_id INT UNSIGNED NOT NULL,
  spents_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (users_id, spents_id),
  INDEX fk_users_has_spents_spents1_idx (spents_id ASC),
  CONSTRAINT fk_users_has_spents_users1
    FOREIGN KEY (users_id)
    REFERENCES pcea.users (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_users_has_spents_spents1
    FOREIGN KEY (spents_id)
    REFERENCES pcea.spents (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = big5;