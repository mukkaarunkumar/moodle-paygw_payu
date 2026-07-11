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
use moodle_url;
use core\di;
use core\http_client;
use GuzzleHttp\Client;

use core_payment\gateway as payment_gateway;
use core_payment\helper;
use paygw_payu\local\service\payment_service;
use paygw_payu\local\hash as hash_helper;
use paygw_payu\local\logger;
use GuzzleHttp\Exception\GuzzleException;
use paygw_payu\event\payment_completed;

/**
 * PayU payment gateway implementation.
 */
class payu_helper {

    /**
     * Sandbox Base URL.
     */
    private const SANDBOX_BASE_URL = 'https://test.payu.in';

    /**
     * Production Base URL.
     */
    private const PRODUCTION_BASE_URL = 'https://secure.payu.in';

    /**
     * Sandbox verify endpoint.
     */
    private const SANDBOX_VERIFY_URL = 'https://test.payu.in/merchant/postservice.php?form=2';

    /**
     * Production verify endpoint.
     */
    private const PRODUCTION_VERIFY_URL = 'https://info.payu.in/merchant/postservice.php?form=2';


    /**
     * @var integer Payment is pending
     */
    public const ORDER_STATUS_CREATED = 0;

    /**
     * @var integer Payment was received.
     */
    public const ORDER_STATUS_INITIATED = 1;

    /**
     * @var integer Payment is pending
     */
    public const ORDER_STATUS_PENDING = 2;

    /**
     * @var integer Payment was received.
     */
    public const ORDER_STATUS_PAID = 3;

    /**
     * @var integer Payment was received.
     */
    public const ORDER_STATUS_FAILED = 4;

    /**
     * Callback processed successfully.
     */
    public const PAYMENT_STATUS_SUCCESS = 'success';

    /**
     * Callback processed but payment failed.
     */
    public const PAYMENT_STATUS_FAILED = 'failed';

    /**
     * @var string component.
     */
    protected $component;

    /**
     * @var string paymentarea.
     */
    protected $paymentarea;

    /**
     * @var int itemid.
     */
    protected $itemid;

    /**
     * @var object config.
     */
    protected $config;

    /**
     * @var object Client.
     */
    protected Client $client;

    /**
     * Constructor.
     */
    public function __construct(string $component, string $paymentarea, int $itemid, object $config) {

        $this->component = $component;
        $this->paymentarea = $paymentarea;
        $this->itemid = $itemid;

        $this->config = $config;

        $this->validate_configuration();

        $client = di::get(http_client::class);

        $this->client = $client;
    }

    /**
     * Returns gateway configuration.
     *
     * @return \stdClass
     */
    public static function get_configuration(): \stdClass {
        return get_config('paygw_payu');
    }

    /**
     * Returns whether sandbox mode is enabled.
     *
     * @return bool
     */
    public function is_sandbox(): bool {

        return ($this->config->environment ?? 'sandbox') === 'sandbox';
    }

    /**
     * Returns whether production mode is enabled.
     *
     * @return bool
     */
    public function is_production(): bool {

        return ($this->config->environment ?? 'production') === 'production';
    }

    /**
     * Returns whether production mode is enabled.
     *
     * @return bool
     */
    public function get_environment(): string {

        return $this->config->environment ?? 'sandbox';
    }

    /**
     * Returns the PayU base URL.
     *
     * @return string
     */
    public function get_api_base_url(): string {

        if ($this->is_sandbox()) {
            return self::SANDBOX_BASE_URL;
        }

        return self::PRODUCTION_BASE_URL;
    }

    /**
     * Returns the PayU base URL.
     *
     * @return string
     */
    public function get_payment_endpoint(): string {

        return $this->get_api_base_url() . '/_payment';
    }

    /**
     * Returns verify endpoint.
     *
     * @return string
     */
    public function get_verify_url(): string {

        if ($this->is_sandbox()) {
            return self::SANDBOX_VERIFY_URL;
        }

        return self::PRODUCTION_VERIFY_URL;
    }

