<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/queries.php';

function page_header(string $title): void
{
    echo '<!doctype html><html lang="el"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/style.css">';
    echo '</head><body>';
    echo '<header class="topbar">';
    echo '<div class="brand"><div class="brand-mark">+</div><div><strong>Υγειόπολη</strong><span>Hospital Database UI</span></div></div>';
    echo '<nav class="main-nav">';
    echo nav_link('dashboard', 'Dashboard');
    echo nav_link('departments', 'Departments');
    echo nav_link('doctors', 'Doctors');
    echo nav_link('patients', 'Patients');
    echo nav_link('nurses', 'Nurses');
    echo nav_link('admin', 'Admin');
    echo nav_link('rooms', 'Rooms');
    echo nav_link('manage', 'Data Manager');
    echo nav_link('queries', 'Queries Q1-Q15');
    echo nav_link('sql', 'SQL Query');
    echo '</nav>';
    echo '</header><main class="page">';
}

function page_footer(): void
{
    echo '</main><script src="assets/app.js"></script></body></html>';
}

function connection_error_page(Throwable $e): void
{
    page_header('Σφάλμα σύνδεσης');
    echo '<section class="hero error-hero">';
    echo '<h1>Δεν έγινε σύνδεση με τη βάση.</h1>';
    echo '<p>Άνοιξε το <code>config.php</code> και έλεγξε host, port, όνομα βάσης, χρήστη και password.</p>';
    echo '<div class="hint-box"><strong>Για MAMP συνήθως:</strong><br>DB_HOST = 127.0.0.1, DB_PORT = 8889, DB_USER = root, DB_PASS = root, DB_NAME = hospitaldb.</div>';
    echo '<pre class="error-pre">' . h($e->getMessage()) . '</pre>';
    echo '</section>';
    page_footer();
}

function dashboard_page(): void
{
    page_header('Dashboard');

    $tables = [
        'staff' => 'Προσωπικό',
        'doctor' => 'Ιατροί',
        'nurse' => 'Νοσηλευτές',
        'admin_staff' => 'Διοικητικοί',
        'department' => 'Τμήματα',
        'bed' => 'Κλίνες',
        'patient' => 'Ασθενείς',
        'hospitalization' => 'Νοσηλείες',
        'triage' => 'Triage',
        'prescription' => 'Συνταγές',
        'medical_procedure' => 'Ιατρικές πράξεις',
        'exam' => 'Εξετάσεις',
        'drug' => 'Φάρμακα',
        'active_substance' => 'Δραστικές ουσίες',
        'entity_image' => 'Εικόνες'
    ];


    dashboard_quick_actions();

    echo '<section class="stats-grid">';

    foreach ($tables as $table => $label) {
        $href = '?page=manage&table=' . rawurlencode($table);

        echo '<a class="stat-card stat-card-link" href="' . h($href) . '">'
            . '<span>' . h($label) . '</span>'
            . '<strong>' . h(table_count($table)) . '</strong>'
            . '<small>' . h($table) . '</small>'
            . '<em>Προβολή / αλλαγές</em>'
            . '</a>';
    }

    echo '</section>';

    echo '<section class="two-columns">';

    echo '<article class="panel"><h2>Κατάσταση κλινών</h2>';
    $bedRows = run_select("SELECT status, COUNT(*) AS total FROM bed GROUP BY status ORDER BY total DESC");
    render_table($bedRows);
    echo '</article>';

    echo '<article class="panel"><h2>Νοσηλείες ανά έτος</h2>';
    $yearRows = run_select("SELECT EXTRACT(YEAR FROM admission_date) AS year, COUNT(*) AS hospitalizations, ROUND(SUM(COALESCE(total_cost,0)),2) AS total_cost FROM hospitalization GROUP BY EXTRACT(YEAR FROM admission_date) ORDER BY year");
    render_table($yearRows);
    echo '</article>';

    echo '</section>';

    echo '<section class="panel">';
    echo '<h2>Ζητούμενα queries</h2>';
    echo '<div class="query-shortcuts">';

    foreach (report_queries() as $id => $q) {
        echo '<a href="?page=queries&id=' . h($id) . '">'
            . '<strong>' . h($q['label']) . '</strong>'
            . '<span>' . h($q['title']) . '</span>'
            . '</a>';
    }

    echo '</div>';
    echo '</section>';

    page_footer();
}

