<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_Widget extends HMW_Classes_FrontController {

    public $riskreport = array();
    public $risktasks;

    public function dashboard() {
        $this->risktasks = HMW_Classes_ObjController::getClass('HMW_Controllers_SecurityCheck')->getRiskTasks();
        $this->riskreport =HMW_Classes_ObjController::getClass('HMW_Controllers_SecurityCheck')->getRiskReport();


	    //Show Hide My WP Offer
	    if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'lite' && date( 'Y-m-d' ) >= '2020-11-27' && date( 'Y-m-d' ) < '2020-11-01' ) {
		    HMW_Classes_Error::showError( sprintf( __( '%sBlack Friday!!%s Get Hide My WP Ghost today with the best discounts of the year. %sSee Ofers!%s', _HMW_PLUGIN_NAME_ ), '<strong style="color: red; font-size: 16px;">', '</strong>', '<a href="https://hidemywpghost.com/hide-my-wp-ghost-black-friday-offer/" target="_blank" style="font-weight: bold">', '</a>' ) );
	    }elseif ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'lite' && date( 'Y-m-d' ) >= '2020-10-28' && date( 'Y-m-d' ) < '2020-11-01' ) {
		    HMW_Classes_Error::showError( sprintf( __( '%sHalloween Special!!%s Get %s80%% OFF%s on Hide My WP Ghost - Unlimited Websites License until 31 October 2020. %sSee Ofer!%s', _HMW_PLUGIN_NAME_ ), '<strong style="color: red; font-size: 16px;">', '</strong>', '<strong style="color: red">', '</strong>', '<a href="https://hidemywpghost.com/hide-my-wp-ghost-halloween-offer/" target="_blank" style="font-weight: bold">', '</a>' ) );
	    }elseif ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'lite' && date( 'm' ) <> 10 && date( 'm' ) <> 11 && ((date( 'd' ) >= 15 && date( 'd' ) <= 20) || (date( 'd' ) >= 25 && date( 'd' ) <= 30))  ) {
		    HMW_Classes_Error::showError( sprintf( __( '%sLimited Time Offer%s: Get %s65%% OFF%s today on Hide My WP Ghost 5 Websites License. %sHurry Up!%s', _HMW_PLUGIN_NAME_ ), '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold;"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold">', '</a>' ) );
	    }

	    echo '<script>var hmwQuery = {"ajaxurl": "' . admin_url( 'admin-ajax.php' ) . '","nonce": "' . wp_create_nonce( _HMW_NONCE_ID_ ) . '"}</script>';
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
