<?php

class block_ues_people extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_ues_people');
    }

    function applicable_format() {
        return array('course' => true, 'site' => false, 'my' => false);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $PAGE, $COURSE, $OUTPUT;

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $content = new stdClass;
        $content->items = array();
        $content->icons = array();
        $content->footer = '';

        $this->content = $content;

        $str = get_string('participants');
        $url = new moodle_url('/blocks/ues_people/index.php', array(
            'id' => $COURSE->id
        ));

        $this->content->items[] = html_writer::link($url, $str);
        $this->content->icons[] = $OUTPUT->pix_icon('i/users', $str);

        return $this->content;
    }
}
