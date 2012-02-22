<?php

abstract class ues_people {
    public static function primary_role() {
        return get_config('enrol_ues', 'editingteacher_role');
    }

    public static function nonprimary_role() {
        return get_config('enrol_ues', 'teacher_role');
    }

    public static function student_role() {
        return get_config('enrol_ues', 'student_role');
    }

    public static function ues_roles() {
        global $DB;

        $role_sql = ues::where()->id->in(
            self::primary_role(), self::nonprimary_role(), self::student_role()
        )->sql();

        return $DB->get_records_sql('SELECT * FROM {role} WHERE ' . $role_sql);
    }

    public static function outputs() {
        $defaults = array('sec_number', 'credit_hours');
        $meta_names = array_merge($defaults, ues_user::get_meta_names());

        $outputs = array();
        foreach ($meta_names as $meta) {
            $outputs[$meta] = new ues_people_element_output($meta);
        }

        $data = new stdClass;
        $data->outputs = $outputs;
        events_trigger('ues_people_outputs', $data);

        return $data->outputs;
    }
}

class ues_people_element_output {
    var $name;

    function __construct($name) {
        $this->name = $name;
    }

    function format($user) {
        if (isset($user->{$this->name})) {
            return $user->{$this->name};
        } else {
            return '';
        }
    }
}
