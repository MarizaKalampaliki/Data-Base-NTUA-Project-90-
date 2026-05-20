SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema hospitaldb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `hospitaldb` DEFAULT CHARACTER SET utf8 ;
USE `hospitaldb` ;

-- -----------------------------------------------------
-- Table `hospitaldb`.`staff`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`staff` (
  `amka` CHAR(11) NOT NULL,
  `first_name` VARCHAR(45) NOT NULL,
  `last_name` VARCHAR(45) NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `email` VARCHAR(100) NULL,
  `phone` VARCHAR(45) NULL,
  `hire_date` DATE NOT NULL,
  `staff_type` VARCHAR(30) NOT NULL,
  PRIMARY KEY (`amka`),
  UNIQUE INDEX `staff_email_UNIQUE` (`email` ASC) VISIBLE,
  CONSTRAINT `chk_staff_type`
    CHECK (`staff_type` IN ('Doctor', 'Nurse', 'Administrative'))
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`doctor`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`doctor` (
  `amka` CHAR(11) NOT NULL,
  `licence_number` VARCHAR(45) NOT NULL,
  `speciality` VARCHAR(45) NOT NULL,
  `rank` VARCHAR(45) NOT NULL,
  `supervisor_amka` CHAR(11) NULL,
  PRIMARY KEY (`amka`),
  UNIQUE INDEX `licence_number_UNIQUE` (`licence_number` ASC) VISIBLE,
  INDEX `doc_supervisor_idx` (`supervisor_amka` ASC) VISIBLE,
  CONSTRAINT `doctor_staff`
    FOREIGN KEY (`amka`)
    REFERENCES `hospitaldb`.`staff` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_supervisor`
    FOREIGN KEY (`supervisor_amka`)
    REFERENCES `hospitaldb`.`doctor` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`department`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`department` (
  `department_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `bed_count` INT NULL,
  `floor` VARCHAR(20) NULL,
  `building` VARCHAR(45) BINARY NULL,
  `head_doctor_amka` CHAR(11) NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE,
  INDEX `department_doctor_idx` (`head_doctor_amka` ASC) VISIBLE,
  CONSTRAINT `department_doctor`
    FOREIGN KEY (`head_doctor_amka`)
    REFERENCES `hospitaldb`.`doctor` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`nurse`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`nurse` (
  `amka` CHAR(11) NOT NULL,
  `grade` VARCHAR(45) NOT NULL,
  `department_id` INT NOT NULL,
  PRIMARY KEY (`amka`),
  INDEX `nurse_dept_idx` (`department_id` ASC) VISIBLE,
  CONSTRAINT `nurse_staff`
    FOREIGN KEY (`amka`)
    REFERENCES `hospitaldb`.`staff` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `nurse_dept`
    FOREIGN KEY (`department_id`)
    REFERENCES `hospitaldb`.`department` (`department_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`admin_staff`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`admin_staff` (
  `amka` CHAR(11) NOT NULL,
  `role` VARCHAR(45) NOT NULL,
  `department_id` INT NOT NULL,
  `office` VARCHAR(45) NULL,
  PRIMARY KEY (`amka`),
  INDEX `admin_dept_idx` (`department_id` ASC) VISIBLE,
  CONSTRAINT `adm_staff`
    FOREIGN KEY (`amka`)
    REFERENCES `hospitaldb`.`staff` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `admin_dept`
    FOREIGN KEY (`department_id`)
    REFERENCES `hospitaldb`.`department` (`department_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`bed`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`bed` (
  `bed_id` INT NOT NULL AUTO_INCREMENT,
  `bed_number` VARCHAR(45) NOT NULL,
  `type` VARCHAR(45) NOT NULL,
  `status` VARCHAR(45) NOT NULL,
  `department_id` INT NOT NULL,
  PRIMARY KEY (`bed_id`),
  INDEX `bed_dept_idx` (`department_id` ASC) VISIBLE,
  CONSTRAINT `bed_dept`
    FOREIGN KEY (`department_id`)
    REFERENCES `hospitaldb`.`department` (`department_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`doctor_department`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`doctor_department` (
  `doctor_amka` CHAR(11) NOT NULL,
  `department_id` INT NOT NULL,
  PRIMARY KEY (`doctor_amka`, `department_id`),
  INDEX `fk_docdept_department_idx` (`department_id` ASC) VISIBLE,
  CONSTRAINT `fk_docept_doctor`
    FOREIGN KEY (`doctor_amka`)
    REFERENCES `hospitaldb`.`doctor` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_docdept_department`
    FOREIGN KEY (`department_id`)
    REFERENCES `hospitaldb`.`department` (`department_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`insurance`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`insurance` (
  `provider_id` INT NOT NULL AUTO_INCREMENT,
  `insurance_number` VARCHAR(45) NULL,
  `provider_name` VARCHAR(100) NOT NULL,
  `provider_type` VARCHAR(45) NULL,
  PRIMARY KEY (`provider_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`patient`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`patient` (
  `amka` CHAR(11) NOT NULL,
  `first_name` VARCHAR(45) NOT NULL,
  `last_name` VARCHAR(45) NOT NULL,
  `father_name` VARCHAR(45) NULL,
  `date_of_birth` DATE NOT NULL,
  `gender` VARCHAR(20) NULL,
  `weight` DECIMAL(5,2) NULL,
  `height` DECIMAL(5,2) NULL,
  `address` VARCHAR(150) NULL,
  `phone` VARCHAR(45) NULL,
  `email` VARCHAR(100) NULL,
  `profession` VARCHAR(100) NULL,
  `nationality` VARCHAR(45) NULL,
  `provider_id` INT NULL,
  PRIMARY KEY (`amka`),
  INDEX `patient_insurance_idx` (`provider_id` ASC) VISIBLE,
  CONSTRAINT `patient_insurance`
    FOREIGN KEY (`provider_id`)
    REFERENCES `hospitaldb`.`insurance` (`provider_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`emergency_contact`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`emergency_contact` (
  `contact_id` INT NOT NULL AUTO_INCREMENT,
  `patient_amka` CHAR(11) NOT NULL,
  `first_name` VARCHAR(45) NOT NULL,
  `last_name` VARCHAR(45) NOT NULL,
  `phone` VARCHAR(45) NOT NULL,
  `relationship` VARCHAR(45) NULL,
  PRIMARY KEY (`contact_id`),
  INDEX `emergency_patient_idx` (`patient_amka` ASC) VISIBLE,
  CONSTRAINT `emergency_patient`
    FOREIGN KEY (`patient_amka`)
    REFERENCES `hospitaldb`.`patient` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`diagnosis`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`diagnosis` (
  `icd10_code` VARCHAR(10) NOT NULL,
  `description` VARCHAR(200) NOT NULL,
  `category_code` VARCHAR(10) NULL,
  `category_name` VARCHAR(150) NULL,
  PRIMARY KEY (`icd10_code`))
ENGINE = InnoDB;



-- -----------------------------------------------------
-- Table `hospitaldb`.`ken`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`ken` (
  `ken_code` VARCHAR(20) NOT NULL,
  `description` VARCHAR(45) NULL,
  `base_cost` DECIMAL(10,2) NOT NULL,
  `expected_duration` INT NOT NULL,
  `extra_daily_cost` DECIMAL(10,2) NULL,
  PRIMARY KEY (`ken_code`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`triage`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`triage` (
  `triage_id` INT NOT NULL AUTO_INCREMENT,
  `patient_amka` CHAR(11) NOT NULL,
  `nurse_amka` CHAR(11) NULL,
  `symptoms` LONGTEXT NULL,
  `priority_level` INT NULL,
`arrival_time` DATETIME NULL,
`waiting_minutes` INT NULL,
`outcome` VARCHAR(45) NULL,
  PRIMARY KEY (`triage_id`),
  INDEX `triage_patient_idx` (`patient_amka` ASC) VISIBLE,
  INDEX `triage_staff_idx` (`nurse_amka` ASC) VISIBLE,
  CONSTRAINT `triage_patient`
    FOREIGN KEY (`patient_amka`)
    REFERENCES `hospitaldb`.`patient` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `triage_staff`
    FOREIGN KEY (`nurse_amka`)
    REFERENCES `hospitaldb`.`nurse` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`hospitalization`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`hospitalization` (
  `hospitalization_id` INT NOT NULL AUTO_INCREMENT,
  `patient_amka` CHAR(11) NOT NULL,
  `department_id` INT NOT NULL,
  `bed_id` INT NOT NULL,
  `ken_code` VARCHAR(20) NOT NULL,
  `admission_date` DATE NOT NULL,
  `discharge_date` DATE NULL,
  `admission_icd10_code` VARCHAR(10) NOT NULL,
  `discharge_icd10_code` VARCHAR(10) NULL,
  `actual_duration` INT NULL,
  `total_cost` DECIMAL(10,2) NULL,
  `triage_id` INT NULL,
  PRIMARY KEY (`hospitalization_id`),
  INDEX `dept_hospitalization_idx` (`department_id` ASC) VISIBLE,
  INDEX `icd10_add_idx` (`admission_icd10_code` ASC) VISIBLE,
  INDEX `icd10_dis_idx` (`discharge_icd10_code` ASC) VISIBLE,
  INDEX `bed_hospitalization_idx` (`bed_id` ASC) VISIBLE,
  INDEX `ken_hospitalization_idx` (`ken_code` ASC) VISIBLE,
  INDEX `patient_hospitalization_idx` (`patient_amka` ASC) VISIBLE,
  INDEX `triage_hospitalization_idx` (`triage_id` ASC) VISIBLE,
  CONSTRAINT `dept_hospitalization`
    FOREIGN KEY (`department_id`)
    REFERENCES `hospitaldb`.`department` (`department_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `icd10_add`
    FOREIGN KEY (`admission_icd10_code`)
    REFERENCES `hospitaldb`.`diagnosis` (`icd10_code`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `icd10_dis`
    FOREIGN KEY (`discharge_icd10_code`)
    REFERENCES `hospitaldb`.`diagnosis` (`icd10_code`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `bed_hospitalization`
    FOREIGN KEY (`bed_id`)
    REFERENCES `hospitaldb`.`bed` (`bed_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `ken_hospitalization`
    FOREIGN KEY (`ken_code`)
    REFERENCES `hospitaldb`.`ken` (`ken_code`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `patient_hospitalization`
    FOREIGN KEY (`patient_amka`)
    REFERENCES `hospitaldb`.`patient` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `triage_hospitalization`
    FOREIGN KEY (`triage_id`)
    REFERENCES `hospitaldb`.`triage` (`triage_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`exam`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`exam` (
  `exam_id` INT NOT NULL AUTO_INCREMENT,
  `hospitalization_id` INT NOT NULL,
  `doctor_amka` CHAR(11) NOT NULL,
  `exam_code` VARCHAR(45) NOT NULL,
  `type` VARCHAR(45) NOT NULL,
  `exam_date` DATETIME NOT NULL,
  `result` LONGTEXT NULL,
  `unit` VARCHAR(45) NULL,
  `cost` DECIMAL(10,2) NULL,
  PRIMARY KEY (`exam_id`),
  INDEX `exam_hospitalization_idx` (`hospitalization_id` ASC) VISIBLE,
  INDEX `exam_doctor_idx` (`doctor_amka` ASC) VISIBLE,
  CONSTRAINT `exam_hospitalization`
    FOREIGN KEY (`hospitalization_id`)
    REFERENCES `hospitaldb`.`hospitalization` (`hospitalization_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `exam_doctor`
    FOREIGN KEY (`doctor_amka`)
    REFERENCES `hospitaldb`.`doctor` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`operating_room`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`operating_room` (
  `room_id` INT NOT NULL AUTO_INCREMENT,
  `room_number` VARCHAR(20) NOT NULL,
  `floor` VARCHAR(45) NULL,
  `building` VARCHAR(45) NULL,
  `type` VARCHAR(45) NULL,
  `status` VARCHAR(45) NULL,
  PRIMARY KEY (`room_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`medical_procedure`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`medical_procedure` (
  `procedure_id` INT NOT NULL AUTO_INCREMENT,
  `hospitalization_id` INT NULL,
  `room_id` INT NULL,
  `procedure_code` VARCHAR(45) NULL,
  `name` VARCHAR(45) NULL,
  `type` VARCHAR(45) NULL,
  `procedure_date` DATETIME NULL,
  `procedure_duration` INT NULL,
  `cost` DECIMAL(10,2) NULL,
  `result` LONGTEXT NULL,
  `head_doctor_amka` CHAR(11) NULL,
  PRIMARY KEY (`procedure_id`),
  INDEX `procedure_hospitalization_idx` (`hospitalization_id` ASC) VISIBLE,
  INDEX `procedure_room_idx` (`room_id` ASC) VISIBLE,
  INDEX `procedure_head_doc_idx` (`head_doctor_amka` ASC) VISIBLE,
  CONSTRAINT `procedure_hospitalization`
    FOREIGN KEY (`hospitalization_id`)
    REFERENCES `hospitaldb`.`hospitalization` (`hospitalization_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `procedure_room`
    FOREIGN KEY (`room_id`)
    REFERENCES `hospitaldb`.`operating_room` (`room_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `procedure_head_doc`
    FOREIGN KEY (`head_doctor_amka`)
    REFERENCES `hospitaldb`.`doctor` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`procedure_staff`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`procedure_staff` (
  `procedure_id` INT NOT NULL,
  `staff_amka` CHAR(11) NOT NULL,
  `role` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`procedure_id`, `staff_amka`),
  INDEX `procedure_staff_idx` (`staff_amka` ASC) VISIBLE,
  CONSTRAINT `procedure_staff`
    FOREIGN KEY (`staff_amka`)
    REFERENCES `hospitaldb`.`staff` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `procedure_procedure`
    FOREIGN KEY (`procedure_id`)
    REFERENCES `hospitaldb`.`medical_procedure` (`procedure_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`drug`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`drug` (
  `drug_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `form` VARCHAR(45) NULL,
  PRIMARY KEY (`drug_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`active_substance`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`active_substance` (
  `substance_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NULL,
  PRIMARY KEY (`substance_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`prescription`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`prescription` (
  `prescription_id` INT NOT NULL AUTO_INCREMENT,
  `hospitalization_id` INT NOT NULL,
  `doctor_amka` CHAR(11) NOT NULL,
  `drug_id` INT NOT NULL,
  `dosage` VARCHAR(100) NOT NULL,
  `frequency` VARCHAR(100) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `patient_amka` CHAR(11) NOT NULL,
  PRIMARY KEY (`prescription_id`),
  INDEX `prescription_hospitalization_idx` (`hospitalization_id` ASC) VISIBLE,
  INDEX `prescription_doctor_idx` (`doctor_amka` ASC) VISIBLE,
  INDEX `prescription_drug_idx` (`drug_id` ASC) VISIBLE,
  INDEX `prescription_patient_idx` (`patient_amka` ASC) VISIBLE,
  CONSTRAINT `prescription_hospitalization`
    FOREIGN KEY (`hospitalization_id`)
    REFERENCES `hospitaldb`.`hospitalization` (`hospitalization_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `prescription_doctor`
    FOREIGN KEY (`doctor_amka`)
    REFERENCES `hospitaldb`.`doctor` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `prescription_drug`
    FOREIGN KEY (`drug_id`)
    REFERENCES `hospitaldb`.`drug` (`drug_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `prescription_patient`
    FOREIGN KEY (`patient_amka`)
    REFERENCES `hospitaldb`.`patient` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `uq_prescription_doctor_patient_drug_start`
    UNIQUE (`doctor_amka`, `patient_amka`, `drug_id`, `start_date`)
)
ENGINE = InnoDB;



-- -----------------------------------------------------
-- Table `hospitaldb`.`patient_allergy`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`patient_allergy` (
  `patient_amka` CHAR(11) NOT NULL,
  `substance_id` INT NOT NULL,
  PRIMARY KEY (`patient_amka`, `substance_id`),
  INDEX `allergy_substance_idx` (`substance_id` ASC) VISIBLE,
  CONSTRAINT `allergy_patient`
    FOREIGN KEY (`patient_amka`)
    REFERENCES `hospitaldb`.`patient` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `allergy_substance`
    FOREIGN KEY (`substance_id`)
    REFERENCES `hospitaldb`.`active_substance` (`substance_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`shift`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`shift` (
  `shift_id` INT NOT NULL AUTO_INCREMENT,
  `department_id` INT NOT NULL,
  `shift_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `shift_type` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`shift_id`),
  INDEX `shift_dept_idx` (`department_id` ASC) VISIBLE,
  CONSTRAINT `shift_dept`
    FOREIGN KEY (`department_id`)
    REFERENCES `hospitaldb`.`department` (`department_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`shift_assignment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`shift_assignment` (
  `assignment_id` INT NOT NULL AUTO_INCREMENT,
  `shift_id` INT NOT NULL,
  `staff_amka` CHAR(11) NOT NULL,
  `role` VARCHAR(45) NULL,
  `status` VARCHAR(45) NULL,
  PRIMARY KEY (`assignment_id`),
  INDEX `assignment_shift_idx` (`shift_id` ASC) VISIBLE,
  INDEX `assignment_staff_idx` (`staff_amka` ASC) VISIBLE,
  CONSTRAINT `assignment_shift`
    FOREIGN KEY (`shift_id`)
    REFERENCES `hospitaldb`.`shift` (`shift_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `assignment_staff`
    FOREIGN KEY (`staff_amka`)
    REFERENCES `hospitaldb`.`staff` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`hospitalization_evaluation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`hospitalization_evaluation` (
  `evaluation_id` INT NOT NULL AUTO_INCREMENT,
  `hospitalization_id` INT NOT NULL,
  `evaluation_date` DATE NOT NULL,
  `nursing_care_score` INT NOT NULL,
  `cleanliness_score` INT NOT NULL,
  `food_score` INT NOT NULL,
  `overall_experience_score` INT NOT NULL,
  `comments` TEXT NULL,
  PRIMARY KEY (`evaluation_id`),
  INDEX `evaluation_hospitalization_idx` (`hospitalization_id` ASC) VISIBLE,
  CONSTRAINT `evaluation_hospitalization`
    FOREIGN KEY (`hospitalization_id`)
    REFERENCES `hospitaldb`.`hospitalization` (`hospitalization_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`doctor_evaluation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`doctor_evaluation` (
  `doctor_evaluation_id` INT NOT NULL AUTO_INCREMENT,
  `hospitalization_id` INT NOT NULL,
  `doctor_amka` CHAR(11) NOT NULL,
  `evaluation_date` DATE NOT NULL,
  `medical_care_score` INT NOT NULL,
  `comments` TEXT NULL,
  PRIMARY KEY (`doctor_evaluation_id`),
  INDEX `doc_eval_hospitalization_idx` (`hospitalization_id` ASC) VISIBLE,
  INDEX `doc_eval_doctor_idx` (`doctor_amka` ASC) VISIBLE,
  CONSTRAINT `doc_eval_hospitalization`
    FOREIGN KEY (`hospitalization_id`)
    REFERENCES `hospitaldb`.`hospitalization` (`hospitalization_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_eval_doctor`
    FOREIGN KEY (`doctor_amka`)
    REFERENCES `hospitaldb`.`doctor` (`amka`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`drug_active_substance`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`drug_active_substance` (
  `drug_id` INT NOT NULL,
  `substance_id` INT NOT NULL,
  PRIMARY KEY (`drug_id`, `substance_id`),
  INDEX `das_substance_idx` (`substance_id` ASC) VISIBLE,
  CONSTRAINT `das_drug`
    FOREIGN KEY (`drug_id`)
    REFERENCES `hospitaldb`.`drug` (`drug_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `das_substance`
    FOREIGN KEY (`substance_id`)
    REFERENCES `hospitaldb`.`active_substance` (`substance_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hospitaldb`.`entity_image`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitaldb`.`entity_image` (
  `image_id` INT NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(45) NOT NULL,
  `entity_key` VARCHAR(45) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`image_id`),
  INDEX `idx_entity_image` (`entity_type` ASC, `entity_key` ASC) VISIBLE
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Additional Constraints
-- -----------------------------------------------------


ALTER TABLE `hospitaldb`.`patient`
ADD CONSTRAINT `chk_patient_weight`
CHECK (`weight` IS NULL OR `weight` > 0);

ALTER TABLE `hospitaldb`.`patient`
ADD CONSTRAINT `chk_patient_height`
CHECK (`height` IS NULL OR `height` > 0);

ALTER TABLE `hospitaldb`.`department`
ADD CONSTRAINT `chk_department_bed_count`
CHECK (`bed_count` IS NULL OR `bed_count` >= 0);

ALTER TABLE `hospitaldb`.`ken`
ADD CONSTRAINT `chk_ken_base_cost`
CHECK (`base_cost` >= 0);

ALTER TABLE `hospitaldb`.`ken`
ADD CONSTRAINT `chk_ken_expected_duration`
CHECK (`expected_duration` > 0);

ALTER TABLE `hospitaldb`.`ken`
ADD CONSTRAINT `chk_ken_extra_daily_cost`
CHECK (`extra_daily_cost` IS NULL OR `extra_daily_cost` >= 0);

ALTER TABLE `hospitaldb`.`triage`
ADD CONSTRAINT `chk_triage_priority`
CHECK (`priority_level` IS NULL OR `priority_level` BETWEEN 1 AND 5);

ALTER TABLE `hospitaldb`.`triage`
ADD CONSTRAINT `chk_triage_waiting_minutes`
CHECK (`waiting_minutes` IS NULL OR `waiting_minutes` >= 0);

ALTER TABLE `hospitaldb`.`hospitalization`
ADD CONSTRAINT `chk_hospitalization_dates`
CHECK (`discharge_date` IS NULL OR `admission_date` IS NULL OR `discharge_date` >= `admission_date`);

ALTER TABLE `hospitaldb`.`hospitalization`
ADD CONSTRAINT `chk_hospitalization_actual_duration`
CHECK (`actual_duration` IS NULL OR `actual_duration` >= 0);

ALTER TABLE `hospitaldb`.`hospitalization`
ADD CONSTRAINT `chk_hospitalization_total_cost`
CHECK (`total_cost` IS NULL OR `total_cost` >= 0);

ALTER TABLE `hospitaldb`.`exam`
ADD CONSTRAINT `chk_exam_cost`
CHECK (`cost` IS NULL OR `cost` >= 0);

ALTER TABLE `hospitaldb`.`medical_procedure`
ADD CONSTRAINT `chk_procedure_cost`
CHECK (`cost` IS NULL OR `cost` >= 0);

ALTER TABLE `hospitaldb`.`medical_procedure`
ADD CONSTRAINT `chk_procedure_duration`
CHECK (`procedure_duration` IS NULL OR `procedure_duration` > 0);

ALTER TABLE `hospitaldb`.`prescription`
ADD CONSTRAINT `chk_prescription_dates`
CHECK (`end_date` IS NULL OR `start_date` IS NULL OR `end_date` >= `start_date`);

ALTER TABLE `hospitaldb`.`hospitalization_evaluation`
ADD CONSTRAINT `chk_nursing_care_score`
CHECK (`nursing_care_score` BETWEEN 1 AND 5);

ALTER TABLE `hospitaldb`.`hospitalization_evaluation`
ADD CONSTRAINT `chk_cleanliness_score`
CHECK (`cleanliness_score` BETWEEN 1 AND 5);

ALTER TABLE `hospitaldb`.`hospitalization_evaluation`
ADD CONSTRAINT `chk_food_score`
CHECK (`food_score` BETWEEN 1 AND 5);

ALTER TABLE `hospitaldb`.`hospitalization_evaluation`
ADD CONSTRAINT `chk_overall_experience_score`
CHECK (`overall_experience_score` BETWEEN 1 AND 5);

ALTER TABLE `hospitaldb`.`doctor_evaluation`
ADD CONSTRAINT `chk_medical_care_score`
CHECK (`medical_care_score` IS NULL OR `medical_care_score` BETWEEN 1 AND 5);

ALTER TABLE `hospitaldb`.`doctor`
ADD CONSTRAINT `chk_doctor_rank`
CHECK (`rank` IN ('Resident', 'Attending B', 'Attending A', 'Director'));

ALTER TABLE `hospitaldb`.`bed`
ADD CONSTRAINT `uq_bed_department_number`
UNIQUE (`department_id`, `bed_number`);

ALTER TABLE `hospitaldb`.`shift_assignment`
ADD CONSTRAINT `uq_shift_staff`
UNIQUE (`shift_id`, `staff_amka`);

ALTER TABLE `hospitaldb`.`hospitalization_evaluation`
ADD CONSTRAINT `uq_hosp_eval_hospitalization`
UNIQUE (`hospitalization_id`);

ALTER TABLE `hospitaldb`.`doctor_evaluation`
ADD CONSTRAINT `uq_doctor_eval`
UNIQUE (`hospitalization_id`, `doctor_amka`);

-- -----------------------------------------------------
-- Extra Indexes
-- -----------------------------------------------------
CREATE INDEX idx_hospitalization_patient_department
ON `hospitaldb`.`hospitalization` (`patient_amka`, `department_id`);

CREATE INDEX idx_hospitalization_department_dates
ON `hospitaldb`.`hospitalization` (`department_id`, `admission_date`, `discharge_date`);

CREATE INDEX idx_hospitalization_ken
ON `hospitaldb`.`hospitalization` (`ken_code`);


CREATE INDEX idx_doctor_speciality
ON `hospitaldb`.`doctor` (`speciality`);

CREATE INDEX idx_medical_procedure_doctor_date
ON `hospitaldb`.`medical_procedure` (`head_doctor_amka`, `procedure_date`);

CREATE INDEX idx_medical_procedure_room_date
ON `hospitaldb`.`medical_procedure` (`room_id`, `procedure_date`);

CREATE INDEX idx_prescription_hospitalization_patient
ON `hospitaldb`.`prescription` (`hospitalization_id`, `patient_amka`);

CREATE INDEX idx_prescription_doctor_hospitalization
ON `hospitaldb`.`prescription` (`doctor_amka`, `hospitalization_id`);


CREATE INDEX idx_patient_allergy_substance
ON `hospitaldb`.`patient_allergy` (`substance_id`);

CREATE INDEX idx_drug_active_substance_substance
ON `hospitaldb`.`drug_active_substance` (`substance_id`, `drug_id`);

CREATE INDEX idx_shift_department_date
ON `hospitaldb`.`shift` (`department_id`, `shift_date`);

CREATE INDEX idx_shift_assignment_staff
ON `hospitaldb`.`shift_assignment` (`staff_amka`);

CREATE INDEX idx_triage_priority_arrival
ON `hospitaldb`.`triage` (`priority_level`, `arrival_time`);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


ALTER DATABASE hospitaldb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
