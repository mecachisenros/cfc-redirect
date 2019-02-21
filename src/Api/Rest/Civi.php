<?php
/**
 * Civi Api class.
 *
 * @since 0.1
 */

namespace CFCR\Api\Rest;

class Civi extends \WP_REST_Controller {

	/**
	 * Route namespace.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $namespace = 'cfcr-api/v2';

	/**
	 * The base route.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $rest_base = 'crm';

	/**
	 * Registers routes.
	 *
	 * @since 0.1
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::ALLMETHODS,
				'callback' => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args' => $this->get_item_args()
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
	public function permissions_check( $request ) {

		$capability = apply_filters( 'cfcr/api/rest/civi/cap', 'manage_options', $request );

		if ( ! current_user_can( $capability ) )
			return new \WP_Error( 'rest_forbidden', __( 'You don\'t have enough permissions.' ), [ 'status' => $this->authorization_status_code() ] );

		return true;

	}

	/**
	 * Get items.
	 *
	 * @since 0.1
	 * @param WP_REST_Request $request
	 */
	public function get_items( $request ) {

		$params = $this->get_formatted_api_params( $request );

		try {

			$items = civicrm_api3( ...$params );

		} catch ( \CiviCRM_API3_Exception $e ) {

			return new \WP_Error( 'civicrm_rest_api_error', $e->getMessage(), [ 'status' => $this->authorization_status_code() ] );

		}

		if ( ! isset( $items ) || empty( $items ) )
			return rest_ensure_response( [] );

		$data = $items;

		$data['values'] = array_reduce( $items['values'], function( $items, $item ) use ( $request ) {

			$response = $this->prepare_item_for_response( $item, $request );

			$items[] = $this->prepare_response_for_collection( $response );

			return $items;

		}, [] );

		$data = apply_filters( 'cfcr/api/rest/civi/before_return_result', $data, $request );

		return rest_ensure_response( $data );

	}

	/**
	 * Get formatted api params.
	 *
	 * @since 0.1
	 * @param WP_REST_Resquest $request
	 * @return array $params
	 */
	public function get_formatted_api_params( $request ) {

		$args = $request->get_params();

		$entity = $args['entity'];
		$action = $args['action'];
		$params = is_string( $args['json'] ) ? json_decode( $args['json'], true ) : $args['json'];

		return apply_filters( 'cfcr/api/rest/civi/formatted_params', [ $entity, $action, $params ], $request );

	}

	/**
	 * Matches the item data to the schema.
	 *
	 * @since 0.1
	 * @param object $item
	 * @param WP_REST_Request $request
	 */
	public function prepare_item_for_response( $item, $request ) {

		return rest_ensure_response( $item );

	}

	/**
	 * Item schema.
	 *
	 * @since 0.1
	 * @return array $schema
	 */
	public function get_item_schema() {

		return [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => 'civicrm_api3',
			'description' => 'CiviCRM API3 wrapper',
			'type' => 'object',
			'required' => [ 'entity', 'action', 'params' ],
			'properties' => [
				'is_error' => [
					'type' => 'integer'
				],
				'version' => [
					'type' => 'integer'
				],
				'count' => [
					'type' => 'integer'
				],
				'values' => [
					'type' => 'array'
				]
			]
		];

	}

	/**
	 * Item arguments.
	 *
	 * @since 0.1
	 * @return array $arguments
	 */
	public function get_item_args() {

		return [
			'entity' => [
				'type' => 'string',
				'required' => true,
				'validate_callback' => function( $value, $request, $key ) {

					return is_string( $value );

				}
			],
			'action' => [
				'type' => 'string',
				'required' => true,
				'validate_callback' => function( $value, $request, $key ) {

					return is_string( $value );

				}
			],
			'json' => [
				'type' => ['string', 'array'],
				'validate_callback' => function( $value, $request, $key ) {

					return is_array( $value ) || $this->is_valid_json( $value );

				}
			]
		];

	}

	/**
	 * Checks if param is string and is valid json.
	 *
	 * @since 0.1
	 * @param string $param
	 * @return bool
	 */
	protected function is_valid_json( $param ) {

		if ( ! is_string( $param ) ) return false;

		$param = json_decode( $param, true );

		if ( ! is_array( $param ) ) return false;

 		return ( json_last_error() == JSON_ERROR_NONE );

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
