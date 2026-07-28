<?php
/**
 * Traffic Threat ListTable Model
 * Called to show the Traffic Threat Log
 *
 * @file  The ThreatsListTable file
 * @package HMWP/TrafficModel
 * @since 8.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HMWP_Models_ThreatsListTable extends WP_List_Table {

	/** @var string Parameter prefix */
	protected $prefix_arg = 'thr_';

	/** @var int Total rows found by the query */
	protected $total_found = 0;

	/** @var string Tab parameter name */
	protected $tabParam = 'threats';

	/** @var HMWP_Models_Firewall_Threats|null */
	protected $threatsModel = null;

	/** @var array */
	protected $threatTypes = array();

	/** @var array code => name pairs for the country filter dropdown */
	protected $countryOptions = array();


	/**
	 * Class constructor to initialize the table settings and traffic model.
	 *
	 * Sets up the singular and plural names for the table, enables/disables AJAX support,
	 * initializes the traffic model instance, and adds custom filters to the current screen's views.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => esc_html__( 'threat', 'hide-my-wp' ),
				'plural'   => esc_html__( 'threats', 'hide-my-wp' ),
				'ajax'     => false,
			)
		);

		$this->threatsModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$database->maybeCreateTable();

	}

	/**
	 * Forces the modification of the current request URI to ensure a specific tab parameter is present.
	 *
	 * This method retrieves the current request URI, removes any existing 'tab' query parameter,
	 * and appends the tab parameter defined in the class. It updates the `$_SERVER['REQUEST_URI']`
	 * with the modified URI and returns the original URI.
	 *
	 * @return string The original request URI before modification.
	 */
	protected function forceTabRequestUri() {
		$old = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$_SERVER['REQUEST_URI'] = add_query_arg(
			array( 'tab' => $this->tabParam ),
			remove_query_arg( array( 'tab' ), $old )
		);

		return $old;
	}

	/**
	 * Restores the previous value of the request URI in the server environment.
	 *
	 * This method checks if the provided old value is a non-empty string and updates
	 * the `REQUEST_URI` server variable to the specified value.
	 *
	 * @param string $old The previous value of the request URI.
	 *
	 * @return void
	 */
	protected function restoreTabRequestUri( $old ) {
		if ( is_string( $old ) && $old !== '' ) {
			$_SERVER['REQUEST_URI'] = $old;
		}
	}

	/**
	 * Renders the page table interface, including the table header, views, search box, and table content.
	 *
	 * @return void
	 */
	public function loadPageTable() {

		$oldUri = $this->forceTabRequestUri();

		$this->tableHead();
		$this->views();
		$this->prepare_items();

		$this->renderView();

		$page = HMWP_Classes_Tools::getValue( 'page', 'hmwp_log' );

		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="' . esc_attr( $page ) . '">';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $this->tabParam ) . '">';

		$this->display();

		echo '</form>';

		$this->restoreTabRequestUri( $oldUri );

	}

	/**
	 * Renders a CTA bar below the table when the view is limited (free version).
	 *
	 * @return void
	 */
	protected function renderView() {

		if ( ! isset( $this->total_found ) ) {
			return;
		}

		$shown = count( $this->items );
		$total = $this->total_found;

		// Only show CTA if there are more items than displayed
		if ( $total <= $shown || $shown === 0 ) {
			return;
		}

		// Check if a country filter is active for contextual message
		$country     = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'country', '', true );
		$countryName = '';

		if ( $country !== '' && ! empty( $this->countryOptions[ $country ] ) ) {
			$countryName = $this->countryOptions[ $country ];
		}

		echo '<div class="col-sm-12 p-3 m-0 bg-warning" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">';

		echo '<span style="font-size: 13px; color: #555;">';
		if ( $countryName !== '' ) {
			echo wp_kses_post( sprintf(
			/* translators: 1: Number of items shown, 2: Total items, 3: Country name. */
				__( 'Showing %1$s of %2$s threats from <strong>%3$s</strong>', 'hide-my-wp' ),
				'<strong>' . esc_html( number_format_i18n( $shown ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>',
				esc_html( $countryName )
			) );
		} else {
			echo wp_kses_post( sprintf(
			/* translators: 1: Number of items shown, 2: Total items. */
				__( 'Showing last %1$s of %2$s threats', 'hide-my-wp' ),
				'<strong>' . esc_html( number_format_i18n( $shown ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>'
			) );
		}
		echo '</span>';

		echo '<a href="#" onclick="jQuery(\'#hmwp_ghost_mode_modal\').modal(\'show\'); return false;" class="text-danger font-weight-bold" >';
		echo esc_html__( 'Unlock full log with filters, search, and export', 'hide-my-wp' );
		echo ' &gt;</a>';

		echo '</div>';

	}

	/**
	 * Outputs custom styles to modify the appearance and layout of table columns in the WordPress admin list table.
	 *
	 * @return void
	 */
	public function tableHead() {
		echo '<style>';
		echo '.wp-list-table{ border-color:#eee !important; }';
		echo '.wp-list-table .column-threat{ width:22%; }';
		echo '.wp-list-table .column-ip{ width:22%; }';
		echo '.wp-list-table .column-details{ width:30%; }';
		echo '.wp-list-table .column-status{ width:12%; }';
		echo '.wp-list-table .column-datetime{ width:14%; }';
		echo '.wp-list-table .column-actions{ width:4%; }';
		echo '</style>';
	}

	/**
	 * Displays a message when no items are available in the table.
	 *
	 * @return void Outputs a localized string indicating no items were found.
	 */
	public function no_items() {
		echo esc_html__( 'No threats found.', 'hide-my-wp' );
	}

	/* ----------------------------
	 * WP_List_Table required methods
	 * ---------------------------- */

	/**
	 * Retrieves the list of columns for a table display.
	 *
	 * @return array An associative array where keys represent column identifiers and
	 *               values are the column titles.
	 */
	public function get_columns() {
		return array(
			'threat'   => esc_html__( 'Threat Type', 'hide-my-wp' ),
			'ip'       => esc_html__( 'Location', 'hide-my-wp' ),
			'details'  => esc_html__( 'Details', 'hide-my-wp' ),
			'status'   => esc_html__( 'Status', 'hide-my-wp' ),
			'datetime' => esc_html__( 'Date', 'hide-my-wp' ),
			'actions'  => '',
		);
	}

	/**
	 * Retrieves the list of sortable columns for a table.
	 *
	 * @return array An associative array where the key is the column identifier
	 *               and the value is an array containing the sorting field and
	 *               a boolean indicating the default sort order.
	 */
	public function get_sortable_columns() {
		return array(
			'threat'   => array( 'event', false ),
			'ip'       => array( 'ip', false ),
			'status'   => array( 'http_code', true ),
			'datetime' => array( 'stamp', true ),
		);
	}

	/**
	 * Prints the column headers for the table while modifying query parameters to include a custom prefix.
	 *
	 * Adjusts the request parameters for sorting, generates the column headers,
	 * and restores the original state after processing. Also replaces standard
	 * query parameters with their prefixed equivalents in the generated output.
	 *
	 * @param bool $with_id Optional. Whether to include an ID attribute in the column headers. Default is true.
	 *
	 * @return void
	 */
	public function print_column_headers( $with_id = true ) {
		$prefix = $this->prefix_arg;

		// Save original state
		$saved_uri     = $_SERVER['REQUEST_URI']; //phpcs:ignore
		$saved_orderby = HMWP_Classes_Tools::getValue( 'orderby' );
		$saved_order   = HMWP_Classes_Tools::getValue( 'order' );

		// Strip prefix from REQUEST_URI so WP's add_query_arg replaces (not appends) the sort params
		$_SERVER['REQUEST_URI'] = str_replace(
			array( $prefix . 'orderby=', $prefix . 'order=' ),
			array( 'orderby=', 'order=' ),
			$_SERVER['REQUEST_URI'] //phpcs:ignore
		);

		// Set standard $_GET params so WP knows the current sort state (arrow + toggle direction)
		if ( HMWP_Classes_Tools::getIsset( $prefix . 'orderby' ) ) {
			$_GET['orderby'] = HMWP_Classes_Tools::getValue( $prefix . 'orderby' );
		}
		if ( HMWP_Classes_Tools::getIsset( $prefix . 'order' ) ) {
			$_GET['order'] = HMWP_Classes_Tools::getValue( $prefix . 'order' );
		}

		ob_start();
		parent::print_column_headers( $with_id );
		$output = ob_get_clean();

		// Restore
		$_SERVER['REQUEST_URI'] = $saved_uri;
		if ( ! $saved_orderby ) {
			unset( $_GET['orderby'] );
		} else {
			$_GET['orderby'] = $saved_orderby;
		}
		if ( ! $saved_order ) {
			unset( $_GET['order'] );
		} else {
			$_GET['order'] = $saved_order;
		}

		// Rename standard params to prefixed ones in the generated links.
		$output = str_replace( '&#038;orderby=', '&#038;' . $this->prefix_arg . 'orderby=', $output );
		$output = str_replace( '&#038;order=', '&#038;' . $this->prefix_arg . 'order=', $output );

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Outputs or processes the additional table navigation elements for the 'top' section.
	 *
	 * @param string $which The location of the table navigation being rendered, e.g., 'top' or 'bottom'.
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( $which === 'top' ) {
			$dropdown = $this->actionsDropdown();

			if ( ! empty( $dropdown ) ) {
				echo $dropdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				submit_button( esc_html__( 'Filter' ), '', 'filter_action', false, array( 'id' => 'trafficlog-submit' ) ); //phpcs:ignore
			}

			return;
		}

		if ( $which !== 'bottom' ) {
			return;
		}

		// Modal shell + JS (printed once)
		echo $this->renderThreatDetailsModal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '<button type="button" onclick="jQuery(\'#hmwp_ghost_mode_modal\').modal(\'show\')" class="btn btn-success btn-sm">' . esc_html__( 'Export CSV', 'hide-my-wp' ) . '</button>';
	}

	/**
	 * Retrieves the display value for a specific column in a table row.
	 *
	 * @param array $item The data item (row) being processed.
	 * @param string $column_name The name of the column being rendered.
	 *
	 * @return string The content to be displayed for the specified column and item.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'datetime':
				$timestamp = isset( $item['stamp'] ) ? (int) $item['stamp'] : 0;
				if ( $timestamp <= 0 ) {
					return '';
				}

				// Local time formatting
				return esc_html( $this->timeElapsed( $timestamp ) );

			default:
				return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
		}
	}

	/**
	 * Generates the display value for the "threat" column in a table row.
	 *
	 * @param array $item The data item (row) containing information about the threat.
	 *
	 * @return string The formatted content to be displayed for the "threat" column.
	 */
	public function column_threat( $item ) {
		$title = isset( $item['threat'] ) ? (string) $item['threat'] : '';

		return '<strong>' . esc_html( $title ) . '</strong>';
	}

	/**
	 * Retrieves the formatted display value for the 'IP' column in a table row.
	 *
	 * @param array $item The data item (row) being processed, containing 'ip' and 'user_id' keys.
	 *
	 * @return string The formatted content to be displayed for the 'IP' column, including an IP address
	 *                and optionally the User ID if available.
	 */
	public function column_ip( $item ) {
		$out    = '';
		$ip     = isset( $item['ip'] ) ? (string) $item['ip'] : '';
		$userId = isset( $item['user_id'] ) ? (int) $item['user_id'] : 0;

		$stored_cc = isset( $item['country_code'] ) ? (string) $item['country_code'] : '';

		/** @var HMWP_Models_Geoip_GeoLocator $geo_locator */
		$geo_locator = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getInstance();
		$iso_code    = ( $stored_cc !== '' && $stored_cc !== '--' ) ? $stored_cc : $geo_locator->getCountryCode( $ip );
		$countries   = $geo_locator->getCountryCodes();

		if ( isset( $countries[ $iso_code ] ) ) {
			$flag = '<img src="' . esc_url( _HMWP_ASSETS_URL_ . 'flags/' . strtolower( $iso_code ) . '.png' ) . '" title="' . $countries[ $iso_code ] . '" style="width: 16px; height: auto;" >';

			$out .= esc_html__( 'Country' ) . ': <strong>' . $flag . ' ' . $countries[ $iso_code ] . '</strong>'; //phpcs:ignore
			$out .= '<br />';
			if ( $userId > 0 ) {
				$user = get_userdata( $userId );
				$out  .= '<br><span style="opacity:.85;">' . esc_html__( 'User', 'hide-my-wp' ) . ': ' . esc_html( (string) $user->display_name ) . '</span>';
			}
		}

		$out .= 'IP: <strong>' . $ip . '</strong>';

		return $out;
	}


	/**
	 * Generates the HTML content for the status column based on the item data.
	 *
	 * @param array $item The data item (row) being processed, including HTTP code and blocked status.
	 *
	 * @return string The formatted HTML string representing the status, including HTTP code and block status.
	 */
	public function column_status( $item ) {

		$prevented = ( isset( $item['blocked'] ) ? (int) $item['blocked'] : 0 );

		$label = $prevented ? esc_html__( 'Prevented', 'hide-my-wp' ) : '';
		$icon  = $prevented ? 'yes-alt' : '';
		$class = $prevented ? 'hmwp-status hmwp-status--blocked' : '';

		// Optionally keep the real code as a tooltip
		$title = $prevented ? esc_html__( 'The hack was prevented by the firewall', 'hide-my-wp' ) : '';

		return sprintf(
			'<span class="%s" title="%s"><span class="dashicons dashicons-%s"></span> %s</span>',
			esc_attr( $class ),
			esc_attr( $title ),
			esc_attr( $icon ),
			esc_html( $label )
		);
	}

	/**
	 * Generates a formatted details box from the provided item data, including computed metadata, request details,
	 * and request fields.
	 *
	 * @param array $item The data item containing details such as threat metadata, request details, and request fields.
	 *
	 * @return string A preformatted HTML string representing the details box, or an empty string if no details are available.
	 */
	public function column_details( $item ) {

		// Build a readable details box from stored JSON
		$lines = array();

		// Request details JSON (ua/referer/host/proto, etc.)
		$details = isset( $item['request_details_arr'] ) && is_array( $item['request_details_arr'] )
			? $item['request_details_arr']
			: array();

		if ( ! empty( $item['uri'] ) && ! empty( $item['request_method'] ) ) {
			$uri    = isset( $item['uri'] ) ? (string) $item['uri'] : '';
			$method = isset( $item['request_method'] ) ? (string) $item['request_method'] : '';

			$lines[] = esc_html__( 'Path', 'hide-my-wp' ) . ': ' . esc_html( $this->capString( wp_parse_url( $uri, PHP_URL_PATH ), 32 ) );
			$lines[] = esc_html__( 'Method', 'hide-my-wp' ) . ': ' . esc_html( $method );
		}

		if ( ! empty( $details['referer'] ) ) {
			if ( wp_parse_url( (string) $details['referer'], PHP_URL_HOST ) !== null ) {
				if ( wp_parse_url( (string) $details['referer'], PHP_URL_HOST ) !== wp_parse_url( (string) home_url(), PHP_URL_HOST ) ) {
					$lines[] = esc_html__( 'Referer', 'hide-my-wp' ) . ': ' . (string) $details['referer'];
				} else {
					$lines[] = esc_html__( 'Referer', 'hide-my-wp' ) . ': ' . (string) wp_parse_url( (string) $details['referer'], PHP_URL_PATH );
				}
			}
		}

		// Request fields (GET/POST merged & already redacted in your logger)
		$fields = isset( $item['request_fields_arr'] ) && is_array( $item['request_fields_arr'] )
			? $item['request_fields_arr']
			: array();

		if ( ! empty( $fields ) ) {
			// show up to 10 key/value pairs to keep the UI readable
			$shown = 0;
			foreach ( $fields as $k => $v ) {
				if ( $shown >= 10 ) {
					$lines[] = '...';
					break;
				}
				$key = sanitize_key( (string) $k );

				if ( ! in_array( $key, array( 'nonce', 'hmwp_nonce', 'pwd', 'brute_num', 'brute_ck', 'g-recaptcha-response', 'redirect_to', 'ssid', 'testcookie' ) ) ) {
					$val     = is_array( $v ) ? '[array]' : (string) $v;
					$lines[] = esc_html__( 'Param', 'hide-my-wp' ) . ': ' . $key . '=' . $this->capString( $val, 120 );
					$shown ++;
				}

			}
		}

		if ( empty( $lines ) ) {
			return '';
		}

		// Escape line-by-line
		$safe = '';
		foreach ( $lines as $line ) {
			$safe .= esc_html( $this->capString( $line, 300 ) ) . "\n";
		}

		return '<pre style="max-width:520px; white-space:pre-wrap; margin:0;">' . $safe . '</pre>';
	}

	/**
	 * Render the 3-dots actions column.
	 *
	 * @param array $item
	 *
	 * @return string
	 * @throws Exception
	 */
	public function column_actions( $item ) {
		$row_id = $this->getRowUid( $item );
		$payload = $this->buildThreatModalPayload( $item );

		// Whitelist URL
		$whitelist_url = '';
		if ( $path = $this->getPath( $item ) ) {
			$whitelist_url = add_query_arg(
				array(
					'action'     => 'hmwp_firewall_whitelist_path',
					'path'       => rawurlencode( $path ),
					'hmwp_nonce' => wp_create_nonce( 'hmwp_firewall_whitelist_path' ),
				)
			);
		}

		// Whitelist Rule Code
		$whitelist_rule = '';
		$details = isset( $item['request_details_arr'] ) && is_array( $item['request_details_arr'] )
			? $item['request_details_arr']
			: array();

		if ( isset( $details['code'] ) && $details['code'] <> '' ) {
			$whitelist_rule = add_query_arg(
				array(
					'action'     => 'hmwp_firewall_whitelist_rule',
					'rule'       => $details['code'],
					'hmwp_nonce' => wp_create_nonce( 'hmwp_firewall_whitelist_rule' ),
				)
			);
		}
		if ( isset( $item['ip'] ) && $item['ip'] <> '' ) {
			$blacklist_ip = add_query_arg(
				array(
					'action'     => 'hmwp_firewall_blacklist_ip',
					'ip'       => $item['ip'],
					'hmwp_nonce' => wp_create_nonce( 'hmwp_firewall_blacklist_ip' ),
				)
			);
		}

		$out = '';

		// JSON container for this row (read by JS on click)
		$out .= '<script type="application/json" id="hmwp_threat_json_' . esc_attr( $row_id ) . '">';
		$out .= wp_json_encode( $payload );
		$out .= '</script>';

		$out .= '<div class="dropdown hmwp-threat-actions" style="text-align:right;">';
		$out .= '  <button class="btn btn-link p-0 m-0" type="button" id="hmwp_threat_menu_' . esc_attr( $row_id ) . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="' . esc_attr__( 'Actions', 'hide-my-wp' ) . '">';
		$out .= '    <span class="fa fa-ellipsis-v"></span>';
		$out .= '  </button>';
		$out .= '  <div class="dropdown-menu dropdown-menu-right p-0 m-0" aria-labelledby="hmwp_threat_menu_' . esc_attr( $row_id ) . '">';
		$out .= '    <a class="dropdown-item hmwp-threat-details-link py-2" style="font-size: 0.87rem;" href="#" data-json="hmwp_threat_json_' . esc_attr( $row_id ) . '">' . esc_html__( 'Threat Details', 'hide-my-wp' ) . '</a>';

		if ( $whitelist_url !== '' ) {
			$out .= '  <a class="dropdown-item border-top py-2" style="font-size: 0.87rem;" href="' . esc_url( $whitelist_url ) . '">' . esc_html__( 'Whitelist Path', 'hide-my-wp' ) . '</a>';
		}
		if ( $whitelist_rule !== '' ) {
			$out .= '  <a class="dropdown-item border-top py-2" style="font-size: 0.87rem;" href="' . esc_url( $whitelist_rule ) . '">' . esc_html__( 'Whitelist Rule', 'hide-my-wp' ) . '</a>';
		}
		if ( $blacklist_ip !== '' ) {
			$out .= '  <a class="dropdown-item border-top py-2" style="font-size: 0.87rem;" href="' . esc_url( $blacklist_ip ) . '">' . esc_html__( 'Blacklist IP', 'hide-my-wp' ) . '</a>';
		}

		// Extension point for other actions
		$out .= (string) apply_filters( 'hmwp_threat_row_actions_html', '', $item );

		$out .= '  </div>';
		$out .= '</div>';

		return $out;
	}

	/* ----------------------------
	 * Data loading (DB-backed)
	 * ---------------------------- */

	/**
	 * Prepares the items and configuration for display in the list table.
	 *
	 * This method handles pagination, column headers, and retrieves the data required
	 * for rendering the table. It also configures additional settings such as
	 * dropdown options for filtering.
	 *
	 * @return void
	 */
	public function prepare_items() {

		// Per page
		$perPage = (int) get_option( 'posts_per_page' );
		if ( $perPage <= 0 ) {
			$perPage = 20;
		}

		$currentPage = $this->get_pagenum();
		if ( $currentPage <= 0 ) {
			$currentPage = 1;
		}

		// Headers
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Load threat types for dropdown (cached)
		$this->threatTypes = $this->getThreatTypeOptions();

		// Load country options for dropdown
		$this->countryOptions = $this->getCountryOptions();

		// Query rows
		$result = $this->queryThreatRows( $perPage, $currentPage );

		$this->items        = $result['items'] ?? array();
		$this->total_found = isset( $result['total'] ) ? (int) $result['total'] : count( $result['items'] );

	}

	/**
	 * Queries and retrieves threat row data from the database based on filters, pagination, and sorting parameters.
	 *
	 * @param int $perPage The number of rows to retrieve per page.
	 * @param int $currentPage The current page number for pagination.
	 *
	 * @return array An associative array with the following structure:
	 *               - 'total' (int): The total number of rows matching the query.
	 *               - 'items' (array): The list of threat rows, with each row represented as an associative array.
	 */
	protected function queryThreatRows( $perPage, $currentPage ) {
		global $wpdb;

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$database->maybeCreateTable();
		$table = $database->tableName();

		$whereParts = array();
		$params     = array();

		$whereParts[] = '1=1';

		// Only not logged-in user events
		$whereParts[] = 'user_id=0';
		$whereParts[] = 'is_bot=0';

		// Blog filter when multisite is enabled
		if ( HMWP_Classes_Tools::isMultisites() ) {
			if ( $blog = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'blog_id' ) ) {
				$whereParts[] = 'blog_id = %d';
				$params[]     = (int) $blog;
			}
		}

		// Filter: blocked
		$blocked = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'blocked' );
		if ( $blocked <> '' ) {
			$whereParts[] = 'blocked = %d';
			$params[] = (int) $blocked;
		}

		// Filter: country
		$country = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'country', '', true );
		if ( $country !== '' ) {
			$whereParts[] = 'country_code = %s';
			$params[]     = $country;
		}

		// Filter: date range in days
		$range = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'range' ); // 1,7,14,21,30,all
		if ( $range <> '' ) {
			$days = (int) $range;
			if ( $days > 0 ) {
				$minStamp     = strtotime( wp_date( 'Y-m-d', strtotime( '- ' . ($days - 1) . ' days' ) ) );
				$whereParts[] = 'stamp >= %d';
				$params[]     = (int) $minStamp;
			}
		}

		// Search (WP uses "s")
		$search = HMWP_Classes_Tools::getValue( 's', '', true );
		if ( $search <> '' ) {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$whereParts[] = '(ip LIKE %s OR uri LIKE %s OR event LIKE %s OR request_id LIKE %s OR JSON_UNQUOTE(JSON_EXTRACT(request_details, \'$.code\')) LIKE %s)';
			$params[]     = $like;
			$params[]     = $like;
			$params[]     = $like;
			$params[]     = $like;
			$params[]     = $like;
		}

		$whereSql = implode( ' AND ', $whereParts );

		// Sorting (whitelist)
		$orderBy = $this->getOrderBySql();
		$order   = $this->getOrderSql();

		// Threat filter (computed) – cannot be done reliably in SQL
		$threatFilter = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'threat', false, true );

		// If a threat type filter is active, we use a bounded buffer and filter in PHP.
		// This keeps correctness while avoiding full-table scans.
		if ( $threatFilter <> '' ) {
			$buffer = (int) apply_filters( 'hmwp_threats_filter_buffer', 5000 );
			if ( $buffer < 500 ) {
				$buffer = 500;
			}

			$sql = "SELECT log_id, request_id, stamp, ip, country_code, user_id, uri, event, request_method, http_code, request_fields, request_details, blog_id, blocked
			        FROM {$table}
			        WHERE {$whereSql}
			        ORDER BY {$orderBy} {$order}
			        LIMIT %d";

			$rows     = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, array( $buffer ) ) ), ARRAY_A ); //phpcs:ignore

			$items = $this->decorateRowsWithThreat( $rows );

			$items = array_values(
				array_filter(
					$items,
					function ( $it ) use ( $threatFilter ) {
						return isset( $it['threat'] ) && (string) $it['threat'] === (string) $threatFilter;
					}
				)
			);

			$total = count( $items );

			$offset = ( ( $currentPage - 1 ) * $perPage );
			$items  = array_slice( $items, $offset, $perPage );

			return array( 'total' => $total, 'items' => $items );
		}

		$countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
		if ( ! empty( $params ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $countSql, $params ) ); //phpcs:ignore
		} else {
			$total = (int) $wpdb->get_var( $countSql ); //phpcs:ignore
		}

		$offset = ( ( $currentPage - 1 ) * $perPage );

		$listSql = "SELECT log_id, request_id, stamp, ip, country_code, user_id, uri, event, request_method, http_code, request_fields, request_details, blog_id, blocked
		            FROM {$table}
		            WHERE {$whereSql}
		            ORDER BY {$orderBy} {$order}
		            LIMIT %d OFFSET %d";

		$rows     = $wpdb->get_results( $wpdb->prepare( $listSql, array_merge( $params, array( (int) $perPage, (int) $offset ) ) ), ARRAY_A ); //phpcs:ignore
		$items = $this->decorateRowsWithThreat( $rows );

		return array( 'total' => $total, 'items' => $items );
	}

	/**
	 * Processes rows by analyzing them for potential threats, enriching each row with threat-related information.
	 *
	 * @param array $rows The input array containing rows of data to be analyzed and decorated with threat information.
	 *
	 * @return array The modified array of rows, each enhanced with additional threat-related properties and metadata.
	 */
	protected function decorateRowsWithThreat( $rows ) {
		$items = array();

		foreach ( (array) $rows as $row ) {

			$requestFieldsArr  = $this->safeJsonDecode( isset( $row['request_fields'] ) ? (string) $row['request_fields'] : '' );
			$requestDetailsArr = $this->safeJsonDecode( isset( $row['request_details'] ) ? (string) $row['request_details'] : '' );
			$threat            = ( isset( $row['event'] ) && $row['event'] <> '' ) ? (string) $row['event'] : esc_html__( 'Threat detected', 'hide-my-wp' );

			$row['threat']              = $threat;
			$row['request_fields_arr']  = $requestFieldsArr;
			$row['request_details_arr'] = $requestDetailsArr;

			$items[] = $row;
		}

		return $items;
	}

	/**
	 * Generates the SQL column name for ordering results based on a given request parameter.
	 *
	 * Maps an orderby request parameter to a corresponding database column name.
	 * If the parameter is not specified or invalid, defaults to a pre-defined column.
	 *
	 * @return string The database column name to use for ordering results.
	 */
	protected function getOrderBySql() {
		$orderby = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'orderby', 'stamp' );

		$map = array(
			'stamp'          => 'stamp',
			'datetime'       => 'stamp',
			'ip'             => 'ip',
			'http_code'      => 'http_code',
			'status'         => 'http_code',
			'request_method' => 'request_method',
			'threat'         => 'event',
		);

		return isset( $map[ $orderby ] ) ? $map[ $orderby ] : 'stamp';
	}

	/**
	 * Generates the SQL order direction based on the request parameter.
	 *
	 * @return string The SQL order direction, either 'ASC' or 'DESC'.
	 */
	protected function getOrderSql() {
		$order = strtolower(HMWP_Classes_Tools::getValue( $this->prefix_arg . 'order', 'desc' ));
		return ( $order === 'asc' ) ? 'ASC' : 'DESC';
	}

	/**
	 * Query the most recent threat records to build a unique, sorted list of event/type values
	 * for use in the filter drop-down.
	 *
	 * The sample size is capped at the value of the `hmwp_threats_type_sample` filter (minimum 200).
	 *
	 * @return string[] Sorted array of unique threat-type (event) strings.
	 */
	protected function getThreatTypeOptions() {
		global $wpdb;

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$table    = $database->tableName();

		// Look at a bounded set of recent records to build options
		$limit = (int) apply_filters( 'hmwp_threats_type_sample', 800 );
		if ( $limit < 200 ) {
			$limit = 200;
		}

		$sql = "SELECT event
		        FROM {$table}
		        WHERE user_id=0
		        ORDER BY stamp DESC
		        LIMIT %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array( $limit ) ), ARRAY_A ); //phpcs:ignore

		$types = array_column( $rows, 'event' );
		$types = array_values( array_unique( array_filter( $types ) ) );
		sort( $types );

		return $types;
	}

	/**
	 * Queries distinct country codes present in threat rows and maps them to country names
	 * for use in the country filter dropdown.
	 *
	 * @return array Associative array of [ code => name ] sorted by name.
	 */
	protected function getCountryOptions() {
		global $wpdb;

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$table    = $database->tableName();

		$sql  = "SELECT DISTINCT country_code FROM {$table} WHERE user_id = 0 AND country_code IS NOT NULL AND country_code != '' AND country_code != '--' ORDER BY country_code";
		$rows = $wpdb->get_col( $sql ); //phpcs:ignore

		if ( empty( $rows ) ) {
			return array();
		}

		/** @var HMWP_Models_Geoip_GeoLocator $geo */
		$geo       = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getInstance();
		$allNames  = ( $geo && method_exists( $geo, 'getCountryCodes' ) ) ? $geo->getCountryCodes() : array();

		$options = array();
		foreach ( $rows as $code ) {
			$code            = (string) $code;
			$options[ $code ] = isset( $allNames[ $code ] ) ? $allNames[ $code ] : $code;
		}

		asort( $options );

		return $options;
	}


	/**
	 * One reusable modal (Bootstrap) for all rows.
	 *
	 * @return string
	 */
	protected function renderThreatDetailsModal() {
		$out = '';
		$out .= '<div id="hmwp_threat_modal" class="modal" tabindex="-1" role="dialog" aria-hidden="true">';
		$out .= '  <div class="modal-dialog modal-lg" role="document">';
		$out .= '    <div class="modal-content">';
		$out .= '      <div class="modal-header">';
		$out .= '        <h5 class="modal-title" data-hmwp="title"></h5>';
		$out .= '        <button type="button" class="close" data-dismiss="modal" aria-label="' . esc_attr__( 'Close', 'hide-my-wp' ) . '">';
		$out .= '          <span aria-hidden="true">&times;</span>';
		$out .= '        </button>';
		$out .= '      </div>';
		$out .= '      <div class="modal-body">';

		$out .= '        <div class="table-responsive">';
		$out .= '          <table class="table table-sm table-bordered mb-3">';
		$out .= '            <tbody id="hmwp_threat_modal_summary"></tbody>';
		$out .= '          </table>';
		$out .= '        </div>';

		$out .= '        <h6 class="mb-2 text-black-50">' . esc_html__( 'Detection details', 'hide-my-wp' ) . '</h6>';
		$out .= '        <div class="table-responsive">';
		$out .= '          <table class="table table-sm table-bordered mb-3">';
		$out .= '            <tbody id="hmwp_threat_modal_details"></tbody>';
		$out .= '          </table>';
		$out .= '        </div>';

		$out .= '        <h6 class="mb-2 text-black-50">' . esc_html__( 'Request fields', 'hide-my-wp' ) . '</h6>';
		$out .= '        <div class="table-responsive">';
		$out .= '          <table class="table table-sm table-bordered mb-0">';
		$out .= '            <tbody id="hmwp_threat_modal_fields"></tbody>';
		$out .= '          </table>';
		$out .= '        </div>';

		$out .= '      </div>';
		$out .= '    </div>';
		$out .= '  </div>';
		$out .= '</div>';

		// JS: open modal and populate
		$out .= '<script>
	(function($){
		function escHtml(s){
			s = (s === null || s === undefined) ? "" : String(s);
			return s.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/\\\'/g,"&#039;");
		}

		function row(k,v){
			return "<tr><th style=\\"width:180px\\">"+escHtml(k)+"</th><td style=\\"word-break: break-word;\\">"+escHtml(v)+"</td></tr>";
		}

		function objectToRows(obj){
			var html = "";
			if (!obj || typeof obj !== "object") return html;
			Object.keys(obj).forEach(function(key){
				html += row(key, obj[key]);
			});
			return html;
		}

		$(document).on("click", ".hmwp-threat-details-link", function(e){
			e.preventDefault();

			var jsonId = $(this).data("json");
			if (!jsonId) return;

			var el = document.getElementById(jsonId);
			if (!el) return;

			var payload = {};
			try { payload = JSON.parse(el.textContent || "{}"); } catch(err) { payload = {}; }

			var $m = $("#hmwp_threat_modal");

			$m.find("[data-hmwp=\\"title\\"]").text(payload.category || "Threat details");

			$("#hmwp_threat_modal_summary").html(
				row("Request", payload.request || "") +
				row("IP", payload.ip || "") +
				row("HTTP status", payload.http_code || "") +
				row("Request ID", payload.request_id || "") +
				row("Bot", payload.is_bot ? "Yes" : "No")
			);

			$("#hmwp_threat_modal_details").html(
				row("Code", payload.code || "") +
				row("Area", payload.area_human || payload.area || "") +
				row("Matched pattern", payload.pattern || "") +
				row("User agent", payload.ua || "") +
				row("Referer", payload.referer || "") +
				row("Protocol", payload.proto || "")
			);

			var fields = payload.request_fields || {};
			$("#hmwp_threat_modal_fields").html( objectToRows(fields) || row("Fields", "No request fields captured.") );

			$m.modal("show");
		});
	})(jQuery);
	</script>';

		return $out;
	}

	/**
	 * Build the modal payload for a row, kept compact to avoid huge HTML.
	 *
	 * @param array $item
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function buildThreatModalPayload( $item ) {
		$details = $this->safeJsonDecodeAssoc( $item['request_details'] ?? '' );
		$fields  = $this->safeJsonDecodeAssoc( $item['request_fields'] ?? '' );
		$fields  = $this->capAssocDeep( $fields, 40, 300 );

		$method   = isset( $item['request_method'] ) ? (string) $item['request_method'] : '';
		$uri      = isset( $item['uri'] ) ? (string) $item['uri'] : '';
		$httpCode = isset( $item['http_code'] ) ? (int) $item['http_code'] : 0;
		$code     = isset( $details['code'] ) ? (string) $details['code'] : 0;
		$category = $this->getCategoryFromCode( $code );

		$request_line = trim( $method . ' ' . $uri );

		return array(
			'category'       => (string) $category,
			'code'           => (string) $code,
			'area'           => isset( $details['area'] ) ? (string) $details['area'] : '',
			'area_human'     => isset( $details['area'] ) ? $this->humanArea( (string) $details['area'] ) : '',
			'pattern'        => isset( $details['pattern'] ) ? (string) $details['pattern'] : '',
			'ua'             => isset( $details['ua'] ) ? (string) $details['ua'] : '',
			'referer'        => isset( $details['referer'] ) ? (string) $details['referer'] : '',
			'proto'          => isset( $details['proto'] ) ? (string) $details['proto'] : '',
			'ip'             => isset( $item['ip'] ) ? (string) $item['ip'] : '',
			'http_code'      => $httpCode,
			'request_id'     => isset( $item['request_id'] ) ? (string) $item['request_id'] : '',
			'is_bot'         => ! empty( $item['is_bot'] ) ? 1 : 0,
			'request'        => $request_line,
			'path'           => (string) $uri,
			'request_fields' => (array) $fields,
		);
	}

	/**
	 * Safely decode a raw value into an associative array.
	 *
	 * Accepts an already-decoded array, a JSON string, or returns an empty array for any
	 * other input type or malformed JSON.
	 *
	 * @param mixed $raw A JSON string or an array.
	 *
	 * @return array The decoded associative array, or an empty array on failure.
	 */
	protected function safeJsonDecodeAssoc( $raw ) {
		if ( is_array( $raw ) ) {
			return $raw;
		}
		if ( ! is_string( $raw ) || $raw === '' ) {
			return array();
		}

		$decoded = json_decode( $raw, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Cap keys + string lengths for modal payload safety.
	 *
	 * @param array $data
	 * @param int $maxKeys
	 * @param int $maxLen
	 *
	 * @return array
	 */
	protected function capAssocDeep( $data, $maxKeys, $maxLen ) {
		$out = array();
		$i   = 0;

		foreach ( (array) $data as $k => $v ) {
			$i ++;
			if ( $i > (int) $maxKeys ) {
				break;
			}

			$k = (string) $k;

			if ( is_array( $v ) ) {
				$out[ $k ] = wp_json_encode( $v );
				continue;
			}

			if ( is_bool( $v ) ) {
				$out[ $k ] = $v ? 'true' : 'false';
				continue;
			}

			$out[ $k ] = $this->capString( (string) $v, (int) $maxLen );
		}

		return $out;
	}

	/**
	 * Stable unique ID for a row.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	protected function getRowUid( $item ) {
		if ( isset( $item['log_id'] ) ) {
			return (string) absint( $item['log_id'] );
		}

		return '';
	}

	/**
	 * Extract a usable URL path from a stored URI (works with absolute URLs or request URIs).
	 *
	 * @param string $uri
	 *
	 * @return string
	 */
	protected function extractPathFromUri( $uri ) {
		$uri = trim( (string) $uri );
		if ( $uri === '' ) {
			return '';
		}

		// Absolute URL
		if ( strpos( $uri, 'http://' ) === 0 || strpos( $uri, 'https://' ) === 0 ) {
			$p = wp_parse_url( $uri );
			if ( is_array( $p ) && ! empty( $p['path'] ) ) {
				return (string) $p['path'];
			}

			return '';
		}

		// Request URI, keep only path
		$qpos = strpos( $uri, '?' );
		if ( $qpos !== false ) {
			$uri = substr( $uri, 0, $qpos );
		}

		// Ensure it starts with /
		if ( $uri !== '' && $uri[0] !== '/' ) {
			$uri = '/' . $uri;
		}

		return $uri;
	}

	/**
	 * Proxy to the Threats model categorizer (avoids duplicating mapping logic here).
	 *
	 * @param string $code
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function getCategoryFromCode( $code ) {
		/** @var HMWP_Models_ThreatsLog $threats */
		$threats = HMWP_Classes_ObjController::getClass( 'HMWP_Models_ThreatsLog' );

		return (string) $threats->getCategoryFromCode( $code );
	}

	/* ----------------------------
	 * Helpers
	 * ---------------------------- */

	/**
	 * Safely decodes a JSON string into an associative array.
	 *
	 * @param string $json The JSON string to decode.
	 *
	 * @return array The decoded associative array, or an empty array if the JSON is invalid or not a string.
	 */
	protected function safeJsonDecode( $json ) {
		if ( ! is_string( $json ) || $json === '' ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Truncates a string to a specified maximum length if it exceeds the limit.
	 *
	 * @param string $str The input string to be truncated.
	 * @param int $max The maximum allowed length of the string.
	 *
	 * @return string The original string if its length is within the limit, or the truncated string if it exceeds the limit.
	 */
	protected function capString( $str, $max ) {
		$str = (string) $str;

		return ( strlen( $str ) <= $max ) ? $str : substr( $str, 0, $max );
	}

	/**
	 * Get the redable time elapsed string
	 *
	 * @param int $time
	 *
	 * @return string
	 *
	 */
	public function timeElapsed( $time ) {

		if ( is_numeric( $time ) ) {
			$etime = $this->gtmTimestamp() - $time;
			if ( $etime >= 1 ) {
				$a = [
					365 * 24 * 60 * 60 => 'year',
					30 * 24 * 60 * 60  => 'month',
					24 * 60 * 60       => 'day',
					60 * 60            => 'hour',
					60                 => 'minute',
					1                  => 'second',
				];

				foreach ( $a as $secs => $str ) {
					$d = $etime / $secs;

					if ( $d >= 1 ) {
						$r = round( $d );

						$time_string = '';

						// Use _n() for proper singular/plural translation
						switch ( $str ) {
							case 'year':
								/* translators: %d: Number of years. */
								$time_string = _n( '%d year ago', '%d years ago', $r, 'hide-my-wp' );
								break;
							case 'month':
								/* translators: %d: Number of months. */
								$time_string = _n( '%d month ago', '%d months ago', $r, 'hide-my-wp' );
								break;
							case 'day':
								/* translators: %d: Number of days. */
								$time_string = _n( '%d day ago', '%d days ago', $r, 'hide-my-wp' );
								break;
							case 'hour':
								/* translators: %d: Number of hours. */
								$time_string = _n( '%d hour ago', '%d hours ago', $r, 'hide-my-wp' );
								break;
							case 'minute':
								/* translators: %d: Number of minute. */
								$time_string = _n( '%d minute ago', '%d minutes ago', $r, 'hide-my-wp' );
								break;
							case 'second':
								/* translators: %d: Number of second. */
								$time_string = _n( '%d second ago', '%d seconds ago', $r, 'hide-my-wp' );
								break;
						}

						return esc_html( sprintf( $time_string, (int) $r ) );
					}
				}
			}
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time );
	}

	/**
	 * Get current GMT date time
	 *
	 * @return false|int
	 * @since 7.0
	 *
	 */
	public function gtmTimestamp() {
		return strtotime( gmdate( 'Y-m-d H:i:s', time() ) );
	}

	/**
	 * Retrieves the path component of a given item's URI.
	 *
	 * This method processes the provided item's URI to extract the path, considering the current blog ID
	 * or an alternate blog ID if specified. Returns the extracted path or false if the URI is invalid.
	 *
	 * @param array $item An associative array containing the 'uri' key with the URI string and an optional 'blog_id' key indicating the blog ID.
	 *
	 * @return string|false The extracted path as a string, or false if the URI is invalid.
	 */
	protected function getPath( $item ) {

		$uri = isset( $item['uri'] ) ? (string) trim( $item['uri'] ) : '';

		if ( $uri === '' || $uri === '/' || strpos( $uri, '/' ) === false ) {
			return false;
		}

		$uri = substr( $uri, 0, strrpos( $uri, '/' ) + 1 );

		if ( $item['blog_id'] !== get_current_blog_id() ) {
			$url = untrailingslashit( get_site_url( $item['blog_id'] ) ) . $uri;
		} else {
			$url = untrailingslashit( home_url() ) . $uri;
		}

		if ( ! $path = wp_parse_url( $url, PHP_URL_PATH ) ) {
			return false;
		}

		if ( $path === '/' ) {
			return false;
		}

		return $path;
	}

	/**
	 * Displays the pagination.
	 *
	 * @param string $which The location of the pagination: Either 'top' or 'bottom'.
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args['total_items'] ) ) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">' . sprintf(
				_n( '%s item', '%s items', $total_items ),  //phpcs:ignore
				number_format_i18n( $total_items )
			) . '</span>';

		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) . wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;

		if ( 1 === $current ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( $total_pages === $current ) {
			$disable_last = true;
			$disable_next = true;
		}

		$page_arg_js = wp_json_encode( (string) $this->prefix_arg . 'paged' );

		$getOnclick = function( $target_page ) use ( $page_arg_js ) {
			$target_page = (int) $target_page;

			// No event dependency; returning false prevents navigation.
			$js = "var f=this;"
			      . "while(f && f.tagName && f.tagName.toLowerCase()!=='form'){f=f.parentNode;}"
			      . "if(!f){window.location=this.href;return false;}"
			      . "var n={$page_arg_js};"
			      . "var i=f.querySelector('input[name=\"'+n+'\"]');"
			      . "if(!i){i=document.createElement('input');i.type='hidden';i.name=n;f.appendChild(i);}"
			      . "i.value='{$target_page}';"
			      . "f.submit();return false;";

			return esc_attr( $js );
		};

		// First
		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$href = esc_url( remove_query_arg( $this->prefix_arg . 'paged', $current_url ) );
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s' onclick='%s'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
				'</a>',
				$href,
				$getOnclick( 1 ),
				__( 'First page' ),  //phpcs:ignore
				'&laquo;'
			);
		}

		// Prev
		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$target = max( 1, $current - 1 );
			$href   = esc_url( add_query_arg( $this->prefix_arg . 'paged', $target, $current_url ) );
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s' onclick='%s'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
				'</a>',
				$href,
				$getOnclick( $target ),
				__( 'Previous page' ),  //phpcs:ignore
				'&lsaquo;'
			);
		}

		// Current page input
		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = sprintf(
				'<span class="screen-reader-text">%s</span>' .
				'<span id="table-paging" class="paging-input">' .
				'<span class="tablenav-paging-text">',
				__( 'Current Page' )  //phpcs:ignore
			);
		} else {
			$html_current_page = sprintf(
				'<label for="current-page-selector" class="screen-reader-text">%s</label>' .
				"<input class='current-page' id='current-page-selector' type='text' name='%s' value='%s' size='%d' aria-describedby='table-paging' />" .
				"<span class='tablenav-paging-text'>",
				__( 'Current Page' ),  //phpcs:ignore
				esc_attr( $this->prefix_arg . 'paged' ),
				esc_attr( $current ),
				strlen( (string) $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		$page_links[] = $total_pages_before . sprintf(
				/* translators: 1: Current page number, 2: Total number of pages. */
				_x( '%1$s of %2$s', 'paging' ),  //phpcs:ignore
				$html_current_page,
				$html_total_pages
			) . $total_pages_after;

		// Next
		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$target = min( $total_pages, $current + 1 );
			$href   = esc_url( add_query_arg( $this->prefix_arg . 'paged', $target, $current_url ) );
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s' onclick='%s'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
				'</a>',
				$href,
				$getOnclick( $target ),
				__( 'Next page' ), //phpcs:ignore
				'&rsaquo;'
			);
		}

		// Last
		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$href = esc_url( add_query_arg( $this->prefix_arg . 'paged', $total_pages, $current_url ) );
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s' onclick='%s'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
				'</a>',
				$href,
				$getOnclick( $total_pages ),
				__( 'Last page' ), //phpcs:ignore
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination; //phpcs:ignore
	}

	/**
	 * Retrieves the current page number from the request if available; defaults to 1.
	 *
	 * @return int The current page number, always a positive integer.
	 */
	public function get_pagenum() {
		$paged = (int) HMWP_Classes_Tools::getValue( $this->prefix_arg . 'paged', 1 );
		return max(1, $paged );
	}


}
