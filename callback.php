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
 * PayU payment callback endpoint.
 *
 * This endpoint receives the browser POST callback from PayU after
 * payment completion. It validates the request and delegates all
 * business logic to the callback handler.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use core_payment\helper;
use paygw_payu\payu_helper;
use paygw_payu\local\logger;
use paygw_payu\local\hash;
use paygw_payu\local\redirect_manager;

require_login();

$success  = required_param('s', PARAM_BOOL);
$failure  = required_param('f', PARAM_BOOL);
$component  = required_param('component', PARAM_RAW);
$paymentarea  = required_param('paymentarea', PARAM_RAW);
$itemid  = required_param('itemid', PARAM_INT);

$status  = required_param('status', PARAM_RAW);
$unmappedstatus   = required_param('unmappedstatus', PARAM_RAW);
$mihpayid   = required_param('mihpayid', PARAM_RAW);
$key     = required_param('key', PARAM_RAW);
$txnid  = required_param('txnid', PARAM_RAW);
$amount   = required_param('amount', PARAM_FLOAT);
$productinfo     = required_param('productinfo', PARAM_RAW);
$firstname  = required_param('firstname', PARAM_RAW);
$email   = required_param('email', PARAM_RAW);
$hash     = required_param('hash', PARAM_RAW);

// This endpoint must only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidrequest', 'paygw_payu');
}

if (!hash::verify_callback_hash($txnid, $hash)) {
    throw new moodle_exception('invalidrequest', 'core_error');
}

$PAGE->set_context(null);
$PAGE->set_url(new moodle_url('/payment/gateway/payu/callback.php',
    ['s' => $success, 'f' => $failure, 'component' => $component,
    'paymentarea' => $paymentarea, 'itemid' => $itemid]));

try {
    $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
    $config = (object) $config;
    $payuhelper = new payu_helper($component, $paymentarea, $itemid, $config);
    $response = $payuhelper->verify_payment($txnid, $mihpayid);

    $transaction = new stdClass();
    $transaction->txnid = $txnid;
    $transaction->hash = $hash;
    $transaction->mihpayid = $mihpayid;

    if ($response['status'] === payu_helper::PAYMENT_STATUS_SUCCESS) {

        $redirecturl = redirect_manager::resolve(
            $component,
            $paymentarea,
            $itemid,
            $transaction
        );

        echo $OUTPUT->header();
            $data = new stdClass();
            $data->success = true;
            $data->failed = false;
            $data->redirecturl = $redirecturl;

            echo $OUTPUT->render_from_template('paygw_payu/transaction_status', $data);

        echo $OUTPUT->footer();
        die();
    }

    echo $OUTPUT->header();
        $data = new stdClass();
        $data->success = false;
        $data->failed = true;
        $data->redirecturl = $CFG->wwwroot;

        echo $OUTPUT->render_from_template('paygw_payu/transaction_status', $data);

    echo $OUTPUT->footer();
} catch (Throwable $exception) {

    debugging(
        $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    throw new moodle_exception(
        'invalidrequest',
        'core_error'
    );
}
