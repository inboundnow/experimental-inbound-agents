jQuery(document).ready(function($){
	/*group refresh waiters*/
	var firstWaiter;
	var secondWaiter;		

	var priorAction;  //the prior agent action button clicked
	var execution;	//lead action being taken

	/**first spinner A options**/	
	var firstSpinnerA = {
		 lines: 13, // The number of lines to draw
		 length: 14, // The length of each line
		 width: 7, // The line thickness
		 radius: 24, // The radius of the inner circle
		 scale: 0.25, // Scales overall size of the spinner
		 corners: 1, // Corner roundness (0..1)
		 color: '#000', // #rgb or #rrggbb or array of colors
		 opacity: 0.25, // Opacity of the lines
		 rotate: 0, // The rotation offset
		 direction: 1, // 1: clockwise, -1: counterclockwise
		 speed: 1, // Rounds per second
		 trail: 60, // Afterglow percentage
		 fps: 20, // Frames per second when using setTimeout() as a fallback for CSS
		 zIndex: 2e9, // The z-index (defaults to 2000000000)
		 className: 'first-lead-group-spinner', // The CSS class to assign to the spinner
		 top: 'calc(100% + 16px)', // Top position relative to parent
		 left: '95%', // Left position relative to parent
		 shadow: false, // Whether to render a shadow
		 hwaccel: false, // Whether to use hardware acceleration
		 position: 'absolute', // Element positioning
	}
	
	/**first spinner B options**/	
	var firstSpinnerB = {
		 lines: 13, // The number of lines to draw
		 length: 14, // The length of each line
		 width: 7, // The line thickness
		 radius: 24, // The radius of the inner circle
		 scale: 0.25, // Scales overall size of the spinner
		 corners: 1, // Corner roundness (0..1)
		 color: '#000', // #rgb or #rrggbb or array of colors
		 opacity: 0.25, // Opacity of the lines
		 rotate: 0, // The rotation offset
		 direction: 1, // 1: clockwise, -1: counterclockwise
		 speed: 1, // Rounds per second
		 trail: 60, // Afterglow percentage
		 fps: 20, // Frames per second when using setTimeout() as a fallback for CSS
		 zIndex: 2e9, // The z-index (defaults to 2000000000)
		 className: 'first-lead-group-spinner', // The CSS class to assign to the spinner
		 top: '-33px', // Top position relative to parent
		 left: '95%', // Left position relative to parent
		 shadow: false, // Whether to render a shadow
		 hwaccel: false, // Whether to use hardware acceleration
		 position: 'absolute', // Element positioning
	}
	
	/**second spinner options**/	
	var secondSpinner = {
		 lines: 13, // The number of lines to draw
		 length: 14, // The length of each line
		 width: 7, // The line thickness
		 radius: 24, // The radius of the inner circle
		 scale: 0.25, // Scales overall size of the spinner
		 corners: 1, // Corner roundness (0..1)
		 color: '#000', // #rgb or #rrggbb or array of colors
		 opacity: 0.25, // Opacity of the lines
		 rotate: 0, // The rotation offset
		 direction: 1, // 1: clockwise, -1: counterclockwise
		 speed: 1, // Rounds per second
		 trail: 60, // Afterglow percentage
		 fps: 20, // Frames per second when using setTimeout() as a fallback for CSS
		 zIndex: 2e9, // The z-index (defaults to 2000000000)
		 className: 'second-lead-group-spinner', // The CSS class to assign to the spinner
		 top: 'calc(100% + 16px)', // Top position relative to parent
		 left: '95%', // Left position relative to parent
		 shadow: false, // Whether to render a shadow
		 hwaccel: false, // Whether to use hardware acceleration
		 position: 'absolute', // Element positioning
	}
	
	jQuery('#inbound-bulk-action-agent-selector,#inbound-bulk-action-lead-group-selector,\
			#inbound-bulk-action-agent-selector-2,#inbound-bulk-action-lead-group-selector-2').select2();	
	
	/**trigger the refresh if the select fron all/common groups changes**/
	jQuery('input[name=inbound-agents-lead-group-radio]').on('change', function(event){
		/*if the second group selector is visible, refresh it too*/
		if(jQuery('#inbound-bulk-action-lead-group-selector-2').is(':visible')){
			refreshSelector1();
			refreshSelector2();
		}else{
			refreshSelector1();
		}
	});
	
		
	
	/**refreshes the first lead group selector after a wait of 750ms**/
	/**Used for Add Leads, Remove Leads and Transfer Leads**/
	jQuery('#inbound-bulk-action-agent-selector').on('change', function(){
		clearTimeout(firstWaiter);
		firstWaiter = setTimeout(refreshSelector1, 750);
		
	});
	
	/**refreshes the second agent selector, if it's visible, after a wait of 750ms**/
	/**For Transfer Leads**/
	jQuery('#inbound-bulk-action-agent-selector-2').on('change', function(){
			clearTimeout(secondWaiter);
			secondWaiter = setTimeout(refreshSelector2, 750);
	});
	
	
	/**refreshes the first lead group selector**/
	/**Used for Add Leads, Remove Leads and Transfer Leads**/
	function refreshSelector1(){
		//empty the selector
		jQuery('#inbound-bulk-action-lead-group-selector').empty();
		jQuery('#inbound-bulk-action-lead-group-selector').select2('data', null);
		
		/*if the option to select from all groups is visible, get the value of it*/
		/*otherwise, set getGroupsInCommon to 1 to get the groups that all the selected agents have*/
		if(jQuery('#inbound-agents-lead-group-radio-container').is(':visible')){
			getGroupsInCommon = jQuery('input[name=inbound-agents-lead-group-radio]:checked').val();
		}else{
			getGroupsInCommon = 1;
		}
		
		if(jQuery('#inbound-bulk-action-agent-selector').val() != null){
			
			/*if the "to another agent" checkbox isn't checked*/
			if(!jQuery('#to-another-agent-checkbox').is(':checked')){
				/*create a new spinner just beneath the group selector*/
				var target = jQuery('#s2id_inbound-bulk-action-lead-group-selector');
				var spinner = new Spinner(firstSpinnerA).spin(target[0]);
				
				var target2 = jQuery('#s2id_inbound-bulk-action-lead-group-selector-2');
				var spinner2 = new Spinner(secondSpinner).spin(target2[0]);
			}else{
				/*otherwise, create a spinner above the group selector*/
				var target = jQuery('#s2id_inbound-bulk-action-lead-group-selector');
				var spinner = new Spinner(firstSpinnerB).spin(target[0]);
			}
			jQuery.ajax({
				type : 'POST',
				url : ajaxurl,
				data : {
					action : 'get_agent_term_lead_groups',
					data : jQuery('#inbound-bulk-action-agent-selector').val(),
					get_groups_in_common : getGroupsInCommon,
					},
				success : function(agentLeadGroups){
					agentLeadGroups = JSON.parse(agentLeadGroups);
					
					/*remove the spinner*/
					jQuery('.first-lead-group-spinner,.second-lead-group-spinner').remove();
					
					
					var options = '';
					/*create the options out of the lead groups that the agents have in common*/
					if(agentLeadGroups != null){
						for(var i = 0; i < agentLeadGroups.length; i++){
							//console.log(agentLeadGroups[i]);
							options += '<option value="' + agentLeadGroups[i] + '">' + agentLeadGroups[i] + '</option>';
						}
					}
					
					/*add the options to the selector*/
					jQuery('#inbound-bulk-action-lead-group-selector').append(options);
					
					/*if leads are being transferred between groups in the same agents, append the options to the recipient group selector*/
					if(!jQuery('#to-another-agent-checkbox').is(':checked')){
						
						/*clear the selector*/
						jQuery('#inbound-bulk-action-lead-group-selector-2').empty();
						jQuery('#inbound-bulk-action-lead-group-selector-2').select2('data', null);
						
						/*append the options*/
						jQuery('#inbound-bulk-action-lead-group-selector-2').append(options);
					}
					
				},
			});
		}	
	}
	
	/**refreshes the second agent selector if it's visible. Used in the moving of leads**/
	/**For Transfer Leads**/
	function refreshSelector2(){
	
		if(jQuery('#inbound-bulk-action-lead-group-selector-2').is(':visible')){
			//empty the selector
			jQuery('#inbound-bulk-action-lead-group-selector-2').empty();
			jQuery('#inbound-bulk-action-lead-group-selector-2').select2('data', null);


			var data = jQuery('#inbound-bulk-action-agent-selector-2').val();

			/*if there are agents selected*/
			if(data != null){
				/*if the option to select from all groups is visible, get the value of it*/
				/*otherwise, set getGroupsInCommon to 1 to get the groups that all the selected agents have*/
				if(jQuery('#inbound-agents-lead-group-radio-container').is(':visible')){
					getGroupsInCommon = jQuery('input[name=inbound-agents-lead-group-radio]:checked').val();
				}else{
					getGroupsInCommon = 1;
				}
				
				/*create a new spinner*/
				var target2 = jQuery('#s2id_inbound-bulk-action-lead-group-selector-2');
				var spinner2 = new Spinner(secondSpinner).spin(target2[0]);

				jQuery.ajax({
					type : 'POST',
					url : ajaxurl,
					data : {
						action : 'get_agent_term_lead_groups',
						data : data,
						get_groups_in_common : getGroupsInCommon,
						},
					success : function(agentLeadGroups){
						jQuery('.second-lead-group-spinner').remove();
						agentLeadGroups = JSON.parse(agentLeadGroups);
							
						var options = '';
						//create the options out of the lead groups that the agents have in common
						if(agentLeadGroups != null){
							for(var i = 0; i < agentLeadGroups.length; i++){
								//console.log(agentLeadGroups[i]);
								options += '<option value="' + agentLeadGroups[i] + '">' + agentLeadGroups[i] + '</option>';
							}
						}	
						//add the options to the selector
						jQuery('#inbound-bulk-action-lead-group-selector-2').append(options);
					},

				});
			}
		}	
	}
	
	/*********************create the popup menu*************************/
	
	/**hide the agent action popup if the lead action isn't an agent lead action**/
	jQuery('body').on('click', '.cd-dropdown li', function(){
		if(jQuery(this).attr('data-value') != 'agent-lead-bulk-actions'){
			jQuery('#inbound-agent-option-popup-container').css({'display' : 'none'});
		}
	});

	/**display agent options**/
	jQuery("body").on('click','#agent-lead-bulk-actions > input[type="button"]', function () {
		
		action = jQuery(this).attr('name');
		execution = action;
		
		/**set which inputs are displayed based on which button was clicked**/
		if(action == 'add-leads'){
			/*if an action different from the last one*/
			if(priorAction != action){
				/*hide the prior action set of inputs*/
				jQuery('.used-in-' + priorAction).css({'display' : 'none'});
				/*display the set of inputs used for adding leads to an agent*/
				jQuery('.used-in-add-leads').css({'display' : 'inline-block'});
				/*display the action popup, incase it isn't already*/
				jQuery('#inbound-agent-option-popup-container').css({'display' : 'block'});				
				
				/*uncheck the "to another agent" checkbox if it's checked*/
				if(jQuery('#to-another-agent-checkbox').is(':checked')){
					jQuery('#to-another-agent-checkbox').prop('checked', false).triggerHandler('click');
				}				
				
				/*set this action as the prior*/
				priorAction = action;
			}


		}else if(action == 'remove-leads'){
			if(priorAction != action){
				jQuery('.used-in-' + priorAction).css({'display' : 'none'});
				jQuery('.used-in-remove-leads').css({'display' : 'inline-block'});
				jQuery('#inbound-agent-option-popup-container').css({'display' : 'block'});	
				
				if(jQuery('#to-another-agent-checkbox').is(':checked')){
					jQuery('#to-another-agent-checkbox').prop('checked', false).triggerHandler('click');
				}
							
				priorAction = action;
			}

		}else if(action == 'transfer-leads'){
			if(priorAction != action){
				jQuery('.used-in-' + priorAction).css({'display' : 'none'});
				jQuery('.used-in-transfer-leads').css({'display' : 'inline-block'});
				jQuery('#inbound-agent-option-popup-container').css({'display' : 'block'});			
				priorAction = action;
			}

		}else{
			jQuery('#inbound-agent-option-popup-container').css({'display' : 'none'});
			alert('Error: unknown action!');
		}
		
		/**when the action changes, reset the visibility of the first lead group selector**/
		jQuery('#inbound-agent-lead-group-selector-container').css({'visibility' : 'visible'});
		
		/**change the button text to reflect the action being taken**/
		var buttonText = {	'add-leads' : 'Add Leads',
							'remove-leads' : 'Remove Leads',
							'transfer-leads' : 'Transfer Leads',
		};
		jQuery('#assigned-agents-bulk-action-submit-button').html(buttonText[action]);
		
		/**show the inputs for transferring to another agent if the checkbox is checked**/
		jQuery('#to-another-agent-checkbox').on('click', function(){
			/*empty the selector*/
			jQuery('#inbound-bulk-action-lead-group-selector-2').empty();
			jQuery('#inbound-bulk-action-lead-group-selector-2').select2('data', null);
			
			/*if the checkbox is checked, show the "transfer to agent" agent selector*/
			if(jQuery('#to-another-agent-checkbox').is(':checked')){
				jQuery('.transfer-box-checked').css({'display' : 'inline-block'});
			}else{
				jQuery('.transfer-box-checked').css({'display' : 'none'});
				/*refresh the group selectors if the user unchecks "transfer to agents"*/
				jQuery('#inbound-bulk-action-agent-selector').trigger('change');
			}
			
		});
		
		/**if remove from agent is checked, hide the lead group selector**/
		jQuery('#inbound-agents-remove-leads-radio-container').on('click', function(){
			if(jQuery('input[name="inbound-agents-remove-leads-radio"]:checked').val() == '1'){
				jQuery('#inbound-agent-lead-group-selector-container').css({'visibility' : 'hidden'});
			}else{
				jQuery('#inbound-agent-lead-group-selector-container').css({'visibility' : 'visible'});
			}
		
		});
	});	
		
	/**agent lead action data vars**/
	var total_records; //number of selected leads
	var leadIds; //array of lead ids
	var batch_limit; //the limit of how many leads to run
	var offset = 0; //starting point in the array of leads
	var endpoint; //end point in the array of leads
	var process_count //used for the number of loops to call, and for the bargraph display
	var process_laps = 0; //number of processed batches
		
	/**the agent lead action data handler**/	
	jQuery('#assigned-agents-bulk-action-submit-button').on('click', function(){
		total_records = jQuery("#lead-manage-table").find("input[name='ids[]']:checked").length;

		/**check to make sure there are leads**/
		var possibleActions = {'add-leads' : 'add to an agent or group.', 'remove-leads' : 'remove from an agent or group', 'transfer-leads' : 'transfer'}; /*action list for UI purposes*/
		if(total_records < 1){
			swal({
				title: 'No leads selected',
				text : 'Please select lead/s to ' + possibleActions[action],
				type: 'warning',
			});
			return false;
		}

		/**make sure all visible agent selectors are filled out**/
		var agentCheck = false
		jQuery('.select2-container.agent-selector:visible').each(function(index){
				if(jQuery(this).css('visibility') != 'hidden'){
					if(jQuery(this).select2('data').length < 1){
						agentCheck = true;
						return;
					}
				}
			});
		if(agentCheck){
			swal({
				title: 'Empty Agent Selector',
				text: 'One of the agent selectors is empty',
				type: 'error',
			});
			return false;
		}
	
		/**make sure all visible group selectors are filled out**/
		if(action != 'add-leads'){
			var groupCheck = false
			jQuery('.select2-container.lead-group-selector:visible').each(function(index){
					if(jQuery(this).css('visibility') != 'hidden'){
						if(jQuery(this).select2('data').length < 1){
							groupCheck = true;
							return;
						}
					}
				});
			if(groupCheck){
				swal({
					title: 'Empty Group Selector',
					text: 'One of the lead group selectors is empty',
					type: 'error',
				});
				return false;
			}
		}
		/*build the array of leads*/
		leadIds = jQuery("#lead-manage-table").find("input.lead-select-checkbox:checked").map(function () {
			return this.value;
		}).get();
	
		/*limiter for how many leads are sent to edit_term_lead_group_data*/
		if(leadIds.length >= 20){
			batch_limit = 20;
		}else{
			batch_limit = leadIds.length; /*if fewer than 20 leads are being processed, set the limit for the lead number so php isn't processing empty indexes*/
		}
		
		endpoint = batch_limit;
		
		process_count = Math.ceil(total_records / batch_limit); /*number of passes it will take to process the leads.*/
		var text = "";
		var i;	/*counter for the number of progress bars to create*/
		
		for (i = 0; i < process_count; i++) {
			text += '<tr id="row"'+i+'><td><p id="progress'+i+'" class="progress"></p></td><td><div  id="progressbar'+i+'" class="ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="0" aria-valuenow="0"></div></td></tr>';
		}
		
		jQuery("#progress-table #the-progress-list").html(text);
		
		
		if(action == 'remove-leads'){
			removeFromAgents = jQuery('input[name=inbound-agents-remove-leads-radio]:checked').val();
		}else if(action == 'transfer-leads'){
			if(jQuery('input[name=inbound-agents-remove-leads-checkbox]:checked').val() == 'on'){
				removeFromAgents = '1';
			}else{
				removeFromAgents = '0';
			};
		}else{
			removeFromAgents = '';
		}

		/*when the submit button is clicked, assemble the data*/
		var data = {
			execution : execution,
			agent_ids : jQuery('#inbound-bulk-action-agent-selector').val(),
			lead_groups : jQuery('#inbound-bulk-action-lead-group-selector').val(),
			lead_ids : '',
			agent_ids_2 : jQuery('#inbound-bulk-action-agent-selector-2').val() ? jQuery('#inbound-bulk-action-agent-selector-2').val() : jQuery('#inbound-bulk-action-agent-selector').val(), //I think this will work, the idea is to have an agent id to send to
			clone_leads : 0,
			lead_groups_2 : jQuery('#inbound-bulk-action-lead-group-selector-2').val(),
			remove_from_agent : removeFromAgents,
		}
		
		/*add the first pass worth of leads*/
		var new_array = leadIds.slice(offset, endpoint);
		data.lead_ids = new_array;
		
		$( "#export-leads" ).trigger( "click" );
		
		/*leadAction function call*/
		agentLeadAction(data);
	
		return false;	
		

	});


	function agentLeadAction(data){
		/**create the completion message info**/
		/*get the selected agent display name*/
		var firstAgentSelector = [];
		jQuery(jQuery('#inbound-bulk-action-agent-selector').select2('data')).each(function(index, value){ firstAgentSelector.push(value.text);});

		/*get the recipient agent display names*/
		var secondAgentSelector = [];
		jQuery(jQuery('#inbound-bulk-action-agent-selector-2').select2('data')).each(function(index, value){ secondAgentSelector.push(value.text);});
		
		/*get the first group selector's displayed values*/
		var firstGroupSelectorA = []; //case: adding leads
		jQuery(jQuery('#inbound-bulk-action-lead-group-selector').select2('data')).each(function(index, value){ firstGroupSelectorA.push(value.text);});
		firstGroupSelectorA = (firstGroupSelectorA.length) ? ' In the groups: ' + firstGroupSelectorA.join(', ') : '';
		
		var firstGroupSelectorB = []; //case: removing leads
		jQuery(jQuery('#inbound-bulk-action-lead-group-selector').select2('data')).each(function(index, value){ firstGroupSelectorB.push(value.text);});
		firstGroupSelectorB = (firstGroupSelectorB.length) ? ' From the groups: ' + firstGroupSelectorB.join(', ') : '';

		/*get the second group selector's displayed values*/
		var secondGroupSelector = [];
		jQuery(jQuery('#inbound-bulk-action-lead-group-selector-2').select2('data')).each(function(index, value){ secondGroupSelector.push(value.text);});
		secondGroupSelector = (secondGroupSelector.length) ? ' to the groups: ' + secondGroupSelector.join(', ') : '';

		/*transfer action text*/
		var transfer = (secondAgentSelector.length > 0) ? 'transferred from: ' + firstAgentSelector.join(', ') +  ' to ' + secondAgentSelector.join(', ') : 'transferred ' + firstGroupSelectorB + secondGroupSelector;


		/*setup the action response text*/
		var possibleActions = {	'add-leads' : 'assigned to: ' + firstAgentSelector.join(', ') + firstGroupSelectorA, 
								'remove-leads' : 'removed from:  ' + firstAgentSelector.join(', ') + firstGroupSelectorB, 
								'transfer-leads' : transfer, 
		};
			
		jQuery.ajax({
			type : 'POST',
			url : ajaxurl,
			data : {
				action : 'edit_term_lead_group_data',
				data : data,
						
				},
			success : function(response){
				/**on each success**/
				response = JSON.parse(response);
				/*fill the progress bar that corresponds to the last batch of leads*/
				jQuery( "#progressbar"+process_laps ).progressbar({ value: 100 });
				
				/*if the end index for string slice is less than the total number of records*/
				if(endpoint <= total_records){
					jQuery( "#progress"+process_laps ).text(offset + " - " + endpoint  + " of " + total_records);
				}	
				
				/*if the number of batches processed is less than the total number of batches to run*/
				if(process_laps < process_count){
					/*slice a batch worth of leads out of the lead array*/
					var new_array = leadIds.slice(offset, endpoint);
					process_laps++;

					/*increment the end index for slice, for the next batch of leads*/
					if((endpoint + batch_limit) >= total_records){
						endpoint = total_records;
					}else{
						endpoint += batch_limit;
					}
					/*increment the start index for slice, for the next batch of leads*/
					offset += batch_limit;
					
					/*set the leads to be processed to the leads that were sliced out of the lead array earlier*/
					data.lead_ids = new_array;
					
					/*call itsself with the new batch of leads*/
					agentLeadAction(data);

				}else{
					/*if the number of batches processed is => than the number of batches to process, output the completed message. Because we're done! */
					jQuery( "#progress"+process_laps ).text(total_records + " - " + total_records + " of " + total_records);
					jQuery(".download-leads-csv").html('<p>' + total_records + ' leads successfully ' + possibleActions[action] + '</p>');
				}
			},
		});

	}
});
