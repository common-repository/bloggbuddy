jQuery(document).ready(function(){
   
   //jQuery(".google-search").click(function() {
	jQuery("body").on('click', '.google-search', function(e) {   
		e.preventDefault();
	
	if(jQuery(this).is(":checked")) {  
   		//alert(1111111111);
		jQuery('.wdm_keyword_results').html('<p style="color:red; font-weight:bold;">Please Upgrade to Use Premium Features. <a href="http://bloggbuddy.com/support" class="button button-primary button-large" style="margin-left:10px;" target="_blank">Upgrade Now</a></p>');
		//return false;
	  }
	  	else{ 	 
			jQuery('.wdm_keyword_results').html('');
		  } 
		  
	});
   
   
   jQuery("body").on('click', 'a.google-search', function(e) {   
		e.preventDefault();
	
		jQuery('.wdm_keyword_results').html('<p style="color:red; font-weight:bold;">Please Upgrade to Use Premium Features. <a href="http://bloggbuddy.com/support" class="button button-primary button-large" style="margin-left:10px;" target="_blank">Upgrade Now</a></p>');
		  
	});
	
	
   jQuery('.wdm_update_btn').click(function(e){
    	e.preventDefault();
		
		if(jQuery(".google-search").is(":checked")) {  
   			return false;
	  	}
	  	else{ 	 
			jQuery('.wdm_keyword_results').html('');
		  }
		  
        var keyword_list = jQuery('#id_keywords').val();
		
        if (keyword_list.length == 0) {
            
            //check for empty fields
            
            alert("Please enter a keyword");
            jQuery('#id_keywords').focus();
        }
        else
        {
            //process the request
            
            //get option status
            
            var wdm_comment_option = jQuery('#wdm_comment_plug').is(':checked');
            var wdm_timespan = jQuery('#wdm_timespan').is(':checked');
            var wdm_title_in = jQuery('#wdm_title_in').is(':checked');
            var wdm_google = jQuery('#wdm_google_in').is(':checked');
            
			if(jQuery('#pr').is(':checked')){var pr = jQuery('#pr').val()}
			if(jQuery('#bl').is(':checked')){var bl = jQuery('#bl').val();}
			if(jQuery('#ar').is(':checked')){var ar = jQuery('#ar').val();}
		
            if (wdm_comment_option == false && wdm_timespan == false && wdm_title_in == false && wdm_google_in == false) {
                
                alert('Please select at least one option to fetch links');
                jQuery('#wdm_comment_plug').focus();
                exit();
            }
            
            //get option value
            
            var comment_option_value = jQuery('#wdm_comment_plug').attr('value');
            var timespan_option_value = jQuery('#wdm_timespan').attr('value');
            var title_option_value = jQuery('#wdm_title_in').attr('value');
            var google_option_value = jQuery('#wdm_google_in').attr('value');
            
            //get exclude list
            
            var wdm_exclude_list = jQuery('.wdm_exclude_list').val().trim();

            wdm_exclude_list = wdm_exclude_list.replace(/\r?\n|\r/g,'');
            
            //Add loading image
           
          jQuery(".wdm_image_container").append("<img src='" + wdm_obj.image_path + "' alt='Loading..' id='img_load'>");
          jQuery('.wdm_keyword_results').empty(); //Clear result div
          
          //Send AJAX Request

          jQuery.ajax({
                            url: wdm_obj.admin_ajax_path, 
                            type: "POST",
                            data: {
                                    action:'wdm_fetch_links',
                                    keywords : keyword_list,
                                    post_id : wdm_obj.post_id,
                                    Commentluv : wdm_comment_option,
                                    timespan : wdm_timespan,
                                    google : wdm_google,
                                    Allintitle : wdm_title_in,
                                    comment_value : comment_option_value,
                                    timespan_value : timespan_option_value,
                                    title_value : title_option_value,
                                    google_value : google_option_value,
                                    exclude_list : wdm_exclude_list,
									pr : pr,
									bl : bl,
									ar : ar
                                  },
                            success: function(data){
                                
                                jQuery("#img_load").remove();
                                jQuery(".wdm_keyword_results").html("<h3> Keyword Results </h3>" + data);
                            }
          });
          
        }
    
   });
    
});