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
 * External service definitions for block_ungraded_assignments.
 *
 * @package    block_ungraded_assignments
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_ungraded_assignments_get_ungraded_assignments' => [
        'classname' => 'block_ungraded_assignments\\external',
        'methodname' => 'get_ungraded_assignments',
        'classpath' => '',
        'description' => 'Get ungraded assignments with pagination.',
        'type' => 'read',
        'ajax' => true,
    ],
];
