<?php
/**
 * Background Cron action
 *
 * @file The Cron file
 * @package HMWP/Cron
 * @since 4.0.0
 */

class HMWP_Controllers_Cron
{

    /**
     * HMWP_Controllers_Cron constructor.
     */
    public function __construct()
    {
        add_filter('cron_schedules', array($this, 'setInterval'));

        //Activate the cron job if not exists.
        if (!wp_next_scheduled(HMWP_CRON)) {
            wp_schedule_event(time(), 'hmwp_every_minute', HMWP_CRON);
        }
    }

    /**
     * Specify the Cron interval
     *
     * @param  $schedules
     * @return mixed
     */
    function setInterval($schedules)
    {
        $schedules['hmwp_every_minute'] = array(
            'display' => 'every 1 minute',
            'interval' => 60
        );
        return $schedules;
    }

    /**
     * Process Cron
     *
     * @throws Exception
     */
    public function processCron()
    {
        //Check the cache plguins and change the paths in the cache files.
        HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->checkCacheFiles();
    }


}
