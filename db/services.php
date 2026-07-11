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
 * External functions and service definitions for the Payu payment gateway plugin.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'paygw_payu_create_order' => [
        'classname'   => 'paygw_payu\external\create_order',
        'classpath'   => '',
        'description' => 'create_order',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'paygw_payu_order_initiated' => [
        'classname'   => 'paygw_payu\external\order_initiated',
        'classpath'   => '',
        'description' => 'order_initiated',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'paygw_payu_transaction_complete' => [
        'classname'   => 'paygw_payu\external\transaction_complete',
        'classpath'   => '',
        'description' => 'transaction_complete',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
