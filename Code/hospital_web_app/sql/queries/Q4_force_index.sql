USE hospitaldb;

-- Q4 με FORCE INDEX

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

JOIN doctor_evaluation de FORCE INDEX (
    doc_eval_doctor_idx,
    doc_eval_hospitalization_idx
)
    ON d.amka = de.doctor_amka

LEFT JOIN hospitalization_evaluation he FORCE INDEX (
    evaluation_hospitalization_idx
)
    ON de.hospitalization_id = he.hospitalization_id

WHERE d.amka = '29106649725'

GROUP BY
    d.amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`;