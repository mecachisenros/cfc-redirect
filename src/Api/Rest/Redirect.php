<?php
/**
 * Rest Api class.
 *
 * @since 0.1
 */

namespace CFCR\Api\Rest;

use CFCR\Api\DB;

class Redirect extends \WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $namespace = 'cfcr-api/v2';

	/**
	 * Resource name.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $rest_base = 'r';

	/**
	 * The database API instance.
	 *
	 * @since 0.1
	 * @var DB
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 * @param CFCR\Api\DB $db DB instance
	 */
	public function __construct( DB $db ) {

		$this->db = $db;

	}

	/**
	 * Registers routes.
	 *
	 * @since 0.1
	 */
	public function register_routes() {

		// main endpoint /r
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
			],
			[
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args' => $this->get_item_args( [ 'entity_id', 'post_id', 'page_type' ] )
			],
			[
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				'args' => $this->get_item_args( [ 'id' ] )
			],
			'schema' => [ $this, 'get_item_schema' ]
		] );

		// id endpoint /r/<id>
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args' => $this->get_item_args( [ 'id' ] )
			],
			'schema' => [ $this, 'get_item_schema' ]
		] );

		// entity_id endpoint /r/entity/<id>
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/entity' . '/(?P<entity_id>[\d]+)', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args' => $this->get_item_args( [ 'entity_id' ] )
			],
			'schema' => [ $this, 'get_item_schema' ]
		] );

	}

	/**
	 * Check get permission.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {

		return true;

	}

	/**
	 * Get items.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 */
	public function get_items( $request ) {

		// get from db
		$items = $this->db->get_all();

		if ( empty( $items ) )
			return rest_ensure_response( [] );

		$data = array_reduce( (array) $items, function( $items, $item ) use ( $request ) {

			$response = $this->prepare_item_for_response( $item, $request );

			$items[] = $this->prepare_response_for_collection( $response );

			return $items;

		}, [] );

		$data = apply_filters( 'cfcr/api/rest/before_return_collection', $data, $request );

		return rest_ensure_response( $data );

	}

	/**
	 * Get item.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response $response
	 */
	public function get_item( $request ) {

		$params = $request->get_params();

		if ( isset( $params['entity_id'] ) ) {

			$item = isset( $params['page_type'] )
				? $this->db->get_by_entity_id( $params['entity_id'], $params['page_type'] )
				: $this->db->get_by_entity_id( $params['entity_id'] );

		} else {

			$item = $this->db->get_by_id( (int) $params['id'] );

		}

		$item = apply_filters( 'cfcr/api/rest/before_return_redirect', $item, $params, $request );

		if ( empty( $item ) )
			return rest_ensure_response( [] );

		$response = $this->prepare_item_for_response( $item, $request );

		return $response;

	}

	/**
	 * Create item.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response $response
	 */
	public function create_item( $request ) {

		$params = $request->get_params();

		// set post_type
		$params['post_type'] = get_post_type( $params['post_id'] );

		// set post_title
		$params['post_title'] = get_the_title( $params['post_id'] );

		// entity
		$entity = $params['page_type'] == 'event' ? 'Event' : 'ContributionPage';

		// get exisitng redirect if any
		$existing_item = $this->db->get_by_entity_id( $params['entity_id'], $params['page_type'] );

		// prevent creating redirect if already exists, use PATCH method to update
		if ( isset( $existing_item ) && $request->get_method() == 'POST' )
			return new \WP_Error( 'rest_create_error', __( 'Only one redirect per contribution page/event please.' ), [ 'status' => $this->authorization_status_code() ] );

		try {

			$page = civicrm_api3( $entity, 'getsingle', [
				'id' => $params['entity_id'],
				'return' => 'title'
			] );

			// set title
			if ( ! isset( $page['is_error'] ) || ! $page['is_error'] )
				$params['page_title'] = $page['title'];

		} catch ( \CiviCRM_API3_Exception $e ) {

			return new \WP_Error( 'rest_create_error', $e->getMessage(), [ 'status' => $this->authorization_status_code() ] );

		}

		// update or create
		if ( isset( $params['id'] ) ) {

			$id['id'] = $params['id'];

			$params = apply_filters( 'cfcr/api/rest/before_update_redirect', $params, $request );

			unset( $params['id'] );
			$item = $this->db->update( $params, $id );

		} else {

			$params = apply_filters( 'cfcr/api/rest/before_insert_redirect', $params, $request );

			$item = $this->db->insert( $params );
			
		}

		if ( empty( $item ) )
			return rest_ensure_response( [] );

		$response = $this->prepare_item_for_response( $item, $request );

		return $response;

	}

	/**
	 * Check create permission.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {

		$capability = apply_filters( 'cfcr/api/rest/create/cap', 'manage_options', $request );

		if ( ! current_user_can( $capability ) )
			return new \WP_Error( 'rest_forbidden', __( 'You don\'t have enough permissions.' ), [ 'status' => $this->authorization_status_code() ] );

		return true;

	}

	/**
	 * Delete item.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response $response
	 */
	public function delete_item( $request ) {

		$params = $request->get_params();

		$params = apply_filters( 'cfcr/api/rest/before_delete_redirect', $params, $request );

		$item = $this->db->delete( $params );

		if ( empty( $item ) )
			return rest_ensure_response( [] );

		$response = $this->prepare_item_for_response( $item, $request );

		return $response;

	}

	/**
	 * Check delete permission.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ) {

		$capability = apply_filters( 'cfcr/api/rest/delete/cap', 'manage_options', $request );

		if ( ! current_user_can( $capability ) )
			return new \WP_Error( 'rest_forbidden', __( 'You don\'t have enough permissions.' ), [ 'status' => $this->authorization_status_code() ] );

		return true;

	}

	/**
	 * Matches the item data to the schema.
	 *
	 * @since 0.1
	 * @param object $item
	 * @param WP_REST_Request $request
	 */
	public function prepare_item_for_response( $item, $request ) {

		$schema = $this->get_item_schema();

		$item_data = array_filter( (array) $item, function( $value, $property ) use ( $schema ) {

			return isset( $schema['properties'][$property] );

		}, ARRAY_FILTER_USE_BOTH );

		return rest_ensure_response( $item_data );

	}

	/**
	 * Prepare a response for inserting into a collection of responses.
	 *
	 * This is copied from WP_REST_Controller class.
	 *
	 * @param WP_REST_Response $response Response object
	 * @return array Response data, ready for insertion into collection data
	 */
	public function prepare_response_for_collection( $response ) {

		if ( ! ( $response instanceof \WP_REST_Response ) ) return $response;

		$data = (array) $response->get_data();
		$server = rest_get_server();

		if ( method_exists( $server, 'get_compact_response_links' ) ) {
			$links = call_user_func( [ $server, 'get_compact_response_links' ], $response );
		} else {
			$links = call_user_func( [ $server, 'get_response_links' ], $response );
		}

		if ( ! empty( $links ) )
			$data['_links'] = $links;

		return $data;

	}

	/**
	 * Item schema.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 * @return array $schema
	 */
	public function get_item_schema() {

		return [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => 'redirect',
			'description' => 'A redirect',
			'type' => 'object',
			'additionalProperties' => false,
			'required' => [ 'id', 'entity_id', 'post_id', 'type' ],
			'properties' => [
				'id' => [
					'description' => __( 'Unique identifier for the redirect.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'integer',
					'context' => [ 'view', 'edit', 'embed' ],
					'readonly' => true
				],
				'entity_id' => [
					'description' => __( 'The entity id i.e. <event_id>|<contribution_page_id>.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'integer'
				],
				'page_type' => [
					'description' => __( 'The page type i.e. event|contribution_page.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'string',
					'enum' => [ 'event', 'contribution_page' ]
				],
				'page_title' => [
					'description' => __( 'The page title.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'string'
				],
				'post_id' => [
					'description' => __( 'The post/page id to redirect to.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'integer'
				],
				'post_type' => [
					'description' => __( 'The post type i.e. posts|pages.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'string',
					'enum' => [ 'post', 'page' ]
				],
				'post_title' => [
					'description' => __( 'The page title.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'string'
				],
				'is_active' => [
					'description' => __( 'Wheather is active or not.', 'caldera-forms-civicrm-redirect' ),
					'type' => 'integer',
					'enum' => [ 0, 1 ],
					'minimum' => 0,
					'maximum' => 1,
				]
			],
		];

	}

	/**
	 * Item arguments.
	 *
	 * @since 0.1
	 * @return array $arguments
	 */
	public function get_item_args( array $return = [] ) {

		$args = [
			'id' => [
				'type' => 'integer',
				'required' => true
			],
			'entity_id' => [
				'type' => 'integer',
				'required' => true
			],
			'page_type' => [
				'type' => 'string',
				'required' => true,
				'validate_callback' => function( $param, $request, $key ) {

					return is_string( $param ) && in_array( $param, [ 'contribution_page', 'event' ] );

				}
			],
			'page_title' => [
				'type' => 'string'
			],
			'post_id' => [
				'type' => 'integer',
				'required' => true
			],
			'post_type' => [
				'type' => 'string',
				'required' => true,
				'validate_callback' => function( $param, $request, $key ) {

					return is_string( $param ) && in_array( $param, [ 'post', 'page' ] );

				}
			],
			'post_title' => [
				'type' => 'string'
			],
			'is_active' => [
				'type' => 'integer',
				'validate_callback' => function( $param, $request, $key ) {

					return is_numeric( $param ) && in_array( $param, [ 0, 1 ] );

				}
			],
		];

		if ( empty( $return ) ) return $args;

		return array_filter( $args, function( $attr, $key ) use ( $return ) {

			return in_array( $key, $return );

		}, ARRAY_FILTER_USE_BOTH );

	}

	/**
	 * Authorization status code.
	 *
	 * @since 0.1
	 * @return int $status
	 */
	public function authorization_status_code() {

		$status = 401;

		if ( is_user_logged_in() ) $status = 403;

		return $status;

	}

}
