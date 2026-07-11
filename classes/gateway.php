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
 * PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

use context;
use core_payment\gateway as payment_gateway;
use core_payment\helper;
use moodle_url;
use paygw_payu\local\service\payment_service;

/**
 * The gateway class for PayU payment gateway.
 *
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends payment_gateway {

    /**
     * Returns supported currencies.
     *
     * @return array
     */
    public static function get_supported_currencies(): array {
        return [
            'INR',
        ];
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'merchantkey', get_string('merchantkey', 'paygw_payu'));
        $mform->setType('merchantkey', PARAM_TEXT);
        $mform->addHelpButton('merchantkey', 'merchantkey', 'paygw_payu');

        $mform->addElement('text', 'merchantsalt', get_string('merchantsalt', 'paygw_payu'));
        $mform->setType('merchantsalt', PARAM_TEXT);
        $mform->addHelpButton('merchantsalt', 'merchantsalt', 'paygw_payu');

        $options = [
            'production' => get_string('production', 'paygw_payu'),
            'sandbox'  => get_string('sandbox', 'paygw_payu'),
        ];

        $mform->addElement('select', 'environment', get_string('environment', 'paygw_payu'), $options);
        $mform->addHelpButton('environment', 'environment', 'paygw_payu');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(\core_payment\form\account_gateway $form,
                                                 \stdClass $data, array $files, array &$errors): void {
        if ($data->enabled&&
                (empty($data->merchantkey) || empty($data->merchantsalt))) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }

}
