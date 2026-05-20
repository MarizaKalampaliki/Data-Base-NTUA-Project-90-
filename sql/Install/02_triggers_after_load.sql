USE `hospitaldb`;

DELIMITER $$

-- =====================================================
-- triggers_after_load_beginner.sql
-- Τρέχει ΜΕΤΑ το load των δεδομένων.
-- Beginner / φοιτητική έκδοση.
-- =====================================================


-- -----------------------------------------------------
-- 1. Νοσηλεία - υπολογισμός κόστους ΚΕΝ
-- -----------------------------------------------------

CREATE TRIGGER `trg_hospitalization_calculate_cost_insert`
BEFORE INSERT ON `hospitaldb`.`hospitalization`
FOR EACH ROW
BEGIN
  DECLARE v_expected_duration INT;
  DECLARE v_base_cost DECIMAL(10,2);
  DECLARE v_extra_daily_cost DECIMAL(10,2);
  DECLARE v_extra_days INT;

  IF NEW.discharge_date IS NOT NULL 
     AND NEW.discharge_date < NEW.admission_date THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Hospitalization rejected: discharge before admission.';
  END IF;

  IF NEW.discharge_date IS NOT NULL THEN
    SET NEW.actual_duration = DATEDIFF(NEW.discharge_date, NEW.admission_date);

    SELECT expected_duration, base_cost, COALESCE(extra_daily_cost, 0)
    INTO v_expected_duration, v_base_cost, v_extra_daily_cost
    FROM `hospitaldb`.`ken`
    WHERE ken_code = NEW.ken_code;

    SET v_extra_days = GREATEST(NEW.actual_duration - v_expected_duration, 0);
    SET NEW.total_cost = v_base_cost + (v_extra_days * v_extra_daily_cost);
  END IF;
END$$


CREATE TRIGGER `trg_hospitalization_calculate_cost_update`
BEFORE UPDATE ON `hospitaldb`.`hospitalization`
FOR EACH ROW
BEGIN
  DECLARE v_expected_duration INT;
  DECLARE v_base_cost DECIMAL(10,2);
  DECLARE v_extra_daily_cost DECIMAL(10,2);
  DECLARE v_extra_days INT;

  IF NEW.discharge_date IS NOT NULL 
     AND NEW.discharge_date < NEW.admission_date THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Hospitalization rejected: discharge before admission.';
  END IF;

  IF NEW.discharge_date IS NOT NULL THEN
    SET NEW.actual_duration = DATEDIFF(NEW.discharge_date, NEW.admission_date);

    SELECT expected_duration, base_cost, COALESCE(extra_daily_cost, 0)
    INTO v_expected_duration, v_base_cost, v_extra_daily_cost
    FROM `hospitaldb`.`ken`
    WHERE ken_code = NEW.ken_code;

    SET v_extra_days = GREATEST(NEW.actual_duration - v_expected_duration, 0);
    SET NEW.total_cost = v_base_cost + (v_extra_days * v_extra_daily_cost);
  END IF;
END$$


-- -----------------------------------------------------
-- 2. Γιατροί - απλοί έλεγχοι βαθμίδας / supervisor
-- -----------------------------------------------------

CREATE TRIGGER `trg_doctor_check_insert`
BEFORE INSERT ON `hospitaldb`.`doctor`
FOR EACH ROW
BEGIN
  IF NEW.rank NOT IN ('Resident', 'Attending B', 'Attending A', 'Director') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: invalid rank.';
  END IF;

  IF NEW.rank = 'Resident' AND NEW.supervisor_amka IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: resident must have supervisor.';
  END IF;

  IF NEW.rank = 'Director' AND NEW.supervisor_amka IS NOT NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: director cannot have supervisor.';
  END IF;

  IF NEW.supervisor_amka = NEW.amka THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: doctor cannot supervise himself.';
  END IF;
END$$