function departments_page(): void
{
    page_header('Departments');
    echo '<section class="section-title"><h1>Τμήματα</h1></section>';
    render_search_form('departments');
    $search = '%' . get_search() . '%';
    $limit = limit_value();
    $rows = run_select(<<<SQL
SELECT
    dep.department_id,
    dep.name,
    dep.description,
    dep.bed_count,
    dep.floor,
    dep.building,
    CONCAT(st.first_name, ' ', st.last_name) AS head_doctor,
    (SELECT COUNT(*) FROM bed b WHERE b.department_id = dep.department_id) AS beds_in_table,
    (SELECT COUNT(*) FROM bed b WHERE b.department_id = dep.department_id AND LOWER(b.status) LIKE '%available%') AS available_beds,
    ei.image_path,
    ei.alt_text
FROM department dep
LEFT JOIN doctor doc ON doc.amka = dep.head_doctor_amka
LEFT JOIN staff st ON st.amka = doc.amka
LEFT JOIN entity_image ei ON ei.entity_type = 'Department' AND ei.entity_key = CAST(dep.department_id AS CHAR)
WHERE dep.name LIKE :search OR dep.description LIKE :search OR dep.building LIKE :search
ORDER BY dep.name
LIMIT $limit
SQL, ['search' => $search]);

    render_cards($rows, function ($r) {
        $label = $r['name'] ?? '';
        return '<article class="entity-card">'
            . image_html($r['image_path'] ?? '', $r['alt_text'] ?? '', $label)
            . '<div class="entity-body"><h2>' . h($label) . '</h2>'
            . '<p>' . h($r['description']) . '</p>'
            . '<dl><dt>ID</dt><dd>' . h($r['department_id']) . '</dd><dt>Κτίριο/Όροφος</dt><dd>' . h($r['building']) . ' / ' . h($r['floor']) . '</dd><dt>Διευθυντής</dt><dd>' . h($r['head_doctor']) . '</dd><dt>Κλίνες</dt><dd>' . h($r['beds_in_table']) . ' στον πίνακα, ' . h($r['available_beds']) . ' διαθέσιμες</dd></dl>'
            . '</div></article>';
    });
    page_footer();
}

function doctors_page(): void
{
    page_header('Doctors');
    echo '<section class="section-title"><h1>Ιατροί</h1></section>';
    render_search_form('doctors');
    $search = '%' . get_search() . '%';
    $limit = limit_value();
    $rows = run_select(<<<SQL
SELECT
    d.amka,
    s.first_name,
    s.last_name,
    TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) AS age,
    s.email,
    s.phone,
    d.licence_number,
    d.speciality,
    d.`rank`,
    CONCAT(sup_s.first_name, ' ', sup_s.last_name) AS supervisor,
    GROUP_CONCAT(DISTINCT dep.name ORDER BY dep.name SEPARATOR ', ') AS departments,
    ei.image_path,
    ei.alt_text
FROM doctor d
JOIN staff s ON s.amka = d.amka
LEFT JOIN doctor sup ON sup.amka = d.supervisor_amka
LEFT JOIN staff sup_s ON sup_s.amka = sup.amka
LEFT JOIN doctor_department dd ON dd.doctor_amka = d.amka
LEFT JOIN department dep ON dep.department_id = dd.department_id
LEFT JOIN entity_image ei ON ei.entity_type = 'Doctor' AND ei.entity_key = d.amka
WHERE d.amka LIKE :search OR s.first_name LIKE :search OR s.last_name LIKE :search OR d.speciality LIKE :search OR d.`rank` LIKE :search
GROUP BY
    d.amka, s.first_name, s.last_name, s.date_of_birth, s.email, s.phone,
    d.licence_number, d.speciality, d.`rank`, sup_s.first_name, sup_s.last_name,
    ei.image_path, ei.alt_text
ORDER BY s.last_name, s.first_name
LIMIT $limit
SQL, ['search' => $search]);

    render_cards($rows, function ($r) {
        $label = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        return '<article class="entity-card">'
            . image_html($r['image_path'] ?? '', $r['alt_text'] ?? '', $label)
            . '<div class="entity-body"><h2>' . h($label) . '</h2>'
            . '<p class="tag-line">' . h($r['speciality']) . ' · ' . h($r['rank']) . '</p>'
            . '<dl><dt>ΑΜΚΑ</dt><dd>' . h($r['amka']) . '</dd><dt>Ηλικία</dt><dd>' . h($r['age']) . '</dd><dt>Τμήματα</dt><dd>' . h($r['departments']) . '</dd><dt>Επόπτης</dt><dd>' . h($r['supervisor']) . '</dd><dt>Email</dt><dd>' . h($r['email']) . '</dd></dl>'
            . '</div></article>';
    });
    page_footer();
}

