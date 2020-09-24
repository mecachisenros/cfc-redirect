<?php
/**
 * DB Api class.
 *
 * @since 0.1
 */

namespace CFCR\Api;

class DB {

	/**
	 * Table name.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $table_name = 'cfc_redirects';

	/**
	 * WordPress DB prefix.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $prefix;

	/**
	 * Prefixed table name.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $prefixed_table_name;

	/**
	 * Database version.
	 *
	 * @since 0.1
	 * @var float
	 */
	protected $db_version = 0.1;

	/**
	 * Wpdb class reference.
	 *
	 * @since 0.1
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		global $wpdb;

		$this->wpdb = $wpdb;
		$this->prefix = $wpdb->prefix;
		$this->prefixed_table_name = $this->prefix . $this->table_name;

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		add_action( 'admin_init', [ $this, 'upgrade_db' ] );

	}

	/**
	 * Insert record.
	 *
	 * @since 0.1
	 * @param array $data
	 * @return array|false $insert_id The inserted result
	 */
	public function insert( array $data ) {

		$insert = $this->wpdb->insert( $this->prefixed_table_name, $data );

		if ( $insert ) return $this->get_by_id( $this->wpdb->insert_id );

		return false;

	}

	/**
	 * Update record.
	 *
	 * @since 0.1
	 * @param array $data
	 * @param array $where
	 * @return array|int|false $result
	 */
	public function update( array $data, array $where ) {

		if ( isset( $where['id'] ) ) $id = $where['id'];

		$update = $this->wpdb->update( $this->prefixed_table_name, $data, $where );

		if ( false !== $update ) return $this->get_by_id( $id );

		return $update;

	}

	/**
	 * Get record by id.
	 *
	 * @since 0.1
	 * @param int $id
	 * @return object|null $result
	 */
	public function get_by_id( int $id ) {

		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->prefixed_table_name} where id = %d", $id )
		);

	}

	/**
	 * Get record by contribution|event page id.
	 *
	 * @since 0.1
	 * @param int $id
	 * @param string $type The entity type page|event
	 * @return object|null $result
	 */
	public function get_by_entity_id( int $id, string $type = '' ) {

		$query = ! empty( $type )
			? $this->wpdb->prepare( "SELECT * FROM {$this->prefixed_table_name} WHERE entity_id = %d AND page_type = %s", $id, $type )
			: $this->wpdb->prepare( "SELECT * FROM {$this->prefixed_table_name} WHERE entity_id = %d", $id );

		return $this->wpdb->get_row( $query );

	}

	/**
	 * Get all records.
	 *
	 * @since 0.1
	 * @return object|array $result
	 */
	public function get_all() {

		return $this->wpdb->get_results( "SELECT * FROM {$this->prefixed_table_name}" );

	}

	/**
	 * Delete record.
	 *
	 * @since 0.1
	 * @param array $where
	 * @return int|bool $result
	 */
	public function delete( array $where ) {

		return $this->wpdb->delete( $this->prefixed_table_name, $where );

	}

	/**
	 * Upgrades database.
	 *
	 * @since 0.1
	 */
	public function upgrade_db() {

		$current_db_version = (float) get_option( 'caldera_forms_civicrm_redirect_db_version', false );

		// Create tables on first install.
		if ( empty( $current_db_version ) ) {
			$this->create_tables();
		}

		// Update db version when it changes.
		if ( $current_db_version != $this->db_version ) {
			update_option( 'caldera_forms_civicrm_redirect_db_version', $this->db_version );
		}

	}

	/**
	 * Create tables.
	 *
	 * @since 1.0
	 * @return bool
	 */
	private function create_tables() {

		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->prefixed_table_name} (
			id mediumint(55) NOT NULL AUTO_INCREMENT,
			entity_id bigint(20) UNSIGNED NOT NULL,
			page_type varchar(255) NOT NULL,
			page_title varchar(255) NOT NULL,
			post_id bigint(20) UNSIGNED NOT NULL,
			post_type varchar(255) NOT NULL,
			post_title varchar(255) NOT NULL,
			is_active varchar(255) DEFAULT 0,
			UNIQUE KEY id (id)
		) $charset_collate;";

		if ( ! function_exists( 'dbDelta' ) )
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

		return empty( $this->wpdb->last_error );

	}

}
