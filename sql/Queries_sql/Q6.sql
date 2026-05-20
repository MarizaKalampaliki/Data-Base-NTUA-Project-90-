USE hospitaldb;

-- Q6a
-- Για συγκεκριμένο ασθενή:
-- ιστορικό νοσηλειών, διαγνώσεις ICD-10,
-- συνολικό κόστος ανά νοσηλεία και μέσος όρος αξιολόγησης

WITH
selected_patient AS (
    SELECT '83176473029' AS patient_amka
),

hospitalization_scores AS (
    SELECT
        he.hospitalization_id,
        ROUND(
            (
                he.nursing_care_score
                + he.cleanliness_score
                + he.food_score
                + he.overall_experience_score
            ) / 4.0,
            2
        ) AS hospitalization_avg_rating
    FROM hospitalization_evaluation he
),

patient_score AS (
    SELECT
        h.patient_amka,
        ROUND(
            AVG(
                (
                    he.nursing_care_score
                    + he.cleanliness_score
                    + he.food_score
                    + he.overall_experience_score
                ) / 4.0
            ),
            2
        ) AS patient_avg_rating
    FROM selected_patient sp

    JOIN hospitalization h
        ON h.patient_amka = sp.patient_amka

    JOIN hospitalization_evaluation he
        ON he.hospitalization_id = h.hospitalization_id

    GROUP BY
        h.patient_amka
)

SELECT
    p.amka AS patient_amka,
    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,

    h.hospitalization_id,
    d.name AS department_name,
    b.bed_number,

    h.admission_date,
    h.discharge_date,
    h.actual_duration AS stay_days,

    h.admission_icd10_code,
    adm_diag.description AS admission_diagnosis,

    h.discharge_icd10_code,
    dis_diag.description AS discharge_diagnosis,

    h.ken_code,
    h.total_cost AS hospitalization_total_cost,

    hs.hospitalization_avg_rating,
    ps.patient_avg_rating

FROM selected_patient sp

JOIN patient p
    ON p.amka = sp.patient_amka

JOIN hospitalization h
    ON h.patient_amka = p.amka

JOIN department d
    ON d.department_id = h.department_id

JOIN bed b
    ON b.bed_id = h.bed_id

LEFT JOIN diagnosis adm_diag
    ON adm_diag.icd10_code = h.admission_icd10_code

LEFT JOIN diagnosis dis_diag
    ON dis_diag.icd10_code = h.discharge_icd10_code

LEFT JOIN hospitalization_scores hs
    ON hs.hospitalization_id = h.hospitalization_id

LEFT JOIN patient_score ps
    ON ps.patient_amka = p.amka

ORDER BY
    h.admission_date,
    h.hospitalization_id;