jQuery(window).load(function($){
	
	var wait,
		agentList, 
		formSettings,
		commonAgentLeadGroups,
		firstCall = true,
		displayNames = [],
		formSettingSelectedAgents = [],
		formSettingSelectedLeadGroups = [],
		formSettingList = {};
	
	/**spinner options**/	
	var opts = {
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
		 className: 'group-spinner', // The CSS class to assign to the spinner
		 top: '16px', // Top position relative to parent
		 left: 'calc(100% + 25px)', // Left position relative to parent
		 shadow: false, // Whether to render a shadow
		 hwaccel: false, // Whether to use hardware acceleration
		 position: 'relative', // Element positioning
	}
	var target = document.getElementsByClassName('parent-inbound_shortcode_inbound_assign_to_agent_lead_group[]');
	var spinner = new Spinner(opts).spin(target[0].childNodes[0]);
		
	getSettings(); //get the saved settings
	getAgentList(); //get the saved agents


	/**invoke select2**/
	jQuery('#inbound_shortcode_inbound_assign_agent\\[\\],#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').select2();

	
	/**if the saved settings are set to assign to an agent, display the agent selector, 
	 * the option to assign to groups, and the option to notify the assigned agents **/
	if(formSettingList.assignToAgent == 'on'){
		jQuery('.parent-inbound_shortcode_inbound_assign_agent\\[\\],\
		.parent-inbound_shortcode_inbound_assign_to_agent_lead_group_enable,\
		.parent-inbound_shortcode_inbound_assign_notify_agents').css({'display' : 'table-row-group'});
	}
	/**if the saved settings are set to assign to an agent group, display the group selector**/
	if(formSettingList.assignToGroups == 'on'){
		jQuery('.parent-inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').css({'display' : 'table-row-group'});		
	}

	/**the assign to agent checkbox listener**/
	jQuery('#inbound_shortcode_inbound_assign_agent_enable').on('change', function(){
		if(jQuery('#inbound_shortcode_inbound_assign_agent_enable').is(':checked')){
			jQuery('.parent-inbound_shortcode_inbound_assign_agent\\[\\],\
					.parent-inbound_shortcode_inbound_assign_to_agent_lead_group_enable,\
					.parent-inbound_shortcode_inbound_assign_notify_agents').css({'display' : 'table-row-group'});
		}else{
			jQuery('.parent-inbound_shortcode_inbound_assign_agent\\[\\],\
					.parent-inbound_shortcode_inbound_assign_to_agent_lead_group_enable,\
					.parent-inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\],\
					.parent-inbound_shortcode_inbound_assign_notify_agents').css({'display' : 'none'});
			
			//clear the agent selector
			jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').select2('data', null);

			//remove the status icon
			jQuery('#agent-group-loading-status-icon').remove();

			//set the group and notify checks to off
			jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group_enable,\
					#inbound_shortcode_inbound_assign_notify_agents').prop('checked', false);
			
			//empty the group selector
			jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').select2('data', null);
			jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').empty();
		}
	});
	
	/**the assign to agent lead group checkbox listener**/
	jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group_enable').on('change', function(){
		if(jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group_enable').is(':checked')){
			jQuery('.parent-inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').css({'display' : 'table-row-group'});
		}else{
			jQuery('.parent-inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').css({'display' : 'none'});
			
			//remove the status icon
			jQuery('#agent-group-loading-status-icon').remove();
			
			//clear the group selector
			jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').select2('data', null);
		}
	
	});


	/**listen to the agent selector and 950ms after the last change, get the common lead groups**/
	jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').on('change', function(){
			clearTimeout(wait);
			wait = setTimeout(getLeadGroupList, 950);
	});


	/**get agent assign settings at page load**/
	function getSettings(){
		var SelectionData = jQuery("#cpt-form-serialize-default").text();
		    if (SelectionData != "") {
				jQuery.each(SelectionData.split('&'), function (index, elem) {
					 
					 var vals = elem.split('=');

						if(vals[0] == 'inbound_shortcode_inbound_assign_agent_enable'){
							formSettingList['assignToAgent'] = vals[1];
							}
							
						if(vals[0] == 'inbound_shortcode_inbound_assign_to_agent_lead_group_enable'){
							formSettingList['assignToGroups'] = vals[1];
                            console.log(vals);
							}

						if(vals[0] == 'inbound_shortcode_inbound_assign_agent%5B%5D'){
							formSettingSelectedAgents.push(decodeURIComponent(vals[1].replace(/\+/g, ' ')));
							}
														
						if(vals[0] == 'inbound_shortcode_inbound_assign_to_agent_lead_group%5B%5D'){
							formSettingSelectedLeadGroups.push(decodeURIComponent(vals[1].replace(/\+/g, ' ')));// = vals[1];
							}
							
				});
			}
	}

	/**get the selected agents at page load**/
	function getAgentList(){	
		jQuery.ajax({
			type : 'POST',
			url : ajaxurl,
			data : {
				action : 'get_agent_list',
				},
			success : function(response){
				var agentList = JSON.parse(response);

				//set the agents as selected based on the settings
				jQuery(formSettingSelectedAgents).each(function(index, value){
					displayNames.push(value);
				});
				jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').val(displayNames);
                jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').trigger('change');
				

            },
		});

	}

	/**gets the groups that the selected agents have in common**/
	function getLeadGroupList(){
		if((firstCall == true && formSettingList.assignToGroups == 'on') || firstCall == false){
			/*if there is atleast one agent*/
			if(jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').val()){
					console.log(jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').val());
					
					//show the spinner
					jQuery('.group-spinner').css({'visibility' : 'visible',});
					
					//remove the status icon
					jQuery('#agent-group-loading-status-icon').remove();
					
					if(jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').val() != null){
						jQuery.ajax({
							type : 'POST',
							url : ajaxurl,
							data : {
								action : 'get_agent_term_lead_groups',
								data : jQuery('#inbound_shortcode_inbound_assign_agent\\[\\]').val(),
								get_groups_in_common : '1',
								},
							success : function(response){
								commonAgentLeadGroups = JSON.parse(response);

								/*on success hide the spinner*/
								jQuery('.group-spinner').css({'visibility' : 'hidden',});
								
								/*if groups have been returned, show the green checkmark*/
								if(commonAgentLeadGroups != null){
									jQuery('.parent-inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').find('.select2-container').prepend('<div id="agent-group-loading-status-icon" style="position: absolute; left: calc(100% + 12px); top:5px;"><i id="agent-success-checkmark" style="color: green; font-size: 24px;" class="fa fa-check" aria-hidden="true"></i></div>');
								}

								setupLeadGroupList();
								},
							error: function (MLHttpRequest, textStatus, errorThrown) {
								alert("Ajax not enabled");
										
								/*On failure hide the spinner and show the failure Xmark*/
								jQuery('.js-spin-spinner').css('visibility', 'hidden');
								jQuery('.parent-inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').find('.select2-container').prepend('<div id="agent-group-loading-status-icon" style="position: absolute; left: calc(100% + 12px); top:5px;"><i id="agent-failure-xmark" style="color: red; font-size: 24px;" class="fa fa-times" aria-hidden="true"></i></div>');							
							},
								
								
						});
					}
				}		
				/*otherwise, just empty the selector and remove the status icon*/
				else{
					jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').select2('data', null);
					jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').empty().trigger('change');
					jQuery('#agent-group-loading-status-icon').remove();
				}
		}else{
			firstCall = false;
		}
	}

	/**populates the lead group selector. Also sets the previously chosen lead groups to selected**/
	function setupLeadGroupList(){
		var displayLeadGroups = [];
		var options = [];
		
		//empty the selector
		jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').empty();
		jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').select2('data', null);

		//create the options out of the lead groups that the agents have in common
		if(commonAgentLeadGroups != null){
			for(var i = 0; i < commonAgentLeadGroups.length; i++){
                options.push(new Option(commonAgentLeadGroups[i], commonAgentLeadGroups[i], false, false));
			}
		}		
		//add the options to the selector
		jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').append(options);
			
		//if the page has just loaded, set the lead groups for selected based on the settings
		if(firstCall){
			for(var j = 0; j < formSettingSelectedLeadGroups.length; j++){
                displayLeadGroups.push(formSettingSelectedLeadGroups[j]);
			}
            //select the saved groups
            jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').val(displayLeadGroups);
		}
		
        //refresh the group dropdown
        jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').trigger('change');
        
		firstCall = false;
	}
	
	/**
	 * Updates form agent data:
	 * Resets the agent rotation counter,
	 * Saves whether agents should be notified,
	 * Saves which groups the leads go to,
	 * On form save.
	**/
	jQuery('a#inbound_save_form.button-primary').on('click', function(){
		var noGroups = false;

		/**check to see if groups have been selected. If they haven't, hide the selector and unset the groups checkbox*/
		if(jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group_enable').is(':checked')){
            if(jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').val() == null){
				jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group_enable').prop('checked', false);
				noGroups = true;
				/**hide and clear the group selector**/
				jQuery('.parent-inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').css({'display' : 'none'});
				//remove the status icon
				jQuery('#agent-group-loading-status-icon').remove();
				//clear the group selector
				jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').select2('data', null);
				
			}
		}
		
		jQuery.ajax({
			type : 'POST',
			url : ajaxurl,
			data : {
				action : 'save_form_agent_data',
				post_id :  jQuery("#post_ID").val(),
				notify_agents : (jQuery('#inbound_shortcode_inbound_assign_notify_agents').is(':checked')) ? 1 : 0,
				agent_groups: jQuery('#inbound_shortcode_inbound_assign_to_agent_lead_group\\[\\]').val(),
				no_groups : noGroups,
				nonce : inbound_shortcodes.inbound_shortcode_nonce,
				},
			success : function(response){
			//	console.log(JSON.parse(response));
				},
			});

	});
	
	

});
