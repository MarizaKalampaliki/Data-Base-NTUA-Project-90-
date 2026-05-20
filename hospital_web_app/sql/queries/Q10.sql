USE hospitaldb;

-- Q10
-- Top-3 ζεύγη δραστικών ουσιών που συνταγογραφήθηκαν ταυτόχρονα
-- στον ίδιο ασθενή κατά την ίδια νοσηλεία.

WITH substance_pairs AS (
    SELECT DISTINCT
        p1.patient_amka,
        p1.hospitalization_id,

        LEAST(das1.substance_id, das2.substance_id) AS substance_1_id,
        GREATEST(das1.substance_id, das2.substance_id) AS substance_2_id

    FROM prescription p1

    JOIN prescription p2
        ON p1.patient_amka = p2.patient_amka
       AND p1.hospitalization_id = p2.hospitalization_id
       AND p1.prescription_id < p2.prescription_id

       -- Τα δύο φάρμακα θεωρούνται ταυτόχρονα
       -- όταν τα διαστήματα χορήγησης επικαλύπτονται.
       AND COALESCE(p1.start_date, '1000-01-01') <= COALESCE(p2.end_date, '9999-12-31')
       AND COALESCE(p2.start_date, '1000-01-01') <= COALESCE(p1.end_date, '9999-12-31')

    JOIN drug_active_substance das1
        ON das1.drug_id = p1.drug_id

    JOIN drug_active_substance das2
        ON das2.drug_id = p2.drug_id

    WHERE das1.substance_id <> das2.substance_id
)

SELECT
    sp.substance_1_id,
    s1.name AS substance_1_name,

    sp.substance_2_id,
    s2.name AS substance_2_name,

    COUNT(*) AS frequency

FROM substance_pairs sp

JOIN active_substance s1
    ON s1.substance_id = sp.substance_1_id

JOIN active_substance s2
    ON s2.substance_id = sp.substance_2_id

GROUP BY
    sp.substance_1_id,
    s1.name,
    sp.substance_2_id,
    s2.name

ORDER BY
    frequency DESC,
    substance_1_name,
    substance_2_name

LIMIT 3;