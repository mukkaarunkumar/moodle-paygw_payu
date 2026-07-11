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

namespace paygw_payu\external;

use core_payment\helper;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use paygw_payu\payu_helper;

/**
 * This class updates status when order initiated for Payu payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class order_initiated extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'txnid' => new external_value(PARAM_RAW, 'Transaction ID'),
        ]);
    }

    /**
     * Payu payment redirection initiated.
     *
     * @param txnid $txnid
     * @return string[]
     */
    public static function execute(string $txnid): array {
        global $DB;
        self::validate_parameters(self::execute_parameters(), [
            'txnid' => $txnid,
        ]);

        $record = $DB->get_record('paygw_payu', ['txnid' => $txnid]);

        $record->status = payu_helper::ORDER_STATUS_INITIATED;
        $record->timeupdated = time();

        try {
            $DB->update_record('paygw_payu', $record);
            $status = true;
        } catch (\Exception $e) {
            $status = false;
        }

        $return = [
            'status' => $status,
        ];

        return $return;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'status'),
        ]);
    }
}