function patients_page(): void
{
    page_header('Patients');
    echo '<section class="section-title"><h1>Ασθενείς</h1></section>';
    render_search_form('patients');
    $search = '%' . get_search() . '%';
    $limit = limit_value();
    $rows = run_select(<<<SQL
SELECT
    p.amka,
    p.first_name,
    p.last_name,
    p.father_name,
    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
    p.gender,
    p.phone,
    p.email,
    p.profession,
    p.nationality,
    i.provider_name,
    (SELECT COUNT(*) FROM hospitalization h WHERE h.patient_amka = p.amka) AS hospitalizations_count,
    (SELECT MAX(h.admission_date) FROM hospitalization h WHERE h.patient_amka = p.amka) AS last_admission,
    ei.image_path,
    ei.alt_text
FROM patient p
LEFT JOIN insurance i ON i.provider_id = p.provider_id
LEFT JOIN entity_image ei ON ei.entity_type = 'Patient' AND ei.entity_key = p.amka
WHERE p.amka LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR p.phone LIKE :search OR p.email LIKE :search
ORDER BY p.last_name, p.first_name
LIMIT $limit
SQL, ['search' => $search]);

    render_cards($rows, function ($r) {
        $label = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        return '<article class="entity-card">'
            . image_html($r['image_path'] ?? '', $r['alt_text'] ?? '', $label)
            . '<div class="entity-body"><h2>' . h($label) . '</h2>'
            . '<p class="tag-line">' . h($r['provider_name']) . '</p>'
            . '<dl><dt>ΑΜΚΑ</dt><dd>' . h($r['amka']) . '</dd><dt>Ηλικία/Φύλο</dt><dd>' . h($r['age']) . ' / ' . h($r['gender']) . '</dd><dt>Νοσηλείες</dt><dd>' . h($r['hospitalizations_count']) . '</dd><dt>Τελευταία εισαγωγή</dt><dd>' . h($r['last_admission']) . '</dd><dt>Email</dt><dd>' . h($r['email']) . '</dd></dl>'
            . '</div></article>';
    });
    page_footer();
}

