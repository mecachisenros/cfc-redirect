<?php
/**
 * Main plugin class.
 *
 * @since 0.1
 */

namespace CFCR;

class Plugin {

	/**
	 * Event URI.
	 *
	 * @since 0.1
	 */
	const EVENT_URI = [
		'civicrm',
		'event',
		'register'
	];

	/**
	 * Contribution URI.
	 *
	 * @since 0.1
	 */
	const CONTRIBUTION_URI = [
		'civicrm',
		'contribute',
		'transact'
	];

	/**
	 * Plugin file path.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $plugin_file_path;

	/**
	 * Redirect page type.
	 *
	 * @since 0.1
	 * @var string event|contribution_page
	 */
	protected $redirect_page_type;

	/**
	 * The DB class.
	 *
	 * @since 0.1
	 * @var Api\DB
	 */
	protected $db;

	/**
	 * The Settings class.
	 *
	 * @since 0.1
	 * @var Admin\Settings
	 */
	protected $admin_page;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct( $plugin_file_path ) {

		$this->plugin_file_path = $plugin_file_path;

		$this->setup_objects();

		$this->register_hooks();

	}

	/**
	 * Setup objects.
	 *
	 * @since 0.1
	 */
	protected function setup_objects() {

		$this->db = new Api\DB;

		$this->admin_page = new Admin\Page;

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0
	 */
	protected function register_hooks() {

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		add_filter( 'civicrm_context', [ $this, 'maybe_do_redirect' ] );

	}

	/**
	 * Registers Rest API routes.
	 *
	 * @since 0.1
	 */
	public function register_rest_routes() {

		// redirect rest endpoint
		$redirects_controller = new Api\Rest\Redirect( $this->db );
		$redirects_controller->register_routes();

		// civi rest endpoint, for this plugin's use only
		$civicrm_controller = new Api\Rest\Civi;
		$civicrm_controller->register_routes();

		/**
		 * Opportunity to add more rest routes.
		 *
		 * @since 0.1
		 */
		do_action( 'cfcr/plugin/rest_routes_registered' );

	}

	/**
	 * Redirects a Contribution or Event page to its Caldera Forms page.
	 *
	 * @since 1.0
	 * @param bool $is_civi Wheather WP is in CiviCRM context
	 * @return bool $is_civi
	 */
	public function maybe_do_redirect( $context ) {

		if ( $context != 'basepage' ) return $context;

		$args = $this->get_query_args();

		if ( ! $this->is_redirect_page( $args ) ) return $context;

		$entity_id = $this->query_get_entity_id();

		if ( ! $entity_id ) return $context;

		$post_id = $this->get_post_id_for_entity( $entity_id );

		if ( ! $post_id ) return $context;

		/**
		 * Filter redirect target.
		 *
		 * @since 0.1
		 * @param int $to The target post id to redirect to
		 * @param int $from The contribution page or event id
		 * @param string $page_type The Civi page type event|contribution_page
		 * @param array $args The query args
		 */
		$post_id = apply_filters( 'cfcr/plugin/before_do_redirect', $post_id, $entity_id, $this->redirect_page_type, $args );

		$this->do_redirect( $post_id );

	}

	/**
	 * Redirect to a post id.
	 *
	 * @since 0.1
	 * @param int $post_id Post id to redirecto to
	 */
	protected function do_redirect( int $post_id ) {

		wp_safe_redirect( get_permalink( $post_id ) );

		exit;

	}

	/**
	 * Get query arguments from $_GET['q'].
	 *
	 * @since 0.1
	 * @return array $args
	 */
	protected function get_query_args() {

		return explode( '/', $_GET['q'] );

	}

	/**
	 * Get constribution page id or event id.
	 *
	 * @since 0.1
	 * @return int|bool $entity_id
	 */
	protected function query_get_entity_id() {

		if ( isset( $_GET['id'] ) ) return $_GET['id'];

		return false;

	}

	/**
	 * Is redirect page.
	 *
	 * @since 0.1
	 * @param array $args The query args
	 * @return bool
	 */
	protected function is_redirect_page( array $args ) {

		if ( ! in_array( 'civicrm', $args ) ) return false;

		return $this->is_event_page( $args ) || $this->is_contribution_page( $args );

	}

	/**
	 * Get redirect post_id from api without extra request.
	 *
	 * @since 0.1
	 * @param int $entity_id The page or event id
	 * @return int $post_id
	 */
	protected function get_post_id_for_entity( int $entity_id ) {

		// request object
		$request = new \WP_REST_Request( 'GET', sprintf( '/cfcr-api/v2/r/entity/%d', $entity_id ) );

		// set entity_id and page_type if we have one
		$request->set_param( 'entity_id', $entity_id );
		if ( ! empty( $this->redirect_page_type ) ) $request->set_param( 'page_type', $this->redirect_page_type );

		// do request
		$response = rest_do_request( $request );

		if ( $response->is_error() ) return false;

		// get redirect data
		$redirect = $response->get_data();

		if ( $redirect['is_active'] ) return $redirect['post_id'];

		return false;

	}

	/**
	 * Is event page.
	 *
	 * @since 0.1
	 * @param array $args The query args
	 * @return bool
	 */
	protected function is_event_page( array $args ) {

		$is_event_page = false;

		if ( count( array_intersect( $args, self::EVENT_URI ) ) >= 3 ) {

			$is_event_page = true;

			$this->redirect_page_type = 'event';

		}

		return $is_event_page;

	}

	/**
	 * Is contribution page.
	 *
	 * @since 0.1
	 * @param array $args The query args
	 * @return bool
	 */
	protected function is_contribution_page( array $args ) {

		$is_contribution_page = false;

		if ( count( array_intersect( $args, self::CONTRIBUTION_URI ) ) >= 3 ) {

			$is_contribution_page = true;

			$this->redirect_page_type = 'contribution_page';

		}

		return $is_contribution_page;

	}

}
