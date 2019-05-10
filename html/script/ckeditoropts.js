jQuery(function(){
	//Hook the save button
	jQuery(".savechanges").click(function( e ){
		//Run markdown conversion and AJAX
		convert_markdown();	
		//Prevent default shit
		e.preventDefault();		
	});
});