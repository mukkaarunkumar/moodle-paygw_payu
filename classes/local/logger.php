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
 * PayU logger.
 *
 * @package    paygw_payu
 * @copyright  2026 Mukka Arun Kumar <arunkumar.mukka@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\local;

/**
 * Utility class for all logs routines helper.
 */
final class logger {

    /**
     * Sensitive fields that must never be logged.
     */
    private const MASKEDFIELDS = [
        'card_number',
        'cvv',
        'token',
        'authorization',
    ];

    /**
     * Log debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $message, array $context = []): void {
        self::write('DEBUG', $message, $context);
    }

    /**
     * Log information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $message, array $context = []): void {
        self::write('INFO', $message, $context);
    }

    /**
     * Log warning.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning(string $message, array $context = []): void {
        self::write('WARNING', $message, $context);
    }

    /**
     * Log error.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $message, array $context = []): void {
        self::write('ERROR', $message, $context);
    }

    /**
     * Write log.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private static function write(
        string $level,
        string $message,
        array $context = []
    ): void {
        global $DB;

        $context = self::mask($context);

        // Moodle developer debugging.
        debugging(
            sprintf('[MDL][PayU][%s] %s', $level, $message),
            DEBUG_DEVELOPER
        );

        // PHP error log.
        debugging(
            sprintf(
                '[MDL][PayU][%s] %s %s',
                $level,
                $message,
                json_encode($context)
            )
        );

        if ($level === 'ERROR') {

            $record = new \stdClass();

            $record->transactionid = $context['txnid'] ?? null;
            $record->direction = strtolower($level);
            $record->endpoint = $context['endpoint'] ?? '';
            $record->requestheaders = isset($context['headers']) ? json_encode($context['headers']) : null;
            $record->requestbody = isset($context['request']) ? json_encode($context['request']) : null;
            $record->responsecode = $context['responsecode'] ?? null;
            $record->responsebody = isset($context['response']) ? json_encode($context['response']) : null;
            $record->errormessage = $level === 'ERROR' ? $message : null;
            $record->timecreated = time();

            $DB->insert_record(
                'paygw_payu_log',
                $record
            );
        }
    }

    /**
     * Remove sensitive values.
     *
     * @param array $context
     * @return array
     */
    private static function mask(array $context): array {

        foreach (self::MASKEDFIELDS as $field) {
            if (array_key_exists($field, $context)) {
                $context[$field] = '********';
            }
        }

        array_walk_recursive(
            $context,
            function (&$value, $key): void {

                if (in_array($key, self::MASKEDFIELDS, true)) {
                    $value = '********';
                }
            }
        );

        return $context;
    }
}
