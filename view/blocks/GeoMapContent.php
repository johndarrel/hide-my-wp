<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php if ( ! isset( $view ) ) { return; } ?>

<?php
$mapPoints = isset( $view->mapPoints ) && is_array( $view->mapPoints ) ? $view->mapPoints : array();
$topPoints = isset( $view->topPoints ) && is_array( $view->topPoints ) ? $view->topPoints : array();

$flagsUrl = _HMWP_ASSETS_URL_ . 'flags/';
$flagsDir = _HMWP_ASSETS_DIR_ . 'flags/';

$topMax = 1;
foreach ( $topPoints as $row ) {
    $total = isset( $row['total'] ) ? (int) $row['total'] : 0;
    if ( $total > $topMax ) {
        $topMax = $total;
    }
}

$mapColClass = ! empty( $topPoints ) ? 'col-sm-8 p-0 pr-2' : 'col-sm-12 p-0';
?>

<?php if ( empty( $mapPoints ) ) : ?>
    <div id="hmwp-geomap-wrap" class="col-sm-12 p-0 m-0 text-center">
        <div class="text-black-50 small px-1 py-3">
            <?php echo esc_html__( 'No threat data recorded yet. Attacks will appear here once the Security Threats Log captures activity.', 'hide-my-wp' ); ?>
        </div>
    </div>
    <?php return; ?>
<?php endif; ?>

<div id="hmwp-geomap-wrap" class="col-sm-12 p-0 m-0">
    <div class="col-sm-12 row p-0 m-0">

        <div class="<?php echo esc_attr( $mapColClass ); ?>">
            <div class="hmwp-geomap" id="hmwp-geomap-svg-wrap" style="background:#f0f0f0;">
                <?php
                $mapFile = _HMWP_ASSETS_DIR_ . 'img/map.svg';

                if ( file_exists( $mapFile ) && is_readable( $mapFile ) ) {
                    $svg = file_get_contents( $mapFile );

                    if ( $svg ) {
                        echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        echo '<div class="p-3 text-black-50 small">' . esc_html__( 'Could not load the map SVG.', 'hide-my-wp' ) . '</div>';
                    }
                } else {
                    echo '<div class="p-3 text-black-50 small">' . esc_html__( 'Map SVG file not found.', 'hide-my-wp' ) . '</div>';
                }
                ?>
            </div>
        </div>

        <?php if ( ! empty( $topPoints ) ) : ?>
            <div class="col-sm-4 p-0 pl-2">
                <div class="font-weight-bold small mb-2"><?php echo esc_html__( 'Top Threat Countries (7d)', 'hide-my-wp' ); ?></div>

                <div class="hmwp-geomap-top-list">
                    <?php foreach ( $topPoints as $item ) : ?>
                        <?php
                        $cc        = strtoupper( $item['cc'] ?? '' );
                        $name      = $item['name'] ?? $cc;
                        $total     = isset( $item['total'] ) ? (int) $item['total'] : 0;
                        $pct       = $topMax > 0 ? round( $total / $topMax * 100 ) : 0;
                        $flagFile  = strtolower( $cc ) . '.png';
                        $isBlocked = ! empty( $item['blocked'] );
                        $barColor  = $isBlocked ? '#9e9e9e' : '#e53935';
                        ?>
                        <div class="hmwp-geomap-country">
                            <?php if ( $cc && file_exists( $flagsDir . $flagFile ) ) : ?>
                                <img src="<?php echo esc_url( $flagsUrl . $flagFile ); ?>" alt="<?php echo esc_attr( $cc ); ?>" width="20" height="14">
                            <?php else : ?>
                                <span class="hmwp-geomap-country-cc"><?php echo esc_html( $cc ); ?></span>
                            <?php endif; ?>

                            <span class="hmwp-geomap-country-name" title="<?php echo esc_attr( $name ); ?>">
								<?php echo esc_html( $name ); ?>
							</span>

                            <div class="bar-wrap">
                                <div class="bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%;background:<?php echo esc_attr( $barColor ); ?>;"></div>
                            </div>

                            <span class="count" title="<?php echo esc_attr__( 'Threats', 'hide-my-wp' ); ?>">
								<?php echo esc_html( number_format( $total ) ); ?>
							</span>

                            <?php if ( $isBlocked ) : ?>
                                <span class="hmwp-geomap-blocked-badge" title="<?php echo esc_attr__( 'Blocked', 'hide-my-wp' ); ?>">&#x1F6AB;</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ( HMWP_Classes_Tools::getValue( 'page' ) <> 'hmwp_firewall' ) : ?>
                    <div class="mt-2 pt-2" style="border-top:1px solid #eee;">
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall&tab=geoblock', true ) ); ?>" class="btn btn-sm btn-default small d-block text-center">
                            <?php echo esc_html__( 'Block Countries &rsaquo;', 'hide-my-wp' ); ?>
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</div>