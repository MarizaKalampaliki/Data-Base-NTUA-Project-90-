USE hospitaldb;

-- Q2
-- Για συγκεκριμένη ειδικότητα ιατρού,
-- βρίσκουμε τους γιατρούς αυτής της ειδικότητας,
-- αν είχαν εφημερία στο τρέχον έτος
-- και πόσες χειρουργικές επεμβάσεις έκαναν ως κύριοι χειρουργοί.

SELECT
    d.amka AS doctor_amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`,

    CASE
        WHEN COUNT(DISTINCT sh.shift_id) > 0 THEN 'YES'
        ELSE 'NO'
    END AS had_shift_current_year,

    COUNT(DISTINCT sh.shift_id) AS shifts_current_year,

    COUNT(DISTINCT mp.procedure_id) AS surgeries_as_head_doctor

FROM doctor d

JOIN staff s
    ON d.amka = s.amka

LEFT JOIN shift_assignment sa
    ON d.amka = sa.staff_amka

LEFT JOIN shift sh
    ON sa.shift_id = sh.shift_id
   AND YEAR(sh.shift_date) = YEAR(CURDATE())

LEFT JOIN medical_procedure mp
    ON d.amka = mp.head_doctor_amka
   AND mp.type = 'Surgical'

WHERE d.speciality = 'General Surgery'

GROUP BY
    d.amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`

ORDER BY
    surgeries_as_head_doctor DESC,
    shifts_current_year DESC;