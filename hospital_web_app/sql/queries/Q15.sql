USE hospitaldb;

-- Q15
-- Κατανομή περιστατικών triage ανά επίπεδο επείγοντος,
-- με μέσο χρόνο αναμονής, ποσοστό νοσηλείας και κατανομή ανά τμήμα

WITH priority_stats AS (
    SELECT
        priority_level,

        COUNT(*) AS total_triage_cases,

        ROUND(AVG(waiting_minutes), 2) AS average_waiting_minutes,

        ROUND(
            100.0 * SUM(
                CASE
                    WHEN outcome = 'Admitted' THEN 1
                    ELSE 0
                END
            ) / COUNT(*),
            2
        ) AS hospitalization_percentage

    FROM triage

    GROUP BY
        priority_level
)

SELECT
    t.priority_level,

    ps.total_triage_cases,
    ps.average_waiting_minutes,
    ps.hospitalization_percentage,

    COALESCE(d.department_id, 0) AS department_id,
    COALESCE(d.name, 'Χωρίς νοσηλεία') AS department_name,

    COUNT(DISTINCT t.triage_id) AS cases_per_department,

    ROUND(
        100.0 * COUNT(DISTINCT t.triage_id) / ps.total_triage_cases,
        2
    ) AS department_distribution_percentage

FROM triage t

JOIN priority_stats ps
    ON ps.priority_level = t.priority_level

LEFT JOIN hospitalization h
    ON h.triage_id = t.triage_id

LEFT JOIN department d
    ON h.department_id = d.department_id

GROUP BY
    t.priority_level,
    ps.total_triage_cases,
    ps.average_waiting_minutes,
    ps.hospitalization_percentage,
    COALESCE(d.department_id, 0),
    COALESCE(d.name, 'Χωρίς νοσηλεία')

ORDER BY
    t.priority_level,
    cases_per_department DESC,
    department_name;
