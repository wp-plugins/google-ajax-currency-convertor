/*
 * Google AJAX Currency Convertor JS lib
 * by nostop
 */

(function($) {
    var $cc_url = '/';

    $.fn.google_ajax_cc = function() {
        $(this).each(function() {
            var $form = $(this);
            var $amount = $('.amount input', $form),
                $from = $('.from select', $form),
                $to = $('.to select', $form),
                $result = $('.result span', $form),

                allowedKeyCodes = [48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 190],
                num_regexp = new RegExp('[^0-9.]', 'g');

            $form.submit(function(e) {
                e.preventDefault();
            })
            $amount.keyup(amount_change);
            $from.change(amount_change);
            $to.change(amount_change);
            
            amount_change('start');
            
            function amount_change(e) {
                console.log(e);
                if((e.type == 'keyup' && allowedKeyCodes.indexOf(e.keyCode) != -1)
                    || e.type == 'change'
                    || e == 'start') {
                    var data = {
                        google_ajax_cc: {
                            amount: $amount.val(),
                            from: $from.val(),
                            to: $to.val()
                        }
                    }
                    $.post($cc_url, data, function(data) {
                        var rhs = Math.round(100 * data.rhs.replace(num_regexp, '')) / 100;
                        $result.text(rhs)
                    })
                }
                else {
                    $amount.val($amount.val().replace(num_regexp, ''))
                }
            }
        })
    }
})(jQuery);

jQuery(document).ready(function($) {
    $('form.google_ajax_cc').google_ajax_cc();
})