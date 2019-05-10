jQuery(function(){          
    var $tagInp = jQuery("#tagedit");
    jQuery('#tagedit').tagit({
        itemName: 'item',
        fieldName: 'tags[]',
        allowSpaces: false,
        minLength: 2,
        tagSource: function( request, response ) {
            jQuery.ajax({
                url: "/post/edit/autocomplete", 
                type: "POST",
                data: { term:request.term },
                dataType: "json",
                success: function( data ) {
                    response( jQuery.map( data, function( item ) {
                        return {
                            label: item.label,
                            value: item.value
                        }
                    }));
                }
            });
        },
        preprocessTag: function (val) {
            if (!val) {
                return '';
            }
            var values = val.split(" ");
            if (values.length > 1) {
                for (var i = 0; i < values.length; i++) {
                    $tagInp.tagit("createTag", values[i]);
                }
                return ''
            } else {
                return val
            }
        }
    });

    jQuery.ui.autocomplete.prototype._renderItem = function (ul, item) {
     return jQuery( "<li>" )
    .append( "<a><span class='" + item.label + "'>" + item.value + "</span></a>" )
    .appendTo( ul );
    };
                  
     jQuery( "#editpost" )
      .click(function() {
        jQuery( "#editform" ).dialog( "open" );
     });
    });

jQuery(function() {
    jQuery( "#editform" ).dialog({
      autoOpen: false,
      height: 465,
      width: 750,
      modal: true,
      buttons: {
        Save: function() {
            jQuery("#edit_form").submit();
            jQuery( this ).dialog( "close" );
        },
        Cancel: function() {
          jQuery( this ).dialog( "close" );
        }
      }
    });
    
    jQuery( "#edit" )
      .click(function() {
        jQuery( "#editform" ).dialog( "open" );
      });
    
    jQuery( "#deletepost" ).dialog({
      autoOpen: false,
      height: 265,
      width: 550,
      modal: true,
      buttons: {
        Delete: function() {
            jQuery("#delete_form").submit();
            jQuery( this ).dialog( "close" );
        },
        Cancel: function() {
          jQuery( this ).dialog( "close" );
        }
      }
    });
    
    jQuery( "#delete" )
      .click(function() {
        jQuery( "#deletepost" ).dialog( "open" );
      });
      
    jQuery( "#permdeletepost" ).dialog({
      autoOpen: false,
      height: 180,
      width: 450,
      modal: true,
      buttons: {
        Delete: function() {
            jQuery("#permdelete_form").submit();
            jQuery( this ).dialog( "close" );
        },
        Cancel: function() {
          jQuery( this ).dialog( "close" );
        }
      }
    });
    
    jQuery( "#permdelete" )
      .click(function() {
        jQuery( "#permdeletepost" ).dialog( "open" );
      });
    
    jQuery( "#restorepost" ).dialog({
      autoOpen: false,
      height: 180,
      width: 450,
      modal: true,
      buttons: {
        Undelete: function() {
            jQuery("#restore_form").submit();
            jQuery( this ).dialog( "close" );
        },
        Cancel: function() {
          jQuery( this ).dialog( "close" );
        }
      }
    });
    
    jQuery( "#restore" )
      .click(function() {
        jQuery( "#restorepost" ).dialog( "open" );
      });
    
    jQuery( "#approvepost" ).dialog({
      autoOpen: false,
      height: 180,
      width: 450,
      modal: true,
      buttons: {
        Approve: function() {
            jQuery("#approve_form").submit();
            jQuery( this ).dialog( "close" );
        },
        Cancel: function() {
          jQuery( this ).dialog( "close" );
        }
      }
    });
    
    jQuery( "#approve" )
      .click(function() {
        jQuery( "#approvepost" ).dialog( "open" );
      });
    
    jQuery( "#showhide_comments" )
      .click(function() {
        jQuery( "#comments" ).toggle();
      });
      
    jQuery( "#addposttopool" ).dialog({
      autoOpen: false,
      height: 200,
      width: 800,
      modal: true,
      buttons: {
        "Add Post": function() {
            jQuery("#addtopool_form").submit();
            jQuery( this ).dialog( "close" );
        },
        Cancel: function() {
          jQuery( this ).dialog( "close" );
        }
      }
    });
    
    jQuery( "#addtopool" )
      .click(function() {
        jQuery( "#addposttopool" ).dialog( "open" );
      });
});