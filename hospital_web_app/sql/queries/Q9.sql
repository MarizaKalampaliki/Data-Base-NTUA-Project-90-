USE hospitaldb;

-- Q9
-- Ασθενείς που νοσηλεύτηκαν τον ίδιο συνολικό αριθμό ημερών
-- μέσα στο ίδιο έτος, με συνολική διάρκεια άνω των 15 ημερών.

WITH patient_year_days AS (
    SELECT
        h.patient_amka,
        EXTRACT(YEAR FROM h.admission_date) AS hospitalization_year,
        SUM(h.actual_duration) AS total_days
    FROM hospitalization h
    WHERE h.actual_duration IS NOT NULL
    GROUP BY
        h.patient_amka,
        EXTRACT(YEAR FROM h.admission_date)
    HAVING SUM(h.actual_duration) > 15
)

SELECT
    pyd1.hospitalization_year,

    pyd1.patient_amka AS patient_1_amka,
    p1.first_name AS patient_1_first_name,
    p1.last_name AS patient_1_last_name,

    pyd2.patient_amka AS patient_2_amka,
    p2.first_name AS patient_2_first_name,
    p2.last_name AS patient_2_last_name,

    pyd1.total_days AS common_total_days

FROM patient_year_days pyd1

JOIN patient_year_days pyd2
    ON pyd1.hospitalization_year = pyd2.hospitalization_year
   AND pyd1.total_days = pyd2.total_days
   AND pyd1.patient_amka < pyd2.patient_amka

JOIN patient p1
    ON pyd1.patient_amka = p1.amka

JOIN patient p2
    ON pyd2.patient_amka = p2.amka

ORDER BY
    pyd1.hospitalization_year,
    common_total_days DESC,
    patient_1_amka,
    patient_2_amka;