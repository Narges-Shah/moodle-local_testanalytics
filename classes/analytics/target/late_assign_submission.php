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
 * @package   core_analytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_testanalytics\analytics\target;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @package   core_analytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class late_assign_submission extends \core_analytics\local\target\binary {

    public static function get_name() : \lang_string {
        return new \lang_string('lateassignsubmission', 'local_testanalytics');
    }

    /**
     * Returns the analyser class that should be used along with this target.
     *
     * @return string The full class name as a string
     */
    public function get_analyser_class() {
        return '\local_testanalytics\analytics\analyser\assign_submissions';
    }

    /**
     * Allows the target to verify that the analysable is a good candidate.
     *
     * This method can be used as a quick way to discard invalid analysables.
     * e.g. Imagine that your analysable don't have students and you need them.
     *
     * @param \core_analytics\analysable $analysable
     * @param bool $fortraining
     * @return true|string
     */
    public function is_valid_analysable(\core_analytics\analysable $cm, $fortraining = true) {
        if (!$cm->get_end()) {
            return 'no due date';
        }

        if (!$cm->get_cm_info()->visible) {
            return 'course module not visible';
        }

        if ($cm->get_start() > time()) {
            return 'not yet started';
        }

        if ($fortraining && $cm->get_end() > time()) {
            return 'still open';
        }

        // Weird but possible.
        if ($cm->get_start() >= $cm->get_end()) {
            return 'wrong dates';
        }

        if ($cm->get_instance()->teamsubmission) {
            // Method calculate_sample looks for logs; on team submissions assignments not
            // all students have logs.
            return 'sorry, this model can\'t use team submissions assignments yet';
        }

        return true;
    }

    /**
     * Checks user enrolments timestart and timeends.
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $analysable
     * @param bool $fortraining
     * @return bool
     */
    public function is_valid_sample($sampleid, \core_analytics\analysable $cm, $fortraining = true) {

        $userenrol = $this->retrieve('user_enrolments', $sampleid);
        $lastaccess = $this->retrieve('user_lastaccess', $sampleid);
        $course = $this->retrieve('course', $sampleid);
        $assign = $this->retrieve('assign', $sampleid);

        if (!$lastaccess) {
            return false;
        }

        if ($userenrol->timeend &&
                ($course->startdate > $userenrol->timeend || $assign->duedate > $userenrol->timeend)) {
            // Discard enrolments which time end is prior to the course start or assignment due date. This should get rid of
            // old user enrolments that remain on the course.
            return false;
        }

        if ($assign->duedate) {
            $limit = $assign->duedate - (YEARSECS + (WEEKSECS * 4));
        } else if ($course->startdate) {
            $limit = $course->startdate - (YEARSECS + (WEEKSECS * 4));
        }

        if ($limit) {

            if (($userenrol->timestart && $userenrol->timestart < $limit) ||
                    (!$userenrol->timestart && $userenrol->timecreated < $limit)) {
                // We will discard enrolments that last more than 1 academic year
                // because they have incorrect start and end dates or because they are reused along multiple years
                // without removing previous academic years students. This may not be very accurate because some courses
                // can last just some months, but it is better than nothing and they will be flagged as drop out anyway
                // in most of the cases.
                return false;
            }

            // Same reason than above.
            if ($lastaccess->timeaccess < $limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculates this target for the provided samples.
     *
     * In case there are no values to return or the provided sample is not applicable just return null.
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $analysable
     * @param int|false $starttime Limit calculations to start time
     * @param int|false $endtime Limit calculations to end time
     * @return float|null
     */
    protected function calculate_sample($sampleid, \core_analytics\analysable $analysable, $starttime = false, $endtime = false) {

        $assign = $this->retrieve('assign', $sampleid);
        $course = $this->retrieve('course', $sampleid);
        $submission = $this->retrieve('assign_submission', $sampleid);

        if (!$logstore = \core_analytics\manager::get_analytics_logstore()) {
            throw new \coding_exception('No available log stores');
        }

        // We want to use this sample userid for uservisible.
        $modinfo = get_fast_modinfo($course, $submission->userid);

        $cm = $modinfo->get_cm($analysable->get_id());
        if (!$cm->uservisible) {
            return null;
        }

        $select = "userid = :userid AND contextlevel = :contextlevel AND contextinstanceid = :contextinstanceid AND " .
            "crud = :crud AND eventname = :eventname";
        $params = array('userid' => $submission->userid, 'contextlevel' => CONTEXT_MODULE,
            'contextinstanceid' => $cm->id, 'crud' => 'u', 'eventname' => '\mod_assign\event\assessable_submitted');
        $nlogs = $logstore->get_events_select($select, $params, 'timecreated ASC', 0, 1);
        if (!$nlogs) {
            return 1;
        }

        $log = reset($nlogs);
        if ($log->timecreated > $analysable->get_end()) {
            // Assessable submitted after the end time (due date or cut off if no due date)
            return 1;
        }

        return 0;
    }
}
