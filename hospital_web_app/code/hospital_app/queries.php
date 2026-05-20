<?php

function report_queries(): array
{
    return [

        'q01' => [
            'label' => 'Q1',
            'title' => 'Έσοδα ανά τμήμα, έτος, ΚΕΝ και ασφαλιστικό φορέα',
            'description' => 'Ανάλυση βασικού κόστους, πρόσθετης χρέωσης και συνολικού εσόδου.',

            'sql' => <<<'SQL'
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
    insurance_provider
SQL
        ],

        'q02' => [
            'label' => 'Q2',
            'title' => 'Ιατροί συγκεκριμένης ειδικότητας',
            'description' => 'Εμφανίζει αν είχαν εφημερία στο τρέχον έτος και πόσες χειρουργικές πράξεις έκαναν ως κύριοι χειρουργοί.',

            'params' => [
                'speciality' => [
                    'label' => 'Ειδικότητα',
                    'type' => 'select',
                    'options_sql' => "SELECT DISTINCT speciality AS value, speciality AS label FROM doctor ORDER BY speciality",
                    'default' => 'General Surgery'
                ]
            ],

            'sql' => <<<'SQL'
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

WHERE d.speciality = :speciality

GROUP BY
    d.amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`

ORDER BY
    surgeries_as_head_doctor DESC,
    shifts_current_year DESC
SQL
        ],

        'q03' => [
            'label' => 'Q3',
            'title' => 'Ασθενείς με πάνω από 3 νοσηλείες στο ίδιο τμήμα',
            'description' => 'Επιστρέφει το πλήθος νοσηλειών και το συνολικό κόστος ανά ασθενή και τμήμα.',

            'sql' => <<<'SQL'
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
    total_hospitalization_cost DESC
SQL
        ],

        'q04' => [
            'label' => 'Q4',
            'title' => 'Αξιολογήσεις συγκεκριμένου ιατρού',
            'description' => 'Μέσος όρος ιατρικής φροντίδας και συνολικής εντύπωσης νοσηλείας.',

            'allow_explain' => true,
            'params' => [
                'doctor_amka' => [
                    'label' => 'ΑΜΚΑ ιατρού',
                    'type' => 'select',
                    'options_sql' => "SELECT d.amka AS value, CONCAT(d.amka, ' - ', s.first_name, ' ', s.last_name, ' / ', d.speciality) AS label FROM doctor d JOIN staff s ON s.amka = d.amka ORDER BY s.last_name, s.first_name",
                    'default_sql' => "SELECT doctor_amka FROM doctor_evaluation GROUP BY doctor_amka ORDER BY COUNT(*) DESC LIMIT 1",
                    'default' => '29106649725'
                ]
            ],
            'variants' => [
                'normal' => [
                    'label' => 'Κανονικό query',
                    'sql' => <<<'SQL'
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

JOIN doctor_evaluation de
    ON d.amka = de.doctor_amka

LEFT JOIN hospitalization_evaluation he
    ON de.hospitalization_id = he.hospitalization_id

WHERE d.amka = :doctor_amka

GROUP BY
    d.amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`
SQL
                ],
                'force' => [
                    'label' => 'Με FORCE INDEX',
                    'sql' => <<<'SQL'
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

WHERE d.amka = :doctor_amka

GROUP BY
    d.amka,
    s.first_name,
    s.last_name,
    d.speciality,
    d.`rank`
SQL
                ]
            ]
        ],

        'q05' => [
            'label' => 'Q5',
            'title' => 'Νέοι ιατροί με τις περισσότερες χειρουργικές πράξεις',
            'description' => 'Κατάταξη νέων ιατρών με βάση το πλήθος χειρουργικών πράξεων.',

            'sql' => <<<'SQL'
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
    age ASC
SQL
        ],

        'q06' => [
            'label' => 'Q6',
            'title' => 'Ιστορικό νοσηλειών συγκεκριμένου ασθενή',
            'description' => 'Νοσηλείες, ICD-10 διαγνώσεις, κόστος και μέσος όρος αξιολόγησης.',

            'allow_explain' => true,
            'params' => [
                'patient_amka' => [
                    'label' => 'ΑΜΚΑ ασθενή',
                    'type' => 'select',
                    'options_sql' => "SELECT p.amka AS value, CONCAT(p.amka, ' - ', p.first_name, ' ', p.last_name) AS label FROM patient p JOIN hospitalization h ON h.patient_amka = p.amka GROUP BY p.amka, p.first_name, p.last_name ORDER BY COUNT(*) DESC, p.last_name LIMIT 80",
                    'default_sql' => "SELECT patient_amka FROM hospitalization GROUP BY patient_amka ORDER BY COUNT(*) DESC LIMIT 1",
                    'default' => '83176473029'
                ]
            ],
            'variants' => [
                'normal' => [
                    'label' => 'Κανονικό query',
                    'sql' => <<<'SQL'
WITH
selected_patient AS (
    SELECT :patient_amka AS patient_amka
),

hospitalization_scores AS (
    SELECT
        he.hospitalization_id,
        ROUND(
            (
                he.nursing_care_score
                + he.cleanliness_score
                + he.food_score
                + he.overall_experience_score
            ) / 4.0,
            2
        ) AS hospitalization_avg_rating
    FROM hospitalization_evaluation he
),

patient_score AS (
    SELECT
        h.patient_amka,
        ROUND(
            AVG(
                (
                    he.nursing_care_score
                    + he.cleanliness_score
                    + he.food_score
                    + he.overall_experience_score
                ) / 4.0
            ),
            2
        ) AS patient_avg_rating
    FROM selected_patient sp

    JOIN hospitalization h
        ON h.patient_amka = sp.patient_amka

    JOIN hospitalization_evaluation he
        ON he.hospitalization_id = h.hospitalization_id

    GROUP BY
        h.patient_amka
)

SELECT
    p.amka AS patient_amka,
    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,

    h.hospitalization_id,
    d.name AS department_name,
    b.bed_number,

    h.admission_date,
    h.discharge_date,
    h.actual_duration AS stay_days,

    h.admission_icd10_code,
    adm_diag.description AS admission_diagnosis,

    h.discharge_icd10_code,
    dis_diag.description AS discharge_diagnosis,

    h.ken_code,
    h.total_cost AS hospitalization_total_cost,

    hs.hospitalization_avg_rating,
    ps.patient_avg_rating

FROM selected_patient sp

JOIN patient p
    ON p.amka = sp.patient_amka

JOIN hospitalization h
    ON h.patient_amka = p.amka

JOIN department d
    ON d.department_id = h.department_id

JOIN bed b
    ON b.bed_id = h.bed_id

LEFT JOIN diagnosis adm_diag
    ON adm_diag.icd10_code = h.admission_icd10_code

LEFT JOIN diagnosis dis_diag
    ON dis_diag.icd10_code = h.discharge_icd10_code

LEFT JOIN hospitalization_scores hs
    ON hs.hospitalization_id = h.hospitalization_id

LEFT JOIN patient_score ps
    ON ps.patient_amka = p.amka

ORDER BY
    h.admission_date,
    h.hospitalization_id
SQL
                ],
                'force' => [
                    'label' => 'Με FORCE INDEX',
                    'sql' => <<<'SQL'
WITH
selected_patient AS (
    SELECT :patient_amka AS patient_amka
),

hospitalization_scores AS (
    SELECT
        he.hospitalization_id,
        ROUND(
            (
                he.nursing_care_score
                + he.cleanliness_score
                + he.food_score
                + he.overall_experience_score
            ) / 4.0,
            2
        ) AS hospitalization_avg_rating
    FROM hospitalization_evaluation he FORCE INDEX (evaluation_hospitalization_idx)
),

patient_score AS (
    SELECT
        h.patient_amka,
        ROUND(
            AVG(
                (
                    he.nursing_care_score
                    + he.cleanliness_score
                    + he.food_score
                    + he.overall_experience_score
                ) / 4.0
            ),
            2
        ) AS patient_avg_rating
    FROM selected_patient sp

    JOIN hospitalization h FORCE INDEX (patient_hospitalization_idx)
        ON h.patient_amka = sp.patient_amka

    JOIN hospitalization_evaluation he FORCE INDEX (evaluation_hospitalization_idx)
        ON he.hospitalization_id = h.hospitalization_id

    GROUP BY
        h.patient_amka
)

SELECT
    p.amka AS patient_amka,
    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,

    h.hospitalization_id,
    d.name AS department_name,
    b.bed_number,

    h.admission_date,
    h.discharge_date,
    h.actual_duration AS stay_days,

    h.admission_icd10_code,
    adm_diag.description AS admission_diagnosis,

    h.discharge_icd10_code,
    dis_diag.description AS discharge_diagnosis,

    h.ken_code,
    h.total_cost AS hospitalization_total_cost,

    hs.hospitalization_avg_rating,
    ps.patient_avg_rating

FROM selected_patient sp

JOIN patient p
    ON p.amka = sp.patient_amka

JOIN hospitalization h FORCE INDEX (patient_hospitalization_idx)
    ON h.patient_amka = p.amka

JOIN department d
    ON d.department_id = h.department_id

JOIN bed b
    ON b.bed_id = h.bed_id

LEFT JOIN diagnosis adm_diag
    ON adm_diag.icd10_code = h.admission_icd10_code

LEFT JOIN diagnosis dis_diag
    ON dis_diag.icd10_code = h.discharge_icd10_code

LEFT JOIN hospitalization_scores hs
    ON hs.hospitalization_id = h.hospitalization_id

LEFT JOIN patient_score ps
    ON ps.patient_amka = p.amka

ORDER BY
    h.admission_date,
    h.hospitalization_id
SQL
                ]
            ]
        ],

        'q07' => [
            'label' => 'Q7',
            'title' => 'Δραστικές ουσίες και αλλεργίες',
            'description' => 'Σύγκριση δηλωμένων αλλεργιών με χρήση δραστικών ουσιών σε συνταγές.',

            'sql' => <<<'SQL'
SELECT
    a.substance_id,
    a.name AS active_substance_name,

    COUNT(DISTINCT pa.patient_amka) AS allergic_patients_count,

    COUNT(DISTINCT das.drug_id) AS drugs_containing_substance_count

FROM active_substance a

LEFT JOIN patient_allergy pa
    ON a.substance_id = pa.substance_id

LEFT JOIN drug_active_substance das
    ON a.substance_id = das.substance_id

GROUP BY
    a.substance_id,
    a.name

ORDER BY
    allergic_patients_count DESC,
    drugs_containing_substance_count DESC,
    active_substance_name ASC
SQL
        ],

        'q08' => [
            'label' => 'Q8',
            'title' => 'Προσωπικό χωρίς εφημερία σε ημερομηνία και τμήμα',
            'description' => 'Εντοπίζει διαθέσιμο προσωπικό για συγκεκριμένη ημερομηνία και τμήμα.',

            'params' => [
                'target_date' => [
                    'label' => 'Ημερομηνία',
                    'type' => 'date',
                    'default' => '2026-03-01'
                ],
                'target_department_id' => [
                    'label' => 'Τμήμα',
                    'type' => 'select',
                    'options_sql' => "SELECT department_id AS value, CONCAT(department_id, ' - ', name) AS label FROM department ORDER BY name",
                    'default' => '1'
                ]
            ],

            'sql' => <<<'SQL'
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
    ON dep.department_id = :target_department_id

WHERE
    (
        dd.department_id = :target_department_id
        OR n.department_id = :target_department_id
        OR a.department_id = :target_department_id
    )

    AND NOT EXISTS (
        SELECT 1
        FROM shift_assignment sa
        JOIN shift sh
            ON sa.shift_id = sh.shift_id
        WHERE sa.staff_amka = st.amka
          AND sh.department_id = :target_department_id
          AND sh.shift_date = :target_date
    )

ORDER BY
    st.staff_type,
    st.last_name,
    st.first_name
SQL
        ],

        'q09' => [
            'label' => 'Q9',
            'title' => 'Ασθενείς με ίδιο συνολικό αριθμό ημερών νοσηλείας',
            'description' => 'Ομαδοποίηση ασθενών ανά έτος και συνολικές ημέρες νοσηλείας.',

            'sql' => <<<'SQL'
WITH patient_year_days AS (
    SELECT
        h.patient_amka,
        EXTRACT(YEAR FROM h.admission_date) AS hospitalization_year,
        SUM(h.actual_duration) AS total_days
    FROM hospitalization h
    WHERE h.actual_duration IS NOT NULL
    GROUP BY
        h.patient_amka,
        EXTRACT(YEAR FROM h.admission_date)
    HAVING SUM(h.actual_duration) > 15
)

SELECT
    pyd1.hospitalization_year,

    pyd1.patient_amka AS patient_1_amka,
    p1.first_name AS patient_1_first_name,
    p1.last_name AS patient_1_last_name,

    pyd2.patient_amka AS patient_2_amka,
    p2.first_name AS patient_2_first_name,
    p2.last_name AS patient_2_last_name,

    pyd1.total_days AS common_total_days

FROM patient_year_days pyd1

JOIN patient_year_days pyd2
    ON pyd1.hospitalization_year = pyd2.hospitalization_year
   AND pyd1.total_days = pyd2.total_days
   AND pyd1.patient_amka < pyd2.patient_amka

JOIN patient p1
    ON pyd1.patient_amka = p1.amka

JOIN patient p2
    ON pyd2.patient_amka = p2.amka

ORDER BY
    pyd1.hospitalization_year,
    common_total_days DESC,
    patient_1_amka,
    patient_2_amka
SQL
        ],

        'q10' => [
            'label' => 'Q10',
            'title' => 'Top-3 ζεύγη δραστικών ουσιών',
            'description' => 'Ζεύγη δραστικών ουσιών που συνταγογραφήθηκαν ταυτόχρονα στην ίδια νοσηλεία.',

            'sql' => <<<'SQL'
WITH substance_pairs AS (
    SELECT DISTINCT
        p1.patient_amka,
        p1.hospitalization_id,

        LEAST(das1.substance_id, das2.substance_id) AS substance_1_id,
        GREATEST(das1.substance_id, das2.substance_id) AS substance_2_id

    FROM prescription p1

    JOIN prescription p2
        ON p1.patient_amka = p2.patient_amka
       AND p1.hospitalization_id = p2.hospitalization_id
       AND p1.prescription_id < p2.prescription_id

       AND COALESCE(p1.start_date, '1000-01-01') <= COALESCE(p2.end_date, '9999-12-31')
       AND COALESCE(p2.start_date, '1000-01-01') <= COALESCE(p1.end_date, '9999-12-31')

    JOIN drug_active_substance das1
        ON das1.drug_id = p1.drug_id

    JOIN drug_active_substance das2
        ON das2.drug_id = p2.drug_id

    WHERE das1.substance_id <> das2.substance_id
)

SELECT
    sp.substance_1_id,
    s1.name AS substance_1_name,

    sp.substance_2_id,
    s2.name AS substance_2_name,

    COUNT(*) AS frequency

FROM substance_pairs sp

JOIN active_substance s1
    ON s1.substance_id = sp.substance_1_id

JOIN active_substance s2
    ON s2.substance_id = sp.substance_2_id

GROUP BY
    sp.substance_1_id,
    s1.name,
    sp.substance_2_id,
    s2.name

ORDER BY
    frequency DESC,
    substance_1_name,
    substance_2_name

LIMIT 3
SQL
        ],

        'q11' => [
            'label' => 'Q11',
            'title' => 'Ιατροί με τουλάχιστον 5 λιγότερες επεμβάσεις από τον πρώτο',
            'description' => 'Σύγκριση χειρουργικών πράξεων ανά ιατρό για το τρέχον έτος.',

            'sql' => <<<'SQL'
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
    ds.first_name
SQL
        ],

        'q12' => [
            'label' => 'Q12',
            'title' => 'Προσωπικό ανά τμήμα και βάρδια για εβδομάδα',
            'description' => 'Ανάλυση προσωπικού ανά υποκλάση για συγκεκριμένη εβδομάδα.',

            'params' => [
                'week_start' => [
                    'label' => 'Αρχή εβδομάδας',
                    'type' => 'date',
                    'default' => '2026-03-01'
                ],
                'week_end' => [
                    'label' => 'Τέλος εβδομάδας',
                    'type' => 'date',
                    'default' => '2026-03-07'
                ]
            ],

            'sql' => <<<'SQL'
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

WHERE sh.shift_date BETWEEN :week_start AND :week_end

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
    staff_subclass
SQL
        ],

        'q13' => [
            'label' => 'Q13',
            'title' => 'Ιεραρχία εποπτείας ιατρών',
            'description' => 'Αναδρομική εμφάνιση της αλυσίδας εποπτείας κάθε ιατρού.',

            'sql' => <<<'SQL'
WITH RECURSIVE doctor_hierarchy AS (

    SELECT
        d.amka AS doctor_amka,
        CONCAT(s.first_name, ' ', s.last_name) AS doctor_name,
        d.speciality AS doctor_speciality,
        d.`rank` AS doctor_rank,

        sup.amka AS supervisor_amka,
        CONCAT(ss.first_name, ' ', ss.last_name) AS supervisor_name,
        sup.speciality AS supervisor_speciality,
        sup.`rank` AS supervisor_rank,

        1 AS hierarchy_level

    FROM doctor d

    JOIN staff s
        ON d.amka = s.amka

    JOIN doctor sup
        ON d.supervisor_amka = sup.amka

    JOIN staff ss
        ON sup.amka = ss.amka


    UNION ALL


    SELECT
        dh.doctor_amka,
        dh.doctor_name,
        dh.doctor_speciality,
        dh.doctor_rank,

        sup2.amka AS supervisor_amka,
        CONCAT(ss2.first_name, ' ', ss2.last_name) AS supervisor_name,
        sup2.speciality AS supervisor_speciality,
        sup2.`rank` AS supervisor_rank,

        dh.hierarchy_level + 1 AS hierarchy_level

    FROM doctor_hierarchy dh

    JOIN doctor current_supervisor
        ON dh.supervisor_amka = current_supervisor.amka

    JOIN doctor sup2
        ON current_supervisor.supervisor_amka = sup2.amka

    JOIN staff ss2
        ON sup2.amka = ss2.amka

    WHERE dh.hierarchy_level < 10
)

SELECT
    doctor_amka,
    doctor_name,
    doctor_speciality,
    doctor_rank,

    hierarchy_level,

    supervisor_amka,
    supervisor_name,
    supervisor_speciality,
    supervisor_rank

FROM doctor_hierarchy

ORDER BY
    doctor_name,
    hierarchy_level
SQL
        ],

        'q14' => [
            'label' => 'Q14',
            'title' => 'ICD-10 κατηγορίες με ίδιο πλήθος εισαγωγών σε συνεχόμενα έτη',
            'description' => 'Σύγκριση εισαγωγών ανά ICD-10 κατηγορία και έτος.',

            'sql' => <<<'SQL'
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
    y1.admission_year
SQL
        ],

        'q15' => [
            'label' => 'Q15',
            'title' => 'Κατανομή triage ανά επίπεδο επείγοντος',
            'description' => 'Μέσος χρόνος αναμονής, ποσοστό νοσηλείας και κόστος ανά επίπεδο triage.',

            'sql' => <<<'SQL'
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
    department_name
SQL
        ],

    ];
}
