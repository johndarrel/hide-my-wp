<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Register & Translate Option Strings for Multilingual Plugins
 *
 * @package HMWP/Translate
 * @since 8.3.0
 */
class HMWP_Models_Translate {

	/** @var array|string[] Option strings that should be translatable. */
	protected $strings = array();

	/** @var string Default locale. */
	protected $default_locale = 'en_US';

	public function __construct() {

		add_action( 'init', array( $this, 'initStrings' ), 1 );

		// Register strings for registry-based multilingual plugins in wp-admin.
		add_action( 'admin_init', array( $this, 'registerStrings' ) );

		// Attach filters early so any option retrieval gets translated.
		add_action( 'init', array( $this, 'attachOptionFilters' ), 2 );
	}

	/**
	 * Initialize translatable strings.
	 * Must run on 'init' (not in __construct) so the plugin text domain
	 * is already loaded when __() is called.
	 *
	 * @return void
	 */
	public function initStrings() {
		$this->strings = array(
			'hmwp_disable_click_message'      => __( "Right click is disabled!", 'hide-my-wp' ),
			'hmwp_disable_inspect_message'    => __( "View Source is disabled!", 'hide-my-wp' ),
			'hmwp_disable_source_message'     => __( "View Source is disabled!", 'hide-my-wp' ),
			'hmwp_disable_copy_paste_message' => __( "Copy/Paste is disabled!", 'hide-my-wp' ),
			'hmwp_disable_drag_drop_message'  => __( "Drag-n-Drop is disabled!", 'hide-my-wp' ),
			'hmwp_2falogin_message'           => __( "ERROR: Too many invalid verification codes, you can try again in {time}.", 'hide-my-wp' ),
			'hmwp_2falogin_fail_message'      => __( "WARNING: Your account has attempted to login {count} times without providing a valid code. The last failed login occurred {time} ago. If this wasn't you, please reset your password.", 'hide-my-wp' ),
			'hmwp_2falogin_email_subject'     => __( "Your Login Confirmation Code", 'hide-my-wp' ),
			'hmwp_2falogin_email_message'     => __( "Your login confirmation code is: %s\n\nThis code is valid for a limited time and can be used only once.\n\nIf you did not attempt to log in, please ignore this email.", 'hide-my-wp' ), //phpcs:ignore
			'hmwp_brute_message'              => __( "Your IP has been flagged for potential security violations. Please try again in a little while.", 'hide-my-wp' ),
		);
	}

	/**
	 * Get the registered strings.
	 *
	 * @return string[]
	 */
	public function getStrings() {
		// Allow external filters first
		$strings = apply_filters( 'hmwp_translate_strings', $this->strings );

		// Keep only keys that exist in options
		return array_intersect_key( $strings, HMWP_Classes_Tools::$options );

	}

	/**
	 * Get the registered strings.
	 *
	 * @return string[]
	 */
	public function getRawStrings() {

		// switch_to_locale() re-runs create_initial_taxonomies/post_types, which detaches custom taxonomies from CPTs and crashes the Block Editor; suppress them around the switch.
		$has_tax = has_action( 'change_locale', 'create_initial_taxonomies' );
		$has_cpt = has_action( 'change_locale', 'create_initial_post_types' );
		if ( false !== $has_tax ) {
			remove_action( 'change_locale', 'create_initial_taxonomies', $has_tax );
		}
		if ( false !== $has_cpt ) {
			remove_action( 'change_locale', 'create_initial_post_types', $has_cpt );
		}

		switch_to_locale( $this->default_locale );
		$strings = apply_filters( 'hmwp_translate_strings', $this->strings );
		restore_previous_locale();

		if ( false !== $has_cpt ) {
			add_action( 'change_locale', 'create_initial_post_types', $has_cpt );
		}
		if ( false !== $has_tax ) {
			add_action( 'change_locale', 'create_initial_taxonomies', $has_tax );
		}

		// Keep only keys that exist in options
		return array_intersect_key( $strings, HMWP_Classes_Tools::$options );

	}

	/**
	 * Register strings with WPML and Polylang (if available).
	 *
	 * Note: Registering on every admin request is acceptable for a small list,
	 * but best practice is to also register on option-save events.
	 *
	 * @return void
	 */
	public function registerStrings() {

		foreach ( $this->getRawStrings() as $key => $value) {

			$value = ( HMWP_Classes_Tools::$options[$key] ?: $value );
			$this->registerString( $key, $value );

		}

	}

