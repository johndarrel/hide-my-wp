<?php
/**
 * The main class for controllers
 *
 * @package HMWP/Main
 * @file The Front Controller file
 *
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Classes_FrontController
{

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
     * HMWP_Classes_FrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {

        /* get the name of the current class */
        $this->name = get_class($this);

        /* load the model and hooks here for WordPress actions to take efect */
        /* create the model and view instances */
        $model_classname = str_replace('Controllers', 'Models', $this->name);
        if(HMWP_Classes_ObjController::getClassByPath($model_classname)) {
            $this->model = HMWP_Classes_ObjController::getClass($model_classname);
        }

        //IMPORTANT TO LOAD HOOKS HERE
        /* check if there is a hook defined in the controller clients class */
        HMWP_Classes_ObjController::getClass('HMWP_Classes_HookController')->setHooks($this);

        /* Set the debug if activated */
        if (defined('HMWP_DEBUG') && HMWP_DEBUG) {
            HMWP_Classes_ObjController::getClass('HMWP_Classes_Debug');
        }

        /* Load the rewrite */
        HMWP_Classes_ObjController::getClass('HMWP_Controllers_Rewrite');

        /* Load the Main classes Actions Handler */
        HMWP_Classes_ObjController::getClass('HMWP_Classes_Action');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController');
        HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility_Abstract');

    }

    /**
     * load sequence of classes
     * Function called usualy when the controller is loaded in WP
     *
     * @return HMWP_Classes_FrontController
     * @throws Exception
     */
    public function init()
    {
        return $this;
    }

    /**
     * Get the block view
     *
     * @param  string $view
     * @param  stdClass $obj
     * @return string HTML
     * @throws Exception
     */
    public function getView($view = null, $obj = null)
    {
        if(!isset($obj)) {
            $obj = $this;
        }

        //Get the view class name if not defined
        if (!isset($view)) {
            if ($class = HMWP_Classes_ObjController::getClassByPath($this->name)) {
                $view = $class['name'];
            }
        }

        //Call the display class to load the view
        if (isset($view)) {
            $this->view = HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController');
            return $this->view->getView($view, $obj);
        }

        return '';
    }

    /**
     * Called as menu callback to show the block
     *
     * @param  string $view
     * @throws Exception
     */
    public function show($view = null)
    {
        echo $this->getView($view);
    }

    /**
     * first function call for any class on form submit
     */
    protected function action()
    {
        // called within each class with the action
    }


    /**
     * initialize settings
     * Called from index
     *
     * @return void
     */
    public function hookInit()
    { 
    }


    /**
     * Called on frontend. For disconnected users
     */
    public function hookFrontinit()
    { 
    }

    /**
     * Hook the admin head
     * This function will load the media in the header for each class
     *
     * @return void
     */
    public function hookHead()
    { 
    }

}
