<?php
require_once __DIR__ . '/db.php';

function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function current_page(): string
{
    return $_GET['page'] ?? 'dashboard';
}

function nav_link(string $page, string $label): string
{
    $active = current_page() === $page ? 'active' : '';
    return '<a class="nav-link ' . $active . '" href="?page=' . h($page) . '">' . h($label) . '</a>';
}

function get_param(string $name, $default = '')
{
    return $_GET[$name] ?? $default;
}

function get_search(): string
{
    return trim((string)get_param('search', ''));
}

function limit_value(): int
{
    $limit = (int)get_param('limit', DEFAULT_LIMIT);
    if ($limit < 10) {
        return 10;
    }
    if ($limit > 2000) {
        return 2000;
    }
    return $limit;
}

function placeholder_initials(string $label): string
{
    $initials = '';
    foreach (preg_split('/\s+/', trim($label)) as $part) {
        if ($part !== '') {
            if (function_exists('mb_substr')) {
                $initials .= mb_substr($part, 0, 1, 'UTF-8');
            } else {
                $initials .= substr($part, 0, 1);
            }
        }
        $length = function_exists('mb_strlen') ? mb_strlen($initials, 'UTF-8') : strlen($initials);
        if ($length >= 2) {
            break;
        }
    }
    return $initials !== '' ? $initials : '🏥';
}

function image_placeholder_html(string $label = '', string $class = 'entity-image', string $style = ''): string
{
    $styleAttr = $style !== '' ? ' style="' . h($style) . '"' : '';
    return '<div class="' . h($class) . ' image-placeholder"' . $styleAttr . '><span>' . h(placeholder_initials($label)) . '</span></div>';
}

function normalize_image_path(?string $path): string
{
    $path = trim((string)$path);
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^\./#', '', $path);

    $markers = [
        '/hospital_app/',
        '/hospital_wui_mamp_full/',
        '/code/hospital_app/',
    ];
    foreach ($markers as $marker) {
        $pos = strpos($path, $marker);
        if ($pos !== false) {
            $path = substr($path, $pos + strlen($marker));
            break;
        }
    }

    if (str_starts_with($path, 'hospital_app/')) {
        $path = substr($path, strlen('hospital_app/'));
    }
    if (str_starts_with($path, 'code/hospital_app/')) {
        $path = substr($path, strlen('code/hospital_app/'));
    }

    return ltrim($path, '/');
}

function image_html(?string $path, ?string $alt, string $label = '', string $class = 'entity-image'): string
{
    $path = normalize_image_path($path);
    $alt = trim((string)$alt);

    if ($path === '') {
        return image_placeholder_html($label, $class);
    }

    $fallback = image_placeholder_html($label, $class, 'display:none');
    return '<div class="image-frame">'
        . '<img class="' . h($class) . '" src="' . h($path) . '" alt="' . h($alt ?: $label) . '" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'grid\';">'
        . $fallback
        . '</div>';
}

