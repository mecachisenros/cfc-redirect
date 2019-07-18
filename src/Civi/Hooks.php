<?php
/**
 * Civi Hooks class.
 *
 * @since 0.3
 */

namespace CFCR\Civi;

class Hooks {

	/**
	 * Redirect DB api.
	 *
	 * @since 0.3
	 * @var \CFCR\Api\DB
	 */
	protected $redirectApi;

	/**
	 * Constructor.
	 *
	 * @since 0.3
	 */
	public function __construct() {

		$this->redirectApi = new \CFCR\Api\DB;

		$this->register_hooks();

	}

	/**
	 * Registers hooks.
	 *
	 * @since 0.3
	 */
	public function register_hooks() {

		// register custom php directory
		add_action( 'civicrm_config', [ $this, 'register_custom_php_directory' ], 1, 1 );
		// register custom template directory
		add_action( 'civicrm_config', [ $this, 'register_custom_tpl_directory' ], 1, 1 );
		// register menu file
		add_action( 'civicrm_xmlMenu', [ $this, 'register_xml_menu_file' ], 10, 1 );
		// add redirect tab
		add_action( 'civicrm_tabset', [ $this, 'add_redirect_tab' ], 10, 3 );
		// add redirect link in manage events
		// add_action( 'civicrm_links', [ $this, 'add_redirect_link' ], 10, 6 );
		// add redirect from template
		add_action( 'civicrm_post', [ $this, 'create_redirect_from_template' ], 10, 4 );
		// delete redirect on event deletion
		add_action( 'civicrm_post', [ $this, 'delete_redirect_for_event' ], 10, 4 );

	}

	/**
	 * Registers the CiviCRM menu file path.
	 *
	 * @since 0.3
	 * @param array &$files The menu file paths
	 */
	public function register_xml_menu_file( &$files ) {

		$files[] = CFC_REDIRECT_SRC . '/xml/menu.xml';

	}

	/**
	 * Registers the CiviCRM custom php directory.
	 *
	 * @uses 'hook_civicrm_config'
	 * @since 0.3
	 */
	public function register_custom_php_directory() {

		$custom_path = CFC_REDIRECT_SRC . '/CustomPHP';
		$include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $include_path );

	}

	/**
	 * Registers the CiviCRM template directory.
	 *
	 * @uses 'hook_civicrm_config'
	 * @since 0.3
	 */
	public function register_custom_tpl_directory() {

		$custom_path = CFC_REDIRECT_SRC . '/CustomTpl';
		\CRM_Core_Smarty::singleton()->addTemplateDir( $custom_path );
		$include_template_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $include_template_path );

	}


	/**
	 * Adds the Caldera Forms Redirect tab to the events screen.
	 *
	 * @uses 'hook_civicrm_tabset'
	 * @since 0.3
	 * @param string $tabset_name Name of the screen or visual elemen
	 * @param array &$tabs The array of tabs that will be displayed
	 * @param array $context Extra data about the screen or context in which the tab is used
	 */
	public function add_redirect_tab( $tabset_name, &$tabs, $context ) {

		if ( $tabset_name != 'civicrm/event/manage' ) return;

		if ( empty( $context['event_id'] ) ) return;

		$tabs['cfcr'] = [
			'title' => __( 'Caldera Forms Redirect' ),
			'link' => \CRM_Utils_System::url( 'civicrm/event/manage/cfcr', ['id' => $context['event_id']] ),
			'valid' => $this->redirectApi->get_by_entity_id( $context['event_id'], 'event' ),
			'active' => true,
			'class' => 'ajaxForm'
		];

	}

	/**
	 * Adds the Caldera Forms Civicrm Redirect link.
	 *
	 * @uses 'hook_civicrm_links'
	 * @since 0.3
	 * @param string $operation The context in which the links appear,
	 * @param string $object_name The entity the links relate
	 * @param int $object_id The CiviCRM internal ID of the entity
	 * @param array &$links The links to modify in place
	 * @param int &$mask A bitmask that will fiter $links
	 * @param array &$values The values to fill $links['url'], $links['qs']
	 */
	public function add_redirect_link( $operation, $object_name, $object_id, &$links, &$mask, &$values ) {

		if ( $operation != 'event.manage.list' ) return;

		if ( $object_name != 'Event' ) return;

		$links[] = [
			'name' => __( 'Caldera Forms Redirect' ),
			'url' => 'civicrm/event/manage/cfcr',
			'qs' => 'reset=1&id=%%id%%'
		];

	}

	/**
	 * Creates a redirect for an event created from a template.
	 *
	 * @uses 'hook_civicrm_post'
	 * @since 0.3
	 * @param string $operation The operation being performed on a CiviCRM object
	 * @param string $object_name The CiviCRM entity name
	 * @param int $object_id The unique identifier for the object
	 * @param object &$object The reference to the object
	 */
	public function create_redirect_from_template( $operation, $object_name, $object_id, &$object ) {

		if ( $object_name != 'EventTemplate' && $operation != 'create' ) return;

		if ( empty( $object->template_title ) ) return;

		// get template
		$template = civicrm_api3( 'Event', 'get', [
			'is_template' => 1,
			'template_title' => $object->template_title
		] );

		// bail if we have more than one template or none
		if ( $template['count'] > 1 || ! $template['count'] ) return;

		$redirect = $this->redirectApi->get_by_entity_id( $template['id'], 'event' );

		if ( ! $redirect ) return;

		$this->copy_redirect_for_event( (array) $redirect, (array) $object );

	}

	/**
	 * Deletes a redirect when the event is deleted.
	 *
	 * @uses 'hook_civicrm_post'
	 * @since 0.3
	 * @param string $operation The operation being performed on a CiviCRM object
	 * @param string $object_name The CiviCRM entity name
	 * @param int $object_id The unique identifier for the object
	 * @param object &$object The reference to the object
	 */
	public function delete_redirect_for_event( $operation, $object_name, $object_id, &$object ) {

		if ( $object_name != 'Event' ) return;

		if ( $operation != 'delete' ) return;

		$redirect = $this->redirectApi->get_by_entity_id( $object_id, 'event' );

		if ( ! $redirect ) return;

		$this->redirectApi->delete( (array) $redirect );

	}

	/**
	 * Copies/creates a ridrect for a given event.
	 *
	 * @since 0.3
	 * @param array $redirect The redirect to copy
	 * @param array $event The event settings
	 */
	public function copy_redirect_for_event( array $redirect, array $event ) {

		// update params
		$redirect['page_title'] = $event['title'];
		$redirect['entity_id'] = $event['id'];
		unset( $redirect['id'] );

		// create redirect for the new event
		$result = $this->redirectApi->insert( $redirect );

	}

}
