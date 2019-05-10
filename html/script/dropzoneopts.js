
jQuery(function(){
  var maxup = jQuery('#usrmaxup').val();
  Dropzone.options.uploadposts = {
  
    //Configuration options brah
    autoProcessQueue: false,
    uploadMultiple: true,
    parallelUploads: maxup,
    maxFiles: maxup,
    previewsContainer: '#dropzoneprev',
    addRemoveLinks: true,
  
    //Setup dropzone
    init: function() {
      var myDropzone = this;
  
      //First change the button to actually tell Dropzone to process the queue
      this.element.querySelector("button[type=submit]").addEventListener("click", function(e) {
        if (myDropzone.getQueuedFiles().length > 0) {                        
           //Make sure that the form isn't actually being sent
           e.preventDefault();
           e.stopPropagation();
           myDropzone.processQueue();  
        }else{
           //Send form
           jQuery("#uploadposts").submit();
        }
      });
  
      //Listen for error adding file to dropzone
      this.on("errormultiple", function(files, response) {
        //Loop through each file
        files.forEach(function (file){
          //Remove file from dropzone
          myDropzone.removeFile(file);
        });
      });
  
      //Listen for completed file upload
      this.on("completemultiple", function (files) {
        //Loop through each file
        var i = 0;
        files.forEach(function (file){
          //Make sure the file is valid (apparently the error callback fires here too)
          if (file.status != "error"){
            //Append info to div
            jQuery("#upload_status").css("display", "block");
            resp = JSON.parse(file.xhr.responseText);
            if (resp[i].error != "success"){
                //Append status info
                jQuery("#upload_status").append("<p class='uploaderror'>Error uploading "+file.name+": "+resp[i].error+"</p>");
                myDropzone.removeFile(file);
            }else{
                //Append status info
                jQuery("#upload_status").append("<p class='uploadsuccess'>File <i>"+file.name+"</i> has been successfully posted as <a href='/post/view/"+resp[i].postid+"'>#"+resp[i].postid+"</a></p>");
                //Remove file from dropzone
                myDropzone.removeFile(file);
            }
          }
          ++i;
        });
  
      });
    }
  
  }
});