function nurses_page(): void
{
    page_header('Nurses');
    echo '<section class="section-title"><h1>Νοσηλευτές</h1></section>';
    render_search_form('nurses');
    $search = '%' . get_search() . '%';
    $limit = limit_value();
    $rows = run_select(<<<SQL
SELECT
    n.amka,
    s.first_name,
    s.last_name,
    TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) AS age,
    s.email,
    s.phone,
    n.grade,
    dep.name AS department_name,
    ei.image_path,
    ei.alt_text
FROM nurse n
JOIN staff s ON s.amka = n.amka
JOIN department dep ON dep.department_id = n.department_id
LEFT JOIN entity_image ei ON ei.entity_type = 'Nurse' AND ei.entity_key = n.amka
WHERE n.amka LIKE :search OR s.first_name LIKE :search OR s.last_name LIKE :search OR n.grade LIKE :search OR dep.name LIKE :search
ORDER BY s.last_name, s.first_name
LIMIT $limit
SQL, ['search' => $search]);

    render_cards($rows, function ($r) {
        $label = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        return '<article class="entity-card">'
            . image_html($r['image_path'] ?? '', $r['alt_text'] ?? '', $label)
            . '<div class="entity-body"><h2>' . h($label) . '</h2>'
            . '<p class="tag-line">' . h($r['grade']) . ' · ' . h($r['department_name']) . '</p>'
            . '<dl><dt>ΑΜΚΑ</dt><dd>' . h($r['amka']) . '</dd><dt>Ηλικία</dt><dd>' . h($r['age']) . '</dd><dt>Email</dt><dd>' . h($r['email']) . '</dd><dt>Τηλέφωνο</dt><dd>' . h($r['phone']) . '</dd></dl>'
            . '</div></article>';
    });
    page_footer();
}

function admin_page(): void
{
    page_header('Admin Staff');
    echo '<section class="section-title"><h1>Διοικητικό προσωπικό</h1></section>';
    render_search_form('admin');
    $search = '%' . get_search() . '%';
    $limit = limit_value();
    $rows = run_select(<<<SQL
SELECT
    a.amka,
    s.first_name,
    s.last_name,
    TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) AS age,
    s.email,
    s.phone,
    a.role,
    a.office,
    dep.name AS department_name,
    ei.image_path,
    ei.alt_text
FROM admin_staff a
JOIN staff s ON s.amka = a.amka
JOIN department dep ON dep.department_id = a.department_id
LEFT JOIN entity_image ei ON ei.entity_type = 'Administrative' AND ei.entity_key = a.amka
WHERE a.amka LIKE :search OR s.first_name LIKE :search OR s.last_name LIKE :search OR a.role LIKE :search OR dep.name LIKE :search
ORDER BY s.last_name, s.first_name
LIMIT $limit
SQL, ['search' => $search]);

    render_cards($rows, function ($r) {
        $label = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        return '<article class="entity-card">'
            . image_html($r['image_path'] ?? '', $r['alt_text'] ?? '', $label)
            . '<div class="entity-body"><h2>' . h($label) . '</h2>'
            . '<p class="tag-line">' . h($r['role']) . ' · ' . h($r['department_name']) . '</p>'
            . '<dl><dt>ΑΜΚΑ</dt><dd>' . h($r['amka']) . '</dd><dt>Γραφείο</dt><dd>' . h($r['office']) . '</dd><dt>Email</dt><dd>' . h($r['email']) . '</dd><dt>Τηλέφωνο</dt><dd>' . h($r['phone']) . '</dd></dl>'
            . '</div></article>';
    });
    page_footer();
}

function rooms_page(): void
{
    page_header('Rooms');
    echo '<section class="section-title"><h1>Χειρουργεία / αίθουσες επεμβάσεων</h1></section>';
    render_search_form('rooms');
    $search = '%' . get_search() . '%';
    $limit = limit_value();
    $rows = run_select(<<<SQL
SELECT
    r.room_id,
    r.room_number,
    r.floor,
    r.building,
    r.type,
    r.status,
    (SELECT COUNT(*) FROM medical_procedure mp WHERE mp.room_id = r.room_id) AS procedures_count,
    ei.image_path,
    ei.alt_text
FROM operating_room r
LEFT JOIN entity_image ei ON ei.entity_type = 'Procedure Room' AND ei.entity_key = CAST(r.room_id AS CHAR)
WHERE r.room_number LIKE :search OR r.type LIKE :search OR r.status LIKE :search OR r.building LIKE :search
ORDER BY r.room_number
LIMIT $limit
SQL, ['search' => $search]);

    render_cards($rows, function ($r) {
        $label = 'Room ' . ($r['room_number'] ?? '');
        return '<article class="entity-card">'
            . image_html($r['image_path'] ?? '', $r['alt_text'] ?? '', $label)
            . '<div class="entity-body"><h2>' . h($label) . '</h2>'
            . '<p class="tag-line">' . h($r['type']) . ' · ' . h($r['status']) . '</p>'
            . '<dl><dt>ID</dt><dd>' . h($r['room_id']) . '</dd><dt>Κτίριο/Όροφος</dt><dd>' . h($r['building']) . ' / ' . h($r['floor']) . '</dd><dt>Πράξεις</dt><dd>' . h($r['procedures_count']) . '</dd></dl>'
            . '</div></article>';
    });
    page_footer();
}

