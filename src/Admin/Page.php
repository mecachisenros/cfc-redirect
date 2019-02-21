<?php
/**
 * Admin settings page class.
 *
 * @since 0.1
 */

namespace CFCR\Admin;

class Page {

	/**
	 * Admin parent page.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $parent_page = \Caldera_Forms::PLUGIN_SLUG;

	/**
	 * Admin page suffix.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $page_suffix = '-civicrm-redirect';

	/**
	 * Page title.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $page_title;

	/**
	 * Menu title.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $menu_title;

	/**
	 * Hook suffix.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $hook_suffix;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		$this->page_title = __( 'Caldera Forms CiviCRM Redirect', 'caldera-forms-civicrm-redirect' );
		$this->menu_title = __( 'CiviCRM Redirect', 'caldera-forms-civicrm-redirect' );

		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		add_action( 'admin_menu', [ $this, 'register_page' ] );

		add_filter( 'plugin_action_links', [ $this, 'add_plugin_action_links' ], 10, 2 );

	}

	/**
	 * Registers menu page.
	 *
	 * @since 1.0
	 */
	public function register_page() {

		$capability = apply_filters( 'cfcr/admin/settings/cap', 'manage_options' );

		if ( ! current_user_can( $capability ) ) return;

		$this->hook_suffix = add_submenu_page(
			$this->parent_page,
			$this->page_title,
			$this->menu_title,
			$capability,
			$this->parent_page . $this->page_suffix,
			[ $this, 'render_container' ]
		);

		add_action( 'load-' . $this->hook_suffix, [ $this, 'initialize_app' ] );

	}

	/**
	 * Add Settings link to plugin listing page.
	 *
	 * @since 0.1
	 * @param array $links
	 * @param strinf $file
	 */
	public function add_plugin_action_links( $links, $file ) {


		if ( $file != CFC_REDIRECT_BASE ) return $links;

		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>', 
			admin_url( 'admin.php?page=' . $this->parent_page . $this->page_suffix ), 
			__( 'Settings' )
		);

		return $links;

	}

	/**
	 * Renders the app container.
	 *
	 * @since 0.1
	 */
	public function render_container() {

		$app_container = apply_filters( 'cfcr/admin/page/app_container', '<div id="cfcr-app"></div>' );

		echo '<div class=wrap>' . $app_container . '</div>';

	}

	/**
	 * Enqueues and initializes the app.
	 *
	 * @since 0.1
	 */
	public function initialize_app() {

		$app_state = apply_filters( 'cfcr/admin/page/app_state', [
			'restBase' => rest_url(),
			'wpBase' => rest_url( 'wp/v2' ),
			'redirectBase' => rest_url( 'cfcr-api/v2/r' ),
			'crmBase' => rest_url( 'cfcr-api/v2/crm' ),
			'nonce' => wp_create_nonce( 'wp_rest' )
		] );

		wp_enqueue_script( 'cfc-redirect', CFC_REDIRECT_URL . 'assets/dist/bundle.js', [], CFC_REDIRECT_VERSION, true );

		/**
		 * Opportunity to register more scripts.
		 *
		 * @since 0.1
		 */
		do_action( 'cfcr/admin/page/init_app' );

		wp_localize_script( 'cfc-redirect', 'State', $app_state );
		
	}
}
