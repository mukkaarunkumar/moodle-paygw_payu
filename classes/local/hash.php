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
 * PayU Hash Helper.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\local;

use core_payment\helper;
use moodle_exception;

/**
 * Helper class for PayU hash generation and verification.
 */
final class hash {

    /**
     * Generate payment request hash.
     *
     * PayU Request Format:
     * key|txnid|amount|productinfo|firstname|email|
     * udf1|udf2|udf3|udf4|udf5||||||salt
     *
     * @param object $config
     * @param array $data
     * @return string
     * @throws moodle_exception
     */
    public static function generate_request_hash(object $config, array $data): string {

        if (empty($config->merchantkey) || empty($config->merchantsalt)) {
            throw new moodle_exception('missingmerchantcredentials', 'paygw_payu');
        }

        $required = [
            'txnid',
            'amount',
            'productinfo',
            'firstname',
            'email',
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new moodle_exception(
                    'missingfield',
                    'paygw_payu',
                    '',
                    $field
                );
            }
        }

        $fields = [
            $config->merchantkey,
            $data['txnid'],
            $data['amount'],
            $data['productinfo'],
            $data['firstname'],
            $data['email'],
            $data['udf1'] ?? '',
            $data['udf2'] ?? '',
            $data['udf3'] ?? '',
            $data['udf4'] ?? '',
            $data['udf5'] ?? '',
            '',
            '',
            '',
            '',
            '',
            $config->merchantsalt,
        ];

        return strtolower(hash('sha512', implode('|', $fields)));
    }

    /**
     * Generate payment verify hash.
     *
     * PayU verify Request Format:
     * key|command|var1|salt
     *
     * @param object $config
     * @param string $txnid
     * @return string
     * @throws moodle_exception
     */
    public static function generate_verify_hash(object $config, string $txnid): string {

        if (empty($config->merchantkey) || empty($config->merchantsalt)) {
            throw new moodle_exception('missingmerchantcredentials', 'paygw_payu');
        }

        $command = 'verify_payment';

        $fields = [
            $config->merchantkey,
            $command,
            $txnid,
            $config->merchantsalt,
        ];

        return strtolower(hash('sha512', implode('|', $fields)));
    }

    /**
     * Verify callback hash.
     *
     * @param string $txnid
     * @param string $hash
     * @return bool
     */
    public static function verify_callback_hash(string $txnid, string $hash): bool {
        global $DB;

        $requesthash = $DB->get_field('paygw_payu', 'hash', ['txnid' => $txnid]);

        return hash_equals(
            $requesthash,
            $hash
        );
    }

}
