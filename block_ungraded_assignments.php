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
 * @package    block_ungraded_assignments
 * @copyright  2025 Abhishek Karadbhuje <abhishek.karadbhuje@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_ungraded_assignments extends block_base {
    public function init() {
        $this->title = get_string('ungraded_activities', 'block_ungraded_assignments');
    }

    /**
     * Expose site-level block configuration.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $assignmentdata = \block_ungraded_assignments\local\service::get_paginated_ungraded_assignments(1);
        $context = new stdClass();
        $context->activities = $assignmentdata['activities'];
        $context->hasnext = $assignmentdata['hasnext'];
        $context->hasprev = $assignmentdata['hasprev'];
        $context->page = $assignmentdata['page'];
        $context->blockid = $this->instance->id;

        $this->content->text = $OUTPUT->render_from_template('block_ungraded_assignments/activity_listing', $context);

        return $this->content;
    }
}