    /**
     * Validate configuration.
     *
     * @throws \moodle_exception
     */
    protected function validate_configuration(): void {

        if (empty($this->config->merchantkey)) {
            throw new \moodle_exception(
                'missingmerchantkey',
                'paygw_payu'
            );
        }

        if (empty($this->config->merchantsalt)) {
            throw new \moodle_exception(
                'missingmerchantsalt',
                'paygw_payu'
            );
        }
    }

    /**
     * Generate transaction hash.
     *
     * @param array $paymentdata
     * @return string
     */
    public function generate_request_hash(array $paymentdata): string {
        return hash_helper::generate_request_hash($this->config, $paymentdata);
    }

    /**
     * Generate transaction verify hash.
     *
     * @param string $txnid
     * @return string
     */
    public function generate_verify_hash(string $txnid): string {
        return hash_helper::generate_verify_hash($this->config, $txnid);
    }

    /**
     * Create Order request.
     *
     * @param float $amount Amount
     * @param string $currency Currency
     * @return array
     */
    public function create_order(float $amount, string $currency): array {
        global $USER, $CFG;

        $txnid = self::generate_txnid_reference();

        $requiredfields = [
            'txnid',
            'amount',
            'firstname',
            'email',
            'phone',
            'productinfo',
            'surl',
            'furl',
        ];

        $surl = new moodle_url('/payment/gateway/payu/callback.php',
            ['s' => 1, 'f' => 0, 'component' => $this->component, 'paymentarea' => $this->paymentarea, 'itemid' => $this->itemid]);
        $furl = new moodle_url('/payment/gateway/payu/callback.php',
            ['s' => 0, 'f' => 1, 'component' => $this->component, 'paymentarea' => $this->paymentarea, 'itemid' => $this->itemid]);

        $paymentdata = [
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => 'Course Fee',
            'firstname' => $USER->firstname,
            'lastname' => $USER->lastname,
            'email' => $USER->email,
            'phone' => $USER->phone1 ?? null,
            'surl' => $surl->out(false),
            'furl' => $furl->out(false),
        ];

        return $this->build_payment_request($paymentdata);
    }

