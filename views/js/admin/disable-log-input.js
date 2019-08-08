$(document).ready(function () {

    hideShowField();
    $(document).on('change', $('#REVERS_IO_ENABLE_LOGGING_SETTING_on'), function () {
        hideShowField();
    });

    function hideShowField() {
        if ($("#REVERS_IO_ENABLE_LOGGING_SETTING_on:checked").val() === '1') {
            $('#conf_id_REVERS_IO_STORE_LOGS').closest('.form-group').show();
            $('#conf_id_REVERSIODownload').closest('.form-group').show();
        } else {
            $('#conf_id_REVERS_IO_STORE_LOGS').closest('.form-group').hide();
            $('#conf_id_REVERSIODownload').closest('.form-group').hide();
        }
    }
});
