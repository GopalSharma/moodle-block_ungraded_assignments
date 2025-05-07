<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains the class for the Ungraded Activities.
 *
 * @package    block_ungraded_activities
 * @copyright  2025 Abhishek Karadbhuje <abhishek.karadbhuje@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_ungraded_activities extends block_base {
    public function init() {
        $this->title = get_string('ungraded_activities', 'block_ungraded_activities');
    }

    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $context = new stdClass();
        $context->allactivities = $this->get_ungraded_activities();

        $this->content->text = $OUTPUT->render_from_template('block_ungraded_activities/activity_listing', $context);

        return $this->content;
    }

    /**
     * This function will get all ungraded activities which requires grading.
     *
     * @return array List of ungraded activities.
     */
    protected function get_ungraded_activities() {
        $allactivities = array();
        $this->get_ungraded_assignments($allactivities);
        $this->get_ungraded_quizes($allactivities);
        return array_values($allactivities);
    }

    /**
     * This function will get all ungraded assignments which requires grading.
     *
     * @return array List of ungraded assignments.
     */
    protected function get_ungraded_assignments(&$allactivities) {
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

        foreach ($assignments as $assignment) {
            list ($course, $cm) = get_course_and_cm_from_cmid($assignment->cmid, 'assign');
            $context = context_module::instance($cm->id);

            if (require_capability('mod/assign:grade', $context)) {
                continue;
            }

            $assign = new assign($context, $cm, $course);
            $table = new assign_grading_table($assign, 0, ASSIGN_FILTER_REQUIRE_GRADING, 0, false);
            $userid = $table->get_column_data('userid');

            if (empty($userid)) {
                continue;
            }

            $assignment->url = new moodle_url('/mod/assign/view.php', ['id' => $assignment->cmid, 'action' => 'grader']);
            $assignment->name = format_string($assignment->name);

            if (!isset($allactivities[$assignment->courseid])) {

                $allactivities[$assignment->courseid] = new stdClass();
                $allactivities[$assignment->courseid]->coursename = format_string($assignment->fullname);
                $allactivities[$assignment->courseid]->activities = array();
            }

            $allactivities[$assignment->courseid]->activities[] = $assignment;
        }

        return array_values($allactivities);
    }


    /**
     * This function will get all ungraded Quizes which requires grading.
     *
     * @return array List of ungraded Quizes.
     */
    protected function get_ungraded_quizes(&$allactivities) {
        global $DB;

        $sql = "SELECT distinct q.id, q.name, c.fullname, cm.id as cmid, c.id as courseid
                    FROM {quiz_attempts} qa
                    JOIN {quiz} q ON qa.quiz = q.id
                    JOIN {course_modules} cm ON cm.instance = q.id AND cm.deletioninprogress = 0 AND cm.visible = 1
                    JOIN {course} c ON c.id = cm.course
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                    WHERE qa.state = 'finished'
                    AND qa.sumgrades IS NULL";

        $quizes = $DB->get_records_sql($sql);

        foreach ($quizes as $quiz) {
            $quiz->url = new moodle_url('/mod/quiz/report.php', ['id' => $quiz->cmid, 'mode' => 'grading']);
            $quiz->name = format_string($quiz->name);
            $quiz->coursename = format_string($quiz->fullname);

            if (!isset($allactivities[$quiz->courseid])) {
                $allactivities[$quiz->courseid] = new stdClass();
                $allactivities[$quiz->courseid]->coursename = $quiz->coursename;
                $allactivities[$quiz->courseid]->activities = array();
            }

            $allactivities[$quiz->courseid]->activities[] = $quiz;
        }

        return array_values($allactivities);
    }
}