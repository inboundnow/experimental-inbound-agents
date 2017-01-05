<?php

if(!class_exists('Inbound_Assigned_Agents_Leads_Post_Type')){
	
	class Inbound_Assigned_Agents_Leads_Post_Type{
		
		function __construct(){
			self::add_hooks();
			
		}
		
		public static function add_hooks(){
			/*replace the default metabox callback*/
			add_action('add_meta_boxes', array(__CLASS__, 'change_inbound_agent_metabox_callback'));
			/*on post save, update the lead groups*/
			add_action('save_post', array(__CLASS__, 'save_inbound_agent_metabox_settings'));
			
		}
		
		/**
		 * Replaces the default metabox callback for the Inbound Agents metabox with a new one
		 */
		public static function change_inbound_agent_metabox_callback($post_type){
			global $post, $wp_meta_boxes;
			$wp_meta_boxes['wp-lead']['side']['core']['inbound_assigned_leaddiv']['callback'] = array(__CLASS__, 'inbound_agents_lead_cpt_agent_metabox');
		}
		
		/**
		 * Outputs a custom Inbound Agent metabox
		 */
		public static function inbound_agents_lead_cpt_agent_metabox($post){
			$all_agents = get_terms(array('taxonomy' => 'inbound_assigned_lead', 'hide_empty' => false));
			$agents_assigned_to_this_lead = wp_get_post_terms($post->ID, 'inbound_assigned_lead', array('fields' => 'ids'));
			?>

			<div id="taxonomy-inbound_assigned_lead" class="categorydiv">
				<ul id="inbound_assigned_lead-tabs" class="category-tabs">
					<li class="tabs"><a href="#inbound_assigned_lead-all">All Agents</a></li>
					<li class="hide-if-no-js"><a href="#inbound_assigned_lead-pop">Most Used</a></li>
				</ul>

				<div id="inbound_assigned_lead-pop" class="tabs-panel" style="display: none;">
					<ul id="inbound_assigned_leadchecklist-pop" class="categorychecklist form-no-clear">
						
						<li id="popular-inbound_assigned_lead-189" class="popular-category">
							<label class="selectit">
								<input id="in-popular-inbound_assigned_lead-189" type="checkbox" checked="checked" value="189">
								Matt
							</label>
						</li>

						<li id="popular-inbound_assigned_lead-190" class="popular-category">
							<label class="selectit">
								<input id="in-popular-inbound_assigned_lead-190" type="checkbox" checked="checked" value="190">
								Bob
							</label>
						</li>
					</ul>
				</div>

				<div id="inbound_assigned_lead-all" class="tabs-panel">
					<input type="hidden" name="tax_input[inbound_assigned_lead][]" value="0">			
					<ul id="inbound_assigned_leadchecklist" data-wp-lists="list:inbound_assigned_lead" class="categorychecklist form-no-clear">

					<?php
					foreach($all_agents as $agent){
						/*if the agent has been assigned to this lead*/
						if(in_array($agent->term_id, $agents_assigned_to_this_lead)){
							?>
							<li id="inbound_assigned_lead-<?php echo $agent->term_id; ?>" class="popular-category">
								<input value="<?php echo $agent->term_id; ?>" type="checkbox" name="tax_input[inbound_assigned_lead][]" id="in-inbound_assigned_lead_category<?php echo $agent->term_id; ?>" checked="checked">
								<label for="in-inbound_assigned_lead-<?php echo $agent->term_id; ?>" class="selectit"><?php echo $agent->name; ?></label></li>
								<?php
								/*if there are lead groups for this agent*/
								if(!empty(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent->term_id])){
								?>
								<ul class="children">
								<?php
									foreach(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent->term_id] as $group=>$leads){
										if(in_array($post->ID, $leads)){
											?>
											<input value="<?php echo $group; ?>" type="checkbox" name="inbound_agents_lead_group_input[inbound_assigned_lead][<?php echo $agent->term_id; ?>][]" id="in-inbound_assigned_lead_group-<?php echo $group; ?>" checked="checked">
											<label for="in-inbound_assigned_lead_group-<?php echo $group; ?>" class="selectit"><?php echo $group; ?></label>
											<br />
											<?php
										}else{
											?>
											<input value="<?php echo $group; ?>" type="checkbox" name="inbound_agents_lead_group_input[inbound_assigned_lead][<?php echo $agent->term_id; ?>][]" id="in-inbound_assigned_lead_group-<?php echo $group; ?>">
											<label for="in-inbound_assigned_lead_group-<?php echo $group; ?>" class="selectit"><?php echo $group; ?></label>
											<br />
											<?php
										}
									}
								?>
								</ul>
								<?php
								}

						}else{
							?>
							<li id="inbound_assigned_lead-<?php echo $agent->term_id; ?>">
								<input value="<?php echo $agent->term_id; ?>" type="checkbox" name="tax_input[inbound_assigned_lead][]" id="in-inbound_assigned_lead-<?php echo $agent->term_id; ?>">
								<label for="in-inbound_assigned_lead-<?php echo $agent->term_id; ?>" class="selectit"><?php echo $agent->name; ?></label></li>
								<?php
								/*if there are lead groups for this agent*/
								if(!empty(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent->term_id])){
									?>
									<ul class="children">
									<?php
									foreach(Inbound_Assigned_Agents_Resources::$agent_term_lead_groups[$agent->term_id] as $group=>$leads){
										?>
										<input value="<?php echo $group; ?>" type="checkbox" name="inbound_agents_lead_group_input[inbound_assigned_lead][<?php echo $agent->term_id; ?>][]" id="in-inbound_assigned_lead_group-<?php echo $group; ?>">
										<label for="in-inbound_assigned_lead_group-<?php echo $group; ?>" class="selectit"><?php echo $group; ?></label>
										<br />
									<?php
									}
									?>
									</ul>
									<?php
								}
						}//end else
							?>
							</li>
			<?php	}//end foreach ?>
				</div>
			</div>
			<?php
		}
		
		/**
		 * Saves the lead group metabox settings
		 */
		public static function save_inbound_agent_metabox_settings($post_id){
	
			/*if the post isn't a lead post exit*/
			if(get_post_type($post_id) != 'wp-lead'){
				return;
			}
		
			if(!empty($_POST['inbound_agents_lead_group_input']['inbound_assigned_lead'])){
				/*foreach agent, transfer leads out of all other groups and into the ones set by the metabox*/
				foreach($_POST['inbound_agents_lead_group_input']['inbound_assigned_lead'] as $agent_id=>$lead_groups){
					$args = array(	'agent_ids' => array($agent_id), 'agent_ids_2' => array($agent_id), 
									'lead_groups_2' => array_values($lead_groups), 'lead_ids' => array($post_id),
									'transfer_from_other_groups' => '1', 'execution' => 'transfer-leads');
					Inbound_Assigned_Agents_Management::edit_term_lead_group_data($args);
				}
			}
		}
		
	}
	
	new Inbound_Assigned_Agents_Leads_Post_Type;
	
}

?>