CREATE TRIGGER `trg_doctor_check_update`
BEFORE UPDATE ON `hospitaldb`.`doctor`
FOR EACH ROW
BEGIN
  IF NEW.rank NOT IN ('Resident', 'Attending B', 'Attending A', 'Director') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: invalid rank.';
  END IF;

  IF NEW.rank = 'Resident' AND NEW.supervisor_amka IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: resident must have supervisor.';
  END IF;

  IF NEW.rank = 'Director' AND NEW.supervisor_amka IS NOT NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: director cannot have supervisor.';
  END IF;

  IF NEW.supervisor_amka = NEW.amka THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor rejected: doctor cannot supervise himself.';
  END IF;
END$$


-- -----------------------------------------------------
-- 3. Φάρμακα - έλεγχος αλλεργιών και σωστής νοσηλείας
-- -----------------------------------------------------

CREATE TRIGGER `trg_prescription_allergy_check_insert`
BEFORE INSERT ON `hospitaldb`.`prescription`
FOR EACH ROW
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `hospitaldb`.`hospitalization` h
    WHERE h.hospitalization_id = NEW.hospitalization_id
      AND h.patient_amka = NEW.patient_amka
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Prescription rejected: patient does not match hospitalization.';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM `hospitaldb`.`patient_allergy` pa
    JOIN `hospitaldb`.`drug_active_substance` das
      ON das.substance_id = pa.substance_id
    WHERE pa.patient_amka = NEW.patient_amka
      AND das.drug_id = NEW.drug_id
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Prescription rejected: patient allergy found.';
  END IF;
END$$


CREATE TRIGGER `trg_prescription_allergy_check_update`
BEFORE UPDATE ON `hospitaldb`.`prescription`
FOR EACH ROW
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `hospitaldb`.`hospitalization` h
    WHERE h.hospitalization_id = NEW.hospitalization_id
      AND h.patient_amka = NEW.patient_amka
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Prescription rejected: patient does not match hospitalization.';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM `hospitaldb`.`patient_allergy` pa
    JOIN `hospitaldb`.`drug_active_substance` das
      ON das.substance_id = pa.substance_id
    WHERE pa.patient_amka = NEW.patient_amka
      AND das.drug_id = NEW.drug_id
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Prescription rejected: patient allergy found.';
  END IF;
END$$


-- -----------------------------------------------------
-- 4. Medical procedure overlap
-- Δεν επιτρέπει ίδιο room ή ίδιο head doctor την ίδια ώρα.
-- -----------------------------------------------------

CREATE TRIGGER `trg_medical_procedure_conflict_insert`
BEFORE INSERT ON `hospitaldb`.`medical_procedure`
FOR EACH ROW
BEGIN
  DECLARE v_conflicts INT;

  SELECT COUNT(*)
  INTO v_conflicts
  FROM `hospitaldb`.`medical_procedure` mp
  WHERE (
      mp.room_id = NEW.room_id
      OR mp.head_doctor_amka = NEW.head_doctor_amka
    )
    AND mp.procedure_date IS NOT NULL
    AND NEW.procedure_date IS NOT NULL
    AND mp.procedure_duration IS NOT NULL
    AND NEW.procedure_duration IS NOT NULL
    AND DATE_ADD(mp.procedure_date, INTERVAL mp.procedure_duration MINUTE) > NEW.procedure_date
    AND DATE_ADD(NEW.procedure_date, INTERVAL NEW.procedure_duration MINUTE) > mp.procedure_date;

  IF v_conflicts > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Procedure rejected: room or head doctor already occupied.';
  END IF;
END$$


CREATE TRIGGER `trg_medical_procedure_conflict_update`
BEFORE UPDATE ON `hospitaldb`.`medical_procedure`
FOR EACH ROW
BEGIN
  DECLARE v_conflicts INT;

  SELECT COUNT(*)
  INTO v_conflicts
  FROM `hospitaldb`.`medical_procedure` mp
  WHERE mp.procedure_id <> OLD.procedure_id
    AND (
      mp.room_id = NEW.room_id
      OR mp.head_doctor_amka = NEW.head_doctor_amka
    )
    AND mp.procedure_date IS NOT NULL
    AND NEW.procedure_date IS NOT NULL
    AND mp.procedure_duration IS NOT NULL
    AND NEW.procedure_duration IS NOT NULL
    AND DATE_ADD(mp.procedure_date, INTERVAL mp.procedure_duration MINUTE) > NEW.procedure_date
    AND DATE_ADD(NEW.procedure_date, INTERVAL NEW.procedure_duration MINUTE) > mp.procedure_date;

  IF v_conflicts > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Procedure rejected: room or head doctor already occupied.';
  END IF;
