<?php

/**
 * Caldera Forms CiviCRM Redirect form class.
 *
 * @since 0.3
 */
class CRM_Event_Form_ManageEvent_CFCR extends CRM_Event_Form_ManageEvent {

	/**
	 * The elements to render on the form.
	 *
	 * @since 0.3
	 * @var array
	 */
	protected $elementsToRender = [
		'cfcr_is_active',
		'cfcr_post_or_page',
		'cfcr_post',
		'cfcr_page'
	];

	/**
	 * The event data.
	 *
	 * @since 0.3
	 * @var array
	 */
	protected $event = [];

	/**
	 * The redirect data.
	 *
	 * @since 0.3
	 * @var null|array
	 */
	protected $redirect = null;

	/**
	 * The posts to show on the form.
	 *
	 * @since 0.3
	 * @var array
	 */
	protected $posts = [];

	/**
	 * The pages to show on the form.
	 *
	 * @since 0.3
	 * @var array
	 */
	protected $pages = [];

	/**
	 * Redirect DB api.
	 *
	 * @since 0.3
	 * @var \CFCR\Api\DB
	 */
	protected $redirectApi;

	/**
	 * Called prior to building and submitting the form.
	 *
	 * @since 0.3
	 */
	public function preProcess() {

		// add script
		Civi::resources()->addScriptUrl( CFC_REDIRECT_URL . 'assets/src/civi/cfcr.js' );

		parent::preProcess();

		// get event data
		$params = ['id' => $this->getEventId() ];
		CRM_Event_BAO_Event::retrieve($params, $this->event);

		// init redirect db api
		$this->redirectApi = new \CFCR\Api\DB;

		if ( $redirect = $this->getRedirectByEntityId( $this->getEventId() ) ) {

			$this->redirect = $redirect;

			// set default values
			$this->setDefaultValues();

		};

	}

	/**
	 * Called before to outputting html.
	 *
	 * @since 0.3
	 */
	public function buildQuickForm() {

		$this->add(
			'checkbox',
			'cfcr_is_active',
			ts( 'Is active?' )
		);

		$this->addSelect(
			'cfcr_post_or_page',
			[
				'label' => ts( 'Post type' ),
				'placeholder' => ts( 'Select a Post type' ),
				'options' => [
					'page' => ts( 'Page' ),
					'post' => ts( 'Post' )
				],
				'class' => 'huge'
			],
			true
		);

		$this->addSelect(
			'cfcr_page',
			[
				'label' => ts( 'Pages' ),
				'placeholder' => ts( 'Select a Page' ),
				'options' => $this->getWPPosts( $type = 'page' ),
				'class' => 'huge'
			]
		);

		$this->addSelect(
			'cfcr_post',
			[
				'label' => ts( 'Posts' ),
				'placeholder' => ts( 'Select a Post' ),
				'options' => $this->getWPPosts(),
				'class' => 'huge'
			]
		);

		// form elements
		$this->assign( 'formElements', $this->getElementsToRender() );

		// redirect page link
		$this->assign( 'manageRedirects', admin_url( 'admin.php?page=caldera-forms-civicrm-redirect' ) );

		parent::buildQuickForm();

	}

	/**
	 * Called after form is successfully submitted.
	 *
	 * @since 0.3
	 */
	public function postProcess() {

		if ( empty( $this->event ) ) return;

		// form values
		$values = $this->exportValues();

		// get post id for page or post
		$postId = $values['cfcr_post_or_page'] == 'post'
				? $values['cfcr_post']
				: $values['cfcr_page'];

		// redirect params
		$redirect = [
			'entity_id' => $this->getEventId(),
			'page_type' => 'event',
			'is_active' => ! empty( $values['cfcr_is_active'] )
				? $values['cfcr_is_active']
				: 0,
			'post_type' => $values['cfcr_post_or_page'],
			'post_id' => $postId,
			'page_title' => $this->isTemplate()
				? CRM_Utils_Array::value( 'template_title', $this->event )
				: CRM_Utils_Array::value( 'title', $this->event ),
			'post_title' => $this->getWPPosts( $values['cfcr_post_or_page'] )[$postId]
		];

		// update or create a redirect
		if ( ! empty( $this->redirect->id ) ) {

			$redirect['id'] = $this->redirect->id;

			// update redirect
			$this->redirectApi->update( $redirect, ['id' => $redirect['id']] );

		} else {

			$this->redirectApi->insert( $redirect );

		}

		parent::postProcess();

	}

