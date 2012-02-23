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

    public static function defaults() {
        return explode(',', get_config('block_ues_people', 'outputs'));
    }

    public static function initial_bars($label, $name, $url) {
        $current = optional_param($name, 'all', PARAM_TEXT);

        $bar = html_writer::start_tag('div', array('class' => 'initialbar lastinitial'));
        $bar .= $label . ' : ';

        $letters = array('all' => get_string('all'));
        $alpha = explode(',', get_string('alphabet', 'langconfig'));

        $letters += array_combine($alpha, $alpha);

        foreach ($letters as $key => $letter) {
            if ($key == $current) {
                $bar .= html_writer::tag('strong', $letter);
            } else {
                $bar .= '<a href="'. $url . '&amp;' . $name . '=' . $key.'">'.$letter.'</a>';
            }
        }

        $bar .= html_writer::end_tag('div');

        return $bar;
    }

    public static function outputs() {
        $defaults = self::defaults();

        $internal = array('sec_number', 'credit_hours');
        $meta_names = array_merge($internal, ues_user::get_meta_names());

        $_s = ues::gen_str('block_ues_people');

        $outputs = array();

        foreach ($meta_names as $meta) {
            // Admin choice on limits
            if (!in_array($meta, $defaults)) {
                continue;
            }

            $element = in_array($meta, $internal) ?
                new ues_people_element_output($meta, $_s($meta)) :
                new ues_people_element_output($meta);

            $outputs[$meta] = $element;
        }

        $data = new stdClass;
        $data->outputs = $outputs;

        // Plugin interference
        events_trigger('ues_people_outputs', $data);

        return $data->outputs;
    }
}

class ues_people_element_output {
    var $name;
    var $field;

    function __construct($field, $name = '') {
        $this->field = $field;
        if (empty($name)) {
            $name = $field;
        }
        $this->name = $name;
    }

    function format($user) {
        if (isset($user->{$this->field})) {
            return $user->{$this->field};
        } else {
            return '';
        }
    }
}
