<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

if (!defined('DEFAULT_PAGE_SIZE')) {
    define('DEFAULT_PAGE_SIZE', 20);
}

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$roleid = optional_param('roleid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$meta = optional_param('meta', 'lastname', PARAM_TEXT);
$sortdir = optional_param('dir', 'DESC', PARAM_TEXT);

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

$user = ues_user::get_by_id($USER->id);

$allroles = get_all_roles();
$roles = get_profile_roles($context);

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

$groupmode = groups_get_course_groupmode($course);

// UES section enrollment
$all_sections = ues_section::from_course($course);

$primary = has_capability('moodle/site:accessallgroups', $context);

$enrolled_sections = function($class, $extra = array()) use ($all_sections) {
    global $USER;

    $sql = ues::where()
        ->sectionid->in(array_keys($all_sections))
        ->status->in(ues::ENROLLED, ues::PROCESSED)
        ->userid->equal($USER->id);

    foreach ($extra as $key => $value) {
        $sql->{$key}->equal($value);
    }

    $sections = array();
    $instances = $class::get_all($sql);

    foreach ($instances as $instance) {
        $sections[$instance->sectionid] = $all_sections[$instance->sectionid];
    }

    return $sections;
};

if ($primary) {
    $sections = $all_sections;
} else if (!$user->is_primary()) {
    $sections = $enrolled_sections('ues_teacher', array('primary_flag' => 0));
} else {
    $sections = $enrolled_sections('ues_student');
}

if ($groupid) {
    $active_sections = array($sections[$groupid]);
}

$meta_names = ues_user::get_meta_names();

$using_meta_sort = in_array($meta, $meta_names);
$using_section_sort = $meta == 'section';

$is_separate_groups = (
    $course->groupmode == SEPARATEGROUPS and
    !has_capability('moodle/site:accessallgroups', $context)
);

$PAGE->set_title("$course->shortname: " . get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

echo $OUTPUT->header();

$table = new html_table();

echo html_writer::table($table);

echo $OUTPUT->footer();
