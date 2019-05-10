jQuery(function(){
	//Backup the div with the posts for later
	var gallerybackup = jQuery(".gallery").html()
    //Hook mode dropdown change event
	jQuery('select[name="mode"]').change(function(){
		//Empty div and insert backup
		jQuery('.gallery').empty();
		jQuery('.gallery').html(gallerybackup);
		if (jQuery(this).val() == "reorder"){
    		//This is called when the mode is changed to Re-order
			jQuery("ul.reorder-photos-list").sortable({ tolerance: 'pointer' });
			jQuery('.reorder_link').html('save reordering');
			jQuery('.reorder_link').attr("id","save_reorder");
			jQuery('#reorder-helper').slideDown('slow');
			jQuery('.image_link').attr("href","javascript:void(0);");
			jQuery('.image_link').css("cursor","move");
			jQuery(".savechanges").click(function( e ){
				if( !jQuery("#save_reorder i").length ){
					jQuery(this).html('').prepend('<img src="/theme/default/images/loading.gif" height="30" width="30" />');
					jQuery("ul.reorder-photos-list").sortable('destroy');
					jQuery("#reorder-helper").html( "Saving... This could take a moment. Please don't navigate away from this page." ).removeClass('light_box').addClass('notice notice_error');
		
					var h = [];
					jQuery("ul.reorder-photos-list li").each(function() {  h.push(jQuery(this).attr('id').substr(9));  });
					jQuery.ajax({
						type: "POST",
						url: "/pool/edit/order",
						data: {ids: " " + h + "", poolid: "" + poolid, page: "" + page},
						success: function(html) {
	                         if (html.indexOf("ERROR") == -1) {
	                            if (html == "OK") {
		                        	window.location.reload();
	                            }
	            			}else{
	            				alert(html);
	                        }
						}
					});	
					return false;
				}	
				e.preventDefault();		
				});
		}else if(jQuery(this).val() == "manualreorder"){
			//This is called when the mode is changed to Manual Re-order
			//Run through each post
			jQuery("li.thumb").each(function(index) {
			  //Add textbox to each post with unique ids
			  jQuery(this).append("<br />Post ID: "+jQuery(this).attr('id').substr(9)+"<br /><input type='text' id='order_"+jQuery(this).attr('id').substr(9)+"' maxlength='3' size='3' value='"+(index+1+page)+"'>");
			});
		    
		    var h = [];
		    var i = [];
		    var j = [];
		    //Event for when the save changes button is pressed
		    jQuery(".savechanges").click(function( e ){
				//Loop through each post
				jQuery("ul.reorder-photos-list li").each(function() {  
					//Get the post number and order
					var aid = jQuery(this).attr('id').substr(9);
					var inp = jQuery('#order_'+aid).val();
			        i.push({
			            order: inp,
			            postid: aid
			        });
				});
				//Sort array by order
				var j = i.sort(function(a, b) {
    				return (a.order - b.order);
				});
				//Create new array with just the post ids
				var h = jQuery.map(j, function(value, index) {
					return [value.postid];
				}); 
				console.log(h);
				//This is where the magic happens
				if( !jQuery("#save_reorder i").length ){
					//Add loading gif
					jQuery(this).html('').prepend('<img src="/theme/default/images/loading.gif" height="30" width="30" />');
					//Run ajax call to send info to server
					jQuery.ajax({
						type: "POST",
						url: "/pool/edit/order",
						data: {ids: " " + h + "", poolid: "" + poolid, page: "" + page, limit: "" + limit},
						success: function(html) {
	                         if (html.indexOf("ERROR") == -1) {
	                            if (html == "OK") {
		                        	window.location.reload();
	                            }
	            			}else{
	            				alert(html);
	                        }
						}
					});	
					return false;
				}	
				e.preventDefault();
		    });
		}else if(jQuery(this).val() == "delete"){
			//This is called when the mode is changed to Delete
			//Run through each post
			jQuery("li.thumb").each(function(index) {
			  //Add buttons to each post with unique ids
			  jQuery(this).append("<br /><button class='removebtn' id='removebtn_"+jQuery(this).attr('id').substr(9)+"' type='button'>Remove</button>");
			});
		    
		    //Event for when a remove button is pressed
		    jQuery(".removebtn").click(function(){
		        //Hide that little shit
		        jQuery("#image_li_"+jQuery(this).attr('id').substr(10)).hide();
		    });
		    
		    var h = [];
		    //Event for when the save changes button is pressed
		    jQuery(".savechanges").click(function( e ){
				//Loop through each post
				jQuery("ul.reorder-photos-list li").each(function() {  
					//Check if its hidden, add to array if so
					if (jQuery(this).css('display') == 'none') {
					    h.push(jQuery(this).attr('id').substr(9)); 
					}
				});
				//This is where the magic happens
				if( !jQuery("#save_reorder i").length ){
					//Add loading gif
					jQuery(this).html('').prepend('<img src="/theme/default/images/loading.gif" height="30" width="30" />');
					//Run ajax call to send info to server
					jQuery.ajax({
						type: "POST",
						url: "/pool/edit/delete",
						data: {ids: " " + h + "", poolid: "" + poolid, page: "" + page},
						success: function(html) {
	                         if (html.indexOf("ERROR") == -1) {
	                            if (html == "OK") {
		                        	window.location.reload();
	                            }
	            			}else{
	            				alert(html);
	                        }
						}
					});	
					return false;
				}
				e.preventDefault();
		    });
		}
	});
});