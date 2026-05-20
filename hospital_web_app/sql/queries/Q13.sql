USE hospitaldb;

-- Q13
-- Για κάθε ιατρό βρίσκουμε την ιεραρχία εποπτείας του.
-- Δηλαδή εμφανίζουμε τον άμεσο επόπτη, μετά τον επόπτη του επόπτη κ.ο.κ.

WITH RECURSIVE doctor_hierarchy AS (

    -- Αρχικό βήμα: βρίσκουμε τον άμεσο επόπτη κάθε ιατρού
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


    -- Αναδρομικό βήμα: βρίσκουμε τον επόπτη του προηγούμενου επόπτη
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

    -- Απλή προστασία ώστε να μη γίνει άπειρη αναδρομή αν υπάρχει λάθος στα δεδομένα
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
    hierarchy_level;