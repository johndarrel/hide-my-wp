<?php
defined('ABSPATH') || die('Cheatin\' uh?');

/**
 * Set the ajax action and call for wordpress
 */
class HMW_Classes_Action extends HMW_Classes_FrontController {

    /** @var array with all form and ajax actions */
    var $actions = array();

    /** @var array from core config */
    private static $config;


    /**
     * The hookAjax is loaded as custom hook in hookController class
     *
     * @return void
     */
    public function hookInit() {
        if (HMW_Classes_Tools::isAjax()) {
            $this->getActions(true);
        }
    }

    function hookFrontinit() {
        /* Only if post */
        if (HMW_Classes_Tools::isAjax()) {
            $this->getActions();
        }
    }

    /**
     * The hookSubmit is loaded when action si posted
     *
     * @return void
     */
    function hookMenu() {
        /* Only if post */
        if (!HMW_Classes_Tools::isAjax()) {
            $this->getActions();
        }
    }

    function hookMultisiteMenu() {
        /* Only if post */
        if (!HMW_Classes_Tools::isAjax()) {
            $this->getActions();
        }
    }


    /**
     * Get all actions from config.json in core directory and add them in the WP
     *
     * @param boolean $ajax
     * @return void
     */
    public function getActions($ajax = false) {
        $this->actions = array();
        $action = HMW_Classes_Tools::getValue('action');
        $nonce = HMW_Classes_Tools::getValue('hmw_nonce');

        if ($action == '' || $nonce == '') {
            return;
        }

        /* if config allready in cache */
        if (!isset(self::$config)) {
            $config_file = _HMW_ROOT_DIR_ . '/config.json';
            if (!file_exists($config_file))
                return;

            /* load configuration blocks data from core config files */
            self::$config = json_decode(file_get_contents($config_file), 1);
        }

        if (is_array(self::$config))
            foreach (self::$config['blocks']['block'] as $block) {
                if (isset($block['active']) && $block['active'] == 1)
                    if (isset($block['admin']) &&
                        (($block['admin'] == 1 && (is_admin() || is_network_admin())) ||
                            $block['admin'] == 0)
                    ) {
                        /* if there is a single action */
                        if (isset($block['actions']['action']))

                            /* if there are more actions for the current block */
                            if (!is_array($block['actions']['action'])) {
                                /* add the action in the actions array */
                                if ($block['actions']['action'] == $action)
                                    $this->actions[] = array('class' => $block['name']);
                            } else {
                                /* if there are more actions for the current block */
                                foreach ($block['actions']['action'] as $value) {
                                    /* add the actions in the actions array */
                                    if ($value == $action)
                                        $this->actions[] = array('class' => $block['name']);
                                }
                            }
                    }
            }

        if ($ajax) {
            check_ajax_referer(_HMW_NONCE_ID_, 'hmw_nonce');
        } else {
            check_admin_referer($action, 'hmw_nonce');
        }
        /* add the actions in WP */
        foreach ($this->actions as $actions) {
            HMW_Classes_ObjController::getClass($actions['class'])->action();
        }
    }

}