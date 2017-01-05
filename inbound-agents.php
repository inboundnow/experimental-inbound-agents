<?php

/*
Plugin Name: Inbound Extension - Agents
Plugin URI: http://www.inboundnow.com/
Description: Enables the assigning of leads to marketing agents for review.
Version: 1.0.0
Author: Inbound Now
Contributors: Matt Bissett, Hudson Atwell
Author URI: http://www.inboundnow.com/
*/

if(!class_exists('Inbound_Lead_Assignment')){
	
	class Inbound_Lead_Assignment{

        /**
         *  Initialize class
         */
        public function __construct() {
            self::define_constants();
            self::include_files();
            self::load_hooks();
        }


        /**
         *  Define constants
         */
        public static function define_constants() {
            define('INBOUNDNOW_LEAD_ASSIGNMENTS_CURRENT_VERSION', '1.0.0');
            define('INBOUNDNOW_LEAD_ASSIGNMENTS_LABEL', __('Inbound Agents', 'inbound-pro'));
            define('INBOUNDNOW_LEAD_ASSIGNMENTS_SLUG', 'inbound-lead-assignments' );
            define('INBOUNDNOW_LEAD_ASSIGNMENTS_FILE', __FILE__);
            define('INBOUNDNOW_LEAD_ASSIGNMENTS_REMOTE_ITEM_NAME', 'inbound-lead-assignments');
            define('INBOUNDNOW_LEAD_ASSIGNMENTS_PATH', realpath(dirname(__FILE__)) . '/');
            $upload_dir = wp_upload_dir();
            $url = ( !strstr( INBOUNDNOW_LEAD_ASSIGNMENTS_PATH , 'plugins' )) ? $upload_dir['baseurl'] . '/inbound-pro/extensions/' .plugin_basename( basename(__DIR__) ) .'/' : WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' ;
            define('INBOUNDNOW_LEAD_ASSIGNMENTS_URLPATH', $url );
        }

        public static function include_files() {
           if (is_admin()) {
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-page.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-taxonomy.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-bulk-actions.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-inbound-forms-integration.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-form-submissions.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-management.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-resources.php');	
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-agent-profile.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-lead-post-type.php');
            }else{
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-inbound-forms-integration.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-form-submissions.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-management.php');
				include_once(INBOUNDNOW_LEAD_ASSIGNMENTS_PATH . 'classes/class.assigned-agents-resources.php');	
			
			}

			
        }

        /**
         * Load Hooks & Filters
         */
        public static function load_hooks() {

            /* WP-Admin Only */
            if (is_admin()) {

                /*  Add settings to inbound pro  */
                add_filter('inbound_settings/extend', array(__CLASS__, 'add_pro_settings'));

                /* Add non-pro settings page */
                add_filter('lp_define_global_settings', array(__CLASS__, 'add_non_pro_settings'));
                add_filter('wpleads_define_global_settings', array(__CLASS__, 'add_non_pro_settings'));
                add_filter('wp_cta_define_global_settings', array(__CLASS__, 'add_non_pro_settings'));

				            
				/* Setup Automatic Updating & Licensing */
				add_action( 'admin_init', array(__CLASS__, 'license_setup' ) );

            }
		
        }

		/**
         *  Add inbound pro settings references
         */
        public static function add_pro_settings($settings) {
            /*pro settings*/
            $settings['inbound-pro-settings'][] = array(
                'group_name' => INBOUNDNOW_LEAD_ASSIGNMENTS_SLUG,
                'keywords' => __('assigned agents, inbound assigned agents, inbound agents', 'inbound-pro'),
                'fields' => array(
                    array(
                        'id' => 'inbound_agents_header',
                        'type' => 'header',
                        'default' => __('Inbound Agents', 'inbound-pro'),
                        'options' => null
                    ),
                    array(
                        'id' => 'inbound_agents_rotate_agent_lead_assignment',
                        'type' => 'radio',
                        'label' => __('Lead assignment policy', 'inbound-pro'),
                        'default' => '0',
                        'options' => array(
							'0' => __('Assign leads to all form agents', 'inbound-pro'), 
							'1' => __('Assign leads on a rotational basis', 'inbound-pro'),
						),
                        'description' => __('Choose whether leads generated by forms will be assigned to all form agents, or if they will be assigned to a form agent on a rotating basis. So if there are three agents assigned to a form, and rotation is on, the first agent will be assigned to the first lead. The second lead will be assigned to the second agent. The third lead to the third agent, the forth lead to the first agent... and so on.', 'inbound-pro')
                    ),
                    array(
                        'id' => 'inbound_agents_lead_cloning',
                        'type' => 'radio',
                        'label' => __('Clone leads when cloning groups?', 'inbound-pro'),
                        'default' => '0',
                        'options' => array('0' => 'No', '1' => 'Yes'),
                        'description' => __('This lets you choose to have leads cloned too when cloning a lead group from one agent to another', 'inbound-pro')
                    ),
                   /* array(
                        'id' => 'inbound_agents_lead_group_creation',
                        'type' => 'dropdown',
                        'label' => __('Lead Group Creation Policy', 'inbound-pro'),
                        'default' => '0',
                        'options' => array(
							'0' => 'Create lead groups for individual agents',
							'1' => 'Create lead groups for all agents',
                        ),
                        'description' => __('This lets you choose to create lead groups on a per agent basis, or to create groups for all agents NOT IMPLEMENTED', 'inbound-pro')
                    ),*/
                    array(
                        'id' => 'inbound_agents_lead_group_limits',
                        'type' => 'dropdown',
                        'label' => __('Lead Group form limit policy', 'inbound-pro'),
                        'default' => '0',
                        'options' => array(
							'0' => __('Set limits on a per-group basis', 'inbound-pro'),
							'1' => __('Set limits on a per-agent basis', 'inbound-pro'),
                        ),
                        'description' => __('This lets you choose to set form submission limits for lead groups across agents, or for individual agents. NOTE: Setting to \'Set limits for all agent groups\' causes the group limits to be set to the most common non-infinite limit. So if individually four agents\' \'Clients\' group limit is 10, but a fifth agent\'s \'Clients\' limit is 20, it will be set to 10 because that\'s the most common limit.', 'inbound-pro')
                    ),
                    /*array(
                        'id' => 'inbound_agents_lead_group_deletion',
                        'type' => 'dropdown',
                        'label' => __('Lead Group Deletion Policy', 'inbound-pro'),
                        'default' => '0',
                        'options' => array(
                            '0' => __('Delete lead groups from indvidual agents', 'inbound-pro'),
                            '1' => __('Delete lead groups from all agents', 'inbound-pro')
                        ),
                        'description' => __('This lets you choose to delete leads groups on a per agent basis, or to delete lead groups from all agents. NOT IMPLEMENTED', 'inbound-pro')
                    ),*/

                )

            );

            return $settings;

        }
		
		
		/**
         *  Legacy settings model
         */
        public static function add_non_pro_settings($global_settings) {

            /* ignore these hooks if inbound pro is active */
            if (defined('INBOUND_PRO_CURRENT_VERSION')) {
                return $global_settings;
            }
            switch (current_filter()) {
                case "lp_define_global_settings":
                    $tab_slug = 'lp-extensions';
                    break;
                case "wpleads_define_global_settings":
                    $tab_slug = 'wpleads-extensions';
                    break;
                case "wp_cta_define_global_settings":
                    $tab_slug = 'wp-cta-extensions';
                    break;
            }

           $global_settings[$tab_slug]['settings'][] =  array(
               'id' => 'inbound_agents_header',
               'type' => 'header',
               'default' => __('Inbound Agents', 'inbound-pro'),
               'options' => null
           );
           
           $global_settings[$tab_slug]['settings'][] =  array(
               'id' => 'inbound_agents_rotate_agent_lead_assignment',
               'type' => 'radio',
               'label' => __('Lead assignment policy', 'inbound-pro'),
               'default' => '0',
               'options' => array(
					'0' => __('Assign leads to all form agents', 'inbound-pro'), 
					'1' => __('Assign leads on a rotational basis', 'inbound-pro'),
				),
               'description' => __('Choose whether leads generated by forms will be assigned to all form agents, or if they will be assigned to a form agent on a rotating basis. So if there are three agents assigned to a form, and rotation is on, the first agent will be assigned to the first lead. The second lead will be assigned to the second agent. The third lead to the third agent, the forth lead to the first agent... and so on.', 'inbound-pro')
           );
           
           $global_settings[$tab_slug]['settings'][] =  array(
				'id' => 'inbound_agents_lead_cloning',
                'type' => 'radio',
                'label' => __('Clone leads when cloning groups?', 'inbound-pro'),
                'default' => '0',
                'options' => array('0' => 'No', '1' => 'Yes'),
                'description' => __('This lets you choose to have leads cloned too when cloning a lead group from one agent to another', 'inbound-pro')
           );
          /* $global_settings[$tab_slug]['settings'][] =  array(
                'id' => 'inbound_agents_lead_group_creation',
                'type' => 'dropdown',
                'label' => __('Lead Group Creation Policy', 'inbound-pro'),
                'default' => '0',
                'options' => array(
					'0' => 'Create lead groups for individual agents',
					'1' => 'Create lead groups for all agents',
					),
                'description' => __('This lets you choose to create lead groups on a per agent basis, or to create groups for all agents NOT IMPLEMENTED', 'inbound-pro')
           );*/
           
           $global_settings[$tab_slug]['settings'][] =  array(
                'id' => 'inbound_agents_lead_group_limits',
                'type' => 'dropdown',
                'label' => __('Lead Group form limit policy', 'inbound-pro'),
                'default' => '0',
                'options' => array(
					'0' => __('Set limits on a per-group basis', 'inbound-pro'),
					'1' => __('Set limits on a per-agent basis', 'inbound-pro'),
                ),
                'description' => __('This lets you choose to set form submission limits for lead groups across agents, or for individual agents. NOTE: Setting to "Set limits for all agent groups" causes the group limits to be set to the most common non-infinite limit. So if individually four agents\' "Clients" group limit is 10, but a fifth agent\'s "Clients" limit is 20, it will be set to 10 because that\'s the most common limit.', 'inbound-pro')
           );
           
           /*$global_settings[$tab_slug]['settings'][] =  array(
                'id' => 'inbound_agents_lead_group_deletion',
                'type' => 'dropdown',
                'label' => __('Lead Group Deletion Policy', 'inbound-pro'),
                'default' => '0',
                'options' => array(
                    '0' => __('Delete lead groups from indvidual agents', 'inbound-pro'),
                    '1' => __('Delete lead groups from all agents', 'inbound-pro')
                ),
                'description' => __('This lets you choose to delete leads groups on a per agent basis, or to delete lead groups from all agents. NOT IMPLEMENTED', 'inbound-pro')
           );*/

            return $global_settings;
        }

		/**
		 * Setups Software Update API
		 * @since
		 */
		public static function license_setup () {
			/* ignore these hooks if inbound pro is active */
			if ( defined('INBOUND_ACCESS_LEVEL') && INBOUND_ACCESS_LEVEL > 0 && INBOUND_ACCESS_LEVEL != 9 ) {
				return;
			}
			
			/*PREPARE THIS EXTENSION FOR LICENSING*/
			if ( class_exists( 'Inbound_License' ) ) {
				$license = new Inbound_License( INBOUNDNOW_LEAD_ASSIGNMENTS_FILE, INBOUNDNOW_LEAD_ASSIGNMENTS_LABEL, INBOUNDNOW_LEAD_ASSIGNMENTS_SLUG, INBOUNDNOW_LEAD_ASSIGNMENTS_CURRENT_VERSION, INBOUNDNOW_LEAD_ASSIGNMENTS_REMOTE_ITEM_NAME );
			}
		}
	}//end class Inbound_Lead_Assignment

	new Inbound_Lead_Assignment;

}

