USE hospitaldb;

-- EXPLAIN για το κανονικό Q4

EXPLAIN
SELECT
    d.amka AS doctor_amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`,

    COUNT(DISTINCT de.doctor_evaluation_id) AS total_doctor_evaluations,

    ROUND(AVG(de.medical_care_score), 2) AS average_medical_care_score,

    ROUND(AVG(he.overall_experience_score), 2) AS average_overall_hospitalization_experience

FROM doctor d

JOIN staff s
    ON d.amka = s.amka

JOIN doctor_evaluation de
    ON d.amka = de.doctor_amka

LEFT JOIN hospitalization_evaluation he
    ON de.hospitalization_id = he.hospitalization_id

WHERE d.amka = '29106649725'

GROUP BY
    d.amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`;