function dashboard_quick_actions(): void
{
    echo '<section class="quick-action-strip quick-action-single">';
    echo '<div><p class="eyebrow">Quick Action</p><h2>Διαχείριση εγγραφών</h2></div>';
    echo '</section>';
}

function manage_page(): void
{
    $table = $_GET['table'] ?? 'patient';
    if (!is_editable_table($table)) {
        $table = 'patient';
    }

    $message = handle_manage_post($table);
    $mode = $_GET['mode'] ?? 'list';
    $search = get_search();
    $limit = limit_value();
    $tables = editable_tables();

    page_header('Data Manager');
    echo '<section class="section-title manager-title"><h1>Διαχείριση δεδομένων</h1></section>';

    if ($message) {
        $class = ($message['type'] ?? '') === 'ok' ? 'success-box' : 'error-box';
        echo '<div class="empty-box ' . h($class) . '">' . h($message['text'] ?? '') . '</div>';
    }

    echo '<div class="manager-layout">';
    echo '<aside class="manager-sidebar"><h2>Πίνακες</h2><input class="client-filter" type="text" placeholder="Φίλτρο πινάκων..." data-filter-target=".manager-table-link">';
    echo '<div class="manager-table-list">';
    foreach ($tables as $t => $label) {
        $active = $t === $table ? 'active' : '';
        echo '<a class="manager-table-link ' . $active . '" href="?page=manage&table=' . h($t) . '"><strong>' . h($label) . '</strong><span>' . h($t) . ' · ' . h(table_count($t)) . ' εγγραφές</span></a>';
    }
    echo '</div></aside>';

    echo '<section class="manager-main">';
    echo '<article class="panel manager-toolbar-panel"><div><h2>' . h(table_label($table)) . '</h2><p class="small-muted">Πίνακας: <code>' . h($table) . '</code></p></div>';
    echo '<form class="toolbar compact-toolbar" method="get">';
    echo '<input type="hidden" name="page" value="manage"><input type="hidden" name="table" value="' . h($table) . '">';
    echo '<label>Αναζήτηση <input type="text" name="search" value="' . h($search) . '" placeholder="ψάξε σε όλα τα πεδία"></label>';
    echo '<label>Εγγραφές <input type="number" name="limit" min="10" step="10" value="' . h($limit) . '"></label>';
    echo '<button type="submit">Φιλτράρισμα</button><a class="ghost-button" href="?page=manage&table=' . h($table) . '">Καθαρισμός</a>';
    echo '</form></article>';

    if ($mode === 'add') {
        render_manage_form($table, 'add', null);
    } elseif ($mode === 'edit') {
        $pkValues = get_pk_from_array($_GET, primary_key_columns($table));
        $row = get_row_by_pk($table, $pkValues);
        if ($row) {
            render_manage_form($table, 'edit', $row);
        } else {
            echo '<div class="empty-box error-box">Δεν βρέθηκε η εγγραφή για επεξεργασία.</div>';
        }
    }

    $rows = managed_rows($table, $search, $limit);
    echo '<article class="panel"><div class="panel-head"><div><h2>Εγγραφές πίνακα</h2><p class="small-muted">Εμφάνιση έως ' . h($limit) . ' εγγραφών.</p></div><a class="primary-action" href="?page=manage&table=' . h($table) . '&mode=add">+ Νέα εγγραφή</a></div>';
    render_manage_table($table, $rows);
    echo '</article>';
    echo '</section></div>';

    page_footer();
}

