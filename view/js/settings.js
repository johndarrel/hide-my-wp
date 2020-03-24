(function ($) {
    "use strict";

    $.fn.hmw_loading = function (state) {
        var $this = this;
        var loading = '<i class="fa fa-circle-o-notch fa-spin mr-1 hmw_loading"></i>';
        $this.find('i').remove()
        if (state) {
            $this.prepend(loading);
        } else {
            $('.hmw_loading').remove();
        }

        return $this;
    };

    $.fn.hmw_fixSettings = function (name, value) {
        var $div = $('#hmw_wrap');
        var $this = this;
        $this.hmw_loading(true);
        $.post(
            hmwQuery.ajaxurl,
            {
                action: 'hmw_fixsettings',
                name: name,
                value: value,
                hmw_nonce: hmwQuery.nonce
            }
        ).done(function (response) {
            $this.hmw_loading(false);
            if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                if (response.success) {
                    $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-success text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                    $this.hide();
                } else {
                    $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                }
            }
            setTimeout(function () {
                $('.hmw_alert').remove();
            }, 5000)
        }).error(function () {
            $this.hmw_loading(false);
            $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>Ajax Error.</strong></div>');
            setTimeout(function () {
                $('.hmw_alert').remove();
            }, 5000)
        }, 'json');
    };

    $.fn.hmw_fixConfig = function (name, value) {
        var $div = $('#hmw_wrap');
        var $this = this;
        $this.hmw_loading(true);
        $.post(
            hmwQuery.ajaxurl,
            {
                action: 'hmw_fixconfig',
                name: name,
                value: value,
                hmw_nonce: hmwQuery.nonce
            }
        ).done(function (response) {
            $this.hmw_loading(false);
            if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                if (response.success) {
                    $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-success text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                    $this.hide();
                } else {
                    $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                }
            }
            setTimeout(function () {
                $('.hmw_alert').remove();
            }, 5000)
        }).error(function () {
            $this.hmw_loading(false);
            $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>Ajax Error.</strong></div>');
            setTimeout(function () {
                $('.hmw_alert').remove();
            }, 5000)
        }, 'json');
    };

    $.fn.hmw_securityExclude = function (name) {
        var $div = $('#hmw_wrap');
        var $this = this;
        $.post(
            hmwQuery.ajaxurl,
            {
                action: 'hmw_securityexclude',
                name: name,
                hmw_nonce: hmwQuery.nonce
            }
        ).done(function (response) {
            if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                if (response.success) {
                    $this.parents('tr:last').fadeOut();
                    $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-success text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                } else {
                    $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                }
            }
            setTimeout(function () {
                $('.hmw_alert').remove();
            }, 5000)
        }).error(function () {
            $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>Ajax Error.</strong></div>');
            setTimeout(function () {
                $('.hmw_alert').remove();
            }, 5000)
        }, 'json');
    };


    $.fn.hmw_settingsListen = function () {
        var $this = this;

        $this.find('input[name=hmw_admin_url]').on('keyup', function () {
            if ($(this).val() !== 'wp-admin' && $(this).val() != '') {
                //hmw_hide_newadmin
                $this.find('.admin_warning').show();
                $this.find('.hmw_hide_newadmin_div').show();
            } else {
                $this.find('.admin_warning').hide();
                $this.find('.hmw_hide_newadmin_div').hide();
            }
        });

        $this.find('input[name=hmw_login_url]').on('keyup', function () {
            if ($(this).val() !== 'wp-login.php'  && $(this).val() != '') {
                $this.find('.hmw_hide_login_div').show();
            } else {
                $this.find('.hmw_hide_login_div').hide();
            }
        });

        $this.find("input[name=hmw_hide_admin].switch").change(function () {
            if ($(this).prop('checked')) {
                $this.find('.wp-admin_warning').show();
                $this.find('.hmw_hide_newadmin_div').show();
            } else {
                $this.find('.wp-admin_warning').hide();
                $this.find('.hmw_hide_newadmin_div').hide();
            }
        });

        $this.find('select[name=hmw_mode]').on('change', function () {
            $this.find('.tab-panel').hide();
            $this.find('.hmw_' + $(this).val()).show();
        });


        $this.find("input[name=hmw_bruteforce].switch").change(function () {
            if ($(this).prop('checked')) {
                $this.find('.hmw_brute_enabled').show();
            } else {
                $this.find('.hmw_brute_enabled').hide();
            }
        });

        if ($this.find('#hmw_blockedips').length > 0) {
            $.post(
                hmwQuery.ajaxurl,
                {
                    action: 'hmw_blockedips',
                    hmw_nonce: hmwQuery.nonce
                }
            ).done(function (response) {
                if (typeof response.data !== 'undefined') {
                    $('#hmw_blockedips').html(response.data);
                }
            }).error(function () {
                $('#hmw_blockedips').html('no blocked ips');
            }, 'json');
        }

        $this.find('.start_securitycheck').find('button').on('click', function () {
            var $div = $this.find('.start_securitycheck');
            $div.after('<div class="wp_loading"></div>');
            $div.hide();
            $.post(
                hmwQuery.ajaxurl,
                {
                    action: 'hmw_securitycheck',
                    hmw_nonce: hmwQuery.nonce
                }
            ).done(function (response) {
                location.reload();
            }).error(function () {
                location.reload();
            });
            return false;
        });

        $this.find('button.hmw_resetexclude').on('click', function () {
            var $div = $this.find('.start_securitycheck');
            $.post(
                hmwQuery.ajaxurl,
                {
                    action: 'hmw_resetexclude',
                    hmw_nonce: hmwQuery.nonce
                }
            ).done(function (response) {
                if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                    if (response.success) {
                        $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-success text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                    } else {
                        $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>' + response.message + '</strong></div>');
                    }
                }
                setTimeout(function () {
                    $('.hmw_alert').remove();
                }, 5000)
            }).error(function () {
                $div.prepend('<div class="fixed-top mt-4 pt-4 alert alert-danger text-center hmw_alert" role="alert"><strong>Ajax Error.</strong></div>');
                setTimeout(function () {
                    $('.hmw_alert').remove();
                }, 5000)
            });
            return false;
        });


        $this.find('.hmw_plugin_install').on('click', function () {
            var button = $(this);
            button.hmw_loading(true);
            $.post(
                hmwQuery.ajaxurl,
                {
                    action: 'hmw_plugin_install',
                    plugin: button.data('plugin'),
                    hmw_nonce: hmwQuery.nonce
                }
            ).done(function (response) {
                if (typeof response.success !== 'undefined' && response.success) {
                    location.reload();
                } else {
                    button.hmw_loading(false);
                    button.html("Error.. Try again");
                }
            }).error(function () {
                button.removeClass('wp_loading');
                button.after('<div class="text-danger m-2">Could not install the plugin.</div>');
            });
        });

        $this.find('#hmw_support button').on('click', function () {
            var form = $this.find('#hmw_support');
            if (form.find("input#hmw_email").val() == '') {
                form.find("input#hmw_email").fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
                return;
            }
            if (form.find("textarea#hmw_question").val() == '') {
                form.find("textarea#hmw_question").fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
                return;
            }
            form.find(".hmw_error").hide();
            form.find(".hmw_success").hide();
            $(this).hmw_loading(true);

            $.post(
                hmwQuery.ajaxurl,
                {
                    action: 'hmw_support',
                    hmw_email: form.find("input#hmw_email").val(),
                    hmw_message: form.find("textarea#hmw_question").val(),
                    hmw_nonce: hmwQuery.nonce
                }
            ).done(function (response) {
                $(this).hmw_loading(false);

                if (typeof response.success !== 'undefined') {
                    form.find(".hmw_success").show();
                    form.find(".hmw_field").hide();
                } else {
                    form.find(".hmw_error").show();
                }
            }).fail(function (response) {
                $(this).hmw_loading(false);
                form.find(".hmw_error").show();
            }, 'json');
        });

    };

    $(document).ready(function () {
        $('#hmw_wrap').hmw_settingsListen();

        $(function () {
            $('[data-toggle="popover"]').popover()
        })

    });
})(jQuery);




