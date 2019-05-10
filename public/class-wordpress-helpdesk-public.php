<?php

class WordPress_Helpdesk_Public
{
    private $plugin_name;
    private $version;
    private $options;

    /**
     * Store Locator Plugin Construct
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @param   string                         $plugin_name
     * @param   string                         $version
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Enqueue Styles
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  boolean
     */
    public function enqueue_styles()
    {
        global $wordpress_helpdesk_options;

        $this->options = $wordpress_helpdesk_options;

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__).'css/wordpress-helpdesk-public.css', array(), $this->version, 'all');
        wp_enqueue_style('font-awesome', plugin_dir_url(__FILE__).'vendor/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0', 'all');
        wp_enqueue_style('Luminous', plugin_dir_url(__FILE__).'vendor/luminous-2.2.1/dist/luminous-basic.min.css', array(), '2.2.1', 'all');
        wp_enqueue_style('datatables', plugin_dir_url(__FILE__).'vendor/DataTables/DataTables-1.10.18/css/jquery.dataTables.min.css', array(), '1.10.18', 'all');
        $customCSS = $this->get_option('customCSS');

        file_put_contents(dirname(__FILE__)  . '/css/wordpress-helpdesk-custom.css', $customCSS);

        wp_enqueue_style($this->plugin_name.'-custom', plugin_dir_url(__FILE__) . 'css/wordpress-helpdesk-custom.css', array(), $this->version, 'all');

        return true;
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  boolean
     */
    public function enqueue_scripts()
    {
        global $wordpress_helpdesk_options;

        $this->options = $wordpress_helpdesk_options;

        wp_enqueue_script('push', plugin_dir_url(__FILE__).'vendor/push-js/bin/push.min.js', array('jquery'), '1.0.7', true);
        wp_enqueue_script('Luminous', plugin_dir_url(__FILE__).'vendor/luminous-2.2.1/dist/Luminous.min.js', array('jquery'), '2.2.1', true);
        wp_enqueue_script('jquery-datatables', plugin_dir_url(__FILE__).'vendor/DataTables/DataTables-1.10.18/js/jquery.dataTables.min.js', array('jquery'), '1.10.18', true);
        wp_enqueue_script($this->plugin_name.'-public', plugin_dir_url(__FILE__).'js/wordpress-helpdesk-public.js', array('jquery', 'push', 'Luminous', 'jquery-datatables'), $this->version, true);
        
        $forJS = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'integrationsWooCommerce' => $this->get_option('integrationsWooCommerce'),
            // Live Chat
            'enableLiveChat' => $this->get_option('enableLiveChat'),
            'liveChatAJAXInterval' => $this->get_option('liveChatAJAXInterval') ?  $this->get_option('liveChatAJAXInterval') : 2000,
            // FAQ
            'FAQShowSearch' => $this->get_option('FAQShowSearch'),
            'FAQRatingEnable' => $this->get_option('FAQRatingEnable'),
            // Desktop Notifications
            'enableDesktopNotifications' => $this->get_option('enableDesktopNotifications'),
            'desktopNotificationsWelcomeTitle' => $this->get_option('desktopNotificationsWelcomeTitle'),
            'desktopNotificationsWelcomeText' => $this->get_option('desktopNotificationsWelcomeText'),
            'desktopNotificationsIcon' => $this->get_option('desktopNotificationsIcon')['url'],
            'desktopNotificationsTimeout' => $this->get_option('desktopNotificationsTimeout'),
            'desktopNotificationsWelcomeTimeout' => $this->get_option('desktopNotificationsWelcomeTimeout'),
            'desktopNotificationsAJAXInterval' => $this->get_option('desktopNotificationsAJAXInterval') ?  $this->get_option('desktopNotificationsAJAXInterval') : 2000,
            // Datatables
            'myTicketsDatatablesEnable' => $this->get_option('myTicketsDatatablesEnable'),
            'myTicketsDatatablesLanguageURL' => '//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/' . $this->get_option('myTicketsDatatablesLanguage') . '.json',
        );
        wp_localize_script($this->plugin_name.'-public', 'helpdesk_options', $forJS);

        $customJS = $this->get_option('customJS');
        if (empty($customJS)) {
            return false;
        }

        file_put_contents(dirname(__FILE__)  . '/js/wordpress-helpdesk-custom.js', $customJS);

        wp_enqueue_script($this->plugin_name.'-custom', plugin_dir_url(__FILE__).'js/wordpress-helpdesk-custom.js', array('jquery'), $this->version, false);

        return true;
    }

    /**
     * Get Options
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @param   mixed                         $option The option key
     * @return  mixed                                 The option value
     */
    private function get_option($option)
    {
        if (!is_array($this->options)) {
            return false;
        }

        if (!array_key_exists($option, $this->options)) {
            return false;
        }

        return $this->options[$option];
    }

    /**
     * Init the Public
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  boolean
     */
    public function init()
    {
        global $wordpress_helpdesk_options;

        $this->options = $wordpress_helpdesk_options;

        if (!$this->get_option('enable')) {
            return false;
        }

        return true;
    }

    public function maybe_add_crisp_code()
    {
        $crispLiveChat = $this->get_option('enableLiveChatCrisp');
        if(!$crispLiveChat) {
            return false;
        }

        $code = $this->get_option('liveChatCrispCode');
        if(!empty($code)) {
            echo $code;
        }
    }

    public function maybe_add_pure_chat_code()
    {
        $PureChatLiveChat = $this->get_option('enableLiveChatPureChat');
        if(!$PureChatLiveChat) {
            return false;
        }

        $code = $this->get_option('liveChatPureChatCode');
        if(!empty($code)) {
            echo $code;
        }
    }

    public function maybe_add_chatra_code()
    {
        $ChatraLiveChat = $this->get_option('enableLiveChatChatra');
        if(!$ChatraLiveChat) {
            return false;
        }

        $code = $this->get_option('liveChatChatraCode');
        if(!empty($code)) {
            echo $code;
        }
    }

    // https://www.simoahava.com/analytics/add-facebook-messenger-chat-google-tag-manager/
    // https://developers.facebook.com/docs/messenger-platform/discovery/customer-chat-plugin
    public function maybe_add_fb_messenger()
    {
        $FBMessengerLiveChat = $this->get_option('enableLiveChatFBMessenger');
        if(!$FBMessengerLiveChat) {
            return false;
        }

        $code = $this->get_option('liveChatFBMessengerCode');
        if(!empty($code)) {
            echo $code;
        }
    }
}