	/**
	 * Register a string for translation with WPML or Polylang if respective plugins are enabled.
	 *
	 * @param string $key The unique identifier for the string.
	 * @param string $value The string value to register for translation.
	 *
	 * @return void
	 */
	public function registerString( $key, $value) {

		if ( $value === '' ) {
			return;
		}

		// WPML registration.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			do_action( 'wpml_register_single_string', 'hide-my-wp', $key, $value );
		}

		// Polylang registration (string translations module / Pro).
		if ( function_exists( 'pll_register_string' ) ) {
			pll_register_string( $key, $value, 'hide-my-wp' );
		}

	}

	/**
	 * Attach translation filters for each option key.
	 *
	 * IMPORTANT:
	 * Your option getter must call:
	 * apply_filters( 'hmwp_option_' . $key, $value, $key );
	 *
	 * @return void
	 */
	public function attachOptionFilters() {

		foreach ( $this->getStrings() as $key => $value ) {
			add_filter( 'hmwp_option_' . $key,
				function ( $option_value ) use ( $key ) {
					return $this->getOption( $key, $option_value );
				}, 10, 1
			);
		}
	}

	/**
	 * Retrieves an option value, potentially localized, based on the provided key and default value.
	 * This method integrates with various multilingual plugins to return translated values where available.
	 *
	 * @param string $key The key identifying the option to retrieve.
	 * @param string $option_value The default value to return if the key does not exist or is not translated.
	 *
	 * @return string The localized and/or translated value of the option, or the default value if no translation is available.
	 */
	public function getOption( $key, $option_value ) {

		// Get the current locale
		$locale = $this->getLocale();

		// Get the registered strings
		$strings = $this->getStrings();

		if ( $locale <> $this->default_locale ) {
			$rawStrings = $this->getRawStrings();
		} else {
			$rawStrings = $strings;
		}

		$value = ( $rawStrings[ $key ] ?? '' );

		// if we are in wp-admin,
		// return the default value if the option is not set (even as empty)
		if ( is_admin() ){
			return ( $option_value !== false ? $option_value : $value );
		}

		// WPML
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$translated = apply_filters( 'wpml_translate_single_string', $value, 'hide-my-wp', $key );
			if ( is_string( $translated ) && $translated !== '' && $translated !== $value ) {
				return $translated;
			}
		}

		// Polylang
		if ( function_exists( 'pll_translate_string' ) ) {
			$translated = pll_translate_string( $value, $locale );
			if ( is_string( $translated ) && $translated !== '' && $translated !== $value ) {
				return $translated;
			}
		} elseif ( function_exists( 'pll__' ) ) {
			$translated = pll__( $value );
			if ( is_string( $translated ) && $translated !== '' && $translated !== $value ) {
				return $translated;
			}
		}

		// returns the segment for the current language, falling back to the default.
		if ( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$translated = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
			if ( is_string( $translated ) && $translated !== '' && $translated !== $value ) {
				return $translated;
			}
		}

		// Any multilingual plugin not listed above can hook here to provide a translation
		// without needing changes to this class. Return a non-empty string to take over.
		$translated = apply_filters( 'hmwp_translate_string', $value, $key, $locale );
		if ( is_string( $translated ) && $translated !== '' && $translated !== $value ) {
			return $translated;
		}

		// Return the default value if no translation is available
		return ( $option_value !== false ? ( $option_value <> '' ? __( $option_value, 'hide-my-wp' ) : '' ) : ( $strings[ $key ] ?? '' ) ); //phpcs:ignore 	WordPress.WP.I18n.NonSingularStringLiteralText

	}

	/**
	 * Resolve the current locale in a login-aware way.
	 *
	 * @return string
	 */
	protected function getLocale() {
		if ( function_exists( 'determine_locale' ) ) {
			return (string) determine_locale();
		}

		return (string) get_locale();
	}

	/**
	 * Checks if a multilingual plugin is active on the site.
	 *
	 * @return bool True if a multilingual plugin is active, false otherwise.
	 */
	public function isMultilingualPluginActive() {
		return ( defined( 'ICL_SITEPRESS_VERSION' ) || function_exists( 'pll_register_string' ) ) || function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' );
	}
}