USE hospitaldb;

-- Q7
-- Για κάθε δραστική ουσία βρίσκουμε:
-- 1. πόσοι ασθενείς έχουν δηλώσει αλλεργία σε αυτήν
-- 2. πόσα φάρμακα την περιέχουν.
-- Ταξινόμηση κατά αριθμό αλλεργικών ασθενών.

SELECT
    a.substance_id,
    a.name AS active_substance_name,

    COUNT(DISTINCT pa.patient_amka) AS allergic_patients_count,

    COUNT(DISTINCT das.drug_id) AS drugs_containing_substance_count

FROM active_substance a

LEFT JOIN patient_allergy pa
    ON a.substance_id = pa.substance_id

LEFT JOIN drug_active_substance das
    ON a.substance_id = das.substance_id

GROUP BY
    a.substance_id,
    a.name

ORDER BY
    allergic_patients_count DESC,
    drugs_containing_substance_count DESC,
    active_substance_name ASC;