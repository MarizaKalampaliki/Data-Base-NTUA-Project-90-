SELECT
    EXTRACT(YEAR FROM h.discharge_date) AS hospitalization_year,
    d.name AS department_name,
    h.ken_code,
    i.provider_name AS insurance_provider,

    COUNT(DISTINCT h.hospitalization_id) AS hospitalizations_count,

    SUM(k.base_cost) AS total_base_cost,

    SUM(
        CASE
            WHEN h.actual_duration > k.expected_duration
            THEN (h.actual_duration - k.expected_duration) * k.extra_daily_cost
            ELSE 0
        END
    ) AS total_extra_charge,

    SUM(
        k.base_cost +
        CASE
            WHEN h.actual_duration > k.expected_duration
            THEN (h.actual_duration - k.expected_duration) * k.extra_daily_cost
            ELSE 0
        END
    ) AS total_revenue

FROM hospitaldb.hospitalization h
JOIN hospitaldb.department d
    ON h.department_id = d.department_id
JOIN hospitaldb.ken k
    ON h.ken_code = k.ken_code
JOIN hospitaldb.patient p
    ON h.patient_amka = p.amka
JOIN hospitaldb.insurance i
    ON p.provider_id = i.provider_id

WHERE h.discharge_date IS NOT NULL

GROUP BY
    EXTRACT(YEAR FROM h.discharge_date),
    d.name,
    h.ken_code,
    i.provider_name

ORDER BY
    hospitalization_year,
    department_name,
    h.ken_code,
    insurance_provider;