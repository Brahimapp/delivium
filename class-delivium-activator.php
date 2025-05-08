class Delivium_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'delivium_driver_times';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			driver_id mediumint(9) NOT NULL,
			date date NOT NULL,
			status varchar(20) NOT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		$table_name = $wpdb->prefix . 'delivium_tracking';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			order_id mediumint(9) NOT NULL,
			driver_id mediumint(9) NOT NULL,
			latitude varchar(20) NOT NULL,
			longitude varchar(20) NOT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );

		$table_name = $wpdb->prefix . 'delivium_driver_notes';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			order_id mediumint(9) NOT NULL,
			driver_id mediumint(9) NOT NULL,
			note text NOT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );

		$table_name = $wpdb->prefix . 'delivium_failed_delivery_reasons';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			reason varchar(255) NOT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );

		$table_name = $wpdb->prefix . 'delivium_driver_ratings';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			order_id mediumint(9) NOT NULL,
			driver_id mediumint(9) NOT NULL,
			rating mediumint(9) NOT NULL,
			comment text NOT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );

		$table_name = $wpdb->prefix . 'delivium_driver_availability';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			driver_id mediumint(9) NOT NULL,
			date date NOT NULL,
			status varchar(20) NOT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );

		// Add default failed delivery reasons.
		$table_name = $wpdb->prefix . 'delivium_failed_delivery_reasons';
		$reasons = array(
			__( 'Customer not available', 'delivium' ),
			__( 'Wrong address', 'delivium' ),
			__( 'Customer refused delivery', 'delivium' ),
			__( 'Other', 'delivium' ),
		);

		foreach ( $reasons as $reason ) {
			$wpdb->insert(
				$table_name,
				array(
					'reason' => $reason,
					'created' => current_time( 'mysql' ),
				)
			);
		}

		// Add driver role.
		add_role(
			'driver',
			__( 'Driver', 'delivium' ),
			array(
				'read' => true,
				'edit_posts' => false,
				'delete_posts' => false,
			)
		);

		// Add driver capabilities.
		$role = get_role( 'driver' );
		$role->add_cap( 'view_delivium_dashboard' );
		$role->add_cap( 'view_delivium_orders' );
		$role->add_cap( 'edit_delivium_orders' );
		$role->add_cap( 'view_delivium_account' );
		$role->add_cap( 'edit_delivium_account' );
	}
} 