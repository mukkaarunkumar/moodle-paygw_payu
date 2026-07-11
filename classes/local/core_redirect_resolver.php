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
 * Handling proper redirection after payment.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\local;

use moodle_url;

/**
 * Core Moodle redirect resolver.
 *
 * @package    paygw_payu
 */
final class core_redirect_resolver {

    /**
     * Resolve redirect URL.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @return moodle_url
     */
    public static function resolve(
        string $component,
        string $paymentarea,
        int $itemid
    ): moodle_url {
        global $DB;

        switch ($component) {
            case 'enrol_fee':
                $instance = $DB->get_record(
                    'enrol',
                    [
                        'id' => $itemid,
                        'enrol' => 'fee',
                    ],
                    'id,courseid',
                    IGNORE_MISSING
                );

                if ($instance) {
                    return new moodle_url(
                        '/course/view.php',
                        [
                            'id' => $instance->courseid,
                        ]
                    );
                }
                break;
        }

        return new moodle_url('/');
    }
}
