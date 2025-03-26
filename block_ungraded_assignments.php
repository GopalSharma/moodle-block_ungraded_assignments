<?php

use tool_admin_presets\form\continue_form;
defined('MOODLE_INTERNAL') || die();

class block_ungraded_assignments extends block_base {
    public function init() {
        $this->title = get_string('ungraded_assignments', 'block_ungraded_assignments');
    }

    public function get_content() {
        global $DB, $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $context = new stdClass();
        $context->allassiglist = $this->get_ungraded_assignments();

        $this->content->text = $OUTPUT->render_from_template('block_ungraded_assignments/assignment_listing', $context);

        return $this->content;
    }

    /**
     * This function will get all ungraded assignments which requires grading.
     * 
     * @return array List of ungraded assignments.
     */
    protected function get_ungraded_assignments() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $sql = "SELECT a.id, a.name, c.fullname, cm.id as cmid, c.id as courseid
                FROM {assign} a
                JOIN {course_modules} cm
                ON cm.instance = a.id AND cm.deletioninprogress = 0 AND cm.visible = 1
                JOIN {course} c
                ON c.id = cm.course
                JOIN {modules} m
                ON m.id = cm.module AND m.name = 'assign'";

        $params = ['userid' => $USER->id];
        $assignments = $DB->get_records_sql($sql, $params);

        $allassiglist = array();
        foreach($assignments as $assignment) {
            list ($course, $cm) = get_course_and_cm_from_cmid($assignment->cmid, 'assign');
            $context = context_module::instance($cm->id);

            if(require_capability('mod/assign:grade', $context)) {
                continue;
            }

            $assign = new assign($context, $cm, $course);
            $table = new assign_grading_table($assign, 0, ASSIGN_FILTER_REQUIRE_GRADING, 0, false);
            $userid = $table->get_column_data('userid');

            if(empty($userid)) {
                continue;
            }

            $assignment->url = new moodle_url('/mod/assign/view.php', ['id' => $assignment->cmid, 'action' => 'grader']);
            $assignment->name = format_string($assignment->name);
            $assignment->coursename = format_string($assignment->fullname);

            if(!isset($allassiglist[$assignment->courseid])) {
                $allassiglist[$assignment->courseid] = new stdClass();
                $allassiglist[$assignment->courseid]->coursename = $assignment->coursename;
                $allassiglist[$assignment->courseid]->assignments = array();
            }

            $allassiglist[$assignment->courseid]->assignments[] = $assignment;
        }

        return array_values($allassiglist);
    }
}