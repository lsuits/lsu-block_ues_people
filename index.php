<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

require_once 'lib.php';

if (!defined('DEFAULT_PAGE_SIZE')) {
    define('DEFAULT_PAGE_SIZE', 20);
}

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$roleid = optional_param('roleid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$meta = optional_param('meta', 'lastname', PARAM_TEXT);
$sortdir = optional_param('dir', 'ASC', PARAM_TEXT);

$silast = optional_param('silast', 'all', PARAM_TEXT);
$sifirst = optional_param('sifirst', 'all', PARAM_TEXT);

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/blocks/ues_people/index.php', array(
    'id' => $id,
    'roleid' => $roleid,
    'groupid' => $groupid,
    'page' => $page,
    'perpage' => $perpage
));

$PAGE->set_pagelayout('incourse');

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

$context = get_context_instance(CONTEXT_COURSE, $id);

require_capability('moodle/course:viewparticipants', $context);

$_s = ues::gen_str('block_ues_people');

$user = ues_user::get(array('id' => $USER->id));

$allroles = get_all_roles();
$roles = ues_people::ues_roles();

$allrolenames = array();
$rolenames = array(0 => get_string('allparticipants'));
foreach ($allroles as $role) {
    $allrolenames[$role->id] = strip_tags(role_get_name($role, $context));
    if (isset($roles[$role->id])) {
        $rolenames[$role->id] = $allrolenames[$role->id];
    }
}

if (empty($rolenames[$roleid])) {
    print_error('noparticipants');
}

add_to_log($course->id, 'ues_people', 'view all', 'index.php?id='.$course->id, '');

$all_sections = ues_section::from_course($course);

$meta_names = ues_user::get_meta_names();

$using_meta_sort = in_array($meta, $meta_names);
$using_section_sort = $meta == 'section';

$PAGE->set_title("$course->shortname: " . get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

$select = 'SELECT u.id, u.firstname, u.lastname, u.email, sec.sec_number, u.deleted,
                  u.picture, u.imagealt, u.lang, u.timezone, stu.credit_hours';
$joins = array('FROM {user} u');

list($ccselect, $ccjoin) = context_instance_preload_sql('u.id', CONTEXT_USER, 'ctx');

$select .= $ccselect;
$joins[] = $ccjoin;

list($esql, $params) = get_enrolled_sql($context, '', $groupid);

$joins[] = "JOIN ($esql) e ON e.id = u.id";
$joins[] = "LEFT JOIN " . ues_student::tablename('stu') . " ON u.id = stu.userid";
$joins[] = "JOIN " . ues_section::tablename('sec') . " ON stu.sectionid = sec.id";

if ($using_meta_sort) {
    $meta_table = ues_user::metatablename('um');
    $joins[] = 'LEFT JOIN ' . $meta_table. ' ON (
        um.userid = u.id AND um.name = :metakey
    )';
    $params['metakey'] = $meta;
}

$from = implode("\n", $joins);

$wheres = ues::where()
    ->sectionid->in(array_keys($all_sections))
    ->status->in(ues::ENROLLED, ues::PROCESSED);

if ($sifirst != 'all') {
    $wheres->firstname->starts_with($sifirst);
}

if ($silast != 'all') {
    $wheres->lastname->starts_with($silast);
}

if ($roleid) {
    $contextlist = get_related_contexts_string($context);

    $sub = 'SELECT userid FROM {role_assignments}
        WHERE roleid = :roleid AND context ' . $contextlist;

    $wheres->id->raw("IN ($sub)");
}

$where = $wheres->is_empty() ? '' : 'WHERE ' . $wheres->sql(function($k) {
    switch ($k) {
        case 'sectionid': return 'stu.' . $k;
        case 'status': return 'stu.' . $k;
        default: return 'u.' . $k;
    }
});

if ($using_meta_sort) {
    $sort = 'ORDER BY um.value ' . $sortdir;
} else if ($using_section_sort) {
    $sort = 'ORDER BY sec.sec_number ' . $sortdir;
} else {
    $sort = 'ORDER BY u.' . $meta . ' ' . $sortdir;
}

$sql = "$select $from $where $sort";

$users = $DB->get_records_sql($sql, $params, $page, $perpage);

echo $OUTPUT->header();

$table = new html_table();

$headers = array(
    get_string('userpic'),
    get_string('firstname') . ' / ' . get_string('lastname'),
    get_string('email')
);

$headers[] = $_s('sec_number');
$headers[] = $_s('credit_hours');

$table->head = $headers;

foreach ($users as $user) {
    $line = array();
    $line[] = $OUTPUT->user_picture($user, array('courseid' => $id));
    $line[] = fullname($user);
    $line[] = $user->email;
    $line[] = $user->sec_number;
    $line[] = $user->credit_hours;

    $table->data[] = new html_table_row($line);
}

echo html_writer::table($table);

echo $OUTPUT->footer();
