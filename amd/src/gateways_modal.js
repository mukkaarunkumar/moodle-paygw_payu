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
 * @module     paygw_payu/modal_gateways
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from './repository';
import Templates from 'core/templates';
import Modal from 'core/modal';

/**
 * Creates and shows a modal that contains a loading placeholder.
 *
 * @returns {Promise<Modal>}
 */
const showModalWithPlaceholder = async() => await Modal.create({
    body: await Templates.render('paygw_payu/payu_button_placeholder', {}),
    show: true,
    removeOnClose: true,
});

/**
 * Create a hidden input element.
 *
 * @param {HTMLElement} form
 * @param {String} name
 * @param {String} value
 */
const addHiddenField = (form, name, value) => {
    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = name;
    input.value = value ?? '';

    form.appendChild(input);
};

/**
 * Submit the PayU payment form.
 *
 * @param {Object} response
 */
const submitPaymentForm = (response) => {
    if (!response.endpoint) {
        throw new Error('Missing PayU action URL.');
    }

    if (!response.fields || typeof response.fields !== 'object') {
        throw new Error('Missing PayU form fields.');
    }

    const form = document.createElement('form');

    form.method = 'POST';
    form.action = response.endpoint;
    form.style.display = 'none';

    Object.entries(response.fields).forEach(([name, value]) => {
        addHiddenField(form, name, value);
    });

    document.body.appendChild(form);

    Repository.orderInitiated(response.fields['txnid']);

    form.submit();
};

/**
 * Process the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId) => {

    return showModalWithPlaceholder()
        .then(modal => {

            return Repository.createOrder(component, paymentArea, itemId).then(orderdetails => {

                if (orderdetails) {
                    submitPaymentForm(orderdetails);
                }

                return new Promise(() => {});
            }).catch(e => {
                modal.hide();
                // We want to use promise reject here - as that's what core payment stuff expects disable eslint.
                /* eslint-disable */
                return Promise.reject(e.message);
            });            
    });
};
