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
 * Local service for ungraded activities data.
 *
 * @package    block_ungraded_assignments
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ungraded_assignments\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared data service for block rendering and AJAX.
 */
class service {
    /**
     * Get configured records per page with a safe fallback.
     *
     * @return int
     */
    public static function get_records_per_page(): int {
        $configured = (int)get_config('block_ungraded_assignments', 'recordsperpage');
        return $configured > 0 ? $configured : 10;
    }

    /**
     * Returns paginated ungraded activities for the current user.
     *
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public static function get_paginated_ungraded_assignments(int $page = 1, int $perpage = 0): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $page = max(1, $page);
        $perpage = $perpage > 0 ? $perpage : self::get_records_per_page();
        $offset = ($page - 1) * $perpage;
        $limit = ($page * $perpage) + 1;

                $sql = "SELECT a.id, a.name, c.fullname, cm.id AS cmid, c.id AS courseid
                  FROM {assign} a
                  JOIN {course_modules} cm
                    ON cm.instance = a.id AND cm.deletioninprogress = 0 AND cm.visible = 1
                  JOIN {course} c
                    ON c.id = cm.course
                  JOIN {modules} m
                    ON m.id = cm.module AND m.name = 'assign'
              ORDER BY cm.id ASC";

        $assignments = $DB->get_records_sql($sql);
        $allactivities = [];

        foreach ($assignments as $assignment) {
            list($course, $cm) = get_course_and_cm_from_cmid($assignment->cmid, 'assign');
            $context = \context_module::instance($cm->id);

            if (!has_capability('mod/assign:grade', $context)) {
                continue;
            }

            $assign = new \assign($context, $cm, $course);
            $table = new \assign_grading_table($assign, 0, ASSIGN_FILTER_REQUIRE_GRADING, 0, false);
            $userid = $table->get_column_data('userid');

            if (empty($userid)) {
                continue;
            }

            $allactivities[] = [
                'id' => (int)$assignment->id,
                'name' => format_string($assignment->name),
                'coursename' => format_string($assignment->fullname),
                'url' => (new \moodle_url('/mod/assign/view.php', ['id' => $assignment->cmid, 'action' => 'grader']))->out(false),
                'activitytype' => get_string('assignment', 'block_ungraded_assignments'),
                'cmid' => (int)$assignment->cmid,
            ];
        }

        $quizsql = "SELECT DISTINCT q.id, q.name, c.fullname, cm.id AS cmid, c.id AS courseid
                      FROM {quiz_attempts} qa
                      JOIN {quiz} q ON qa.quiz = q.id
                      JOIN {course_modules} cm ON cm.instance = q.id AND cm.deletioninprogress = 0 AND cm.visible = 1
                      JOIN {course} c ON c.id = cm.course
                      JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                     WHERE qa.state = 'finished'
                       AND qa.sumgrades IS NULL
                  ORDER BY cm.id ASC";

        $quizzes = $DB->get_records_sql($quizsql);
        foreach ($quizzes as $quiz) {
            $allactivities[] = [
                'id' => (int)$quiz->id,
                'name' => format_string($quiz->name),
                'coursename' => format_string($quiz->fullname),
                'url' => (new \moodle_url('/mod/quiz/report.php', ['id' => $quiz->cmid, 'mode' => 'grading']))->out(false),
                'activitytype' => get_string('quiz', 'block_ungraded_assignments'),
                'cmid' => (int)$quiz->cmid,
            ];
        }

        usort($allactivities, static function(array $a, array $b): int {
            if ($a['cmid'] === $b['cmid']) {
                return strcmp($a['url'], $b['url']);
            }

            return $a['cmid'] <=> $b['cmid'];
        });

        $total = count($allactivities);
        $currentactivities = array_slice($allactivities, $offset, $perpage);

        foreach ($currentactivities as &$activity) {
            unset($activity['cmid']);
        }
        unset($activity);

        return [
            'activities' => $currentactivities,
            'hasnext' => $total > ($offset + $perpage),
            'hasprev' => $page > 1,
            'page' => $page,
        ];
    }
}