function render_table(array $rows): void
{
    if (!$rows) {
        echo '<div class="empty-box">Δεν βρέθηκαν αποτελέσματα.</div>';
        return;
    }

    $columns = array_keys($rows[0]);
    echo '<div class="table-wrap"><table class="data-table"><thead><tr>';
    foreach ($columns as $column) {
        echo '<th>' . h($column) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($columns as $column) {
            echo '<td>' . h($row[$column]) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function render_search_form(string $page): void
{
    $search = get_search();
    $limit = limit_value();
    echo '<form class="toolbar" method="get">';
    echo '<input type="hidden" name="page" value="' . h($page) . '">';
    echo '<label>Αναζήτηση <input type="text" name="search" value="' . h($search) . '" placeholder="όνομα, ΑΜΚΑ, τμήμα..."></label>';
    echo '<label>Εγγραφές <input type="number" name="limit" min="10" step="10" value="' . h($limit) . '"></label>';
    echo '<button type="submit">Εμφάνιση</button>';
    echo '<a class="ghost-button" href="?page=' . h($page) . '">Καθαρισμός</a>';
    echo '</form>';
}

function option_rows(string $sql, array $params = []): array
{
    try {
        return run_select($sql, $params);
    } catch (Throwable $e) {
        return [];
    }
}

function render_cards(array $rows, callable $cardRenderer): void
{
    if (!$rows) {
        echo '<div class="empty-box">Δεν βρέθηκαν δεδομένα.</div>';
        return;
    }
    echo '<div class="cards-grid">';
    foreach ($rows as $row) {
        echo $cardRenderer($row);
    }
    echo '</div>';
}

function editable_tables(): array
{
    return [
        'patient' => 'Ασθενείς',
        'staff' => 'Προσωπικό',
        'doctor' => 'Ιατροί',
        'nurse' => 'Νοσηλευτές',
        'admin_staff' => 'Διοικητικοί',
        'department' => 'Τμήματα',
        'bed' => 'Κλίνες',
        'operating_room' => 'Χειρουργεία / Αίθουσες',
        'triage' => 'Triage',
        'hospitalization' => 'Νοσηλείες',
        'exam' => 'Εξετάσεις',
        'medical_procedure' => 'Ιατρικές πράξεις',
        'procedure_staff' => 'Προσωπικό πράξεων',
        'prescription' => 'Συνταγές',
        'drug' => 'Φάρμακα',
        'active_substance' => 'Δραστικές ουσίες',
        'drug_active_substance' => 'Φάρμακο - ουσία',
        'patient_allergy' => 'Αλλεργίες ασθενών',
        'insurance' => 'Ασφαλιστικοί φορείς',
        'emergency_contact' => 'Επαφές ανάγκης',
        'diagnosis' => 'ICD-10 διαγνώσεις',
        'ken' => 'ΚΕΝ',
        'shift' => 'Βάρδιες',
        'shift_assignment' => 'Αναθέσεις βαρδιών',
        'hospitalization_evaluation' => 'Αξιολογήσεις νοσηλείας',
        'doctor_evaluation' => 'Αξιολογήσεις ιατρών',
        'entity_image' => 'Εικόνες οντοτήτων',
    ];
}

function is_editable_table(string $table): bool
{
    return array_key_exists($table, editable_tables());
}

function table_label(string $table): string
{
    $tables = editable_tables();
    return $tables[$table] ?? $table;
}

function qi(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function table_columns(string $table): array
{
    if (!is_editable_table($table)) {
        return [];
    }
    $rows = run_select(
        "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA, CHARACTER_MAXIMUM_LENGTH
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table
         ORDER BY ORDINAL_POSITION",
        ['schema' => DB_NAME, 'table' => $table]
    );
    $columns = [];
    foreach ($rows as $row) {
        $columns[$row['COLUMN_NAME']] = $row;
    }
    return $columns;
}

function primary_key_columns(string $table): array
{
    $columns = table_columns($table);
    $pk = [];
    foreach ($columns as $name => $meta) {
        if (($meta['COLUMN_KEY'] ?? '') === 'PRI') {
            $pk[] = $name;
        }
    }
    return $pk;
}

function column_label(string $column): string
{
    $labels = [
        'amka' => 'ΑΜΚΑ',
        'patient_amka' => 'ΑΜΚΑ ασθενή',
        'doctor_amka' => 'ΑΜΚΑ ιατρού',
        'nurse_amka' => 'ΑΜΚΑ νοσηλευτή',
        'staff_amka' => 'ΑΜΚΑ προσωπικού',
        'department_id' => 'Τμήμα',
        'provider_id' => 'Ασφαλιστικός φορέας',
        'bed_id' => 'Κλίνη',
        'room_id' => 'Αίθουσα',
        'drug_id' => 'Φάρμακο',
        'substance_id' => 'Δραστική ουσία',
        'hospitalization_id' => 'Νοσηλεία',
        'triage_id' => 'Triage',
        'ken_code' => 'ΚΕΝ',
        'icd10_code' => 'ICD-10',
        'admission_icd10_code' => 'Διάγνωση εισαγωγής',
        'discharge_icd10_code' => 'Διάγνωση εξόδου',
        'first_name' => 'Όνομα',
        'last_name' => 'Επώνυμο',
        'date_of_birth' => 'Ημερομηνία γέννησης',
        'email' => 'Email',
        'phone' => 'Τηλέφωνο',
        'image_path' => 'Διαδρομή εικόνας',
        'alt_text' => 'Περιγραφή εικόνας',
    ];
    return $labels[$column] ?? ucfirst(str_replace('_', ' ', $column));
}

function option_sql_for_column(string $table, string $column): ?string
{
    $map = [
        'provider_id' => "SELECT provider_id AS value, CONCAT(provider_id, ' - ', provider_name) AS label FROM insurance ORDER BY provider_name LIMIT 1000",
        'department_id' => "SELECT department_id AS value, CONCAT(department_id, ' - ', name) AS label FROM department ORDER BY name LIMIT 1000",
        'head_doctor_amka' => "SELECT d.amka AS value, CONCAT(d.amka, ' - ', s.first_name, ' ', s.last_name, ' / ', d.speciality) AS label FROM doctor d JOIN staff s ON s.amka = d.amka ORDER BY s.last_name, s.first_name LIMIT 1000",
        'supervisor_amka' => "SELECT d.amka AS value, CONCAT(d.amka, ' - ', s.first_name, ' ', s.last_name, ' / ', d.`rank`) AS label FROM doctor d JOIN staff s ON s.amka = d.amka ORDER BY s.last_name, s.first_name LIMIT 1000",
        'doctor_amka' => "SELECT d.amka AS value, CONCAT(d.amka, ' - ', s.first_name, ' ', s.last_name, ' / ', d.speciality) AS label FROM doctor d JOIN staff s ON s.amka = d.amka ORDER BY s.last_name, s.first_name LIMIT 1000",
        'head_doctor_amka' => "SELECT d.amka AS value, CONCAT(d.amka, ' - ', s.first_name, ' ', s.last_name, ' / ', d.speciality) AS label FROM doctor d JOIN staff s ON s.amka = d.amka ORDER BY s.last_name, s.first_name LIMIT 1000",
        'nurse_amka' => "SELECT n.amka AS value, CONCAT(n.amka, ' - ', s.first_name, ' ', s.last_name, ' / ', n.grade) AS label FROM nurse n JOIN staff s ON s.amka = n.amka ORDER BY s.last_name, s.first_name LIMIT 1000",
        'staff_amka' => "SELECT amka AS value, CONCAT(amka, ' - ', first_name, ' ', last_name, ' / ', staff_type) AS label FROM staff ORDER BY last_name, first_name LIMIT 1200",
        'patient_amka' => "SELECT amka AS value, CONCAT(amka, ' - ', first_name, ' ', last_name) AS label FROM patient ORDER BY last_name, first_name LIMIT 1000",
        'bed_id' => "SELECT b.bed_id AS value, CONCAT(b.bed_id, ' - ', b.bed_number, ' / ', d.name, ' / ', b.status) AS label FROM bed b JOIN department d ON d.department_id = b.department_id ORDER BY d.name, b.bed_number LIMIT 1000",
        'ken_code' => "SELECT ken_code AS value, CONCAT(ken_code, ' - ', description) AS label FROM ken ORDER BY ken_code LIMIT 1000",
        'admission_icd10_code' => "SELECT icd10_code AS value, CONCAT(icd10_code, ' - ', description) AS label FROM diagnosis ORDER BY icd10_code LIMIT 1000",
        'discharge_icd10_code' => "SELECT icd10_code AS value, CONCAT(icd10_code, ' - ', description) AS label FROM diagnosis ORDER BY icd10_code LIMIT 1000",
        'icd10_code' => "SELECT icd10_code AS value, CONCAT(icd10_code, ' - ', description) AS label FROM diagnosis ORDER BY icd10_code LIMIT 1000",
        'triage_id' => "SELECT triage_id AS value, CONCAT(triage_id, ' - ', patient_amka, ' / priority ', priority_level) AS label FROM triage ORDER BY triage_id DESC LIMIT 1000",
        'hospitalization_id' => "SELECT hospitalization_id AS value, CONCAT(hospitalization_id, ' - ', patient_amka, ' / ', admission_date) AS label FROM hospitalization ORDER BY hospitalization_id DESC LIMIT 1000",
        'exam_id' => "SELECT exam_id AS value, CONCAT(exam_id, ' - ', exam_code, ' / ', exam_date) AS label FROM exam ORDER BY exam_id DESC LIMIT 1000",
        'room_id' => "SELECT room_id AS value, CONCAT(room_id, ' - ', room_number, ' / ', type, ' / ', status) AS label FROM operating_room ORDER BY room_number LIMIT 1000",
        'procedure_id' => "SELECT procedure_id AS value, CONCAT(procedure_id, ' - ', name, ' / ', procedure_date) AS label FROM medical_procedure ORDER BY procedure_id DESC LIMIT 1000",
        'drug_id' => "SELECT drug_id AS value, CONCAT(drug_id, ' - ', name, ' / ', COALESCE(form,'')) AS label FROM drug ORDER BY name LIMIT 1000",
        'substance_id' => "SELECT substance_id AS value, CONCAT(substance_id, ' - ', name) AS label FROM active_substance ORDER BY name LIMIT 1000",
        'shift_id' => "SELECT shift_id AS value, CONCAT(shift_id, ' - ', shift_date, ' / ', shift_type) AS label FROM shift ORDER BY shift_date DESC, shift_type LIMIT 1000",
    ];
    return $map[$column] ?? null;
}

function fixed_options_for_column(string $table, string $column): array
{
    if ($table === 'staff' && $column === 'staff_type') {
        return ['Doctor' => 'Doctor', 'Nurse' => 'Nurse', 'Administrative' => 'Administrative'];
    }
    if ($table === 'patient' && $column === 'gender') {
        return ['Female' => 'Female', 'Male' => 'Male', 'Other' => 'Other'];
    }
    if ($table === 'bed' && $column === 'status') {
        return ['Available' => 'Available', 'Occupied' => 'Occupied', 'Under Maintenance' => 'Under Maintenance'];
    }
    if ($table === 'operating_room' && $column === 'status') {
        return ['Available' => 'Available', 'Occupied' => 'Occupied', 'Under Maintenance' => 'Under Maintenance'];
    }
    if ($table === 'shift' && $column === 'shift_type') {
        return ['Morning' => 'Morning', 'Evening' => 'Evening', 'Night' => 'Night'];
    }
    if ($table === 'triage' && $column === 'priority_level') {
        return ['1' => '1 - Άμεσο', '2' => '2 - Επείγον', '3' => '3 - Επιτακτικό', '4' => '4 - Λιγότερο επείγον', '5' => '5 - Μη επείγον'];
    }
    if ($table === 'entity_image' && $column === 'entity_type') {
        return [
            'Department' => 'Department', 'Doctor' => 'Doctor', 'Nurse' => 'Nurse',
            'Administrative' => 'Administrative', 'Patient' => 'Patient', 'Procedure Room' => 'Procedure Room'
        ];
    }
    return [];
}

function normalize_form_value($value, array $meta)
{
    $value = is_string($value) ? trim($value) : $value;
    if ($value === '' && ($meta['IS_NULLABLE'] ?? 'NO') === 'YES') {
        return null;
    }
    return $value;
}

function input_value_for_html($value, array $meta): string
{
    if ($value === null) {
        return '';
    }
    if (($meta['DATA_TYPE'] ?? '') === 'datetime' && is_string($value)) {
        return str_replace(' ', 'T', substr($value, 0, 16));
    }
    return (string)$value;
}

function input_type_for_column(string $column, array $meta): string
{
    $dataType = strtolower((string)($meta['DATA_TYPE'] ?? ''));
    if ($column === 'email') return 'email';
    if ($dataType === 'date') return 'date';
    if ($dataType === 'datetime' || $dataType === 'timestamp') return 'datetime-local';
    if ($dataType === 'time') return 'time';
    if (in_array($dataType, ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'double', 'float'], true)) return 'number';
    return 'text';
}

function render_manage_input(string $table, string $column, array $meta, $value, bool $readonly = false): void
{
    $label = column_label($column);
    $valueHtml = input_value_for_html($value, $meta);
    $nullable = (($meta['IS_NULLABLE'] ?? 'NO') === 'YES');
    $required = (!$nullable && !$readonly && strpos((string)($meta['EXTRA'] ?? ''), 'auto_increment') === false) ? 'required' : '';
    $name = 'data[' . $column . ']';

    echo '<label>' . h($label);

    $fixed = fixed_options_for_column($table, $column);
    $optionSql = option_sql_for_column($table, $column);
    if (!$readonly && ($fixed || $optionSql)) {
        echo '<select name="' . h($name) . '" ' . $required . '>';
        if ($nullable) {
            echo '<option value="">— Κενό —</option>';
        }
        if ($fixed) {
            foreach ($fixed as $optValue => $optLabel) {
                $selected = ((string)$optValue === (string)$valueHtml) ? 'selected' : '';
                echo '<option value="' . h($optValue) . '" ' . $selected . '>' . h($optLabel) . '</option>';
            }
        } else {
            $found = false;
            foreach (option_rows($optionSql) as $row) {
                $optValue = (string)$row['value'];
                $optLabel = (string)$row['label'];
                if ($optValue === (string)$valueHtml) $found = true;
                $selected = ($optValue === (string)$valueHtml) ? 'selected' : '';
                echo '<option value="' . h($optValue) . '" ' . $selected . '>' . h($optLabel) . '</option>';
            }
            if (!$found && $valueHtml !== '') {
                echo '<option value="' . h($valueHtml) . '" selected>' . h($valueHtml) . '</option>';
            }
        }
        echo '</select>';
    } else {
        $dataType = strtolower((string)($meta['DATA_TYPE'] ?? ''));
        if (!$readonly && in_array($dataType, ['text', 'longtext'], true)) {
            echo '<textarea name="' . h($name) . '" ' . $required . '>' . h($valueHtml) . '</textarea>';
        } else {
            $type = input_type_for_column($column, $meta);
            $step = in_array($dataType, ['decimal', 'double', 'float'], true) ? ' step="0.01"' : '';
            $ro = $readonly ? 'readonly' : '';
            echo '<input type="' . h($type) . '" name="' . h($name) . '" value="' . h($valueHtml) . '" ' . $required . ' ' . $ro . $step . '>';
        }
    }
    echo '</label>';
}

function get_pk_from_array(array $source, array $pkColumns): array
{
    $pkValues = [];
    foreach ($pkColumns as $col) {
        if (isset($source['pk'][$col])) {
            $pkValues[$col] = $source['pk'][$col];
        }
    }
    return $pkValues;
}

function pk_query(array $pkValues): string
{
    return http_build_query(['pk' => $pkValues]);
}

function get_row_by_pk(string $table, array $pkValues): ?array
{
    $columns = table_columns($table);
    if (!$columns || !$pkValues) return null;
    $where = [];
    $params = [];
    foreach ($pkValues as $col => $val) {
        if (!isset($columns[$col])) continue;
        $where[] = qi($col) . ' = :pk_' . $col;
        $params['pk_' . $col] = $val;
    }
    if (!$where) return null;
    $rows = run_select('SELECT * FROM ' . qi($table) . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1', $params);
    return $rows[0] ?? null;
}

function render_manage_form(string $table, string $mode, ?array $row = null): void
{
    $columns = table_columns($table);
    $pkColumns = primary_key_columns($table);
    $isEdit = $mode === 'edit';
    echo '<article class="panel manager-form-panel">';
    echo '<div class="panel-head"><div><p class="eyebrow">' . ($isEdit ? 'Edit' : 'Add') . '</p><h2>' . ($isEdit ? 'Επεξεργασία' : 'Προσθήκη') . ' - ' . h(table_label($table)) . '</h2></div>';
    echo '<a class="ghost-button" href="?page=manage&table=' . h($table) . '">Κλείσιμο φόρμας</a></div>';

    if ($table === 'doctor') {
        echo '<div class="note-box">Για νέο ιατρό: πρώτα πρόσθεσε εγγραφή στον πίνακα <strong>staff</strong> με staff_type = Doctor και μετά πρόσθεσε εδώ τα στοιχεία doctor με το ίδιο ΑΜΚΑ.</div>';
    } elseif ($table === 'nurse') {
        echo '<div class="note-box">Για νέο νοσηλευτή: πρώτα πρόσθεσε εγγραφή στον πίνακα <strong>staff</strong> με staff_type = Nurse και μετά πρόσθεσε εδώ τα στοιχεία nurse με το ίδιο ΑΜΚΑ.</div>';
    } elseif ($table === 'admin_staff') {
        echo '<div class="note-box">Για νέο διοικητικό: πρώτα πρόσθεσε εγγραφή στον πίνακα <strong>staff</strong> με staff_type = Administrative και μετά πρόσθεσε εδώ τα στοιχεία admin_staff με το ίδιο ΑΜΚΑ.</div>';
    } elseif ($table === 'entity_image') {
        echo '<div class="note-box">Για εικόνα βάλε path όπως <code>images/patients/PAT001.jpg</code>. Το αρχείο πρέπει να υπάρχει μέσα στον φάκελο <code>hospital_app/images/...</code>.</div>';
    }

    echo '<form class="record-form" method="post" action="?page=manage&table=' . h($table) . '">';
    echo '<input type="hidden" name="action" value="' . ($isEdit ? 'update' : 'insert') . '">';
    if ($isEdit && $row) {
        foreach ($pkColumns as $pk) {
            echo '<input type="hidden" name="pk[' . h($pk) . ']" value="' . h($row[$pk] ?? '') . '">';
        }
    }
    echo '<div class="form-grid">';
    foreach ($columns as $name => $meta) {
        $auto = strpos((string)($meta['EXTRA'] ?? ''), 'auto_increment') !== false;
        $isPk = in_array($name, $pkColumns, true);
        if (!$isEdit && $auto) {
            continue;
        }
        $value = $row[$name] ?? ($meta['COLUMN_DEFAULT'] ?? '');
        render_manage_input($table, $name, $meta, $value, $isEdit && $isPk);
    }
    echo '</div><div class="form-actions"><button type="submit">' . ($isEdit ? 'Αποθήκευση αλλαγών' : 'Προσθήκη εγγραφής') . '</button>';
    echo '<a class="ghost-button" href="?page=manage&table=' . h($table) . '">Άκυρο</a></div>';
    echo '</form></article>';
}

function handle_manage_post(string $table): ?array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
    if (!is_editable_table($table)) return ['type' => 'error', 'text' => 'Μη επιτρεπτός πίνακας.'];

    $action = $_POST['action'] ?? '';
    $columns = table_columns($table);
    $pkColumns = primary_key_columns($table);
    try {
        if ($action === 'delete') {
            $pkValues = get_pk_from_array($_POST, $pkColumns);
            if (!$pkValues) throw new RuntimeException('Δεν βρέθηκε primary key για διαγραφή.');
            $where = [];
            $params = [];
            foreach ($pkValues as $col => $val) {
                $where[] = qi($col) . ' = :pk_' . $col;
                $params['pk_' . $col] = $val;
            }
            $stmt = db()->prepare('DELETE FROM ' . qi($table) . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
            $stmt->execute($params);
            return ['type' => 'ok', 'text' => 'Η εγγραφή διαγράφηκε.'];
        }

        $data = $_POST['data'] ?? [];
        if (!is_array($data)) $data = [];

        if ($action === 'insert') {
            $insertCols = [];
            $placeholders = [];
            $params = [];
            foreach ($columns as $name => $meta) {
                $auto = strpos((string)($meta['EXTRA'] ?? ''), 'auto_increment') !== false;
                if ($auto || !array_key_exists($name, $data)) continue;
                $insertCols[] = qi($name);
                $placeholders[] = ':' . $name;
                $params[$name] = normalize_form_value($data[$name], $meta);
            }
            if (!$insertCols) throw new RuntimeException('Δεν υπάρχουν πεδία για εισαγωγή.');
            $sql = 'INSERT INTO ' . qi($table) . ' (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            return ['type' => 'ok', 'text' => 'Η νέα εγγραφή προστέθηκε.'];
        }

        if ($action === 'update') {
            $pkValues = get_pk_from_array($_POST, $pkColumns);
            if (!$pkValues) throw new RuntimeException('Δεν βρέθηκε primary key για ενημέρωση.');
            $set = [];
            $where = [];
            $params = [];
            foreach ($columns as $name => $meta) {
                if (in_array($name, $pkColumns, true) || !array_key_exists($name, $data)) continue;
                $set[] = qi($name) . ' = :' . $name;
                $params[$name] = normalize_form_value($data[$name], $meta);
            }
            foreach ($pkValues as $col => $val) {
                $where[] = qi($col) . ' = :pk_' . $col;
                $params['pk_' . $col] = $val;
            }
            if (!$set) throw new RuntimeException('Δεν υπάρχουν πεδία για αλλαγή.');
            $sql = 'UPDATE ' . qi($table) . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            return ['type' => 'ok', 'text' => 'Οι αλλαγές αποθηκεύτηκαν.'];
        }
    } catch (Throwable $e) {
        return ['type' => 'error', 'text' => $e->getMessage()];
    }
    return null;
}

function managed_rows(string $table, string $search, int $limit): array
{
    $columns = table_columns($table);
    if (!$columns) return [];
    $params = [];
    $whereSql = '';
    if ($search !== '') {
        $parts = [];
        foreach ($columns as $name => $meta) {
            $parts[] = 'CAST(' . qi($name) . ' AS CHAR) LIKE :search';
        }
        $whereSql = ' WHERE ' . implode(' OR ', $parts);
        $params['search'] = '%' . $search . '%';
    }
    $order = primary_key_columns($table);
    $orderSql = $order ? ' ORDER BY ' . implode(', ', array_map('qi', $order)) . ' DESC' : '';
    return run_select('SELECT * FROM ' . qi($table) . $whereSql . $orderSql . ' LIMIT ' . (int)$limit, $params);
}

function render_manage_table(string $table, array $rows): void
{
    if (!$rows) {
        echo '<div class="empty-box">Δεν βρέθηκαν εγγραφές για τον συγκεκριμένο πίνακα.</div>';
        return;
    }
    $pkColumns = primary_key_columns($table);
    $columns = array_keys($rows[0]);
    echo '<div class="table-wrap manager-table"><table class="data-table"><thead><tr>';
    echo '<th>Ενέργειες</th>';
    foreach ($columns as $column) {
        echo '<th>' . h(column_label($column)) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $pkValues = [];
        foreach ($pkColumns as $pk) {
            $pkValues[$pk] = $row[$pk] ?? '';
        }
        $pkQuery = pk_query($pkValues);
        echo '<tr><td class="actions-cell">';
        echo '<a class="mini-button" href="?page=manage&table=' . h($table) . '&mode=edit&' . h($pkQuery) . '">Επεξεργασία</a>';
        echo '<form method="post" action="?page=manage&table=' . h($table) . '" onsubmit="return confirmDelete(this);">';
        echo '<input type="hidden" name="action" value="delete">';
        foreach ($pkValues as $pk => $value) {
            echo '<input type="hidden" name="pk[' . h($pk) . ']" value="' . h($value) . '">';
        }
        echo '<button class="mini-button danger" type="submit">Διαγραφή</button></form>';
        echo '</td>';
        foreach ($columns as $column) {
            $val = $row[$column] ?? '';
            if ($column === 'image_path') {
                echo '<td>' . image_html($val, $row['alt_text'] ?? '', (string)$val, 'thumb-image') . '<div class="small-muted">' . h($val) . '</div></td>';
            } else {
                echo '<td>' . h($val) . '</td>';
            }
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}


function image_url_or_placeholder(?string $path): ?string
{
    if ($path === null || trim($path) === '') {
        return null;
    }

    $path = ltrim(trim($path), '/');

    // Το path στη βάση πρέπει να είναι σχετικό, π.χ. images/doctors/DOC001.jpg
    $absolute = __DIR__ . '/' . $path;

    if (is_file($absolute)) {
        return $path;
    }

    // Δοκίμασε με case-insensitive αναζήτηση στο ίδιο folder, γιατί σε Mac μπορεί να μη φαίνεται πρόβλημα
    // αλλά σε άλλο περιβάλλον να υπάρχει θέμα με DOC001.jpg / doc001.jpg.
    $dir = dirname($absolute);
    $file = strtolower(basename($absolute));
    if (is_dir($dir)) {
        foreach (scandir($dir) ?: [] as $candidate) {
            if (strtolower($candidate) === $file) {
                return dirname($path) . '/' . $candidate;
            }
        }
    }

    return null;
}
