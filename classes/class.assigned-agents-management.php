<?php

if(!class_exists('Inbound_Assigned_Agents_Management')){
	/**
	 * The agent term is the taxonomy term created with the wp user's display name
	 * The id of the term is stored in the wp user's meta
	 * A lead group is a grouping of lead ids stored in the meta of the agent term //taxonomy
	 */
	
	/**
	 * Handles most all of the data management:
	 * 	Agent creation and deletion
	 * 	Lead group creation, editing and deletion
	 * 	Sets and edits lead group submission limits
	 * 
	 */
	class Inbound_Assigned_Agents_Management{
	
		function __construct(){
			self::add_hooks();

		}
	
		public static function add_hooks(){
			

			/*update the lead groups on lead deletion*/
			add_action('before_delete_post', array(__CLASS__, 'synchronize_lead_groups'));			
			
			/**AJAX**/
			
			/*perform agent term actions, create and delete*/
			add_action('wp_ajax_agent_term_actions', array(__CLASS__, 'ajax_agent_term_actions'));
			
			/*create term lead group*/
            add_action('wp_ajax_create_term_lead_group', array(__CLASS__, 'ajax_create_term_lead_group'));
            
            /*edit term lead group data*/
            add_action('wp_ajax_edit_term_lead_group_data', array(__CLASS__, 'ajax_edit_term_lead_group_data'));
            
            /*get agent term lead limits*/
            add_action('wp_ajax_get_term_lead_limits', array(__CLASS__, 'ajax_get_term_lead_limits'));
            
            /*edit agent term lead limits*/
            add_action('wp_ajax_edit_term_lead_limits', array(__CLASS__, 'ajax_edit_term_lead_limits'));
            
            /*get the lead groups for the supplied agents*/
			add_action('wp_ajax_get_agent_term_lead_groups', array(__CLASS__, 'ajax_get_agent_term_lead_groups' ));		
			
			/*create and edit agent extra data*/
			add_action('wp_ajax_edit_agent_extra_data', array(__CLASS__, 'ajax_edit_agent_extra_data' ));
			
			/*get agent extra data*/
			add_action('wp_ajax_get_agent_extra_data', array(__CLASS__, 'ajax_get_agent_extra_data' ));
			
			/*add a list action delete option*/
			add_action('wp_ajax_list_action_delete', array(__CLASS__, 'ajax_list_action_delete' ));
		
		}
		
		/**
		 * Update the lead groups when a lead is deleted
		 */
		public static function synchronize_lead_groups($lead_id) {
			if(get_post_type($lead_id) == 'wp-lead'){
					$data = array('execution' => 'remove-leads', 'agent_ids' => wp_get_post_terms($lead_id, 'inbound_assigned_lead', array('fields' => 'ids')),
								  'lead_ids' => array($lead_id), 'remove_from_agent' => '1', 'lead_deleted' => true,);
					self::edit_term_lead_group_data($data);
			}	
		}		

		/**
		 * Ajax wrapper for agent_term_actions
		 * 
		 */
		public static function ajax_agent_term_actions(){
			$data = $_POST['data'];
			
			echo json_encode(self::agent_term_actions($data));
			die();
		}
	
		/**
		 * Creates and deletes agents
		 */
		public static function agent_term_actions($data){
			
			/*permission check*/
			if(!current_user_can('editor') && !current_user_can('inbound_marketer')&& !current_user_can('administrator')){
				return array('access_denied' =>__('You do not have the access level to preform this action.', 'inbound-pro'));
			}
			
		
			/**
			 * Handles the adding of agents
			 */
			if($data['execution'] == 'create-agent'){
				foreach($data['user_data'] as $user_id_and_screename){
					
					/*the data is stored as a url encoded string and must be decoded*/
					parse_str($user_id_and_screename, $user_data);
					
					/*create a new term based on the user's display name*/
					$new_term = wp_insert_term($user_data['display_name'], 'inbound_assigned_lead');
					
					/*store the term's id in the user's meta, in the inbound_assigned_lead_term key*/
					$profile_updated = update_user_meta($user_data['id'],  'inbound_assigned_lead_term', $new_term['term_id']);
					
					/*create the agent created timestamp and job title placeholder*/
					$time = new DateTime('', new DateTimeZone(get_option('timezone_string')));
					$format = get_option('date_format');
					
					/*if the time object isn't empty*/
					if(!empty($time)){
						$val = array('agent_since' => array(__('Agent Since', 'inbound-pro') => $time->format($format)),
									 'job_title'   => array(__('Job Title', 'inbound-pro') => 'N/A'),
						);
					}
					
					/*and store them in the extra data*/
					update_term_meta($new_term['term_id'], 'inbound_agents_extra_data', $val);
					
					/*if agent created successfully, return success*/
					if($profile_updated && $new_term){
						return array('success' => sprintf( __('%1$s\'s agent profile created successfully!', 'inbound-pro'), $user_data['display_name']));
					}else if($profile_updated && !$new_term){
						return array('non_success' => sprintf( __('%1$s\'s profile is listed as being an agent\'s, but the agent profile has not been created', 'inbound-pro'), $user_data['display_name']));
					}else if($new_term && !$profile_updated){
						return array('non_success' => sprintf( __('%1$s\'s Agent profile created, but it\'s not associated with %1$s\'s profile', 'inbound-pro'), $user_data['display_name']));						
					}else{
						return array('error' => __('There\'s been an error, agent not created'));
					}
				}	
			}
			
			/**
			 * Handles the deletion of agents
			 */
			if($data['execution'] == 'delete-agent'){
				/*for each user id*/
				foreach($data['user_data'] as $user_id_and_screename){
					$term_exists = false;
					
					/*the data is stored as a url encoded string and must be decoded*/
					parse_str($user_id_and_screename, $user_data);
					
					/*get the term id from the user's meta*/
					$agent_term = get_user_meta($user_data['id'], 'inbound_assigned_lead_term', true);

					/*check if the term has already been deleted*/
					if(get_term($agent_term)){
						$term_exists = true;
					}
					
					/*delete the term id from the user's meta*/
					$user_meta_deleted = delete_user_meta($user_data['id'], 'inbound_assigned_lead_term');
					
					/*delete the term*/
					$term_deleted = wp_delete_term($agent_term, 'inbound_assigned_lead');

					/*if the user meta and the term were deleted, or if the user meta is deleted and the term was deleted earlier, return success. */
					if(($user_meta_deleted && $term_deleted) || ($user_meta_deleted && $term_exists == false)){
						return array('success' => sprintf( __('%1$s\'s agent profile successfully deleted', 'inbound-pro'), $user_data['display_name']));
					}else if($user_meta_deleted && !$term_deleted){
						return  array('not_success' => __('User disassociated with agent profile, but agent profile remains', 'inbound-pro'));
					}else if($term_deleted && !$user_meta_deleted){
						return  array('not_success' => sprintf(__('%1$s\'s agent profile has been deleted, but the user profile is still listed as being an agent', 'inbound-pro'), $user_data['display_name']));
					}else{
						return  array('error' => __('There\'s been an error, the agent profile has not been deleted', 'inbound-pro'));
					}

				}
				
			}
			
			return array('error' => __('Data error, the passed execution was not what was expected', 'inbound-pro'));
		}
	
		/**
		 * Create the term lead group
		 * params: agent_ids, lead_group_names
		 */
		public static function ajax_create_term_lead_group(){
			
			/*permission check*/
			if(!current_user_can('editor') && !current_user_can('inbound_marketer')&& !current_user_can('administrator')){
				echo json_encode(array('access_denied' =>__('You do not have the access level to preform this action.', 'inbound-pro')));
				die();
			}
			
			/*sanitize the data*/
			$data = stripslashes_deep($_POST['data']);
			$data['agent_ids'] = array_map(function($id){ return (int)$id; }, $data['agent_ids']);
			$data['lead_group_names'] = sanitize_text_field($data['lead_group_names']);
			
			/*replace double quotes with single ones*/
			$data['lead_group_names'] = str_replace('"', '\'', $data['lead_group_names']);
			
			$output = '';
			
			$submitted_groups = array_map('trim', explode(',', $data['lead_group_names']));
			$flipped_groups = array_flip($submitted_groups);
			
			foreach($data['agent_ids'] as $agent_id){
				$meta = get_term_meta((int)$agent_id, 'inbound_agents_lead_groups');
				
				/*if there's at least one group already stored*/
				if(!empty($meta)){
					
					$new_groups = array_diff_key($flipped_groups, $meta[0]);

					/*if there are new groups*/
					if(!empty($new_groups)){
						$mapped = array_map(function($var){ return $var = array(); }, $new_groups);
						$new_meta = array_merge($meta[0], $mapped);
						update_term_meta($agent_id, 'inbound_agents_lead_groups', $new_meta);
				
						$diff = array_diff(array_keys($mapped), $meta[0]);

						$output .= implode(', ', $diff) . __(' created for ', 'inbound-pro') . Inbound_Assigned_Agents_Resources::$assigned_agents[$agent_id] . '. ';
					}else{
						$output .= $data['lead_group_names'] . __(' already exists for ', 'inbound-pro') . Inbound_Assigned_Agents_Resources::$assigned_agents[$agent_id] . '. ';
					}
				}else{
				/*if this is the first lead group/s for the agent, just push the array of groups*/	
					$new_meta = array_map(function($var){ return $var = array(); }, $flipped_groups);
					update_term_meta($agent_id, 'inbound_agents_lead_groups', $new_meta);
					$output .= $data['lead_group_names'] . __(' created for ', 'inbound-pro') . Inbound_Assigned_Agents_Resources::$assigned_agents[$agent_id] . '.';
				}
			}
			echo json_encode($output);
			die();
		}
	
	
	
		/**
		 * Ajax wrapper for edit_term_lead_group_data.
		 */
		public static function ajax_edit_term_lead_group_data(){
			$data = $_POST['data'];
			$data['lead_groups'] = stripslashes_deep($data['lead_groups']);

			echo json_encode(self::edit_term_lead_group_data($data));
			die();
		}

		/**
		 * Performs actions on existing term lead groups
		 * @param array $data
		 */
		public static function edit_term_lead_group_data($data){

			/*permission check*/
			if(!current_user_can('editor') && !current_user_can('inbound_marketer')&& !current_user_can('administrator')){
				return array('access_denied' =>__('You do not have the access level to preform this action.', 'inbound-pro'));
			}
			
			/*exit if no agent ids have been supplied*/
			if(empty($data['agent_ids'])){
				return array('error' => array(__('No agents supplied', 'inbound-pro')));
			}
			
			/*sanitize the data*/
			if(isset($data['agent_ids'])){
				$data['agent_ids'] = array_map(function($id){ return (int)$id; }, $data['agent_ids']);
			}
			if(isset($data['agent_ids_2'])){
				$data['agent_ids_2'] = array_map(function($id){ return (int)$id; }, $data['agent_ids_2']);
			}
			if(isset($data['lead_ids'])){
				$data['lead_ids'] = array_map(function($id){ return (int)$id; }, $data['lead_ids']);
			}
			if(isset($data['lead_groups'])){
				$data['lead_groups'] = array_map(function($group){ return sanitize_text_field($group); }, $data['lead_groups']);
				$data['lead_groups'] = array_filter($data['lead_groups'], 'strlen' );
			}
			if(isset($data['lead_groups_2'])){
				$data['lead_groups_2'] = array_map(function($group){ return sanitize_text_field($group); }, $data['lead_groups_2']);
				$data['lead_groups_2'] = array_filter($data['lead_groups_2'], 'strlen' );				
			}
	

			/**
			 * add lead ids to a term lead group, 
			 * also adds lead to agent's taxonomy term if it's not already there
			 * @params agent_ids, lead_groups, lead_ids
			 */
			if($data['execution'] == 'add-leads'){
				
				/*exit the lead ids aren't an array or if there's no leads*/
				if(!is_array($data['lead_ids']) || empty($data['lead_ids'])){
					return array('error' => array(__('No leads supplied, or they aren\'t in array format', 'inbound-pro')));
				}
				
				/*foreach agent term*/
				foreach($data['agent_ids'] as $agent_id){
					/*if lead groups and ids have been supplied*/
					if(!empty($data['lead_groups'])){
						/*get the stored lead groups*/
						$existing_values = get_term_meta((int)$agent_id, 'inbound_agents_lead_groups');
						/*foreach of the supplied lead groups*/
						foreach($data['lead_groups'] as $lead_group){
							
							/*if the lead group is empty, just push the leads into it*/
							if(empty($existing_values[0][$lead_group])){
								$existing_values[0][$lead_group] = $data['lead_ids'];
							}else{
							/*if it's not empty, add add all the leads that aren't already stored*/
								$existing_values[0][$lead_group] = array_merge($existing_values[0][$lead_group], array_diff($data['lead_ids'], $existing_values[0][$lead_group]));
							}
												
						}
					
						/*push the updated lead groups*/
						update_term_meta((int)$agent_id, 'inbound_agents_lead_groups', $existing_values[0]);
					}
					/*update lead terms*/
					/*loop through the leads to be added*/
					foreach($data['lead_ids'] as $lead_id){
						/*if the lead doesn't have this agent assigned to it*/
						if(!has_term($agent_id, 'inbound_assigned_lead', $lead_id)){
			
							/*assign the agent. //This is adding the term to the post like a normal taxonomy*/
							wp_set_object_terms((int)$lead_id, (int)$agent_id, 'inbound_assigned_lead', true);
						}
					}
				}
				return __('Leads added', 'inbound-pro');

			}
			
			/**
			 * remove lead ids from the term lead group
			 * optionally removes from the agent taxonomy term
			 * @params execution, agent_ids, lead_groups, lead_ids, remove_from_agent, lead_deleted
			 */
			if($data['execution'] == 'remove-leads'){

				/*exit the lead ids aren't an array or if there's no leads*/
				if(!is_array($data['lead_ids']) || empty($data['lead_ids'])){
					return array('error' => array(__('No leads supplied, or they aren\'t in array format', 'inbound-pro')));
				}
		
				foreach($data['agent_ids'] as $agent_id){
					
					if($data['remove_from_agent'] == '0'){
						$lead_groups = $data['lead_groups'];
					}else if($data['remove_from_agent'] == '1'){
						/*if the lead is being removed from the agent, get all the lead groups*/
						$lead_groups = array_keys(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent_id]);
					}
															
					if(!empty($lead_groups)){
						/*get existing leads in groups*/
						$existing_values = get_term_meta((int)$agent_id, 'inbound_agents_lead_groups');
	
						foreach($lead_groups as $lead_group_name){
							/*if we come across an empty group, skip it*/
							if(empty($existing_values[0][$lead_group_name]) && $existing_values[0][$lead_group_name] == null){
								continue;
							}
							/*set the leads in the current group to all the leads that aren't in $data[leads_ids]*/
							$existing_values[0][$lead_group_name] = array_values(array_diff($existing_values[0][$lead_group_name], $data['lead_ids']));
						}
							/*update the meta with the new list of leads*/
							update_term_meta($agent_id, 'inbound_agents_lead_groups', $existing_values[0]);
			
					}
					
					/*if the leads are to be removed from the agent*/
					if($data['remove_from_agent'] == '1' && $data['lead_deleted'] != true){//lead_deleted is true if the call comes from synchronize_lead_groups

						/*loop through the leads*/
						foreach($data['lead_ids'] as $lead_id){
							/*and remove the lead from the agent's taxonomy.*/
							wp_remove_object_terms($lead_id, (int)$agent_id, 'inbound_assigned_lead');
						}
					}
				}
				return array('success' => __('Leads removed!', 'inbound-pro'));

			}
			
			/**
			 * Moves leads form one group to another.
			 * Optionally move leads from one agent to another, and deletes from the original
			 * @params execution, agent_ids, lead_ids, lead_groups, lead_groups_2, agent_ids_2, remove_from_agent
			 */
			if($data['execution'] == 'transfer-leads'){
				$removed_leads = array();

				/*exit the lead ids aren't an array or if there's no leads*/
				if(!is_array($data['lead_ids']) || empty($data['lead_ids'])){
					return array('error' => array(__('No leads supplied, or they aren\'t in array format', 'inbound-pro')));
				}
		
				foreach($data['agent_ids'] as $agent_id){
					
					/**set the groups to remove from**/
					if($data['remove_from_agent'] == '0' || $data['transfer_from_other_groups'] == '0'){
						$lead_groups = $data['lead_groups'];
					}else if($data['remove_from_agent'] == '1' || $data['transfer_from_other_groups'] == '1'){
						/*if the lead is being removed from the agent or moved from all other groups, get all the lead groups*/
						$lead_groups = array_keys(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent_id]);
					}

					if(!empty($lead_groups)){
						/*remove leads from the lead_groups*/
						$existing_values = get_term_meta((int)$agent_id, 'inbound_agents_lead_groups');
						
						foreach($lead_groups as $lead_group_name){
							$removed_leads += array_intersect($existing_values[0][$lead_group_name], $data['lead_ids']);
							$existing_values[0][$lead_group_name] = array_values(array_diff($existing_values[0][$lead_group_name], $data['lead_ids']));
					
						}
						
						/**remove any duplicate values**/
						$unique = array();
						foreach($removed_leads as $key=>$val) {    
							$unique[$val] = true; 
						} 
						$removed_leads = array_keys($unique);
						
						/*update the meta with the new list of leads*/
						update_term_meta($agent_id, 'inbound_agents_lead_groups', $existing_values[0]);
			
					}
		
					/*if the leads are to be removed from the agent*/
					if($data['remove_from_agent'] == '1'){

						/*loop through the leads*/
						foreach($removed_leads as $lead_id){
							/*and remove the lead from the agent's taxonomy.*/
							wp_remove_object_terms($lead_id, (int)$agent_id, 'inbound_assigned_lead');

						}
					}
				}
				
				/**add the lead to the recipient groups/agents. 
				 * if the lead is being moved in the same agent, then agent_ids_2 will be the same as agent_ids**/
				if(!empty($data['agent_ids_2'])){
					foreach($data['agent_ids_2'] as $new_agent_term){
						/**move leads to groups in the same agent**/  //might only work for single agents
						if($data['transfer_from_other_groups'] == '1'){
							if(!empty($data['lead_ids'])){
								/*get the stored lead groups*/
								$existing_values = get_term_meta((int)$new_agent_term, 'inbound_agents_lead_groups');
								/*foreach of the supplied lead groups*/
								foreach($data['lead_groups_2'] as $lead_group){
									/*if the lead group is empty, just push the leads into it*/
									if(empty($existing_values[0][$lead_group])){
										$existing_values[0][$lead_group] = $data['lead_ids'];
									}else{
									/*if it's not empty, add add all the leads that aren't already stored*/
										$existing_values[0][$lead_group] = array_merge($existing_values[0][$lead_group], array_diff($data['lead_ids'], $existing_values[0][$lead_group]));
									}
								}
								
								/*push the updated lead groups*/
								update_term_meta((int)$new_agent_term, 'inbound_agents_lead_groups', $existing_values[0]);
							}
						}else if(!empty($data['lead_groups_2']) && !empty($removed_leads)){
						/**add to new agents**/

							/*get the stored lead groups*/
							$existing_values = get_term_meta((int)$new_agent_term, 'inbound_agents_lead_groups');
							/*foreach of the supplied lead groups*/
							foreach($data['lead_groups_2'] as $lead_group){
								/*if the lead group is empty, just push the leads into it*/
								if(empty($existing_values[0][$lead_group])){
									$existing_values[0][$lead_group] = $removed_leads;
								}else{
								/*if it's not empty, add add all the leads that aren't already stored*/
									$existing_values[0][$lead_group] = array_merge($existing_values[0][$lead_group], array_diff($removed_leads, $existing_values[0][$lead_group]));
								}
							}
						
							/*push the updated lead groups*/
							update_term_meta((int)$new_agent_term, 'inbound_agents_lead_groups', $existing_values[0]);
						}
						/**update lead terms**/
						/*loop through the leads to be added*/
						foreach($removed_leads as $lead_id){
							/*if the lead doesn't have this agent assigned to it*/
							if(!has_term($new_agent_term, 'inbound_assigned_lead', $lead_id)){

								/*assign the agent. //This is adding the term to the post like a normal taxonomy*/
								wp_set_object_terms((int)$lead_id, (int)$new_agent_term, 'inbound_assigned_lead', true);
							}
						}
						return array('success' => array(__('Leads transferred successfully!', 'inbound-pro')));
					}
				}else{
					return array('error' => array(__('Leads removed but not transferred', 'inbound-pro')));
				}
			}
			
			
			/**
			 * Clone term lead group to another agent. Optionally clone leads too
			 * @params agent_ids, agent_ids_2, lead_groups, clone_leads
			 */
			if($data['execution'] == 'clone-lead-group'){
				$output = '';
				/*if leads are to be cloned too*/
				if($data['clone_leads'] == '1'){
					$groups_with_leads = array_map(function($id){ return get_term_meta((int)$id, 'inbound_agents_lead_groups'); }, $data['agent_ids']);
				}
				
				foreach($data['agent_ids_2'] as $agent_id_2){
					$existing_values = get_term_meta((int)$agent_id_2, 'inbound_agents_lead_groups');
					
					/*if no groups exist, set a default value so we have something to work with*/
					if(empty($existing_values)){
						$existing_values[0] = array();
					}
					
					/*format the groups to clone. the resulting structure is key=>array()*/
					$cloned_groups = array_map(function($value){ return $value = array();}, array_flip($data['lead_groups']));
					/*make sure none of the groups we're cloning already exist for the agent*/
					$diff = array_diff_key($cloned_groups, $existing_values[0]);
					
					/*if there are any groups that the agent doesn't already have*/
					if(!empty($diff)){
						/*if leads are to be cloned too*/
						if($data['clone_leads'] == '1'){
							
							/*get the leads by groups*/
							$leads_by_group = array();
							foreach($groups_with_leads as $set){
								$leads_by_group = array_merge_recursive($leads_by_group, array_intersect_key($set[0], $diff));
							
							}
							
							/*remove duplicate lead listings*/
							$leads_in_groups = array();
							foreach($leads_by_group as $group=>$leads){
								foreach($leads as $i=>$val){
									$leads_in_groups[$group][$val] = $val;
								}
							}
							
							foreach($leads_in_groups as $group=>$leads){
								if(empty($existing_values[0][$group])){
									$existing_values[0][$group] = array();
								}
								/*set the cloned lead group values to, the result of merging the existing values with all the values that aren't already in the group*/
								$existing_values[0][$group] = array_merge($existing_values[0][$group], array_diff(array_keys($leads), $existing_values[0][$group]));
					
								foreach($leads as $lead){
									/*if the lead doesn't have this agent assigned to it*/
									if(!has_term($agent_id_2, 'inbound_assigned_lead', $lead)){
										/*assign the agent. //This is adding the term to the post like a normal taxonomy*/
										wp_set_object_terms((int)$lead, (int)$agent_id_2, 'inbound_assigned_lead', true);
									}
								}
							}
						}
						/*if we're not cloning leads, just clone the lead groups*/
						else{
							/*format the groups to clone. the resulting structure is key=>array()*/
							$cloned_groups = array_map(function($value){ return $value = array();}, array_flip($data['lead_groups']));
							/*make sure none of the groups were cloning already exist for the agent*/
							$diff = array_diff_key($cloned_groups, $existing_values[0]);
							/*merge the arrays*/
							$existing_values[0] = array_merge($existing_values[0], $diff);

						}
						update_term_meta($agent_id_2, 'inbound_agents_lead_groups', $existing_values[0]);
						
						$output .= get_term($agent_id_2)->name . ': ' . implode(', ' , array_keys($diff)) . '. ';
						
						
					}else{
						return array('not_success' => __('No groups cloned, the selected groups already exist', 'inbound-pro'));
					}
				}
				
				if($output != ''){
					return array('success' => __('Lead Groups Cloned: ', 'inbound-pro') . $output);
				}else{
					return array('error' => __('An unknown error has occurred', 'inbound-pro'));
					
				}
			
			}

			/**
			 * delete lead group from an agent. Can accept multiple agents and groups
			 * @params agent_ids, agent_lead_groups
			 */
			if($data['execution'] == 'delete-lead-group'){
				
				foreach($data['agent_ids'] as $agent_id){
					$existing_values = get_term_meta((int)$agent_id, 'inbound_agents_lead_groups');
					foreach($data['lead_groups'] as $lead_group){
						unset($existing_values[0][$lead_group]);
						
					}
					update_term_meta((int)$agent_id, 'inbound_agents_lead_groups', $existing_values[0]);
				}
				return array('success' => __('Lead Groups Deleted', 'inbound-pro'));
			}
			return array('error' => __('Unkown action, No actions were taken. ', 'inbound-pro'));
			
		}//end  edit_term_lead_group_data

	
		/**
		 * Ajax wrapper for get_term_lead_limits
		 */	
		public static function ajax_get_term_lead_limits(){
			$data = $_POST['data'];
			$data['lead_groups'] = stripslashes_deep($data['lead_groups']);
			
			echo json_encode(self::get_term_lead_limits($data));
			die();
	
		}
	
		/**
		 * Retrieves the limits set for form submissions to lead groups
		 * params agent_ids, lead_groups
		 */
		public static function get_term_lead_limits($data){
			$setting = Inbound_Assigned_Agents_Resources::get_setting('inbound_agents_lead_group_limits');
			$agent_limits = array();
			$lead_group_limits = Inbound_Assigned_Agents_Resources::$lead_group_limits;
			
			if($setting == '1'){
				foreach($data['agent_ids'] as $agent_id){
					$agent_limits[$agent_id] = array_intersect_key($lead_group_limits[$agent_id], array_flip($data['lead_groups']));
				}
				
				return $agent_limits;
			}else{
				
				$options = array();
				foreach($lead_group_limits as $groups){
					$options = array_merge($options, $groups);
				}
				
				$intersect = array_intersect_key($options, array_flip($data['lead_groups']));
				return $intersect;
			}
		}
	
	
		/**
		 * ajax wrapper for edit_term_lead_limits
		 */
		public static function ajax_edit_term_lead_limits(){
			$set_limits_per_agent = Inbound_Assigned_Agents_Resources::get_setting('inbound_agents_lead_group_limits');
			$data = $_POST['data'];
			
			echo json_encode(self::edit_term_lead_limits($data, $set_limits_per_agent));
			die();
		
		}
	
		/**
		 * Sets the form submission limits for lead groups
		 */
		public static function edit_term_lead_limits($data, $set_limits_per_agent = 0){
					
			
			/*permission check*/
			if(!current_user_can('editor') && !current_user_can('inbound_marketer')&& !current_user_can('administrator')){
				return array('access_denied' =>__('You do not have the access level to preform this action.', 'inbound-pro'));
			}			
			
			/**sanitize and validate**/
			if($set_limits_per_agent == '1'){
				foreach($data['agent_group_data'] as $agent_id=>$limits){
					foreach($limits as $group=>$limit){
						$data['agent_group_data'][$agent_id][$group] = ((int)$limit) ? (int)$limit : -1;
					}
				}
			}else{
				$data['group_data'] = array_map(function($value){ return ((int)$value) ? (int)$value : -1; }, $data['agent_group_data'][0]);
				unset($data['agent_group_data']);
			}			
			
			
			$lead_group_limits = Inbound_Assigned_Agents_Resources::$lead_group_limits;
			
			/*if set to deal with limits on an individual agent basis*/
			if($set_limits_per_agent == '1'){
				if(!empty($data['agent_group_data'])){
					foreach($data['agent_group_data'] as $agent_id=>$limits){
						/*get the agent's lead limits*/
						$meta = get_term_meta((int)$agent_id, 'inbound_agents_term_lead_limits');
						/*make sure we're only working with existing values*/
						$new_meta = array_intersect_key($limits, $meta[0]);
						/*replace the existing values with the supplied ones*/
						$meta[0] = array_replace($meta[0], $new_meta);
						/*update the group limits*/
						update_term_meta($agent_id, 'inbound_agents_term_lead_limits', $meta[0]);
						/*update the resource variable*/
						Inbound_Assigned_Agents_Resources::$lead_group_limits[$agent_id] = $meta[0];
					}
				}
				return __('Limits Updated!', 'inbound-pro');
			}
			/*if set to set limits for groups across agents*/
			else{
				foreach($lead_group_limits as $agent_id=>$values){
					/*get the current agent's limits*/
					$meta = get_term_meta((int)$agent_id, 'inbound_agents_term_lead_limits');
					/*get the supplied values that are present in the current agent's meta*/
					$new_meta = array_intersect_key($data['group_data'], $meta[0]);
					/*replace the old values with the new ones*/
					$meta[0] = array_replace($meta[0], $new_meta);
					/*update the agent's group limits*/
					update_term_meta((int)$agent_id, 'inbound_agents_term_lead_limits', $meta[0]);
					/*update the resource variable*/
					Inbound_Assigned_Agents_Resources::$lead_group_limits[$agent_id] = $meta[0];
					
				}
				return __('Limits Updated!', 'inbound-pro');
			}
		
		}
	
	/**
	 * Checks to make sure the lead count is under the limit
	 * Returns false if there isn't atleast one group within the limit
	 */
	public static function check_if_under_limits($agent_id, $groups){
		
		$limit = get_term_meta((int)$agent_id, 'inbound_agents_term_lead_limits');
				
		$group_meta = get_term_meta((int)$agent_id, 'inbound_agents_lead_groups');
		$return_groups = array();
						
		$intersect = array_intersect_key($group_meta[0], array_flip($groups));
		foreach($intersect as $key=>$value){
			if($limit[0][$key] == '-1' || count($value) < $limit[0][$key]){
				$return_groups[] = $key;
	
			}		
		}
		return (empty($return_groups)) ? false : $return_groups;
	
	}
	
		/**
		 * Gets the term lead groups that the supplied agents have. 
		 * Optionally retrieves the groups the agents have in common.
		 */
		public static function ajax_get_agent_term_lead_groups(){
			$get_groups_in_common = $_POST['get_groups_in_common'];
			$data = array_map(function($id){ return (int)$id; }, $_POST['data']);
			$agent_terms = Inbound_Assigned_Agents_Resources::$agent_term_lead_groups;
			
			//get the groups in common
			if($get_groups_in_common == '1'){
				
				if(count($data) > 1){
					$search_for_common_terms;
					
					/*get the lead_groups for the supplied agents*/
					foreach($data as $agent_id){
						$search_for_common_terms[] = $agent_terms[$agent_id];
					}
					
					/*find all the keys they have in common. Those are the lead_groups*/
					$intersect = call_user_func_array('array_intersect_key', $search_for_common_terms);
					
					/*get just the keys of the resulting array*/
					$keys = array_keys($intersect);
					
					echo json_encode($keys);
					die();
					
				}else if(count($data) == 1){

					echo json_encode(array_keys($agent_terms[$data[0]]));
					die();
				}else{
					
					echo json_encode(__('No agent selected', 'inbound-pro'));
					die();
				}

			}else{
			//else: get all the groups that the selected agents have
			
				if(count($data) > 1){
					$agent_lead_groups;
					
					/*get the lead_groups for the supplied agents*/
					foreach($data as $agent_id){
						$agent_lead_groups[] = $agent_terms[$agent_id];
					}
					
					/*filter out the indexes of agent's that don't have groups*/
					$agent_lead_groups = array_filter($agent_lead_groups);
					
					/*flatten the resulting array with merge array, also replaces duplicate keys*/
					$result = call_user_func_array('array_merge', $agent_lead_groups);
			
					/*get the keys of the resulting array*/
					$keys = array_keys($result);
					
					/*echo an array of the keys*/
					echo json_encode($keys);
					die();
					
				}else if(count($data) == 1){
					/*if it's querying for only one agent, just return that agent's lead group keys*/
					echo json_encode(array_keys($agent_terms[$data[0]]));
					die();
				}else{
					
					echo json_encode(__('No agent selected', 'inbound-pro'));
					die();
				}
			}
		}
	
	/**
	 * Ajax wrapper for edit_agent_extra_data
	 */
	public static function ajax_edit_agent_extra_data(){
		$agent_id = $_POST['data']['agent_id'];
		$field_data = stripslashes_deep($_POST['data']['field_data']);
		$fields_to_delete = $_POST['data']['fields_to_delete'];

		echo json_encode(self::edit_agent_extra_data($agent_id, $field_data, $fields_to_delete));
		die();
	}
	
	/**
	 * Creates and edits agent extra data. Extra data are things like "Agent Since", or "Job Title"
	 * Also can be used for notes on the agent
	 * Only works on a single agent at a time
	 * params: $agent_id int, $field_data array, $fields_to_delete array
	 */
	public static function edit_agent_extra_data($agent_id, $field_data, $fields_to_delete){
		
			/*permission check*/
			if(!current_user_can('editor') && !current_user_can('inbound_marketer')&& !current_user_can('administrator')){
				return array('access_denied' =>__('You do not have the access level to preform this action.', 'inbound-pro'));
			}
		
		/*if an array is passed, get the int value of the first index*/
		if(is_array($agent_id)){ $agent_id = (int)$agent_id[0]; }

		if(empty($agent_id) || empty($field_data)){
			return array('error' => __('One or more of the fields is empty', 'inbound-pro'));
		}

		if(!is_array($field_data)){
			return array('error' => __('The supplied field data is not an array. The data must be an array', 'inbound-pro'));
		}
		
		/*if $field_data has more than a single index, format it into a single index array*/
		if(isset($field_data[1])){
			$arr = [];
			foreach($field_data as $field){
				$arr += $field;
			}
			$field_data = $arr;
		}
		
		/*sanitize the data*/
		$cleaned_data = array();
		foreach($field_data as $key => $value){
			foreach($value as $label => $string){
			$cleaned_data[$key] = array(sanitize_text_field($label) => sanitize_text_field($string));
			}

		}
		$field_data = $cleaned_data;

		/*get the stored data*/
		$meta = get_term_meta((int)$agent_id, 'inbound_agents_extra_data');

		if(empty($meta[0])){
			//set $meta[0] for an empty array and proceed
			$meta[0] = $field_data;
			update_term_meta((int)$agent_id, 'inbound_agents_extra_data', $meta[0]);

			return array('success' => __('Agent data created!', 'inbound-pro'));
			
		}
		
		$new_meta = array_replace($meta[0], $field_data);
		
		if(!empty($fields_to_delete)){
			$fields_to_delete = array_map( function($field){ return sanitize_text_field($field); }, $fields_to_delete);
			$new_meta = array_diff_key($new_meta, array_flip($fields_to_delete));
		}
		
		update_term_meta((int)$agent_id, 'inbound_agents_extra_data', $new_meta);
		
		return array('success' => __('Agent data updated!', 'inbound-pro'));
	}
	
	/**
	 * Ajax wrapper for get_agent_extra_data
	 */
	public static function ajax_get_agent_extra_data(){
		$agent_id = $_POST['data']['agent_id'];
		$fields = stripslashes_deep($_POST['data']['fields']);
		$return_all_fields = (int)$_POST['data']['return_all_fields'];
		
		echo json_encode(self::get_agent_extra_data($agent_id, $fields, $return_all_fields));
		die();
	}
	
	
	/**
	 * Retrieves agent extra data based on supplied field
	 * The field format is array( $key => array("Field Label" => "Field Value") );
	 * The values are retrieved with $key
	 */
	public static function get_agent_extra_data($agent_id, $fields, $return_all_fields = 0){
		$meta = get_term_meta((int)$agent_id, 'inbound_agents_extra_data');
		
		if($return_all_fields == '1'){
			return $meta[0];
		}
		
		if(is_array($fields) && !empty($fields) && !empty($meta)){
			$intersect = array_intersect_key($meta[0], array_flip($fields));

			return $intersect;
		
		}
		
		return '';
	}
	
	/**
	 * Deletes agent based on input from the agent table list
	 */
	public static function ajax_list_action_delete(){

		/*permission check*/
		if(!current_user_can('editor') && !current_user_can('inbound_marketer')&& !current_user_can('administrator')){
			echo json_encode(array('access_denied' =>__('You do not have the access level to preform this action.', 'inbound-pro')));
			die();
		}

		parse_str($_POST['data'], $data);

		if(!wp_verify_nonce($data['nonce'], 'delete-agent-term' . $data['id'])){
			echo json_encode(array('error' =>__('Invalid nonce', 'inbound-pro')));
			die();
		}
		
		$args = array('number' => -1, 'fields' => 'id');
		
		/*remove the term reference from the user profile*/
		$user_ids = get_users($args);
		foreach($user_ids as $user_id){
			if(get_user_meta($user_id, 'inbound_assigned_lead_term', true) == $data['id']){
				delete_user_meta($user_id, 'inbound_assigned_lead_term');
			}
		}
		
		wp_delete_term($data['id'], $data['taxonomy']);
		
		echo json_encode(array('success' =>__('Agent profile deleted!', 'inbound-pro'), 'tag_id' => $data['id']));
		die();
	}
	
	}//end of class

	new Inbound_Assigned_Agents_Management;

}

?>
