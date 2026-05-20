USE hospitaldb;

-- Q11
-- Ιατροί που έχουν τουλάχιστον 5 λιγότερες χειρουργικές επεμβάσεις
-- από τον ιατρό με τις περισσότερες επεμβάσεις στο τρέχον έτος.

WITH doctor_surgeries AS (
    SELECT
        d.amka AS doctor_amka,
        s.first_name,
        s.last_name,
        d.speciality,
        d.`rank`,

        COUNT(DISTINCT mp.procedure_id) AS total_surgeries

    FROM doctor d

    JOIN staff s
        ON d.amka = s.amka

    LEFT JOIN medical_procedure mp
        ON d.amka = mp.head_doctor_amka
       AND YEAR(mp.procedure_date) = YEAR(CURDATE())
       AND mp.type = 'Surgical'

    GROUP BY
        d.amka,
        s.first_name,
        s.last_name,
        d.speciality,
        d.`rank`
),

max_surgeries AS (
    SELECT
        MAX(total_surgeries) AS max_total_surgeries
    FROM doctor_surgeries
)

SELECT
    ds.doctor_amka,
    ds.first_name,
    ds.last_name,
    ds.speciality,
    ds.`rank`,

    ds.total_surgeries,
    ms.max_total_surgeries,

    ms.max_total_surgeries - ds.total_surgeries AS surgeries_less_than_top

FROM doctor_surgeries ds

CROSS JOIN max_surgeries ms

WHERE ms.max_total_surgeries - ds.total_surgeries >= 5

ORDER BY
    surgeries_less_than_top DESC,
    ds.total_surgeries DESC,
    ds.last_name,
    ds.first_name;