<?php
/**
 * Inline reCAPTCHA key check, included by view/Brute.php with $type set to v2, v3 or enterprise
 *
 * @file The reCAPTCHA check block
 * @package HMWP/Brute
 * @since 9.1.03
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

if ( ! isset( $type ) ) {
    return;
} ?>
<div class="col-sm-12 py-3 mx-0 my-3 hmwp_recaptcha_check" data-type="<?php echo esc_attr( $type ) ?>"
     data-nonce="<?php echo esc_attr( wp_create_nonce( 'hmwp_recaptcha_check' ) ) ?>"
     data-msg-sitekey="<?php echo esc_attr__( 'Fill in the Site Key first.', 'hide-my-wp' ) ?>"
     data-msg-script="<?php echo esc_attr__( 'Google reCAPTCHA could not be loaded. Check the Site Key and the connection to google.com.', 'hide-my-wp' ) ?>"
     data-msg-widget="<?php echo esc_attr__( 'Google refused the Site Key. Check the key, its type and the domains it is registered for.', 'hide-my-wp' ) ?>"
     data-msg-tick="<?php echo esc_attr__( 'Tick the box above to verify the keys with Google.', 'hide-my-wp' ) ?>"
     data-msg-ajax="<?php echo esc_attr__( 'The check could not be sent to your website.', 'hide-my-wp' ) ?>">
    <h4 class="mb-2"><?php echo esc_html__( 'Check the reCAPTCHA keys', 'hide-my-wp' ); ?></h4>
    <div class="text-black-50 small mb-3"><?php echo esc_html__( 'Loads Google reCAPTCHA with the keys above and verifies a token with Google. Change a key and run the check again, there is no need to save first.', 'hide-my-wp' ); ?></div>
    <div class="hmwp_recaptcha_widget mb-2"></div>
    <button type="button" class="btn btn-default hmwp_recaptcha_run"><?php echo esc_html__( 'Run check', 'hide-my-wp' ); ?></button>
    <div class="hmwp_recaptcha_result mt-3" style="display:none"></div>
</div>
<?php if ( empty( $hmwp_recaptcha_check_js ) ) {
    $hmwp_recaptcha_check_js = true; ?>
    <script>
        jQuery(function ($) {
            var loaded = {type: '', key: ''};

            // The keys as typed in the form, not the saved ones
            function fields(type) {
                if (type === 'enterprise') {
                    return {
                        site_key: $('input[name=brute_google_site_key]').val(),
                        project_id: $('input[name=brute_google_project_id]').val(),
                        api_key: $('input[name=brute_google_api_key]').val()
                    };
                }
                var s = (type === 'v3' ? '_v3' : '');
                return {
                    site_key: $('input[name=brute_captcha_site_key' + s + ']').val(),
                    secret_key: $('input[name=brute_captcha_secret_key' + s + ']').val()
                };
            }

            function show($box, level, message, details) {
                var html = '<div class="alert alert-' + level + ' mb-0"><div>' + $('<div>').text(message).html() + '</div>';
                if (details && details.length) {
                    html += '<div class="small mt-1">' + $('<div>').text(details.join(' | ')).html() + '</div>';
                }
                $box.find('.hmwp_recaptcha_result').html(html + '</div>').show();
            }

            // Google binds the site key at script load, so a new key means a fresh script
            function loadScript(type, key) {
                return new Promise(function (resolve, reject) {
                    if (window.grecaptcha && loaded.type === type && loaded.key === key) {
                        resolve();
                        return;
                    }
                    $('script[data-hmwp-recaptcha]').remove();
                    $('.grecaptcha-badge').remove();
                    try { delete window.grecaptcha; } catch (e) { window.grecaptcha = undefined; }
                    window.___grecaptcha_cfg = undefined;

                    var src = (type === 'enterprise')
                        ? 'https://www.google.com/recaptcha/enterprise.js?render=' + encodeURIComponent(key)
                        : 'https://www.google.com/recaptcha/api.js?render=' + (type === 'v3' ? encodeURIComponent(key) : 'explicit');
                    var done = false;
                    var timer = setTimeout(function () { if (!done) { done = true; reject('timeout'); } }, 15000);

                    window.hmwpRecaptchaLoaded = function () {
                        if (!done) { done = true; clearTimeout(timer); loaded = {type: type, key: key}; resolve(); }
                    };
                    var s = document.createElement('script');
                    s.src = src + '&onload=hmwpRecaptchaLoaded';
                    s.async = true;
                    s.defer = true;
                    s.setAttribute('data-hmwp-recaptcha', '1');
                    s.onerror = function () { if (!done) { done = true; clearTimeout(timer); reject('script'); } };
                    document.head.appendChild(s);
                });
            }

            function verify($box, type, token) {
                var data = $.extend({action: 'hmwp_recaptcha_check', hmwp_nonce: $box.data('nonce'), type: type, token: token}, fields(type));
                $.post(ajaxurl, data).done(function (r) {
                    var d = (r && r.data) ? r.data : {};
                    show($box, (r && r.success) ? 'success' : 'danger', d.message || '', d.details || []);
                }).fail(function () {
                    show($box, 'danger', $box.data('msg-ajax'), []);
                }).always(function () {
                    $box.find('.hmwp_recaptcha_run').prop('disabled', false);
                });
            }

            function fail($box, why) {
                show($box, 'danger', $box.data('msg-widget'), why ? [String(why)] : []);
                $box.find('.hmwp_recaptcha_run').prop('disabled', false);
            }

            $('.hmwp_recaptcha_check').on('click', '.hmwp_recaptcha_run', function () {
                var $btn = $(this);
                var $box = $btn.closest('.hmwp_recaptcha_check');
                var type = $box.data('type');
                var f = fields(type);
                var $w = $box.find('.hmwp_recaptcha_widget');

                $box.find('.hmwp_recaptcha_result').hide();
                $w.empty();

                if (!f.site_key) {
                    show($box, 'danger', $box.data('msg-sitekey'), []);
                    return;
                }
                $btn.prop('disabled', true);

                loadScript(type, f.site_key).then(function () {
                    try {
                        if (type === 'v2') {
                            grecaptcha.render($w[0], {
                                sitekey: f.site_key,
                                callback: function (token) { verify($box, type, token); },
                                'error-callback': function () { fail($box, ''); }
                            });
                            show($box, 'info', $box.data('msg-tick'), []);
                            $btn.prop('disabled', false);
                        } else if (type === 'v3') {
                            grecaptcha.ready(function () {
                                try {
                                    grecaptcha.execute(f.site_key, {action: 'hmwp_check'}).then(function (t) { verify($box, type, t); }, function (e) { fail($box, e); });
                                } catch (e) { fail($box, e); }
                            });
                        } else {
                            grecaptcha.enterprise.ready(function () {
                                try {
                                    grecaptcha.enterprise.execute(f.site_key, {action: 'LOGIN'}).then(function (t) { verify($box, type, t); }, function (e) { fail($box, e); });
                                } catch (e) { fail($box, e); }
                            });
                        }
                    } catch (e) { fail($box, e); }
                }).catch(function (why) {
                    show($box, 'danger', $box.data('msg-script'), [String(why)]);
                    $btn.prop('disabled', false);
                });
            });
        });
    </script>
<?php } ?>
