<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php if ( ! isset( $view ) ) { return; } ?>
<?php

if ( ! HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) ) {
    return;
}

HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' )->loadMedia( 'geomap' );
?>

<div id="hmwp-ajax-geomap" class="col-sm-12 p-0 m-0">
    <div class="hmwp-geomap-loader">
        <div class="hmwp-geomap-loader-title"><?php echo esc_html__( 'Threat map is loading', 'hide-my-wp' ); ?></div>
        <div class="hmwp-geomap-loader-subtitle"><?php echo esc_html__( 'Analyzing recent threats and building the map...', 'hide-my-wp' ); ?></div>

        <div class="hmwp-geomap-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="hmwp-geomap-progress-bar"></div>
        </div>

        <div class="hmwp-geomap-progress-text">0%</div>
    </div>
</div>

<script>
    (function ($) {
        let $wrap = $('#hmwp-ajax-geomap');

        if (!$wrap.length || $wrap.data('loaded') === 1) {
            return;
        }

        $wrap.data('loaded', 1);

        let $progress = $wrap.find('.hmwp-geomap-progress');
        let $bar = $wrap.find('.hmwp-geomap-progress-bar');
        let $text = $wrap.find('.hmwp-geomap-progress-text');
        let progress = 0;

        let setProgress = function (value, text) {
            progress = Math.max(0, Math.min(100, value));
            $bar.css('width', progress + '%');
            $progress.attr('aria-valuenow', progress);
            $text.text((text ? text + ' ' : '') + progress + '%');
        };

        setProgress(4, '<?php echo esc_js( __( 'Loading', 'hide-my-wp' ) ); ?>');

        let progressTimer = setInterval(function () {
            if (progress < 60) {
                setProgress(progress + Math.floor(Math.random() * 6) + 2, '<?php echo esc_js( __( 'Loading', 'hide-my-wp' ) ); ?>');
            } else if (progress < 99) {
                setProgress(progress + 1, '<?php echo esc_js( __( 'Almost there...', 'hide-my-wp' ) ); ?>');
            }
        }, 500);

        $.post(
            ajaxurl,
            {
                action: 'hmwp_load_threat_map',
                page: '<?php echo esc_attr( HMWP_Classes_Tools::getValue( 'page' ) ); ?>',
                hmwp_nonce: <?php echo wp_json_encode( wp_create_nonce( 'hmwp_load_threat_map' ) ); ?>
            },
            function (response) {
                clearInterval(progressTimer);

                if (response && response.success && response.data && response.data.html) {
                    setProgress(100, '<?php echo esc_js( __( 'Loaded', 'hide-my-wp' ) ); ?>');

                    if (response.data.mapData) {
                        window.hmwpGeoMapData = response.data.mapData;
                    }

                    setTimeout(function () {
                        $wrap.html(response.data.html);

                        if (typeof window.hmwpRenderGeoMap === 'function') {
                            let tries = 0;

                            let tryRender = function () {
                                tries++;

                                let hasSvg = $('#hmwp-geomap-svg-wrap svg').length > 0;
                                let hasData = window.hmwpGeoMapData && window.hmwpGeoMapData.points && window.hmwpGeoMapData.points.length >= 0;

                                if (hasSvg && hasData) {
                                    window.hmwpRenderGeoMap();
                                    return;
                                }

                                if (tries < 40) {
                                    setTimeout(tryRender, 250);
                                }
                            };

                            setTimeout(tryRender, 50);
                        }
                    }, 180);
                } else {
                    $wrap.html('<div class="text-danger small px-1 py-3"><?php echo esc_js( __( 'Could not load threat map.', 'hide-my-wp' ) ); ?></div>');
                }
            },
            'json'
        ).fail(function () {
            clearInterval(progressTimer);
            $wrap.html('<div class="text-danger small px-1 py-3">' + (typeof ajaxerror !== 'undefined' ? ajaxerror : '<?php echo esc_js( __( 'Could not load threat map.', 'hide-my-wp' ) ); ?>') + '</div>');
        });
    })(jQuery);
</script>