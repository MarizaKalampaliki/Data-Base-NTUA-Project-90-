USE hospitaldb;

-- Q12
-- Προσωπικό ανά τμήμα και ανά βάρδια για συγκεκριμένη εβδομάδα,
-- με ανάλυση ανά υποκλάση προσωπικού.

SET @week_start = '2026-03-01';
SET @week_end = '2026-03-07';

SELECT
    dep.department_id,
    dep.name AS department_name,

    sh.shift_id,
    sh.shift_date,
    sh.shift_type,
    sh.start_time,
    sh.end_time,

    st.staff_type,

    CASE
        WHEN st.staff_type = 'Doctor' THEN d.speciality
        WHEN st.staff_type = 'Nurse' THEN n.grade
        WHEN st.staff_type = 'Administrative' THEN a.role
        ELSE 'Unknown'
    END AS staff_subclass,

    COUNT(DISTINCT st.amka) AS assigned_staff_count,

    CASE
        WHEN st.staff_type = 'Doctor' THEN 3
        WHEN st.staff_type = 'Nurse' THEN 6
        WHEN st.staff_type = 'Administrative' THEN 2
        ELSE 0
    END AS minimum_required_per_shift

FROM shift sh

JOIN department dep
    ON sh.department_id = dep.department_id

JOIN shift_assignment sa
    ON sh.shift_id = sa.shift_id

JOIN staff st
    ON sa.staff_amka = st.amka

LEFT JOIN doctor d
    ON st.amka = d.amka

LEFT JOIN nurse n
    ON st.amka = n.amka

LEFT JOIN admin_staff a
    ON st.amka = a.amka

WHERE sh.shift_date BETWEEN @week_start AND @week_end

GROUP BY
    dep.department_id,
    dep.name,
    sh.shift_id,
    sh.shift_date,
    sh.shift_type,
    sh.start_time,
    sh.end_time,
    st.staff_type,
    CASE
        WHEN st.staff_type = 'Doctor' THEN d.speciality
        WHEN st.staff_type = 'Nurse' THEN n.grade
        WHEN st.staff_type = 'Administrative' THEN a.role
        ELSE 'Unknown'
    END

ORDER BY
    dep.name,
    sh.shift_date,
    sh.start_time,
    st.staff_type,
    staff_subclass;