function query_default_value(array $param): string
{
    if (!empty($param['default_sql'])) {
        $value = fetch_one_value($param['default_sql'], [], null);
        if ($value !== null && $value !== '') {
            return (string)$value;
        }
    }
    return (string)($param['default'] ?? '');
}

function render_query_form(string $id, array $query, string $variant, bool $explain, array $values): void
{
    echo '<form class="query-form" method="get">';
    echo '<input type="hidden" name="page" value="queries">';
    echo '<input type="hidden" name="id" value="' . h($id) . '">';

    if (!empty($query['variants'])) {
        echo '<label>Έκδοση query <select name="variant">';
        foreach ($query['variants'] as $key => $v) {
            $selected = $key === $variant ? 'selected' : '';
            echo '<option value="' . h($key) . '" ' . $selected . '>' . h($v['label']) . '</option>';
        }
        echo '</select></label>';
    }

    if (!empty($query['params'])) {
        foreach ($query['params'] as $name => $param) {
            $value = $values[$name] ?? query_default_value($param);
            echo '<label>' . h($param['label']);
            if (($param['type'] ?? 'text') === 'select') {
                echo '<select name="' . h($name) . '">';
                $rows = option_rows($param['options_sql'] ?? 'SELECT 1 AS value, 1 AS label');
                $found = false;
                foreach ($rows as $row) {
                    $optValue = (string)$row['value'];
                    $optLabel = (string)$row['label'];
                    if ($optValue === (string)$value) {
                        $found = true;
                    }
                    $selected = $optValue === (string)$value ? 'selected' : '';
                    echo '<option value="' . h($optValue) . '" ' . $selected . '>' . h($optLabel) . '</option>';
                }
                if (!$found && $value !== '') {
                    echo '<option value="' . h($value) . '" selected>' . h($value) . '</option>';
                }
                echo '</select>';
            } else {
                $type = $param['type'] ?? 'text';
                echo '<input type="' . h($type) . '" name="' . h($name) . '" value="' . h($value) . '">';
            }
            echo '</label>';
        }
    }

    if (!empty($query['allow_explain'])) {
        $checked = $explain ? 'checked' : '';
        echo '<label class="checkbox-label"><input type="checkbox" name="explain" value="1" ' . $checked . '> Εμφάνιση EXPLAIN</label>';
    }

    echo '<button type="submit">Εκτέλεση</button>';
    echo '</form>';
}

function queries_page(): void
{
    page_header('Queries');
    $queries = report_queries();
    $id = $_GET['id'] ?? 'q01';
    if (!isset($queries[$id])) {
        $id = 'q01';
    }
    $query = $queries[$id];
    $variant = $_GET['variant'] ?? 'normal';
    $explain = isset($_GET['explain']) && !empty($query['allow_explain']);

    if (!empty($query['variants']) && !isset($query['variants'][$variant])) {
        $variant = array_key_first($query['variants']);
    }

    echo '<section class="section-title"><h1>Queries Q1-Q15</h1></section>';

    echo '<div class="queries-layout">';
    echo '<aside class="query-list">';
    foreach ($queries as $qid => $q) {
        $active = $qid === $id ? 'active' : '';
        echo '<a class="' . $active . '" href="?page=queries&id=' . h($qid) . '"><strong>' . h($q['label']) . '</strong><span>' . h($q['title']) . '</span></a>';
    }
    echo '</aside>';

    echo '<section class="query-panel">';
    echo '<h2>' . h($query['label'] . ' - ' . $query['title']) . '</h2>';
    $description = str_replace('Υπάρχει και έκδοση FORCE INDEX.', '', (string)($query['description'] ?? ''));
    if (trim($description) !== '') {
        echo '<p>' . h(trim($description)) . '</p>';
    }

    $values = [];
    $paramsForSql = [];
    if (!empty($query['params'])) {
        foreach ($query['params'] as $name => $param) {
            $values[$name] = $_GET[$name] ?? query_default_value($param);
            $paramsForSql[$name] = $values[$name];
        }
    }

    render_query_form($id, $query, $variant, $explain, $values);

    $sql = '';
    if (!empty($query['variants'])) {
        $sql = $query['variants'][$variant]['sql'];
    } else {
        $sql = $query['sql'];
    }
    if ($explain) {
        $sql = 'EXPLAIN ' . $sql;
    }

    $start = microtime(true);
    try {
        $rows = run_select($sql, $paramsForSql);
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        echo '<div class="result-meta">Αποτελέσματα: <strong>' . h(count($rows)) . '</strong></div>';
        render_table($rows);
    } catch (Throwable $e) {
        echo '<div class="empty-box error-box"><strong>Σφάλμα query:</strong><br>' . h($e->getMessage()) . '</div>';
    }

    echo '<details class="sql-details"><summary>Προβολή SQL</summary><pre>' . h($sql) . '</pre></details>';
    echo '</section></div>';

    page_footer();
}


