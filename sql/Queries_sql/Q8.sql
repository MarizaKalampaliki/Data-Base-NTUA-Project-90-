USE hospitaldb;

-- Q8
-- Προσωπικό που ΔΕΝ έχει προγραμματισμένη εφημερία
-- σε συγκεκριμένη ημερομηνία και συγκεκριμένο τμήμα.

SET @target_date = '2026-03-01';
SET @target_department_id = 1;

SELECT DISTINCT
    st.amka,
    st.first_name,
    st.last_name,
    st.staff_type,

    d.speciality AS doctor_speciality,
    n.grade AS nurse_grade,
    a.role AS admin_role,

    dep.name AS department_name

FROM staff st

LEFT JOIN doctor d
    ON st.amka = d.amka

LEFT JOIN nurse n
    ON st.amka = n.amka

LEFT JOIN admin_staff a
    ON st.amka = a.amka

LEFT JOIN doctor_department dd
    ON d.amka = dd.doctor_amka

JOIN department dep
    ON dep.department_id = @target_department_id

WHERE
    (
        dd.department_id = @target_department_id
        OR n.department_id = @target_department_id
        OR a.department_id = @target_department_id
    )

    AND NOT EXISTS (
        SELECT 1
        FROM shift_assignment sa
        JOIN shift sh
            ON sa.shift_id = sh.shift_id
        WHERE sa.staff_amka = st.amka
          AND sh.department_id = @target_department_id
          AND sh.shift_date = @target_date
    )

ORDER BY
    st.staff_type,
    st.last_name,
    st.first_name;