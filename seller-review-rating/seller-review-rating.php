<?php
/*
Plugin Name: Review and Rating Seller
Plugin URI: https://epinoy.com
Description: List and update Review and Rating of Seller
Version: 1.0
Author: ePinoy
Author URI:  https://epinoy.com
*/
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php'); 
}

class Seller_Review extends WP_List_Table {

	/** 
	 * Class constructor 
	 * singular = singular name of the listed records
	 * plural = plural name of the listed records
	 * ajax = table support ajax or not
	**/
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Review', 'ep' ),
			'plural'   => __( 'Reviews', 'ep' ),
			'ajax'     => false
		] );

	}


	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_reviews( $per_page = 10, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM epinoy_user_rating";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Delete a review record.
	 *
	 * @param int $id review ID
	 */
	public static function delete_review( $id ) {
		global $wpdb;

		$wpdb->delete(
			"epinoy_user_rating",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	/**
     * Update review record, update 0 to 1
     * 
     * @param int $id review ID
	**/
	public static function update_review( $id ) {
		global $wpdb;

		$wpdb->update(
			"epinoy_user_rating",
			[ 'review_status' => '1' ],
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	/**
     * Update review record to pending, update 1 to 0
     * 
     * @param int $id review ID
	**/
	public static function update_pending_review( $id ) {
		global $wpdb;

		$wpdb->update(
			"epinoy_user_rating",
			[ 'review_status' => '0' ],
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM epinoy_user_rating";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No reviews available.', 'ep' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'ratee_id':
			case 'rater_id': 
			 	return $this->get_rater_ratee_id( $item[ $column_name ] );
			case 'review_status':
			 	if ( $item[ $column_name ] == 0) {
			 		return 'For Review';
			 	} else {
			 		return 'Approved';
			 	}
			case 'remark':
				return wp_trim_words( $item[ $column_name ], 40, '...' );
			case 'title':
			case 'rating':
				return $item[ $column_name ];
		}
	}

	/**
     * Get user_login of rater or ratee.
     *
     * @param int ratee_id or rater_id
     *
     * return string
	**/
	public function get_rater_ratee_id($item) {
		$display_name = get_userdata($item);
		return $display_name->user_login;
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$delete_nonce = wp_create_nonce( 'ep_delete_review' );
		$update_nonce = wp_create_nonce( 'ep_update_review' );
		$pending_review_nonce = wp_create_nonce( 'ep_review_review' );

		$title = '<strong>' . $item['title'] . '</strong>';

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&review=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),'update' => sprintf( '<a href="?page=%s&action=%s&review=%s&_wpnonce=%s">Update</a>', esc_attr( $_REQUEST['page'] ), 'update', absint( $item['id'] ), $update_nonce ),'pending_review' => sprintf( '<a href="?page=%s&action=%s&review=%s&_wpnonce=%s">Pending Review</a>', esc_attr( $_REQUEST['page'] ), 'pending_review', absint( $item['id'] ), $pending_review_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'ratee_id'    => __( 'Ratee', 'ep' ),
			'rater_id' => __( 'Rater', 'ep' ),
			'title' => __( 'Title', 'ep' ),
			'remark'    => __( 'Remark', 'ep' ),			
			'rating'    => __( 'Rating', 'ep' ),
			'review_status'    => __( 'Review Status', 'ep' )
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'rating' => array( 'rating', true ),
			'rater_id' => array( 'rater_id', true ),
			'ratee_id' => array( 'ratee_id', true ),
			'title' => array( 'title', true )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete',
			'bulk-update' => 'Update',
			'bulk-review' => 'Pending Review'
		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'reviews_per_page', 10 ); //change 10 if you want to have 10 per page
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page
		] );

		$this->items = self::get_reviews( $per_page, $current_page );
	}

	public function process_bulk_action() {
		// print_r($_POST['bulk-delete']);
		// echo $_POST['action'];
		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'ep_delete_review' ) ) {
				die();
			}
			else {
				self::delete_review( absint( $_GET['review'] ) );
			}

		} else if ( 'update' === $this->current_action() ) {

			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'ep_update_review' ) ) {
				die();
			}
			else {
				self::update_review( absint( $_GET['review'] ) );
			}

		} else if ( 'pending_review' === $this->current_action() ) {

			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'ep_review_review' ) ) {
				die();
			}
			else {
				self::update_pending_review( absint( $_GET['review'] ) );
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_review( $id );

			}

		} else if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-update' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-update' )
		) {
			$update_ids = esc_sql( $_POST['bulk-delete'] );			
			foreach ($update_ids as $id) {
				self::update_review( $id );
			}
		} else if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-review' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-review' )
		) {
			$review_ids = esc_sql( $_POST['bulk-delete'] );			
			foreach ($review_ids as $id) {
				self::update_pending_review( $id );
			}
		}
	}

}


class EP_Plugin {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $reviews_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_menu_page(
			'ePinoy Review Listing Table',
			'Seller Review List',
			'manage_options',
			'seller_review_page',
			[ $this, 'plugin_settings_page' ]
		);

		add_action( "load-$hook", [ $this, 'screen_option' ] );

	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
		?>
		<div class="wrap">
			<h2>ePinoy Review and Rating</h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->reviews_obj->prepare_items();
								$this->reviews_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Reviews',
			'default' => 10,
			'option'  => 'reviews_per_page'
		];

		add_screen_option( $option, $args );

		$this->reviews_obj = new Seller_Review();
	}


	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}


add_action( 'plugins_loaded', function () {
	EP_Plugin::get_instance();
} );