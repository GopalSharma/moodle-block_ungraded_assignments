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
 * External services for ungraded assignments pagination.
 *
 * @package    block_ungraded_assignments
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ungraded_assignments;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External API implementation.
 */
class external extends \external_api {
    /**
     * Parameters for get_ungraded_assignments.
     *
     * @return \external_function_parameters
     */
    public static function get_ungraded_assignments_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'page' => new \external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 1),
        ]);
    }

    /**
     * Returns paginated ungraded activities.
     *
     * @param int $page
     * @return array
     */
    public static function get_ungraded_assignments(int $page = 1): array {
        require_login();

        $params = self::validate_parameters(self::get_ungraded_assignments_parameters(), [
            'page' => $page,
        ]);

        self::validate_context(\context_system::instance());

        return \block_ungraded_assignments\local\service::get_paginated_ungraded_assignments($params['page'], 1);
    }

    /**
     * Return value structure for get_ungraded_assignments.
     *
     * @return \external_single_structure
     */
    public static function get_ungraded_assignments_returns(): \external_single_structure {
        return new \external_single_structure([
            'activities' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'Assignment ID'),
                    'name' => new \external_value(PARAM_TEXT, 'Assignment name'),
                    'coursename' => new \external_value(PARAM_TEXT, 'Course name'),
                    'url' => new \external_value(PARAM_URL, 'Grading URL'),
                    'activitytype' => new \external_value(PARAM_ALPHA, 'Activity type, assignment or quiz'),
                ])
            ),
            'hasnext' => new \external_value(PARAM_BOOL, 'Whether the next page exists'),
            'hasprev' => new \external_value(PARAM_BOOL, 'Whether the previous page exists'),
            'page' => new \external_value(PARAM_INT, 'Current page number'),
        ]);
    }
}
