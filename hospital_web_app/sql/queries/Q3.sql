USE hospitaldb;

SELECT
    p.amka AS patient_amka,
    p.first_name AS patient_first_name,
    p.last_name AS patient_last_name,

    d.department_id,
    d.name AS department_name,

    COUNT(DISTINCT h.hospitalization_id) AS hospitalizations_count,
    SUM(h.total_cost) AS total_hospitalization_cost

FROM patient p

JOIN hospitalization h
    ON p.amka = h.patient_amka

JOIN department d
    ON h.department_id = d.department_id

GROUP BY
    p.amka,
    p.first_name,
    p.last_name,
    d.department_id,
    d.name

HAVING COUNT(DISTINCT h.hospitalization_id) > 3

ORDER BY
    hospitalizations_count DESC,
    total_hospitalization_cost DESC;