function is_allowed_custom_sql(string $sql): bool
{
    $trimmed = trim($sql);
    $trimmed = preg_replace('/^\s*\(+\s*/', '', $trimmed);
    return (bool)preg_match('/^(SELECT|WITH|EXPLAIN|SHOW|DESCRIBE|DESC)\b/i', $trimmed);
}

function sql_query_page(): void
{
    page_header('SQL Query');

    $sql = trim((string)($_POST['custom_sql'] ?? "SELECT table_name, table_rows\nFROM information_schema.tables\nWHERE table_schema = DATABASE()\nORDER BY table_name"));

    echo '<section class="section-title"><h1>SQL Query</h1></section>';
    echo '<section class="query-panel sql-runner">';
    echo '<form method="post" class="custom-sql-form">';
    echo '<label>Δικό σου SQL query<textarea name="custom_sql" spellcheck="false">' . h($sql) . '</textarea></label>';
    echo '<div class="form-actions"><button type="submit">Εκτέλεση query</button><a class="ghost-button" href="?page=sql">Καθαρισμός</a></div>';
    echo '</form>';
    echo '<div class="note-box">Για ασφάλεια επιτρέπονται μόνο queries ανάγνωσης: <code>SELECT</code>, <code>WITH</code>, <code>EXPLAIN</code>, <code>SHOW</code>, <code>DESCRIBE</code>.</div>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $withoutTrailing = rtrim($sql);
            $withoutTrailing = preg_replace('/;\s*$/', '', $withoutTrailing);
            if (str_contains($withoutTrailing, ';')) {
                throw new RuntimeException('Επιτρέπεται μόνο ένα SQL statement κάθε φορά.');
            }
            if (!is_allowed_custom_sql($withoutTrailing)) {
                throw new RuntimeException('Για ασφάλεια το SQL Query δέχεται μόνο SELECT, WITH, EXPLAIN, SHOW ή DESCRIBE.');
            }

            $start = microtime(true);
            $stmt = db()->query($withoutTrailing);
            $rows = $stmt->fetchAll();
            $elapsed = round((microtime(true) - $start) * 1000, 2);
            echo '<div class="result-meta">Αποτελέσματα: <strong>' . h(count($rows)) . '</strong> · Χρόνος: <strong>' . h($elapsed) . ' ms</strong></div>';
            render_table($rows);
        } catch (Throwable $e) {
            echo '<div class="empty-box error-box"><strong>Σφάλμα SQL:</strong><br>' . h($e->getMessage()) . '</div>';
        }
    }

    echo '</section>';
    page_footer();
}

try {
    db();
    $page = current_page();
    switch ($page) {
        case 'departments':
            departments_page();
            break;
        case 'doctors':
            doctors_page();
            break;
        case 'patients':
            patients_page();
            break;
        case 'nurses':
            nurses_page();
            break;
        case 'admin':
            admin_page();
            break;
        case 'rooms':
            rooms_page();
            break;
        case 'manage':
            manage_page();
            break;
        case 'queries':
            queries_page();
            break;
        case 'sql':
            sql_query_page();
            break;
        default:
            dashboard_page();
    }
} catch (Throwable $e) {
    connection_error_page($e);
}
