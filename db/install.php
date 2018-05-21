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


defined('MOODLE_INTERNAL') || die();

function xmldb_local_testanalytics_install() {
    global $DB;

    \core\session\manager::set_user(get_admin());

    $usedtargets = $DB->get_fieldset_select('analytics_models', 'DISTINCT target', '');

    $indicator = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\set_setting');
    $indicators = array($indicator->get_id() => $indicator);

    if (!in_array('\local_testanalytics\analytics\target\linear_example', $usedtargets)) {
        $target = \core_analytics\manager::get_target('\local_testanalytics\analytics\target\linear_example');
        $model = \core_analytics\model::create($target, $indicators, '\core\analytics\time_splitting\single_range');
        $model->enable();
    }

    if (!in_array('\local_testanalytics\analytics\target\discrete_example', $usedtargets)) {
        $target = \core_analytics\manager::get_target('\local_testanalytics\analytics\target\discrete_example');
        $model = \core_analytics\model::create($target, $indicators, '\core\analytics\time_splitting\single_range');
        $model->enable();
    }

    if (!in_array('\local_testanalytics\analytics\target\binary_example', $usedtargets)) {
        $target = \core_analytics\manager::get_target('\local_testanalytics\analytics\target\binary_example');
        $model = \core_analytics\model::create($target, $indicators, '\core\analytics\time_splitting\single_range');
        $model->enable();
    }

    if (!in_array('\local_testanalytics\analytics\target\undead_users', $usedtargets)) {
        $indicator1 = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\user_suspended');
        $indicator2 = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\user_activity');
        $indicators = array($indicator1->get_id() => $indicator1, $indicator2->get_id() => $indicator2);
        $target = \core_analytics\manager::get_target('\local_testanalytics\analytics\target\undead_users');
        $model = \core_analytics\model::create($target, $indicators, '\core\analytics\time_splitting\single_range');
        $model->enable();
    }

    if (!in_array('\local_testanalytics\analytics\target\useless_categories', $usedtargets)) {
        $indicator = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\category_empty');
        $indicators = array($indicator->get_id() => $indicator);
        $target = \core_analytics\manager::get_target('\local_testanalytics\analytics\target\useless_categories');
        $model = \core_analytics\model::create($target, $indicators, '\core\analytics\time_splitting\single_range');
        $model->enable();
    }

    if (!in_array('\local_testanalytics\analytics\target\without_picture', $usedtargets)) {
        $indicator = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\user_suspended');
        $indicators = array($indicator->get_id() => $indicator);
        $target = \core_analytics\manager::get_target('\local_testanalytics\analytics\target\without_picture');
        $model = \core_analytics\model::create($target, $indicators, '\core\analytics\time_splitting\single_range');
        $model->enable();
    }

    if (!in_array('\local_testanalytics\analytics\target\late_assign_submission', $usedtargets)) {
        $indicator1 = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\grade_to_pass_set');
        $indicator2 = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\grade_item_weight');
        $indicator3 = \core_analytics\manager::get_indicator('\core_course\analytics\indicator\completion_enabled');
        $indicator4 = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\submit_close_to_due');
        $indicator5 = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\submit_close_to_close');
        $indicator6 = \core_analytics\manager::get_indicator('\local_testanalytics\analytics\indicator\submit_choice_close_to_close');
        $indicator7 = \core_analytics\manager::get_indicator('\core\analytics\indicator\any_write_action');
        $indicator8 = \core_analytics\manager::get_indicator('\core\analytics\indicator\any_write_action_in_course');
        $indicator9 = \core_analytics\manager::get_indicator('\core\analytics\indicator\read_actions');
        $indicator10 = \core_analytics\manager::get_indicator('\mod_assign\analytics\indicator\cognitive_depth');
        $indicator11 = \core_analytics\manager::get_indicator('\mod_assign\analytics\indicator\social_breadth');
        $indicators = array(
            $indicator1->get_id() => $indicator1,
            $indicator2->get_id() => $indicator2,
            $indicator3->get_id() => $indicator3,
            $indicator4->get_id() => $indicator4,
            $indicator5->get_id() => $indicator5,
            $indicator6->get_id() => $indicator6,
            $indicator7->get_id() => $indicator7,
            $indicator8->get_id() => $indicator8,
            $indicator9->get_id() => $indicator9,
            $indicator10->get_id() => $indicator10,
            $indicator11->get_id() => $indicator11,
        );
        $target = \core_analytics\manager::get_target('\local_testanalytics\analytics\target\late_assign_submission');
        $model = \core_analytics\model::create($target, $indicators, '\local_testanalytics\analytics\time_splitting\close_to_deadline');
        $model->enable();
    }
}
