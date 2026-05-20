USE hospitaldb;

-- Q5
-- Νέοι ιατροί κάτω των 35 ετών
-- που έχουν εκτελέσει τις περισσότερες χειρουργικές επεμβάσεις
-- ως κύριοι χειρουργοί.

SELECT
    d.amka AS doctor_amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`,

    TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) AS age,

    COUNT(DISTINCT mp.procedure_id) AS surgeries_as_head_doctor

FROM doctor d

JOIN staff s
    ON d.amka = s.amka

JOIN medical_procedure mp
    ON d.amka = mp.head_doctor_amka
   AND mp.type = 'Surgical'

WHERE TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) < 35

GROUP BY
    d.amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`,
    s.date_of_birth

ORDER BY
    surgeries_as_head_doctor DESC,
    age ASC;