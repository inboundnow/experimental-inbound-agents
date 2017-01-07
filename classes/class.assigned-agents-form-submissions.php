<?php

if(!class_exists('Inbound_Assigned_Agents_Inbound_Forms_Submissions')){

	class Inbound_Assigned_Agents_Inbound_Forms_Submissions{
	
	
		function __construct(){
			self::load_hooks();
		}
	
		public static function load_hooks(){
			
			/*after the lead has been created, update the concerned agents*/
			add_filter('inbound_store_lead_post', array(__CLASS__, 'update_agents_with_new_submission'));
			
			/*on form save, reset the rotation counter*/
			add_action('wp_ajax_reset_form_rotating_counter', array(__CLASS__, 'ajax_reset_form_rotating_counter'));
		}
	
		public static function update_agents_with_new_submission($lead){
			
			$settings = Inbound_Assigned_Agents_Resources::get_setting('inbound_agents_rotate_agent_lead_assignment');

			$form_values = get_post_meta($lead['form_id'], 'inbound_form_values');
			parse_str($form_values[0], $form_values);
			
			if(!isset($form_values['inbound_shortcode_inbound_assign_agent_enable']) || $form_values['inbound_shortcode_inbound_assign_agent_enable'] != 'on') {
				return;
			}
			
			/*if rotating lead assignment has been selected in the extension settings*/
			if($settings == 1){

				/*get the rotating assignment counter from the form meta*/
				$rotation_counter = get_post_meta($lead['form_id'], 'inbound_assign_agents_rotation_counter', true);

				/*get the number of agents assigned to the form*/
				$agent_count = count($form_values['inbound_shortcode_inbound_assign_agent']);

				/*get the agent to assign to with the remainder of dividing the counter by the agent count*/
				$pointer = $rotation_counter % $agent_count;

				/*get the agent id*/
				$agent_id = $form_values['inbound_shortcode_inbound_assign_agent'][$pointer];

				/*assign to lead groups if set to*/
				if($form_values['inbound_shortcode_inbound_assign_to_agent_lead_group_enable'] == 'on'){

					/*shorten the group variable*/
					$groups = $form_values['inbound_shortcode_inbound_assign_to_agent_lead_group'];


					for($i = 0; $i < $agent_count; $i++){
						/*check_if_under_limits returns false if the group is not under the limit*/
						$checked_groups = Inbound_Assigned_Agents_Management::check_if_under_limits($agent_id, $groups);
						if($checked_groups !== false){
							break;
						}else{
							//move the pointer one to try again
							$pointer++;
							$agent_id = $form_values['inbound_shortcode_inbound_assign_agent'][$pointer];
						}
					}
						/*if there is an agent who's leads are under the limit*/
						if($checked_groups !== false){
							$data = array(
										'execution' => 'add-leads',
										'agent_ids' => array( $agent_id ),
										'lead_groups' => $checked_groups,
										'lead_ids' => array($lead['id']),
							);

							/*assign the lead to the agent and put the lead in the group/s*/
							Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);

						}

					}else{
						/*if the user opted not to put leads into groups, just add the lead to the agent*/
							$data = array(
										'execution' => 'add-leads',
										'agent_ids' => array( $agent_id ),
										'lead_ids' => array($lead['id']),
							);

							Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);
					}

				/*and update the counter for next time*/
				$update_meta = update_post_meta($lead['form_id'], 'inbound_assign_agents_rotation_counter', (int)$rotation_counter + 1);

			}else{
				/**if leads are to be assigned to all form agents:**/

				/*loop through the assigned agents*/
				foreach($form_values['inbound_shortcode_inbound_assign_agent'] as $agent_id){

					/*assign to lead groups if set to*/
					if($form_values['inbound_shortcode_inbound_assign_to_agent_lead_group_enable'] == 'on'){
						/*shorten the group variable*/
						$groups = $form_values['inbound_shortcode_inbound_assign_to_agent_lead_group'];

						$checked_groups = Inbound_Assigned_Agents_Management::check_if_under_limits($agent_id, $groups);

						if($checked_groups !== false){
							$data = array(
										'execution' => 'add-leads',
										'agent_ids' => array( $agent_id ),
										'lead_groups' => $checked_groups,
										'lead_ids' => array($lead['id']),
							);

							Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);
						}
					}else{
						/**if not set to put in groups, just assign the leads to agents**/
								$data = array(
											'execution' => 'add-leads',
											'agent_ids' => array( $agent_id ),
											'lead_ids' => array($lead['id']),
								);

						/*add lead to the agent term for default taxonomy functionality*/
						Inbound_Assigned_Agents_Management::edit_term_lead_group_data($data);
					}
				}
			}

		}
	
		
		/*reset the rotating agent counter every time the form is saved*/
		public static function ajax_reset_form_rotating_counter(){
			
			$check_nonce = wp_verify_nonce( $_POST['nonce'], 'inbound-shortcode-nonce' );
			if( !$check_nonce ) {
				exit;
			}
			
			if(isset($_POST['post_id']) && !empty($_POST['post_id'])){
				update_post_meta((int)$_POST['post_id'], 'inbound_assign_agents_rotation_counter', 0);
			}
			
		
			die();
		}
		
		
		
		
		
		
		
	}	
		
		

	new Inbound_Assigned_Agents_Inbound_Forms_Submissions;



}







?>