    /**
     * Build PayU payment request.
     *
     * This method prepares the request payload for the PayU Hosted Checkout
     * and returns all fields required to render the auto-submit form.
     *
     * @param array $paymentdata
     * @return array
     * @throws \moodle_exception
     */
    public function build_payment_request(array $paymentdata): array {
        global $DB;

        $requiredfields = [
            'txnid',
            'amount',
            'firstname',
            'email',
            'productinfo',
            'surl',
            'furl',
        ];

        foreach ($requiredfields as $field) {
            if (!isset($paymentdata[$field]) || trim((string)$paymentdata[$field]) === '') {
                throw new \moodle_exception(
                    'missingfield',
                    'paygw_payu',
                    '',
                    $field
                );
            }
        }

        $request = [
            'key' => $this->config->merchantkey,
            'txnid' => trim((string)$paymentdata['txnid']),
            'amount' => $paymentdata['amount'],
            'productinfo' => trim((string)$paymentdata['productinfo']),
            'firstname' => trim((string)$paymentdata['firstname']),
            'lastname' => trim((string)($paymentdata['lastname'] ?? '')),
            'email' => trim((string)$paymentdata['email']),
            'phone' => trim((string)$paymentdata['phone']),

            'surl' => trim((string)$paymentdata['surl']),
            'furl' => trim((string)$paymentdata['furl']),

            /*
             * Optional fields.
             */

            'udf1' => $paymentdata['udf1'] ?? '',
            'udf2' => $paymentdata['udf2'] ?? '',
            'udf3' => $paymentdata['udf3'] ?? '',
            'udf4' => $paymentdata['udf4'] ?? '',
            'udf5' => $paymentdata['udf5'] ?? '',
        ];

        /*
         * Generate PayU SHA512 hash.
         */
        $request['hash'] = $this->generate_request_hash($request);

        logger::info(
            'PayU payment request created.',
            [
                'txnid' => $request['txnid'],
                'hash' => $request['hash'],
                'amount' => $request['amount'],
                'email' => $request['email'],
            ]
        );

        try {
            // Store PayU Order information.
            $record = new \stdClass();
            $record->txnid = $request['txnid'];
            $record->hash = $request['hash'];
            $record->status = self::ORDER_STATUS_CREATED;
            $record->timecreated = time();
            $record->timemodified = time();

            $DB->insert_record('paygw_payu', $record);

            return [
                'endpoint' => $this->get_payment_endpoint(),
                'method' => 'POST',
                'fields' => $request,
            ];

        } catch (\Exception $e) {
            debugging('Exception while trying to process payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $success = false;
            $message = get_string('internalerror', 'paygw_payu');
            throw new \moodle_exception(
                'invalidtxnid',
                'paygw_payu'
            );
        }
    }

    /**
     * Verify transaction with PayU.
     *
     * This method calls the PayU Verify API and returns the
     * verified transaction details.
     *
     * @param string $txnid
     * @return array
     * @throws \moodle_exception
     */
    public function verify_payment(string $txnid, string $mihpayid): array {
        global $USER, $DB;

        if (trim($txnid) === '') {
            throw new \moodle_exception(
                'invalidtxnid',
                'paygw_payu'
            );
        }

        $command = 'verify_payment';

        /*
         * Generate PayU SHA512 hash.
         */
        $request['hash'] = $this->generate_verify_hash($txnid);

        $request = [
            'key' => $this->config->merchantkey,
            'command' => $command,
            'var1' => $txnid,
            'hash' => $request['hash'],
        ];

        try {
            $return = $this->client->post(
                uri: $this->get_verify_url(),
                options: [
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'form_params' => $request,
                ],
            );

            if ($return->getStatusCode() === 200) {
                $status = true;
            } else {
                $status = false;
            }
        } catch (GuzzleException $e) {
            $status = false;

            logger::error(
                'PayU callback processing failed.',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'txnid' => $txnid,
                    'endpoint' => $this->get_verify_url(),
                    'requestheaders' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'requestbody' => $request,
                    'responsecode' => $return->getStatusCode(),
                    'responsebody' => $return->getBody()->getContents(),
                ]
            );
        }

        if (!$status) {
            throw new \moodle_exception(
                'requestfailed',
                'paygw_payu'
            );
        }

        $response = (array) json_decode($return->getBody()->getContents());

        if ($response['status'] === 1) {

            $payable = helper::get_payable($this->component, $this->paymentarea, $this->itemid);

            $surcharge = helper::get_gateway_surcharge('payu');
            $currency = $payable->get_currency();

            $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

            $paymentid = helper::save_payment(
                $payable->get_account_id(),
                $this->component,
                $this->paymentarea,
                $this->itemid,
                $USER->id,
                $amount,
                $currency,
                'payu'
            );

            payment_completed::create([
                'context' => \context_system::instance(),
                'objectid' => $paymentid,
                'userid' => $USER->id,
                'other' => [
                    'component' => $this->component,
                    'paymentarea' => $this->paymentarea,
                    'itemid' => $this->itemid,
                    'amount' => $amount,
                    'currency' => $currency,
                    'txnid' => $txnid,
                    'mihpayid' => $mihpayid,
                ],
            ])->trigger();

            $record = $DB->get_record('paygw_payu', ['txnid' => $txnid]);

            $record->paymentid = $paymentid;
            $record->mihpayid = $mihpayid;
            $record->status = self::ORDER_STATUS_PAID;
            $record->timemodified = time();

            $DB->update_record('paygw_payu', $record);

            logger::debug(
                'PayU transaction verified.',
                [
                    'txnid' => $txnid,
                    'status' => self::PAYMENT_STATUS_SUCCESS,
                ]
            );

            helper::deliver_order($this->component, $this->paymentarea, $this->itemid, $paymentid, $USER->id);

            return ['status' => 'success'];
        }

        return ['status' => 'failed'];
    }

    /**
     * Generate random order reference.
     *
     * @return string
     */
    public static function generate_txnid_reference(): string {
        global $USER;

        return 'MDL-' . 'PAYU-' . $USER->id . '-' . date('YmdHis') . '-' . time() . '-' . random_int(1000, 9999);
    }

}
