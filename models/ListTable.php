<?php
/**
 * ListTable Model
 * Called to show the Events Log
 *
 * @file  The ListTable file
 * @package HMWP/EventsModel
 * @since 6.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HMWP_Models_ListTable extends WP_List_Table {
	protected $_data;

	public function __construct() {
		parent::__construct( array(
				'singular' => esc_html__( 'log', 'hide-my-wp' ),     //singular name of the listed records
				'plural'   => esc_html__( 'logs', 'hide-my-wp' ),   //plural name of the listed records
				'ajax'     => false        //does this table support ajax?
			) );

		add_filter( "views_{$this->screen->id}", array( $this, 'getFilters' ), 10, 1 );

	}

	/**
	 * @param  array  $views  Array of view filters.
	 *
	 * @return array Modified array with additional filters.
	 */
	function getFilters( $views ) {
		$views['note'] = esc_html__( "See the last days actions on this website ...", 'hide-my-wp' );

		return $views;
	}

	/**
	 * @param  string  $which  The location of the extra table navigation ('top' or 'bottom').
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( $which == "top" ) {

			$dropbox = $this->actions_dropdown();

			if ( ! empty( $dropbox ) ) {
				echo $dropbox;
				submit_button( esc_html__( 'Filter' ), '', 'filter_action', false, array( 'id' => 'logaction-submit' ) );
			}

		}
	}

	/**
	 * Sets the data for the class.
	 *
	 * @param  mixed  $data  The data to be set.
	 *
	 * @return void
	 */
	public function setData( $data ) {
		$this->_data = $data;
	}

	/**
	 * Prepares and displays the page table within a form.
	 *
	 * @return void
	 */
	public function loadPageTable() {
		$this->table_head();
		$this->views();
		$this->prepare_items();

		echo '<form method="post">
                 <input type="hidden" name="page" value="hmwp_log">';

		$this->search_box( 'search', 'search_id' );

		$this->display();

		echo '</form>';
	}

	/**
	 * @return void
	 */
	public function table_head() {

		echo '<style>';
		echo '.wp-list-table  { border-color: #eee !important;  }';
		echo '.wp-list-table .column-logaction { width: 20%;  }';
		echo '.wp-list-table .column-ip { width: 15%; }';
		echo '.wp-list-table .column-data { width: 45%;}';
		echo '.wp-list-table .column-datetime { width: 22%;}';
		echo '</style>';

	}

	/**
	 * Outputs a message indicating that no items were found.
	 *
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'No log found.', 'hide-my-wp' );
	}

	/**
	 * Handles the default output for custom columns in a list table.
	 *
	 * @param  array  $item  Data for the current row.
	 * @param  string  $column_name  The name of the column to display.
	 *
	 * @return string The formatted column content.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'logaction':
			case 'ip':
				return $item[ $column_name ];
			case 'datetime':
				$audit_timestamp = strtotime( $item[ $column_name ] ) + ( (int) get_option( 'gmt_offset' ) * 3600 );

				return date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $audit_timestamp );
			case 'data':
				$str = '';
				if ( ! empty( $item[ $column_name ] ) ) {
					foreach ( $item[ $column_name ] as $key => $row ) {
						switch ( $key ) {
							case 'referer':
								$key = 'Path';
								break;
							case 'ip':
								continue 2;
							case 'log':
							case 'username':
								$key = 'Username';
								break;
							case 'post_id':
								$key = 'Posts ids';
								break;
							case 'role':
								$key = 'User Role';
								break;
							case 'post':
								$key = 'Post id';
								break;
							default:
								$key = ucfirst( $key );
								break;
						}
						$str .= $key . ': ' . '<strong>' . join( ',', (array) $row ) . '</strong>' . '<br />';
					}
				}

				return "<pre style='max-width: 400px'>$str</pre>";

		}

		return '';
	}

	/**
	 * Returns an array of columns that are sortable in the list table.
	 *
	 * @return array The array of sortable columns with their sort order.
	 */
	public function get_sortable_columns() {

		return array(
			'logaction' => array( 'logaction', false ),
			'ip'        => array( 'ip', false ),
			'datetime'  => array( 'datetime', false )
		);

	}

	/**
	 * Retrieves the columns for displaying user actions in a list table.
	 *
	 * @return array The associative array of column identifiers and their corresponding labels.
	 */
	public function get_columns() {

		return array(
			'logaction' => esc_html__( 'User Action', 'hide-my-wp' ),
			'ip'        => esc_html__( 'IP', 'hide-my-wp' ),
			'data'      => esc_html__( 'Details', 'hide-my-wp' ),
			'datetime'  => esc_html__( 'Date', 'hide-my-wp' )
		);

	}

	/**
	 * Reorders items in a list based on the specified column and order.
	 *
	 * @param  array  $a  First item to compare.
	 * @param  array  $b  Second item to compare.
	 *
	 * @return int The result of the comparison, adjusted for sort direction.
	 */
	public function usort_reorder( $a, $b ) {

		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'datetime';
		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		// Send final sort direction to usort
		return ( $order === 'desc' ) ? $result : - $result;

	}

	/**
	 * Prepares the items to be displayed in the list table by handling sorting, filtering,
	 * pagination, and setting up the table headers.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// Initialize $total_items
		$total_items = 0;
		// Set the items
		$this->items = $this->_data;
		// Get the number of records per page
		$per_page = get_option( 'posts_per_page' );

		// Get the columns
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Set the table headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		if ( ! empty( $this->items ) ) {
			// Sort the items
			usort( $this->items, array( &$this, 'usort_reorder' ) );

			// Count the page and records
			$current_page = $this->get_pagenum();
			$total_items  = count( $this->items );

			// Filter the Items is set the logaction
			$this->items = array_filter( $this->items, function ( $item ) {
				if ( $logaction = HMWP_Classes_Tools::getValue( 'logaction' ) ) {
					return ( $item['logaction'] == $logaction );
				}

				return $item;
			} );

			// Filter the Items is set the url
			$this->items = array_filter( $this->items, function ( $item ) {
				if ( $logurl = HMWP_Classes_Tools::getValue( 'logurl' ) ) {
					return ( str_replace( ':', '', $item['url'] ) == $logurl );
				}

				return $item;
			} );


			// Slice log by pagination
			$this->items = array_slice( $this->items, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}
		// Set the pagination
		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page
			) );


	}

	/**
	 * Generates a dropdown menu for filtering log actions and URLs.
	 *
	 * This method builds and returns HTML select elements for filtering
	 * by action types and URLs based on the data available in the class.
	 *
	 * @return string The HTML output for the dropdown menus.
	 */
	protected function actions_dropdown() {
		$selected_action = HMWP_Classes_Tools::getValue( 'logaction' );
		$selected_url    = HMWP_Classes_Tools::getValue( 'logurl' );
		$output          = '';

		if ( ! empty( $this->_data ) ) {
			/////////////////////////////////////////////////////////////////
			///
			$actions = array_map( function ( $val ) {
				return $val['logaction'];
			}, $this->_data );
			$actions = array_unique( $actions );

			if ( ! empty( $actions ) ) {
				$output = "<select name='logaction' class='postform'  style='max-width: 210px;'>\n";
				$output .= "\t<option value='0' " . ( ( ! $selected_action ) ? " selected='selected'" : '' ) . ">" . esc_html__( 'All Actions' ) . "</option>\n";


				foreach ( $actions as $action ) {
					$output .= "\t<option value='$action' " . ( ( $selected_action == $action ) ? 'selected="selected"' : '' ) . ">" . ucfirst( $action ) . "</option>\n";
				}

				$output .= "</select>\n";
			}

			/////////////////////////////////////////////////////////////////
			$urls = array_map( function ( $val ) {
				return $val['url'];
			}, $this->_data );
			$urls = array_unique( $urls );

			if ( ! empty( $urls ) && count( $urls ) > 1 ) {
				$output .= "<select name='logurl' class='postform' style='max-width: 210px;'>\n";
				$output .= "\t<option value='0' " . ( ( ! $selected_url ) ? " selected='selected'" : '' ) . ">" . esc_html__( 'All Websites' ) . "</option>\n";

				foreach ( $urls as $url ) {
					$output .= "\t<option value='$url' " . ( ( $selected_url == str_replace( ':', '', $url ) ) ? 'selected="selected"' : '' ) . ">" . $url . "</option>\n";
				}

				$output .= "</select>\n";
			}

		}

		return $output;
	}

}



