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
 * @package   local_testanalytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_testanalytics\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

/**
 * @package   local_testanalytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_item_weight extends \core_analytics\local\indicator\discrete {

    /**
     * @var \stdClass[] No memory usage worries, indicators are filled per-analysable basis.
     */
    protected $cmclass = [];

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('gradeitemweight', 'local_testanalytics');
    }

    /**
     * required_sample_data
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('course_modules');
    }

    protected static function get_classes() {
        return [0, 1, 2, 3, 4];
    }

    public function get_calculation_outcome($value, $subtype = false) {
		return self::OUTCOME_OK;
    }

    /**
     * calculate_sample
     *
     * @param int $sampleid
     * @param string $sampleorigin
     * @param int $starttime
     * @param int $endtime
     * @return float
     */
    protected function calculate_sample($sampleid, $sampleorigin, $starttime = false, $endtime = false) {
        global $DB;

        $cm = $this->retrieve('course_modules', $sampleid);

        if (!isset($this->cmclass[$cm->id])) {
            $module = $DB->get_record('modules', array('id' => $cm->module));
            $params = array('itemtype' => 'mod', 'itemmodule' => $module->name, 'iteminstance' => $cm->instance);
            $gi = \grade_item::fetch($params);
            if (!$gi) {
                $this->cmclass[$cm->id] = null;
                return $this->cmclass[$cm->id];
            }

            if ($gi->gradetype === GRADE_TYPE_NONE) {
                // This is equal to the 0 below, so minimum grade.
                $this->cmclass[$cm->id] = 0;
                return $this->cmclass[$cm->id];
            }

            // TODO This should be part of grades API. I am probably missing important stuff.
            $weight = $gi->aggregationcoef2;
            $category = $gi->get_parent_category();
            while (!$category->is_course_category()) {
                $gi = $category->get_grade_item();
                $weight = $weight * $gi->aggregationcoef2;
                $category = $category->get_parent_category();
            }

            if ($weight == 0) {
                $this->cmclass[$cm->id] = 0;
            } else if ($weight < 0.1) {
                $this->cmclass[$cm->id] = 1;
            } else if ($weight < 0.2) {
                $this->cmclass[$cm->id] = 2;
            } else if ($weight < 0.5) {
                $this->cmclass[$cm->id] = 3;
            } else {
                $this->cmclass[$cm->id] = 4;
            }
        }

        if (!$this->cmclass[$cm->id]) {
            return null;
        }
        return $this->cmclass[$cm->id];
    }
}
