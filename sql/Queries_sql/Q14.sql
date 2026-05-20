USE hospitaldb;

WITH yearly_counts AS (
    SELECT
        d.category_code,
        MIN(d.category_name) AS category_name,
        YEAR(h.admission_date) AS admission_year,
        COUNT(*) AS admissions_count
    FROM hospitalization h
    JOIN diagnosis d
        ON h.admission_icd10_code = d.icd10_code
    WHERE d.category_code IS NOT NULL
      AND d.category_code <> ''
    GROUP BY
        d.category_code,
        YEAR(h.admission_date)
    HAVING COUNT(*) >= 5
)

SELECT
    y1.category_code,
    y1.category_name,
    y1.admission_year AS first_year,
    y2.admission_year AS next_year,
    y1.admissions_count AS first_year_admissions,
    y2.admissions_count AS next_year_admissions
FROM yearly_counts y1
JOIN yearly_counts y2
    ON y1.category_code = y2.category_code
    AND y2.admission_year = y1.admission_year + 1
    AND y1.admissions_count = y2.admissions_count
ORDER BY
    y1.category_code,
    y1.admission_year;
