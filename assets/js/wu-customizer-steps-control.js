function getQueryParam(param) {
    location.search.substr(1)
        .split("&")
        .some(function(item) { // returns first occurence and stops
            return item.split("=")[0] === param && (param = item.split("=")[1]);
        });
    return param;
}

var delay = (function() {
    var timer = 0;
    return function(callback, ms) {
        clearTimeout(timer);
        timer = setTimeout(callback, ms);
    };
})();

(function($) {
    $(document).ready(function() {

        if (getQueryParam('wu-customize') === '1') {
            wp.customize.panel('wu_customizer_panel').focus();
        }

        /** Collapse the Field */
        $('.customize-control-steps_field').on('click', '.widget-action', function(event) {

            var widget = $(this).parents('.customize-control-widget_form');
            var block = widget.parents('.customize-control-steps_field');
            var open = widget.is('.expanded');

            block.find('.customize-control-widget_form.expanded .widget-inside').filter(function(index) {

                return !$(this).parents('.customize-control-widget_form').is(widget);

            }).slideUp(function() {

                $(this).parents('.customize-control-widget_form').toggleClass('expanded');

            });

            $('.widget-inside', widget).slideToggle(open);

            widget.toggleClass('expanded');

        });

    });
})(jQuery);

(function($, api) {

    /* === Steps Field === */
    api.controlConstructor.steps_field = api.Control.extend({

        ready: function() {

            var control = this;

            $('.customize-control-steps_field > ul').sortable({
                handle: '.widget-wu-step',
                axis: 'y',
                update: function(event, ui) {

                    $('.customize-control-steps_field > ul > li').each(function(index, $el) {

                        $(this).find('.step-order').val((index + 1) * 10);

                    });

                    var content = $('.customize-control-steps_field > ul :input').serialize();

                    control.setting.set(content);

                }
            });

            /** On name change */
            $('.customize-control-steps_field > ul input').on('change keydown keypress keyup mousedown click mouseup', function(event) {

                var _this = this;

                delay(function() {

                    var content = $(_this).parents('.customize-control-steps_field > ul').find(':input').serialize();

                    control.setting.set(content);

                }, 400);

            });

            /** Add new Steps */
            $('#add-new-step', '.customize-control-steps_field').on('click', function(e) {

                e.preventDefault();

                var clone = $('#widget-plan-list-item').clone();

                clone.find(':input').val('').attr({
                    'placeholder': '',
                    'name': function() {

                        var name = $(this).attr('name');

                        if (name) {
                            return $(this).attr('name').replace('plan', 'teste');
                        } else {
                            return '';
                        }

                    }
                });

                $(this).parents('.customize-control-steps_field > ul').append(clone);

            });

        }
    });

})(jQuery, wp.customize);