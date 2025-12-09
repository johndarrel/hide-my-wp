<?php
/**
 * The main class for controllers
 *
 * @package HMWP/Main
 * @file The Front Controller file
 *
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * The class FrontController from /classes
 *
 * @var object of the model class
 */
class HMWP_Classes_FrontController {

	/**
	 * The class Model from /models
	 *
	 * @var object of the model class
	 */
	public $model;

	/**
	 * The class view from /views
	 *
	 * @var object of the view class
	 */
	public $view;

	/**
	 * The class name
	 *
	 * @var string name of theclass
	 */
	protected $name;

	/**
	 * Constructor initializes the class by setting up model and hook instances, checking for debug mode,
	 * and loading necessary controller and handler classes for WordPress actions.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function __construct() {

		// Get the name of the current class
		$this->name = get_class( $this );

		// Load the model and hooks here for WordPress actions to take effect
		// Create the model and view instances
		$model_classname = str_replace( 'Controllers', 'Models', $this->name );
		if ( HMWP_Classes_ObjController::getClassByPath( $model_classname ) ) {
			$this->model = HMWP_Classes_ObjController::getClass( $model_classname );
		}

		//IMPORTANT TO LOAD HOOKS HERE
		// Check if there is a hook defined in the controller clients class
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_HookController' )->setHooks( $this );

		// Set the debug if activated in wp_config file
		if ( defined( 'HMWP_DEBUG' ) && HMWP_DEBUG ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Debug' );
		}

		// Load the rewrite
		HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Rewrite' );

		// Load the Main classes Actions Handler
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_Action' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility_Abstract' );
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Abstract' );

	}

	/**
	 * Initializes the instance
	 * This function prepares and returns the current instance
	 *
	 * @return $this The current instance
	 */
	public function init() {
		return $this;
	}

	/**
	 * Retrieve and display the specified view
	 * Similar to the MVC pattern, this method fetches and renders a view.
	 *
	 * @param  string|null  $view  The name of the view to be fetched. If not provided, it will attempt to derive it.
	 * @param  object|null  $obj  The object related to the view context. Defaults to the current object if not specified.
	 *
	 * @return string The rendered view, or an empty string if the view could not be determined.
	 */
	public function getView( $view = null, $obj = null ) {
		if ( ! isset( $obj ) ) {
			$obj = $this;
		}

		// Get the view class name if not defined
		if ( ! isset( $view ) ) {
			if ( $class = HMWP_Classes_ObjController::getClassByPath( $this->name ) ) {
				$view = $class['name'];
			}
		}

		// Call the display class to load the view
		if ( isset( $view ) ) {
			$this->view = HMWP_Classes_ObjController::getClass( 'HMWP_Classes_DisplayController' );

			return $this->view->getView( $view, $obj );
		}

		return '';
	}

	/**
	 * Displays the specified view
	 * This function will output the content of the provided view.
	 *
	 * @param  string|null  $view  The name of the view to display. If null, a default view will be used.
	 *
	 * @return void
	 */
	public function show( $view = null ) {
		echo $this->getView( $view );
	}

	/**
	 * Performs the designated action.
	 * This method is called within each class that defines an action.
	 *
	 * @return void
	 */
	protected function action() {
		// Called within each class with the action
	}


	/**
	 * Initialize the hook
	 * This function executes initial setup tasks when the hook is triggered
	 *
	 * @return void
	 */
	public function hookInit() {
		// Called within each class with the action
	}


	/**
	 * Hook the front initialization
	 * This function will be called during the front office initialization process
	 *
	 * @return void
	 */
	public function hookFrontinit() {
		// Called within each class with the action
	}

	/**
	 * Executes actions or filters to be applied to the head section of the HTML document.
	 * This method is typically used to insert additional scripts, styles, or meta tags.
	 *
	 * @return void
	 */
	public function hookHead() {
		// Called within each class with the action
	}

}