END$$


-- -----------------------------------------------------
-- 5. Procedure staff overlap
-- Για βοηθούς/προσωπικό σε procedure.
-- -----------------------------------------------------

CREATE TRIGGER `trg_procedure_staff_conflict_insert`
BEFORE INSERT ON `hospitaldb`.`procedure_staff`
FOR EACH ROW
BEGIN
  DECLARE v_conflicts INT;

  SELECT COUNT(*)
  INTO v_conflicts
  FROM `hospitaldb`.`procedure_staff` ps_old
  JOIN `hospitaldb`.`medical_procedure` mp_old
    ON mp_old.procedure_id = ps_old.procedure_id
  JOIN `hospitaldb`.`medical_procedure` mp_new
    ON mp_new.procedure_id = NEW.procedure_id
  WHERE ps_old.staff_amka = NEW.staff_amka
    AND mp_old.procedure_id <> NEW.procedure_id
    AND mp_old.procedure_date IS NOT NULL
    AND mp_new.procedure_date IS NOT NULL
    AND mp_old.procedure_duration IS NOT NULL
    AND mp_new.procedure_duration IS NOT NULL
    AND DATE_ADD(mp_old.procedure_date, INTERVAL mp_old.procedure_duration MINUTE) > mp_new.procedure_date
    AND DATE_ADD(mp_new.procedure_date, INTERVAL mp_new.procedure_duration MINUTE) > mp_old.procedure_date;

  IF v_conflicts > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Procedure staff rejected: staff already in another procedure.';
  END IF;
END$$


-- -----------------------------------------------------
-- 6. Αξιολογήσεις μόνο μετά από ολοκληρωμένη νοσηλεία
-- -----------------------------------------------------

CREATE TRIGGER `trg_hospitalization_evaluation_insert`
BEFORE INSERT ON `hospitaldb`.`hospitalization_evaluation`
FOR EACH ROW
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `hospitaldb`.`hospitalization` h
    WHERE h.hospitalization_id = NEW.hospitalization_id
      AND h.discharge_date IS NOT NULL
      AND NEW.evaluation_date >= h.discharge_date
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Evaluation rejected: hospitalization not completed or bad date.';
  END IF;
END$$


CREATE TRIGGER `trg_doctor_evaluation_insert`
BEFORE INSERT ON `hospitaldb`.`doctor_evaluation`
FOR EACH ROW
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `hospitaldb`.`hospitalization` h
    WHERE h.hospitalization_id = NEW.hospitalization_id
      AND h.discharge_date IS NOT NULL
      AND NEW.evaluation_date >= h.discharge_date
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor evaluation rejected: hospitalization not completed or bad date.';
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `hospitaldb`.`prescription` p
    WHERE p.hospitalization_id = NEW.hospitalization_id
      AND p.doctor_amka = NEW.doctor_amka
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Doctor evaluation rejected: doctor did not prescribe in this hospitalization.';
  END IF;
END$$


-- -----------------------------------------------------
-- 7. Απλό κόστος εξετάσεων / πράξεων στο total_cost
-- Φοιτητικό: δουλεύει σε INSERT. Δεν καλύπτει τέλεια update/delete.
-- -----------------------------------------------------

CREATE TRIGGER `trg_exam_add_cost`
AFTER INSERT ON `hospitaldb`.`exam`
FOR EACH ROW
BEGIN
  UPDATE `hospitaldb`.`hospitalization`
  SET total_cost = COALESCE(total_cost, 0) + COALESCE(NEW.cost, 0)
  WHERE hospitalization_id = NEW.hospitalization_id;
END$$


CREATE TRIGGER `trg_medical_procedure_add_cost`
AFTER INSERT ON `hospitaldb`.`medical_procedure`
FOR EACH ROW
BEGIN
  UPDATE `hospitaldb`.`hospitalization`
  SET total_cost = COALESCE(total_cost, 0) + COALESCE(NEW.cost, 0)
  WHERE hospitalization_id = NEW.hospitalization_id;
END$$


DELIMITER ;
