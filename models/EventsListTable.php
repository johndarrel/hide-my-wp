<?php
/**
 * Events ListTable Model
 * Called to show the Events Log stored in the same DB table as Threats.
 *
 * Rules:
 * - Events are rows where user_id > 0 (logged-in user activity).
 *
 * @file  The EventsListTable file
 * @package HMWP/EventsModel
 * @since 8.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HMWP_Models_EventsListTable extends WP_List_Table {

	/** @var string Parameter prefix */
	protected $prefix_arg = 'ev_';

	/** @var int Total rows found by the query */
	protected $total_found = 0;

	/** @var string Tab parameter name */
	protected $tabParam = 'events';

	/** @var array */
	protected $eventActions = array();

	/** @var array code => name pairs for the country filter dropdown */
	protected $countryOptions = array();

	/**
	 * @return void
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => esc_html__( 'event', 'hide-my-wp' ),
				'plural'   => esc_html__( 'events', 'hide-my-wp' ),
				'ajax'     => false,
			)
		);

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$database->maybeCreateTable();

	}

	/**
	 * Forces the `REQUEST_URI` to include the current tab parameter in the query string.
	 *
	 * @return string The previous `REQUEST_URI` value before modification.
	 */
	protected function forceTabRequestUri() {
		$old = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash($_SERVER['REQUEST_URI']) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$_SERVER['REQUEST_URI'] = add_query_arg(
			array( 'tab' => $this->tabParam ),
			remove_query_arg( array( 'tab' ), $old )
		);
		return $old;
	}

	/**
	 * Restores the previous value of the request URI.
	 *
	 * @param string $old The previous request URI to be restored. Must be a non-empty string.
	 *
	 * @return void
	 */
	protected function restoreTabRequestUri( $old ) {
		if ( is_string( $old ) && $old !== '' ) {
			$_SERVER['REQUEST_URI'] = $old;
		}
	}

	/**
	 * Loads and renders the page table interface, including search functionality, table views,
	 * and necessary form elements for interactions.
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

		if ( $total <= $shown || $shown === 0 ) {
			return;
		}

		$country     = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'country', '', true );
		$countryName = '';

		if ( $country !== '' && ! empty( $this->countryOptions[ $country ] ) ) {
			$countryName = $this->countryOptions[ $country ];
		}

		echo '<div class="col-sm-12 p-3 m-0 bg-warning" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">';

		echo '<span>';
		if ( $countryName !== '' ) {
			echo wp_kses_post( sprintf(
			/* translators: 1: Number of items shown, 2: Total items, 3: Country name. */
				__( 'Showing %1$s of %2$s events from <strong>%3$s</strong>', 'hide-my-wp' ),
				'<strong>' . esc_html( number_format_i18n( $shown ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>',
				esc_html( $countryName )
			) );
		} else {
			echo wp_kses_post( sprintf(
			/* translators: 1: Number of items shown, 2: Total items. */
				__( 'Showing last %1$s of %2$s events', 'hide-my-wp' ),
				'<strong>' . esc_html( number_format_i18n( $shown ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>'
			) );
		}
		echo '</span>';

		echo '<a href="#" onclick="jQuery(\'#hmwp_ghost_mode_modal\').modal(\'show\'); return false;" class="text-danger font-weight-bold">';
		echo esc_html__( 'Unlock full log with filters, search, and export', 'hide-my-wp' );
		echo ' &gt;</a>';

		echo '</div>';

	}

	/**
	 * Outputs custom CSS styles for modifying the appearance and column widths of the WP List Table.
	 *
	 * @return void
	 */
	public function tableHead() {
		echo '<style>';
		echo '.wp-list-table{ border-color:#eee !important; }';
		echo '.wp-list-table .column-action{ width:22%; }';
		echo '.wp-list-table .column-ip{ width:24%; }';
		echo '.wp-list-table .column-details{ width:32%; }';
		echo '.wp-list-table .column-user{ width:12%; }';
		echo '.wp-list-table .column-datetime{ width:14%; }';
		echo '</style>';
	}

	/**
	 * Displays a message when no items are available in the table.
	 *
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'No events found.', 'hide-my-wp' );
	}

	/* ----------------------------
	 * Columns
	 * ---------------------------- */

	/**
	 * Returns the list of columns for the events table.
	 *
	 * @return array Associative array of column keys to display labels.
	 */
	public function get_columns() {
		return array(
			'action'   => esc_html__( 'User Action', 'hide-my-wp' ),
			'ip'       => esc_html__( 'Location', 'hide-my-wp' ),
			'details'  => esc_html__( 'Details', 'hide-my-wp' ),
			'user'     => esc_html__( 'User', 'hide-my-wp' ),
			'datetime' => esc_html__( 'Date', 'hide-my-wp' ),
		);
	}

	/**
	 * Returns the list of sortable columns and their corresponding DB sort fields.
	 *
	 * @return array Column key => array( db_field, default_desc ).
	 */
	public function get_sortable_columns() {
		return array(
			// action is computed; sorting uses stamp internally
			'action'   => array( 'event', true ),
			'user'     => array( 'user_id', false ),
			'ip'       => array( 'ip', false ),
			'datetime' => array( 'stamp', true ),
		);
	}

	/**
	 * Prints the column headers, remapping sort parameters to the table's prefix
	 * so that two list tables on the same page don't share sort state.
	 *
	 * @param bool $with_id Whether to include an ID attribute on the header row. Default true.
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
	 * Renders extra navigation controls above the table: filter dropdowns, a Filter
	 * submit button, and an Export CSV link.
	 *
	 * @param string $which Position of the nav bar — 'top' or 'bottom'.
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( $which === 'top' ) {
			$dropdown = $this->actionsDropdown();

			if ( ! empty( $dropdown ) ) {
				echo $dropdown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				submit_button( esc_html__( 'Filter' ), '', 'filter_action', false, array( 'id' => 'eventslog-submit' ) ); // phpcs:ignore
			}

			return;
		}

		if ( $which !== 'bottom' ) {
			return;
		}

		echo '<button type="button" onclick="jQuery(\'#hmwp_ghost_mode_modal\').modal(\'show\')" class="btn btn-success btn-sm">' . esc_html__( 'Export CSV', 'hide-my-wp' ) . '</button>';
	}

	/**
	 * Returns the display value for columns not handled by a dedicated column method.
	 * Formats the 'datetime' column as a human-readable elapsed-time string.
	 *
	 * @param array  $item        The current row data.
	 * @param string $column_name The column identifier.
	 *
	 * @return string Escaped HTML for the cell.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'datetime':
				$timestamp = isset( $item['stamp'] ) ? (int) $item['stamp'] : 0;
				if ( $timestamp <= 0 ) {
					return '';
				}
				return esc_html( $this->timeElapsed( $timestamp ) );
			default:
				return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
		}
	}

	/**
	 * Renders the 'User Action' column, enriching the raw event name with context
	 * from request details (plugin name, post type, menu name, media uploads, etc.).
	 *
	 * @param array $item The current row data.
	 *
	 * @return string HTML for the action cell.
	 */
	public function column_action( $item ) {
		$action = isset( $item['event'] ) ? (string) $item['event'] : '';
		$uri    = isset( $item['uri'] ) ? (string) $item['uri'] : '';

		if ( in_array( 'plugin', array_keys( $item['request_details_arr'] ) ) ) {
			$action .= ' ' . esc_html__( 'plugin', 'hide-my-wp' );
		}
		if ( in_array( 'post_type', array_keys( $item['request_details_arr'] ) ) ) {
			$action .= ' ' . $item['request_details_arr']['post_type'];
		}
		if ( in_array( 'menu-name', array_keys( $item['request_details_arr'] ) ) ) {
			$action = esc_html__( 'Edit Menu', 'hide-my-wp' ) . ': ' . $item['request_details_arr']['menu-name'];
		}
		if ( $action == 'edit' && in_array( 'post', array_keys( $item['request_details_arr'] ) ) && ! in_array( 'post_type', array_keys( $item['request_details_arr'] ) ) ) {
			$action = esc_html__( 'Edit Media', 'hide-my-wp' );
		}
		if ( $action == 'upload-attachment' && in_array( 'name', array_keys( $item['request_details_arr'] ) ) ) {
			$action = esc_html__( 'Uploaded Media', 'hide-my-wp' );
		}

		$actionLabel = $action !== '' ? ucwords( str_replace( array( '_', '-' ), ' ', $action ) ) : esc_html__( 'Event', 'hide-my-wp' );

		return '<strong>' . esc_html( $actionLabel ) . '</strong>';

	}

	/**
	 * Renders the 'User' column with a link to the user's edit page when available.
	 * Displays "Guest" for unauthenticated rows and "Unknown" for deleted users.
	 *
	 * @param array $item The current row data.
	 *
	 * @return string HTML for the user cell.
	 */
	public function column_user( $item ) {
		$userId = isset( $item['user_id'] ) ? (int) $item['user_id'] : 0;
		if ( $userId <= 0 ) {
			return '<span style="opacity:.7;">' . esc_html__( 'Guest', 'hide-my-wp' ) . '</span>';
		}

		$user = get_userdata( $userId );
		if ( ! $user ) {
			return '<span style="opacity:.7;">' . esc_html__( 'Unknown', 'hide-my-wp' ) . '</span>';
		}

		$link = function_exists( 'get_edit_user_link' ) ? get_edit_user_link( $userId ) : '';
		$name = $user->display_name ? (string) $user->display_name : (string) $user->user_login;

		if ( $link ) {
			return '<a href="' . esc_url( $link ) . '"><strong>' . esc_html( $name ) . '</strong></a><br><span style="opacity:.75;">' . ( $user->user_login <> $name ? esc_html( $user->user_login ) : '' ) . '</span>';
		}

		return '<strong>' . esc_html( $name ) . '</strong><br><span style="opacity:.75;">' . esc_html( $user->user_login ) . '</span>';
	}

	/**
	 * Renders the 'Location' column, showing the country flag and name (resolved via
	 * GeoIP) followed by the raw IP address.
	 *
	 * @param array $item The current row data.
	 *
	 * @return string HTML for the location cell.
	 */
	public function column_ip( $item ) {
		$ip = isset( $item['ip'] ) ? (string) $item['ip'] : '';

		if ( $ip === '' ) {
			return '';
		}

		$out = '';

		$stored_cc = isset( $item['country_code'] ) ? (string) $item['country_code'] : '';

		/** @var HMWP_Models_Geoip_GeoLocator $geo_locator */
		$geo_locator = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getInstance();
		$iso_code    = ( $stored_cc !== '' && $stored_cc !== '--' ) ? $stored_cc : $geo_locator->getCountryCode( $ip );
		$countries   = $geo_locator->getCountryCodes();

		if ( $iso_code && isset( $countries[ $iso_code ] ) ) {
			$flag = '<img src="' . esc_url( _HMWP_ASSETS_URL_ . 'flags/' . strtolower( $iso_code ) . '.png' ) . '" title="' . esc_attr( $countries[ $iso_code ] ) . '" style="width:16px;height:auto;">';
			$out .= esc_html__( 'Country', 'hide-my-wp' ) . ': <strong>' . $flag . ' ' . esc_html( $countries[ $iso_code ] ) . '</strong><br />';
		}

		$out .= esc_html__( 'IP', 'hide-my-wp' ) . ': <strong>' . esc_html( $ip ) . '</strong>';

		return $out;
	}

	/**
	 * Renders the 'Details' column, surfacing the most useful fields from the stored
	 * request data (referer, username, role, post type, plugin, etc.).
	 *
	 * @param array $item The current row data.
	 *
	 * @return string Preformatted HTML block, or empty string when no data is available.
	 */
	public function column_details( $item ) {
		$details = isset( $item['request_details_arr'] ) && is_array( $item['request_details_arr'] )
			? $item['request_details_arr']
			: array();

		$fields = isset( $item['request_fields_arr'] ) && is_array( $item['request_fields_arr'] )
			? $item['request_fields_arr']
			: array();

		$lines = array();

		// Prefer showing the path/referer
		if ( ! empty( $fields['referer'] ) ) {
			$lines[] = esc_html__('Path', 'hide-my-wp') . ': ' . (string) $fields['referer'];
		}

		// Useful standard fields (when present)
		$preferredKeys = array( 'username', 'role', 'post_type', 'name', 'slug', 'plugin', 'post', 'post_id', 'product_id' );
		foreach ( $preferredKeys as $k ) {
			if ( isset( $fields[ $k ] ) && $fields[ $k ] !== '' ) {
				$label   = ucfirst( str_replace( array( '_', '-' ), ' ', $k ) );
				$val     = is_array( $fields[ $k ] ) ? implode( ', ', array_map( 'strval', $fields[ $k ] ) ) : (string) $fields[ $k ];
				$lines[] = $label . ': ' . $val;
			}
		}

		if ( empty( $lines ) ) {
			return '';
		}

		$safe = '';
		foreach ( $lines as $line ) {
			$safe .= esc_html( $this->capString( $line, 300 ) ) . "\n";
		}

		return '<pre style="max-width:520px; white-space:pre-wrap; margin:0;">' . $safe . '</pre>';
	}

	/* ----------------------------
	 * Data loading (DB-backed)
	 * ---------------------------- */

	/**
	 * Fetches paginated event rows from the database and configures the table's
	 * column headers, pagination, and item list ready for display.
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
		$result = $this->queryEventRows( $perPage, $currentPage );

		$this->items        = $result['items'] ?? array();
		$this->total_found = isset( $result['total'] ) ? (int) $result['total'] : count( $this->items );

	}

	/**
	 * Queries one page of event rows from the database, applying all active filters
	 * (date range, search, action type, blog). When an action filter is set the query
	 * fetches a larger buffer and filters in PHP, because the action value is stored
	 * inside the serialised request_fields column.
	 *
	 * @param int $perPage     Number of rows per page.
	 * @param int $currentPage 1-based current page number.
	 *
	 * @return array { total: int, items: array }
	 */
	protected function queryEventRows( $perPage, $currentPage ) {
		global $wpdb;

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$database->maybeCreateTable();
		$table = $database->tableName();

		$whereParts = array();
		$params     = array();

		$whereParts[] = '1=1';

		// Only logged-in user events
		$whereParts[] = 'user_id > %d';
		$params[]     = 0;

		// Blog filter when multisite is enabled
		if ( HMWP_Classes_Tools::isMultisites() ) {
			if ( $blog = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'blog_id' ) ) {
				$whereParts[] = 'blog_id = %d';
				$params[]     = (int) $blog;
			}
		}

		// Range filter (days)
		$range = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'range' ); // 1,7,30,90,all
		if ( $range <> '' ) {
			$days = (int) $range;
			if ( $days > 0 ) {
				$minStamp     = strtotime( wp_date( 'Y-m-d', strtotime( '- ' . ($days - 1) . ' days' ) ) );
				$whereParts[] = 'stamp >= %d';
				$params[]     = (int) $minStamp;
			}
		}

		// Search
		$search = HMWP_Classes_Tools::getValue( 's', '', true );
		if ( $search <> '' ) {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$whereParts[] = '(ip LIKE %s OR uri LIKE %s OR event LIKE %s)';
			$params[]     = $like;
			$params[]     = $like;
			$params[]     = $like;
		}

		// Filter: country
		$country = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'country', '', true );
		if ( $country !== '' ) {
			$whereParts[] = 'country_code = %s';
			$params[]     = $country;
		}

		$whereSql = implode( ' AND ', $whereParts );

		$orderBy = $this->getOrderBySql();
		$order   = $this->getOrderSql();

		$actionFilter = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'action', false, true );

		// If action filter is active, we filter in PHP (action is stored inside request_fields).
		if ( $actionFilter <> '' ) {
			$buffer = (int) apply_filters( 'hmwp_events_filter_buffer', 3000 );
			if ( $buffer < 500 ) {
				$buffer = 500;
			}

			$sql = "SELECT log_id, stamp, ip, country_code, event, user_id, uri, request_method, http_code, request_fields, request_details, blog_id
			        FROM {$table}
			        WHERE {$whereSql}
			        ORDER BY {$orderBy} {$order}
			        LIMIT %d";

			$rows     = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, array( $buffer ) ) ) , ARRAY_A ); //phpcs:ignore

			$items = $this->decorateRowsWithEvent( $rows );

			$items = array_values(
				array_filter(
					$items,
					function ( $it ) use ( $actionFilter ) {
						return isset( $it['event'] ) && (string) $it['event'] === (string) $actionFilter;
					}
				)
			);

			$total  = count( $items );
			$offset = ( ( $currentPage - 1 ) * $perPage );
			$items  = array_slice( $items, $offset, $perPage );

			return array( 'total' => $total, 'items' => $items );
		}

		// Total count
		$countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
		if ( ! empty( $params ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $countSql, $params ) ); //phpcs:ignore
		} else {
			$total = (int) $wpdb->get_var( $countSql ); //phpcs:ignore
		}

		$offset = ( ( $currentPage - 1 ) * $perPage );

		$listSql = "SELECT log_id, stamp, ip, country_code, event, user_id, uri, request_method, http_code, request_fields, request_details, blog_id
		            FROM {$table}
		            WHERE {$whereSql}
		            ORDER BY {$orderBy} {$order}
		            LIMIT %d OFFSET %d";

		$rows     = $wpdb->get_results( $wpdb->prepare( $listSql, array_merge( $params, array( (int) $perPage, (int) $offset ) ) ), ARRAY_A ); //phpcs:ignore

		$items = $this->decorateRowsWithEvent( $rows );

		return array( 'total' => $total, 'items' => $items );
	}

	/**
	 * Decodes the JSON request_fields and request_details columns for each raw DB row
	 * and adds them as pre-parsed array keys ready for display.
	 *
	 * @param array $rows Raw rows from wpdb.
	 *
	 * @return array Decorated rows with 'request_fields_arr' and 'request_details_arr' keys.
	 */
	protected function decorateRowsWithEvent( $rows ) {
		$items = array();

		foreach ( (array) $rows as $row ) {

			$requestFieldsArr  = $this->safeDecode( isset( $row['request_fields'] ) ? (string) $row['request_fields'] : '' );
			$requestDetailsArr = $this->safeDecode( isset( $row['request_details'] ) ? (string) $row['request_details'] : '' );

			$row['request_fields_arr']  = is_array( $requestFieldsArr ) ? $requestFieldsArr : array();
			$row['request_details_arr'] = is_array( $requestDetailsArr ) ? $requestDetailsArr : array();

			$items[] = $row;
		}

		return $items;
	}

	/**
	 * Returns the whitelisted DB column name to use in ORDER BY based on the current
	 * sort request parameter. Defaults to 'stamp' for unknown values.
	 *
	 * @return string Safe SQL column name.
	 */
	protected function getOrderBySql() {
		$orderby = HMWP_Classes_Tools::getValue( $this->prefix_arg . 'orderby', 'stamp' );

		$map = array(
			'stamp'    => 'stamp',
			'datetime' => 'stamp',
			'ip'       => 'ip',
			'user'     => 'user_id',
			'user_id'  => 'user_id',
			'action'   => 'event',
		);

		return isset( $map[ $orderby ] ) ? $map[ $orderby ] : 'stamp';
	}

	/**
	 * Returns 'ASC' or 'DESC' based on the current sort direction request parameter.
	 * Defaults to 'DESC'.
	 *
	 * @return string 'ASC' or 'DESC'.
	 */
	protected function getOrderSql() {
		$order = strtolower(HMWP_Classes_Tools::getValue( $this->prefix_arg . 'order', 'desc' ));
		return ( $order === 'asc' ) ? 'ASC' : 'DESC';
	}

	/**
	 * Fetches the distinct set of event action values logged for authenticated users,
	 * used to populate the action filter dropdown. The sample size is capped via the
	 * 'hmwp_events_action_sample' filter (default 800, minimum 200).
	 *
	 * @return array Sorted list of unique event action strings.
	 */
	protected function getEventActionOptions() {
		global $wpdb;

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$table    = $database->tableName();

		$limit = (int) apply_filters( 'hmwp_events_action_sample', 800 );
		if ( $limit < 200 ) {
			$limit = 200;
		}

		// Only logged-in user rows
		$sql = "SELECT DISTINCT event
		        FROM {$table}
		        WHERE user_id > %d
		        ORDER BY stamp DESC
		        LIMIT %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array( 0, (int) $limit ) ), ARRAY_A ); //phpcs:ignore

		$actions = array_column($rows, 'event');
		$actions = array_values( array_unique( array_filter( $actions ) ) );
		sort( $actions );

		return $actions;
	}

	/**
	 * Queries distinct country codes present in event rows and maps them to country names
	 * for use in the country filter dropdown.
	 *
	 * @return array Associative array of [ code => name ] sorted by name.
	 */
	protected function getCountryOptions() {
		global $wpdb;

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$table    = $database->tableName();

		$sql  = "SELECT DISTINCT country_code FROM {$table} WHERE user_id > 0 AND country_code IS NOT NULL AND country_code != '' AND country_code != '--' ORDER BY country_code";
		$rows = $wpdb->get_col( $sql ); //phpcs:ignore

		if ( empty( $rows ) ) {
			return array();
		}

		/** @var HMWP_Models_Geoip_GeoLocator $geo */
		$geo      = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getInstance();
		$allNames = ( $geo && method_exists( $geo, 'getCountryCodes' ) ) ? $geo->getCountryCodes() : array();

		$options = array();
		foreach ( $rows as $code ) {
			$code             = (string) $code;
			$options[ $code ] = isset( $allNames[ $code ] ) ? $allNames[ $code ] : $code;
		}

		asort( $options );

		return $options;
	}

	/* ----------------------------
	 * Helpers
	 * ---------------------------- */

	/**
	 * Decodes a stored payload that may be JSON or PHP-serialised, returning an array.
	 * Returns an empty array for empty or non-string input and for payloads that cannot
	 * be decoded by either method.
	 *
	 * @param string $payload Raw string from a database column.
	 *
	 * @return array Decoded associative array, or empty array on failure.
	 */
	protected function safeDecode( $payload ) {
		if ( ! is_string( $payload ) || $payload === '' ) {
			return array();
		}

		// Try JSON first
		$decoded = json_decode( $payload, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		// Then try PHP serialization
		if ( function_exists( 'is_serialized' ) && is_serialized( $payload ) ) {
			$un = maybe_unserialize( $payload );
			return is_array( $un ) ? $un : array();
		}

		// Sometimes request_details might be serialized without is_serialized() being available in some contexts
		$un = @maybe_unserialize( $payload );
		return is_array( $un ) ? $un : array();
	}

	/**
	 * Truncates a string to the given maximum byte length.
	 *
	 * @param string $str The input string.
	 * @param int    $max Maximum allowed length in bytes.
	 *
	 * @return string The original string if within the limit, or the truncated string otherwise.
	 */
	protected function capString( $str, $max ) {
		$str = (string) $str;
		return ( strlen( $str ) <= (int) $max ) ? $str : substr( $str, 0, (int) $max );
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
				_n( '%s item', '%s items', $total_items ), //phpcs:ignore
				number_format_i18n( $total_items )
			) . '</span>';

		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) . wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); //phpcs:ignore
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
				__( 'First page' ), //phpcs:ignore
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
				__( 'Previous page' ), //phpcs:ignore
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
				__( 'Current Page' ) //phpcs:ignore
			);
		} else {
			$html_current_page = sprintf(
				'<label for="current-page-selector" class="screen-reader-text">%s</label>' .
				"<input class='current-page' id='current-page-selector' type='text' name='%s' value='%s' size='%d' aria-describedby='table-paging' />" .
				"<span class='tablenav-paging-text'>",
				__( 'Current Page' ), //phpcs:ignore
				esc_attr( $this->prefix_arg . 'paged' ),
				esc_attr( $current ),
				strlen( (string) $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		$page_links[] = $total_pages_before . sprintf(
				/* translators: 1: Current page number, 2: Total number of pages. */
				_x( '%1$s of %2$s', 'paging' ), //phpcs:ignore
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

		echo $this->_pagination; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
