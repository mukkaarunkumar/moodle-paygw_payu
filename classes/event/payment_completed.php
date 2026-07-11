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
 * The paygw_payu payment completed event.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\event;

/**
 * Event fired when a PayU payment has been completed and the order delivered.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *      - string component: the component the payment belongs to.
 *      - string paymentarea: the payment area within the component.
 *      - int itemid: the item id within the component area.
 *      - float amount: the amount paid.
 *      - string currency: the currency.
 *      - string txnid: the Payu transaction id.
 * }
 *
 * @copyright  2026 Mukka Arun Kumar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_completed extends \core\event\base {
    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'payments';
    }

    /**
     * Returns the localised name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:payment_completed', 'paygw_payu');
    }

    /**
     * Returns a human readable description of the event.
     *
     * @return string
     */
    public function get_description() {
        return get_string('event:payment_completed_desc', 'paygw_payu',
            [
                'userid' => $this->userid,
                'objectid' => $this->objectid,
                'component' => $this->other['component'],
                'txnid' => $this->other['txnid'],
                'mihpayid' => $this->other['mihpayid'],
            ]
        );
    }
}
