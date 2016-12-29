<?php

if(!class_exists('Inbound_Assigned_Agents_Resources')){
	
	class Inbound_Assigned_Agents_Resources{
		
		static $possible_agents;
		static $assigned_agents;
		static $assigned_agents_by_UID;
		static $user_id_by_term_id;
		static $agent_term_lead_groups;
		static $lead_group_limits;
		static $ungrouped_leads;
		
		
		function __construct(){
			self::load_hooks();
		}
		
		public static function load_hooks(){
			
			/**set agent variables**/
			/*get the possible and assigned agents*/
			add_action('init', array(__CLASS__, 'get_agent_info'));
			
			/*get the term lead groups at load*/
			add_action('init', array(__CLASS__, 'get_agent_term_lead_groups'));
			
			/*get the limits on form submissions for lead groups*/
			add_action('init', array(__CLASS__, 'get_agent_term_lead_limits'));	
			
			/*get the leads which aren't in any groups*/
			add_action('init', array(__CLASS__, 'get_ungrouped_leads'));
			
			/**ajax**/	
		
		    /*ajax action for getting the stored agent list*/
            add_action( 'wp_ajax_get_agent_list', array(__CLASS__, 'ajax_get_agent_list'));
            
            /*ajax action for getting the user's profile image*/
            add_action( 'wp_ajax_get_profile_image', array(__CLASS__, 'ajax_get_profile_image'));
            
            /*ajax action for getting the agent's leads*/
            add_action( 'wp_ajax_get_agent_lead_info', array(__CLASS__, 'ajax_get_agent_lead_info'));
		
		}
		
		/**
		 * Get the possible agents and the assigned agents
		 */
		public static function get_agent_info(){
			$users = get_users();
			
			foreach($users as $user){
				$term_id = get_user_meta($user->data->ID, 'inbound_assigned_lead_term', true);
				if($term_id == ''){
					self::$possible_agents[$user->data->ID] = $user->data->display_name;
				}else{
					$term = get_term($term_id);
					self::$assigned_agents[$term_id] = $term->name;
					self::$assigned_agents_by_UID[$user->data->ID] = $term->name; 
					self::$user_id_by_term_id[$term_id] = $user->data->ID;
				}
			}
		//	print_r(self::$assigned_agents);  //debug. Outputs the var value, but makes ajax fail
		}	
	
		/**
		 * Get the lead groups stored in the agent term meta at init
		 */
		public static function get_agent_term_lead_groups(){
			/*loop through the agents, and retrieve their wp user ids*/
			foreach(self::$assigned_agents as $key=>$value){
	
				/*get the term meta*/
				$meta = get_term_meta($key, 'inbound_agents_lead_groups');

				if(!empty($meta[0])){
					/*loop through the term meta to create arrays of the stored values. */
					foreach($meta[0] as $key2=>$value2){
						$key2 = stripslashes($key2);
						$agent_id[$key2] = $value2;
					}
					self::$agent_term_lead_groups[$key] = $agent_id;
					unset($agent_id);
				}
			}
		//	print_r(self::$agent_term_lead_groups); //debug. Outputs the var value, but makes ajax fail
		}
	
		/**
		 * Gets agent term limits at init and does maintenace
		 */
		public static function get_agent_term_lead_limits(){
			$setting = self::get_setting('inbound_agents_lead_group_limits');

			foreach(self::$assigned_agents as $key=>$value){
				$limits = array();
				/*get the term meta*/
				$meta = get_term_meta($key, 'inbound_agents_term_lead_limits');
				$meta2 = get_term_meta($key, 'inbound_agents_lead_groups');
				
				/*if there are no lead groups for this agent, skip*/
				if(empty($meta2)){
					continue;
				}
				
				
				/*if there are limits and there's a group stored in limits but not in lead groups, remove it*/
				if(!empty($meta[0]) && !empty($deleted_groups = array_diff_key($meta[0], $meta2[0]))){
					foreach($deleted_groups as $deleted_group=>$deleted_value){
						unset($meta[0][$deleted_group]);
					}
					update_term_meta((int)$key, 'inbound_agents_term_lead_limits', $meta[0]);
				}

				
				
				/**put together the groups and their limits**/
				/*if the term doesn't have any term limits yet*/
				if(empty($meta[0])){
					foreach(self::$agent_term_lead_groups[$key] as $lead_group=>$leads){
						$limits[$lead_group] = '-1'; //no limit
					}
					update_term_meta((int)$key, 'inbound_agents_term_lead_limits', $limits);
					self::$lead_group_limits[$key] = $limits;
					
				}else if($groups_without_limits = array_diff_key($meta2[0], $meta[0])){
				/*if there is are any groups that aren't in the limit listing*/
				
					foreach($groups_without_limits as $group_without_limit => $no_value){
						$meta[0][$group_without_limit] = '-1';
					}
					update_term_meta((int)$key, 'inbound_agents_term_lead_limits', $meta[0]);
					self::$lead_group_limits[$key] = $meta[0];
				
				}else{
					/*if everything's in order, update the resource variable*/
					self::$lead_group_limits[$key] = $meta[0];
				}
			}

			/**if the settings have been set to having a standard lead group limit, this is what sets it and maintains it**/
			if($setting != '1'){
				$all_groups = array();
				
				/**Create the all_groups variable**/
				/*loop through the agents and groups*/
				foreach(self::$lead_group_limits as $key2 => $value2){
					/*loop through the groups and limits*/
					foreach($value2 as $key3=>$value3){
						/*store the groups and limits as group => array( agent id => limit count )*/
						$all_groups[$key3][$key2] = $value3;
					}
				}

				/*loop through the groups as group => array( agent id => limit count )*/
				foreach($all_groups as $group => $data){
					
					/*count the limits. If the group across agents has the same value, only the first index will have a value*/
					$values = array_count_values($data); 


					/*if the number of group limits in the first index of values is different from the total number of group limits*/
					if(current($values) != count($data)){
						//unset the infinite value
						unset($values['-1']); 

						/*find the most common group limit*/
						$mode = array_search(max($values), $values);

						/*loop through the agents */
						foreach($data as $agent_id=>$group_limit){
							/*find the group who's limit doesn't match the mode*/
							if($group_limit != $mode){
								$meta = get_term_meta((int)$agent_id, 'inbound_agents_term_lead_limits'); 
								$meta[0][$group] = $mode;
								update_term_meta((int)$agent_id, 'inbound_agents_term_lead_limits', $meta[0]);
								self::$lead_group_limits[$agent_id][$group] = $mode;
							}
						}
					}

				}
				
			}
	//		print_r(self::$lead_group_limits);
		}
		
		/**
		 * Gets all leads which aren't in groups an init
		 * 
		 */
		public static function get_ungrouped_leads(){
			$agent_lead_listing = array();
			$agent_leads = array();
			/*foreach agent*/
			foreach(self::$agent_term_lead_groups as $agent_id => $lead_groups){
				/**create an array of all leads in this agent's groups**/
				foreach($lead_groups as $group_name=>$lead_array){
					
					/*if there's no leads in this group, skip it*/
					if(empty($lead_array)){
						continue;
					}
					foreach($lead_array as $key => $lead){
						$agent_leads[$lead] = true;
					}
				}
				
				/*get the leads for each agent, excluding the ones that are in groups*/
				$ungrouped_leads = get_posts(array(
					'post_type' => 'wp-lead',
					'numberposts' => -1,
					'exclude' => (!empty($agent_leads)) ? array_keys($agent_leads) : '',
					'tax_query' => array(
						array(
						'taxonomy' => 'inbound_assigned_lead',
						'field' => 'id',
						'terms' => $agent_id,
						),
					),
				));
				
				/**create an array of all ungrouped lead ids**/
				$ungrouped_lead_ids = array();
				foreach($ungrouped_leads as $lead_object){
					$ungrouped_lead_ids[] = $lead_object->ID;
				}
				
				/*assign the ungrouped lead ids to an index of this agent's id*/
				$agent_lead_listing[$agent_id] = $ungrouped_lead_ids;
				
				/*clear the lead arrays*/
				unset($agent_leads);
				unset($ungrouped_lead_ids);
			}
			
			/*set $ungrouped_leads for the listing of the ungrouped leads*/
			self::$ungrouped_leads = $agent_lead_listing;
		}

	    /**
         * Get Inbound Agents Settings
         * Param: index;
         * @return mixed
         */
        public static function get_setting($key) {
            if (!defined('INBOUND_PRO_CURRENT_VERSION')) {
                $setting = get_option('wpleads-extensions-' . $key, 0);

            } else {
                $settings = Inbound_Options_API::get_option('inbound-pro', 'settings', array());
                $setting = (isset($settings[INBOUNDNOW_LEAD_ASSIGNMENTS_SLUG][$key])) ? $settings[INBOUNDNOW_LEAD_ASSIGNMENTS_SLUG][$key] : 0;
            }

            return $setting;
        }
	
		/*get the agent's profile picture*/
		public static function get_profile_image($user_id){

			$email = get_userdata($user_id)->data->user_email;
	
			$size = 140;

			$default = WPL_URLPATH . '/assets/images/gravatar_default_50.jpg'; // doesn't work for some sites

			$gravatar = "//www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=" . urlencode($default) . "&s=" . $size;

			// Fix for localhost view
			if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
				$gravatar = $default;
			}

			return $gravatar;
		}
	
	
	/***Begin ajax functions***/
	
	
		public static function ajax_get_agent_list(){
			echo json_encode(self::$assigned_agents);
			die();
		}
	
		/**
		 * Ajax wrapper for get_profile_image
		 * Takes agent term id
		 **/
		public static function ajax_get_profile_image(){
			
			/*if term id passed directly*/
			if((int)$_POST['data'] == $_POST['data']){
				$data['id'] = $_POST['data'];
			}else{
				/*if passed in URL encoded string*/
				parse_str($_POST['data'], $data);
			}
			
			$user_id = self::$user_id_by_term_id[$data['id']];

			echo json_encode(self::get_profile_image($user_id));
			die();

		}
		/**
		 * Get basic lead info for referance purposes
		 */
		public static function ajax_get_agent_lead_info(){
			$data['agent_id'] = (int)$_POST['data']['agent_id'];
			$data['lead_ids'] = array_map(function($lead_id){ return (int)$lead_id; }, $_POST['data']['lead_ids']);
			$data['lead_ids'] = array_filter($data['lead_ids']);
			
			$output = array();
			foreach($data['lead_ids'] as $lead_id){
				$lead_data = get_post_meta($lead_id);
				$output[$lead_id]['first_name'] = $lead_data['wpleads_first_name'];
				$output[$lead_id]['last_name'] = $lead_data['wpleads_last_name'];
				$output[$lead_id]['email'] = $lead_data['wpleads_email_address'];
			}
			echo json_encode($output);
			die();
		}
	}

 new Inbound_Assigned_Agents_Resources;


}

?>