	/**
	 * Adds form rules.
	 *
	 * @since 0.3
	 */
	public function addRules() {

		$this->addFormRule( [ $this, 'validateForm' ] );

	}

	/**
	 * Validates the form before submission.
	 *
	 * @since 0.3
	 * @param array $values The submitted values
	 * @return array|bool $errors The errors array or true
	 */
	public function validateForm( $values ) {

		$errors = [];
		if ( ! empty( $values['cfcr_is_active'] ) && empty( $values['cfcr_post_or_page'] ) ) {
			$errors['cfcr_post_or_page'] = ts( 'Post type is required.' );
		};

		if ( ! empty( $values['cfcr_post_or_page'] ) ) {

			$fieldName = 'cfcr_' . $values['cfcr_post_or_page'];

			if ( empty( $values[$fieldName] ) ) {

				$errors[$fieldName] = ts( $values['cfcr_post_or_page'] . ' is required.' );

			}

		}

		return empty( $errors ) ? true : $errors;

	}

	/**
	 * Retrieves the form renderable elements.
	 *
	 * @since 0.3
	 * @return array $elements The form elements to render
	 */
	protected function getElementsToRender() {

		return array_reduce( $this->_elements, function( $elements, $element ) {

			if ( ! in_array( $element->getName(), $this->elementsToRender ) ) return $elements;

			$elements[] = $element->getName();
			return $elements;

		}, [] );

	}

	/**
	 * Retrieves the event id
	 *
	 * @since 0.3
	 * @return int $eventId The Event id
	 */
	protected function getEventId() {
		return $this->_id;
	}

	/**
	 * Retrieves wheather this a template event.
	 *
	 * @since 0.3
	 * @return boolean
	 */
	protected function isTemplate() {
		return $this->_isTemplate;
	}

	/**
	 * Retrives the redirect data for an event.
	 *
	 * @since 0.3
	 * @param int $id The event id
	 * @return object $redirect The redirect data
	 */
	protected function getRedirectByEntityId( $id ) {

		if ( empty( $id ) ) return false;

		// return redirect
		return $this->redirectApi->get_by_entity_id( $id, $type = 'event' );

	}

	/**
	 * Sets the default values for this form.
	 *
	 * @since 0.3
	 */
	public function setDefaultValues() {

		if ( ! $this->redirect ) return;

		$defaults = [
			'cfcr_is_active' => $this->redirect->is_active,
			'cfcr_post_or_page' => $this->redirect->post_type
		];

		if ( $this->redirect->post_type == 'post' ) {

			$defaults['cfcr_post'] = $this->redirect->post_id;

		} else {

			$defaults['cfcr_page'] = $this->redirect->post_id;

		}

		$this->setDefaults( $defaults );
	}

	/**
	 * Retrieves the posts or pages.
	 *
	 * @since 0.3
	 * @param  string $postType The post type
	 * @return array $posts The posts
	 */
	protected function getWPPosts( string $postType = 'post' ) {

		// get posts if set
		$postsResult = $postType == 'post' ? $this->posts : $this->pages;

		if ( ! empty( $postsResult ) ) return $postsResult;

		// query posts
		$query = new WP_Query( [
			'post_type' => $postType,
			'post_status' => 'publish',
			'posts_per_page' => -1
		] );

		// build result
		$result = array_reduce( (array) $query->posts, function( $posts, $post ) {

			$posts[$post->ID] = $post->post_title;
			return $posts;

		}, [] );

		// store result
		if ( $postType == 'post' ) {

			$this->posts = $result;

		} else {

			$this->pages = $result;

		}

		return $result;

	}


}
