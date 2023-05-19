(function ($) {
    "use strict";

    if(typeof ajaxerror === 'undefined'){
        var ajaxerror = 'Ajax is not loading correctly. Clear all cache and try again.';
    }

    //Get the Hash param from URL
    $.hmwp_getHashParam = function (key) {
        var urlparts = location.href.split("#");
        if (urlparts.length >= 2) {
            urlparts.shift();
            var queryString = urlparts.join("#");
            var results = new RegExp("[\\?&#]*" + key + "=([^&#]*)").exec(queryString);
            if (results) {
                return results[1] || 0
            }
        }
        return false
    };

    //Set the Hash param to URL
    $.hmwp_setHashParam = function (key, val) {
        var urlparts = location.href.split("#");
        if (urlparts.length >= 2) {
            var add = true;
            var urlBase = urlparts.shift();
            var queryString = urlparts.join("#");
            var prefix = encodeURIComponent(key) + "=";
            var pars = queryString.split(/[&;]/g);
            for (var i = pars.length; i-- > 0;) {
                if (pars[i].lastIndexOf(prefix, 0) !== -1 || pars[i] === "") {
                    pars[i] = pars[i].replace(pars[i], prefix + val);
                    add = false;
                    break
                }
            }
            add && pars.push(prefix + val);
            location.href = urlBase + "#" + pars.join("&")
        } else {
            location.href += "#" + key + "=" + val
        }
    };

    //Add the loading icon to field
    $.fn.hmwp_loading = function (state) {
        var $this = this;
        var loading = '<i class="fa fa-circle-o-notch fa-spin mr-1 hmwp_loading"></i>';
        $this.find('i').remove();
        if (state) {
            $this.prepend(loading);
        } else {
            $('.hmwp_loading').remove();
        }

        return $this;
    };

    $.fn.hmwp_fixSettings = function (name, value) {
        var $form = $('#hmwp_fixsettings_form');
        var $this = this;

        $this.hmwp_loading(true);

        $.post(
            ajaxurl,
            {
                action: $form.find('input[name=action]').val(),
                name: name,
                value: value,
                hmwp_nonce: $form.find('input[name=hmwp_nonce]').val(),
                _wp_http_referer: $form.find('input[name=_wp_http_referer]').val()
            }
        ).done(
            function (response) {
                $this.hmwp_loading(false);

                if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                    if (response.success) {
                        $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed success" role="alert">' + response.message + '</div>');
                        $this.hide();
                    } else {
                        $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">' + response.message + '</div>');
                    }
                }

                setTimeout(
                    function () {
                        $('.hmwp_notice').remove();
                    }, 5000
                )
            }
        ).error(
            function () {
                $this.hmwp_loading(false);
                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">'+ajaxerror+'</div>');

                setTimeout(
                    function () {
                        $('.hmwp_notice').remove();
                    }, 5000
                )
            }, 'json'
        );
    };

    $.fn.hmwp_fixConfig = function (name, value) {
        var $form = $('#hmwp_fixconfig_form');
        var $this = this;

        $this.hmwp_loading(true);
        $.post(
            ajaxurl,
            {
                action: $form.find('input[name=action]').val(),
                name: name,
                value: value,
                hmwp_nonce: $form.find('input[name=hmwp_nonce]').val(),
                _wp_http_referer: $form.find('input[name=_wp_http_referer]').val()

            }
        ).done(
            function (response) {
                $this.hmwp_loading(false);

                if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                    if (response.success) {
                        $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed success" role="alert">' + response.message + '</div>');
                        $this.hide();
                    } else {
                        $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">' + response.message + '</div>');
                    }
                }

                setTimeout(
                    function () {
                        $('.hmwp_notice').remove();
                    }, 5000
                )
            }
        ).error(
            function () {
                $this.hmwp_loading(false);

                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">'+ajaxerror+'</div>');

                setTimeout(
                    function () {
                        $('.hmwp_notice').remove();
                    }, 5000
                )
            }, 'json'
        );
    };

    //Add Listener for Security Check
    $.fn.hmwp_securityCheckListen = function () {
        var $this = this;

        $this.find('form.hmwp_securityexclude_form').on(
            'submit', function () {
                var $form = $(this);

                $.post(
                    ajaxurl,
                    $form.serialize()
                ).done(
                    function (response) {
                        if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                            if (response.success) {
                                $('body').parents('tr:last').fadeOut();
                                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed success" role="alert">' + response.message + '</div>');
                            } else {
                                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">' + response.message + '</div>');
                            }
                        }
                        setTimeout(
                            function () {
                                $('.hmwp_notice').remove();
                            }, 5000
                        )
                    }
                ).error(
                    function () {
                        $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">'+ajaxerror+'</div>');
                        setTimeout(
                            function () {
                                $('.hmwp_notice').remove();
                            }, 5000
                        )
                    }, 'json'
                );

                return false;
            }
        );

        $this.find('form#hmwp_securitycheck').on(
            'submit', function () {
                var $form = $(this);
                var $div = $this.find('.start_securitycheck');
                $div.after('<div class="wp_loading"></div>');
                $div.hide();

                $.post(
                    ajaxurl,
                    $form.serialize()
                ).done(
                    function (response) {
                        location.reload();
                    }
                ).error(
                    function () {
                        location.reload();
                    }
                );

                return false;
            }
        );

        $this.find('form#hmwp_resetexclude').on(
            'submit', function () {
                var $form = $(this);
                $.post(
                    ajaxurl,
                    $form.serialize()
                ).done(
                    function (response) {

                        if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                            if (response.success) {
                                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed success" role="alert">' + response.message + '</div>');
                            } else {
                                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">' + response.message + '</div>');
                            }
                        }

                        setTimeout(
                            function () {
                                $('.hmwp_notice').remove();
                            }, 5000
                        )

                    }
                ).error(
                    function () {

                        $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">'+ajaxerror+'</div>');

                        setTimeout(
                            function () {
                                $('.hmwp_notice').remove();
                            }, 5000
                        )
                    }
                );

                return false;
            }
        );

        $this.find('button.frontend_test').on(
            'click', function () {
                var $button = $(this);
                var $form = $(this).parent('form');

                $this.find('#hmwp_frontendcheck_content').html('');
                $this.find('#hmwp_solutions').hide();
                $this.find('#hmwp_frontendcheck_content').addClass('wp_loading_min');

                $.post(
                    ajaxurl,
                    $form.serialize()
                ).done(
                    function (response) {
                        if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                            if (response.success) {
                                $this.find('#hmwp_frontendcheck_content').html('<div class="text-center alert alert-success my-2" role="alert">' + response.message + '</div>');
                            } else {
                                $this.find('#hmwp_frontendcheck_content').html('<div class="text-center alert alert-danger my-2" role="alert">' + response.message + '</div>');
                                $this.find('#hmwp_solutions').show();
                            }
                        }
                        $this.find('#hmwp_frontendcheck_content').removeClass('wp_loading_min');
                    }
                ).error(
                    function () {
                        $this.find('#hmwp_frontendcheck_content').html('<div class="text-center alert alert-danger my-2" role="alert">'+ajaxerror+'</div>');
                        $this.find('#hmwp_solutions').show();
                        $this.find('#hmwp_frontendcheck_content').removeClass('wp_loading_min');
                    }
                );

                return false;
            }
        );

    };

    //Add the Listener for Settings
    $.fn.hmwp_settingsListen = function () {
        //set $this as #hmwp_wrap
        var $this = this;
        //init settings as saved
        var unsaved = false;

        //listen the SubMenu click
        $this.find(".hmwp_nav_item").on(
            'click', function ( ev ) {
                ev.preventDefault();

                $this.find('.tab-panel').hide();
                if($this.find('#'+$(this).data('tab')).length > 0) {
                    $this.find('#'+$(this).data('tab')).show();
                    $.hmwp_setHashParam('tab', $(this).data('tab'));
                }
                $this.find('.hmwp_nav_item').removeClass('active');
                $this.find(".hmwp_nav_item[data-tab=" + $(this).data('tab') + "]").addClass('active');
            }
        );


        $('button.hmwp_modal').on(
            'click', function () {
                var $button = $(this);

                if($button.data("remote")) {
                    $($button.data("target") + ' .modal-body').attr('src',$button.data("remote"));

                    $($button.data("target")).on(
                        'hidden.bs.modal', function () {
                            $button.hmwp_loading(true);
                            location.reload();
                        }
                    );
                }

                $($button.data("target")).modal('show');
            }
        );

        if ($('input[name=hmwp_mode]').val() !== 'default') {
            //Check the selected SubMenu in URL
            if ($.hmwp_getHashParam('tab')) {
                var $current = $.hmwp_getHashParam('tab');

                if($current !== '' && $this.find('#'+$current).length > 0) {
                    $this.find('.tab-panel').hide();
                    $this.find('.hmwp_nav_item').removeClass('active');
                    $this.find('#' + $current).show();
                    $this.find(".hmwp_nav_item[data-tab=" + $current + "]").addClass('active');
                }
            } else {
                $this.find("a.hmwp_nav_item:first").addClass('active');
                $this.find("a.hmwp_nav_item:first").trigger('click');
            }
        }

        //Open suboptions for an option if exists
        $this.find("input.switch").not('.nopopup').change(
            function () {

                //set settings as unsaved to prevent browsing our
                unsaved = true;

                if($('div.' + $(this).attr('name')).length) {
                    if ($(this).prop('checked')) {
                        $('div.' + $(this).attr('name')).show();
                    } else {
                        $('div.' + $(this).attr('name')).hide();
                    }
                }
            }
        );

        $this.find("input").not('.nopopup').change(
            function () {
                //set settings as unsaved to prevent browsing our
                unsaved = true;
            }
        );

        $this.find("button[type=submit]").click(function(){
            $(this).hmwp_loading(true);
        });

        $this.find("input[type=submit]").click(function(){
            $(this).hmwp_loading(true);
        });

        $this.find("input.switch").each(
            function () {
                if (!$(this).prop('checked')) {
                    if($('div.' + $(this).attr('name')).length) {
                        $('div.' + $(this).attr('name')).hide();
                    }
                }
            }
        );

        //Admin Security
        $this.find('input[name=hmwp_admin_url]').on(
            'keyup', function () {
                if ($(this).val() !== 'wp-admin' && $(this).val() != '') {
                    //hmwp_hide_newadmin
                    $this.find('.admin_warning').show();
                    $this.find('.hmwp_hide_newadmin_div').show();
                } else {
                    $this.find('.admin_warning').hide();
                    $this.find('.hmwp_hide_newadmin_div').hide();
                }
            }
        );

        //Login Security
        $this.find('input[name=hmwp_login_url]').on(
            'keyup', function () {
                if ($(this).val() !== 'wp-login.php'  && $(this).val() != '') {
                    $this.find('.hmwp_hide_wplogin_div').show();
                } else {
                    $this.find('.hmwp_hide_wplogin_div').hide();
                }

                if ($(this).val() !== 'login'  && $(this).val() != '') {
                    $this.find('.hmwp_hide_login_div').show();
                } else {
                    $this.find('.hmwp_hide_login_div').hide();
                }
            }
        );

        $this.find('input[name=hmwp_login_url]').trigger('keyup');

        $this.find("input[name=hmwp_hide_admin].switch").change(
            function () {
                if ($(this).prop('checked')) {
                    $this.find('.wp-admin_warning').show();
                    $this.find('.hmwp_hide_newadmin_div').show();
                } else {
                    $this.find('.wp-admin_warning').hide();
                    $this.find('.hmwp_hide_newadmin_div').hide();
                }
            }
        );

        //hide WP Core Paths
        $this.find("input[name=hmwp_hide_oldpaths_plugins].switch").change(
            function () {
                if ($(this).prop('checked')) {
                    $this.find('input[name=hmwp_hide_oldpaths]').prop("checked", true);
                }
            }
        );
        $this.find("input[name=hmwp_hide_oldpaths_themes].switch").change(
            function () {
                if ($(this).prop('checked')) {
                    $this.find('input[name=hmwp_hide_oldpaths]').prop("checked", true);
                }
            }
        );

        //Advanced plugins mapping
        $("#hmw_plugins_mapping_new").on(
            'change', function ( ev ) {
                var $name = $(this).find(":selected").text();
                var $value = $(this).find(":selected").val();
                var $div = $('div.hmw_plugins_mapping_new').clone();

                $div.appendTo('div.hmw_plugins_mappings');
                $div.find('.hmw_plugins_mapping_title').html($name);
                $div.find('input').attr('name', 'hmw_plugins_mapping[' + $value + ']');
                $div.find('input').attr('value', $name);

                $(this).find(":selected").remove();
                $div.removeClass('hmw_plugins_mapping_new');

                if($(this).find('option').length == 1) {
                    $('.hmw_plugins_mapping_select').hide();
                }
                $div.show();
            }
        );

        //Advanced Theme Naming
        $("#hmw_themes_mapping_new").on(
            'change', function ( ev ) {
                var $name = $(this).find(":selected").text();
                var $value = $(this).find(":selected").val();
                var $div = $('div.hmw_themes_mapping_new').clone();

                $div.appendTo('div.hmw_themes_mappings');
                $div.find('.hmw_themes_mapping_title').html($name);
                $div.find('input').attr('name', 'hmw_themes_mapping[' + $value + ']');
                $div.find('input').attr('value', $name);

                $(this).find(":selected").remove();
                $div.removeClass('hmw_themes_mapping_new');

                if($(this).find('option').length == 1) {
                    $('.hmw_themes_mapping_select').hide();
                }
                $div.show();
            }
        );

        //Header Security
        $("#hmwp_security_headers_new").on(
            'change', function ( ev ) {
                var $name = $(this).find(":selected").text();
                var $value = $(this).find(":selected").val();
                var $div = $('div.' + $name);

                $div.appendTo('div.hmwp_security_headers');
                $div.find('input').attr('name', 'hmwp_security_headers[' + $name + ']');
                $div.find('input').attr('value', $value);

                $(this).find(":selected").remove();

                if($(this).find('option').length == 1) {
                    $('.hmwp_security_headers_new').hide();
                }
                $div.show();
            }
        );

        $this.find("button.brute_use_math").on(
            'click', function () {
                $this.find('input[name=brute_use_math]').val(1);
                $this.find('input[name=brute_use_captcha]').val(0);
                $this.find('input[name=brute_use_captcha_v3]').val(0);
                $this.find('.group_autoload button').removeClass('active');

                $this.find('div.brute_use_math').show();
                $this.find('div.brute_use_captcha').hide();
                $this.find('div.brute_use_captcha_v3').hide();
            }
        );

        $this.find("button.brute_use_captcha").on(
            'click', function () {
                $this.find('input[name=brute_use_captcha]').val(1);
                $this.find('input[name=brute_use_math]').val(0);
                $this.find('input[name=brute_use_captcha_v3]').val(0);
                $this.find('.group_autoload button').removeClass('active');

                $this.find('div.brute_use_captcha').show();
                $this.find('div.brute_use_math').hide();
                $this.find('div.brute_use_captcha_v3').hide();
            }
        );

        $this.find("button.brute_use_captcha_v3").on(
            'click', function () {
                $this.find('input[name=brute_use_captcha]').val(0);
                $this.find('input[name=brute_use_math]').val(0);
                $this.find('input[name=brute_use_captcha_v3]').val(1);
                $this.find('.group_autoload button').removeClass('active');

                $this.find('div.brute_use_captcha').hide();
                $this.find('div.brute_use_math').hide();
                $this.find('div.brute_use_captcha_v3').show();
            }
        );

        //Load the blocked IP Addresses
        $this.find('#hmwp_blockedips_form').on(
            'submit',function () {
                $this.find('#hmwp_blockedips').html('');
                $this.find('#hmwp_blockedips').hmwp_loading(true);

                $.post(
                    ajaxurl,
                    $('form#hmwp_blockedips_form').serialize()
                ).done(
                    function (response) {

                        if (typeof response.data !== 'undefined') {
                            $('#hmwp_blockedips').html(response.data);
                        }

                        $this.find('#hmwp_blockedips').hmwp_loading();

                    }
                ).error(
                    function () {

                        $('#hmwp_blockedips').html('no blocked ips');

                        $this.find('#hmwp_blockedips').hmwp_loading();

                    }, 'json'
                );

                return false;
            }
        );

        if ($this.find('#hmwp_blockedips').length > 0) {
            $this.find('#hmwp_blockedips_form').trigger('submit');
        }

        //////////////////////////////////////////////

        $this.find('.ajax_submit input').on(
            'change',function () {
                var $form = $(this).parents('form:last');
                var $input = $(this);

                $.post(
                    ajaxurl,
                    $form.serialize()
                ).done(
                    function (response) {
                        if (typeof response.success !== 'undefined' && typeof response.message !== 'undefined') {
                            if (response.success) {
                                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed success" role="alert">' + response.message + '</div>');

                                if($input.prop('checked')) {
                                    $form.parents('.hmwp_feature:last').removeClass('bg-light').addClass('active');
                                }else{
                                    $form.parents('.hmwp_feature:last').removeClass('active').addClass('bg-light');
                                }

                                //set settings as saved
                                unsaved = false;

                            } else {
                                $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">' + response.message + '</div>');
                            }
                        }

                        setTimeout(
                            function () {
                                $('.hmwp_notice').remove();
                            }, 5000
                        )

                    }
                ).error(
                    function () {

                        $('body').prepend('<div class="text-center hmwp_notice hmwp_notice_fixed danger" role="alert">'+ajaxerror+'</div>');

                        setTimeout(
                            function () {
                                $('.hmwp_notice').remove();
                            }, 5000
                        )

                    }
                );
            }
        );

        $this.find('form').on(
            'submit', function () {
                //set settings as saved
                unsaved = false;
            }
        );

        //If the settings are not saved, alert the user on browsing out
        window.onbeforeunload = function (e) {
            e = e || window.event;
            if (unsaved) {
                // For IE and Firefox
                if (e) {
                    e.returnValue = "You have unsaved changes.";
                }
                // For Safari
                return "You have unsaved changes.";
            }
        };

    };


    $('#hmwp_wrap').ready(
        function () {
            $(this).hmwp_settingsListen();
            $(this).hmwp_securityCheckListen();
            //$(this).find('button.frontend_test').trigger('click');
        }
    );
})(jQuery);




