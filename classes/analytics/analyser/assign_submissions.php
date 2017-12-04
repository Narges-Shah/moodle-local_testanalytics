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
 *
 * @package   core
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_testanalytics\analytics\analyser;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 *
 * @package   core
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submissions extends by_activity {

    protected function filter_by_mod() {
        return 'assign';
    }

    protected function get_analysable_class() {
        return '\local_testanalytics\assign';
    }

    /**
     *
     * @return string
     */
    public function get_samples_origin() {
        return 'assign_submission';
    }

    /**
     * Returns the analysable of a sample
     *
     * @param int $sampleid
     * @return \core_analytics\analysable
     */
    public function get_sample_analysable($sampleid) {
        return new \local_testanalytics\assign($sampleid);
    }

    /**
     *
     * @return string[]
     */
    protected function provided_sample_data() {
        return array('course', 'user', 'context', 'course_modules', 'assign', 'assign_submission');
    }

    /**
     * Returns the context of a sample.
     *
     * @param int $sampleid
     * @return \context
     */
    public function sample_access_context($sampleid) {
        return \context_module::instance($sampleid);
    }

    /**
     *
     * @param \core_analytics\analysable $cm
     * @return array
     */
    protected function get_all_samples(\core_analytics\analysable $cm) {
        global $DB;

        $assign = new \assign($cm->get_cm_info()->context, $cm->get_cm_info(), $cm->get_cm_info()->get_course());

        $participants = $assign->list_participants(0, false);

        $samples = array([], []);
        if (!$participants) {
            return $samples;
        }

        // TODO assign API.
        list($sql, $params) = $DB->get_in_or_equal(array_keys($participants), SQL_PARAMS_NAMED);
        $params['assign'] = $cm->get_cm_info()->instance;
        $submissions = $DB->get_records_select('assign_submission', "assignment = :assign AND userid $sql", $params);
        foreach ($submissions as $as) {
            $samples[0][$as->id] = $as->id;
            $samples[1][$as->id] = array(
                'course' => $assign->get_course(),
                'user' => $participants[$as->userid],
                'context' => $cm->get_cm_info()->context,
                'course_modules' => $cm->get_cm_info()->get_course_module_record(),
                'assign' => $assign->get_instance(),
                'assign_submission' => $as
            );
        }
        return $samples;
    }

    /**
     * Returns samples data from sample ids.
     *
     * @param int[] $sampleids
     * @return array
     */
    public function get_samples($sampleids) {
        global $DB;

        // TODO assign API.
        list($sql, $params) = $DB->get_in_or_equal($sampleids, SQL_PARAMS_NAMED);
        $submissions = $DB->get_records_select('assign_submission', "id $sql", $params);

        // To save db queries.
        // TODO Maybe replace by ad-hoc caches with limited staticaccelerationsize for static models get_samples
        // call, it can end up containing a large amount of different assignments.
        $courses = [];
        $cms = [];
        $assigns = [];
        $participants = [];

        $samples = array([], []);
        foreach ($submissions as $as) {

            if (empty($assigns[$as->assignment])) {
                list($courses[$as->course], $cm) = get_course_and_cm_from_instance($as->assignment, 'assign', 0, -1);
                $cms[$cm->id] = $cm;
                $assigns[$as->assignment] = new \assign($cm->context, $cms[$cm->id], $course);
                $participants[$as->assignment] = $assign->list_participants(0, false);
            }

            $samples[0][$as->id] = $as->id;
            $samples[1][$as->id] = array(
                'course' => $assigns[$as->assignment]->get_course(),
                'user' => $participants[$as->assignment][$as->userid],
                'context' => $cms[$as->assignment]->get_cm_info()->context,
                'course_modules' => $cms[$as->assignment]->get_cm_info()->get_course_module_record(),
                'assign' => $assigns[$as->assignment]->get_instance(),
                'assign_submission' => $as
            );
        }

        return $samples;
    }

    /**
     * Returns the sample description
     *
     * @param int $sampleid
     * @param int $contextid
     * @param array $sampledata
     * @return array array(string, \renderable)
     */
    public function sample_description($sampleid, $contextid, $sampledata) {
        $description = format_string($sampledata['assign']->name, true, array('context' => $sampledata['context']));
        $image = new \image_icon('icon', $description, 'assign');
        return array($description, $image);
    }
}