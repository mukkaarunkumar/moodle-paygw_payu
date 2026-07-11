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
 * This class contains a list of webservice functions related to the Payu payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_order extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
        ]);
    }

    /**
     * Returns the payment prerequisites for the Payu.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @return string[]
     */
    public static function execute(string $component, string $paymentarea, int $itemid): array {
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
        ]);

        $surcharge = helper::get_gateway_surcharge('payu');
        $payable = helper::get_payable($component, $paymentarea, $itemid);

        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

        $currency = $payable->get_currency();

        $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

        $config = (object) $config;
        $payuhelper = new payu_helper($component, $paymentarea, $itemid, $config);
        $response = $payuhelper->create_order($amount, $currency);

        $return = [
            'endpoint' => $response['endpoint'],
            'method' => $response['method'],
            'fields' => $response['fields'],
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
            'endpoint' => new external_value(PARAM_URL, 'Payu endpoint'),
            'method' => new external_value(PARAM_RAW, 'method'),
            'fields' => new external_single_structure([
                'key' => new external_value(PARAM_RAW, 'key'),
                'txnid' => new external_value(PARAM_RAW, 'txnid'),
                'amount' => new external_value(PARAM_FLOAT, 'amount'),
                'productinfo' => new external_value(PARAM_RAW, 'productinfo'),
                'firstname' => new external_value(PARAM_RAW, 'firstname'),
                'lastname' => new external_value(PARAM_RAW, 'lastname'),
                'email' => new external_value(PARAM_RAW, 'email'),
                'phone' => new external_value(PARAM_RAW, 'phone'),
                'surl' => new external_value(PARAM_RAW, 'surl'),
                'furl' => new external_value(PARAM_RAW, 'furl'),
                'udf1' => new external_value(PARAM_RAW, 'udf1'),
                'udf2' => new external_value(PARAM_RAW, 'udf2'),
                'udf3' => new external_value(PARAM_RAW, 'udf3'),
                'udf4' => new external_value(PARAM_RAW, 'udf4'),
                'udf5' => new external_value(PARAM_RAW, 'udf5'),
                'hash' => new external_value(PARAM_RAW, 'hash'),
            ]),
        ]);
    }
}
