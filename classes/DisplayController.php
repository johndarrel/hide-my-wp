<?php
/**
 * The class handles the theme part in WP
 *
 * @package HMWP/Display
 * @file The Display View file
 *
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Classes_DisplayController {

	private static $cache;

	/**
	 * echo the css link from theme css directory
	 *
	 * @param string $uri The name of the css file or the entire uri path of the css file
	 * @param array $dependency
	 *
	 * @return void
	 */
	public static function loadMedia( $uri = '', $dependency = null ) {
		$css_uri = '';
		$js_uri  = '';

		if ( HMWP_Classes_Tools::isAjax() ) {
			return;
		}

		if ( isset( self::$cache[ $uri ] ) ) {
			return;
		}

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		self::$cache[ $uri ] = true;

		/* if is a custom css file */
		$name = strtolower( $uri );
		$id   = strtolower( _HMWP_NAMESPACE_ ) . '_' . $name;

		if ( $wp_filesystem->exists( _HMWP_ASSETS_DIR_ . 'css/' . $name . '.css' ) ) {
			$css_uri = _HMWP_ASSETS_URL_ . 'css/' . $name . '.css?ver=' . HMWP_VERSION_ID;
		}elseif ( $wp_filesystem->exists( _HMWP_ASSETS_DIR_ . 'css/' . $name . '.min.css' ) ) {
			$css_uri = _HMWP_ASSETS_URL_ . 'css/' . $name . '.min.css?ver=' . HMWP_VERSION_ID;
		}
		if ( $wp_filesystem->exists( _HMWP_ASSETS_DIR_ . 'js/' . $name . '.js' ) ) {
			$js_uri = _HMWP_ASSETS_URL_ . 'js/' . $name . '.js?ver=' . HMWP_VERSION_ID;
		} elseif ( $wp_filesystem->exists( _HMWP_ASSETS_DIR_ . 'js/' . $name . '.min.js' ) ) {
			$js_uri = _HMWP_ASSETS_URL_ . 'js/' . $name . '.min.js?ver=' . HMWP_VERSION_ID;
		}

		if ( $css_uri <> '' ) {
			if ( ! wp_style_is( $id ) ) {
				if ( did_action( 'wp_print_styles' ) ) {
					//phpcs:ignore 	WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
					echo "<link rel='stylesheet' id='".esc_attr($id)."-css'  href='".esc_url($css_uri)."' type='text/css' media='all' />";
				} elseif ( is_admin() || is_network_admin() ) { //load CSS for admin or on triggered
					wp_enqueue_style( $id, $css_uri, $dependency, HMWP_VERSION_ID );
					wp_print_styles( array( $id ) );
				}
			}
		}

		if ( $js_uri <> '' ) {
			if ( ! wp_script_is( $id ) ) {
				if ( did_action( 'wp_print_scripts' ) ) {
					//phpcs:ignore 	WordPress.WP.EnqueuedResources.NonEnqueuedScript
					echo "<script type='text/javascript' src='".esc_url($js_uri)."'></script>";
				} elseif ( is_admin() || is_network_admin() ) { //load CSS for admin or on triggered

					if ( ! wp_script_is( 'jquery' ) ) {
						wp_enqueue_script( 'jquery' );
						wp_print_scripts( array( 'jquery' ) );
					}

					wp_enqueue_script( $id, $js_uri, $dependency, HMWP_VERSION_ID, true );
					wp_print_scripts( array( $id ) );
				}
			}
		}
	}

    /**
     * Fetches and renders the view file associated with the given block.
     *
     * @param  string  $block  The name of the block whose view file is to be rendered.
     * @param  mixed  $view  Additional data or context to be used within the view.
     *
     * @return string|null The rendered output of the view file, or null if the file does not exist.
     */
	public function getView( $block, $view ) {
		$output = null;

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		//Set the current view file from /view
		$file = _HMWP_THEME_DIR_ . $block . '.php';

		if ( $wp_filesystem->exists( $file ) ) {
			ob_start();
			include $file;
			$output .= ob_get_clean();
		}

		return apply_filters( 'hmwp_getview', $output, $block );
	}

}
