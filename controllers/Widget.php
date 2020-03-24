<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_Widget extends HMW_Classes_FrontController {

    public $riskreport = array();
    public $risktasks;

    public function dashboard() {
        $this->risktasks = HMW_Classes_ObjController::getClass('HMW_Controllers_SecurityCheck')->getRiskTasks();
        $this->riskreport =HMW_Classes_ObjController::getClass('HMW_Controllers_SecurityCheck')->getRiskReport();

        echo $this->getView('Dashboard');
    }

    public function action() {
        parent::action();

        if (!current_user_can('manage_options')) {
            return;
        }

        switch (HMW_Classes_Tools::getValue('action')) {
            case 'hmw_widget_securitycheck':
                HMW_Classes_ObjController::getClass('HMW_Controllers_SecurityCheck')->doSecurityCheck();

                ob_start();
                $this->dashboard();
                $output = ob_get_clean();

                HMW_Classes_Tools::setHeader('json');
                echo json_encode(array('data' => $output));
                exit;

        }
    }
}
