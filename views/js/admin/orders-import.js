/*
 *Copyright (c) 2019 Revers.io
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author revers.io
 * @copyright Copyright (c) permanent, Revers.io
 * @license   Revers.io
 * @see       /LICENSE
 */

$(document).ready(function () {
    $('body').on('click', '.js-revers-io-orders-import-button', importOrdersToReversIo);

    function importOrdersToReversIo() {
        $('.js-revers-io-orders-import').addClass('hidden');
        $('.js-revers-io-orders-import').html("");
        $.ajax(orderImportAjaxUrl, {
            method: 'POST',
            data: {
                action: 'importOrdersToReversIo',
                ajax: 1,
                orders_status: $('select[name="orders_status[]"]').val(),
                orders_date_from: $('input[name="orders_date_from"]').val(),
                orders_date_to: $('input[name="orders_date_to"]').val()
            },
            success: function (response) {
                response = JSON.parse(response);
                $('.js-revers-io-orders-import').removeClass('hidden');
                if (response.importFinished === false) {
                    $('.js-revers-io-orders-import').append('<div id="conf_id_REVERS_IO_ORDER_IMPORT_PROGRESS">\n' +
                        '<label class="control-label col-lg-3">\n' +
                        'Orders import progress </label>' +
                        '<div class="col-lg-5 js-revers-io-orders-import-progress-container" style="padding: 7px 10px 0px 20px;>' +
                        '<div id="order-import-information">Total imported: '+response.totalImported+' and failed: '+response.totalFailed+' from '+response.totalSum+' orders. Import is not finished, ' +
                        'do not close the tab.</div>' +
                        '</div></div>');
                    importOrdersToReversIo();
                }

                $('.js-revers-io-orders-import').append('<div id="conf_id_REVERS_IO_ORDER_IMPORT_PROGRESS">\n' +
                    '<label class="control-label col-lg-3">\n' +
                    'Orders import progress </label>' +
                    '<div class="col-lg-5 js-revers-io-orders-import-progress-container" style="padding: 7px 10px 0px 20px;>' +
                    '<div id="order-import-information">Total imported: '+response.totalImported+' and failed: '+response.totalFailed+' from '+response.totalSum+' orders. Import is finished. </div>' +
                    '</div></div>');

            },
            error: function (response) {
                $('.js-revers-io-orders-import').append('<div id="conf_id_REVERS_IO_ORDER_IMPORT_PROGRESS">\n' +
                    '<label class="control-label col-lg-3">\n' +
                    'Orders import progress </label>' +
                    '<div class="col-lg-5 js-revers-io-orders-import-progress-container" style="padding: 7px 10px 0px 20px;>' +
                    '<div id="order-import-information">Something went wrong while importing please try again import</div>' +
                    '</div></div>');
            }
        });
    }
});
