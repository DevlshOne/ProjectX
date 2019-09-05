var Upload = function (success_function_name, error_function_name){//file) {
	
	this.success_function_name = success_function_name;
	this.error_function_name = error_function_name;
	
    //this.file = file;
};

//Upload.prototype.getType = function() {
//    return this.file.type;
//};
//Upload.prototype.getSize = function() {
//    return this.file.size;
//};
//Upload.prototype.getName = function() {
//    return this.file.name;
//};
Upload.prototype.doUpload = function (frm, upfile_id) {
    var that = this;
    var formData = new FormData();

    

    
    for(var i = 0;i < frm.elements.length;i++){ 

 	   
 	   //alert(fobj.elements[i].type+' '+fobj.elements[i].name+" "+fobj.elements[i].value);
 	   if(!frm.elements[i].name)continue;
 	   
 	   
        switch(frm.elements[i].type){
        case "text": 
       case "textarea":
       case "password":


            formData.append(frm.elements[i].name,frm.elements[i].value);
            
            break; 


       case "hidden":
    	   
    	   
    	   // NAME = VALUE &
    	   //str += fobj.elements[i].name + "=" + escape(fobj.elements[i].value) + "&";
    	   
    	   formData.append(frm.elements[i].name,frm.elements[i].value);
    	   
    	   break;
       case "checkbox":
    	   
    	   //alert(fobj.elements[i].name + " "+fobj.elements[i].value)
    	   
    	   // NAME = VALUE &
    	   if(frm.elements[i].checked){
    	//	   str += fobj.elements[i].name + "=" + escape(fobj.elements[i].value) + "&";
    		   
    		   formData.append(frm.elements[i].name,frm.elements[i].value);
    		   
    	   }
    	   
    	   break;
       case "radio":

    	   // NAME = VALUE &
    	   if(frm.elements[i].checked){
    		   //str += fobj.elements[i].name + "=" + escape(fobj.elements[i].value) + "&";
    		   
    		   formData.append(frm.elements[i].name,frm.elements[i].value);
    	   }
    	   
    	   break;
       case "select-one": 

    	   formData.append(frm.elements[i].name,frm.elements[i].value);
    	   
           //str += fobj.elements[i].name+ "=" +fobj.elements[i].value + "&"; 

           break; 
       case "select-multiple":
    	   
    	   //alert(fobj.elements[i].value);
    	   
    	   var valarr = getSelectValues(frm.elements[i]);
    	   
    	   for(var x=0;x < valarr.length;x++){
    	   
    	   
    		  // str += fobj.elements[i].name+ "=" +valarr[x]+ "&";
    		   
    		   formData.append(frm.elements[i].name,valarr[x]);
    		   
    	   }

           break;
        }
    }
    
    
    if(upfile_id){
    	
    	var upfile = $('#'+upfile_id)[0].files[0];
    	// add assoc key values, this will be posts values
    	formData.append(upfile_id, upfile, upfile.name);
    	
    }
    
    
//    formData.append("upload_file", true);

    
    
    $.ajax({
        type: "POST",
        url: frm.action,
        
        async: true,
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        timeout: 60000,
        
        xhr: function () {
            var myXhr = $.ajaxSettings.xhr();
            if (myXhr.upload) {
                myXhr.upload.addEventListener('progress', that.progressHandling, false);
            }
            return myXhr;
        },
        success: function (data) {
            // your callback here
        	
        //	alert("Success: "+data);
        	
        	
        	that.success_function_name(data);
        	
        	//eval(that.success_function_name+"(\""+data.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"')+"\")" );
        	
        	//call( this.success_function_name , data);
        	
        },
        error: function (error) {
            // handle error
        	
        	//alert("ERROR: "+error);
        	
        	that.error_function_name.call(error);
        	
        	
        	//eval(that.error_function_name+"(\""+error.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"')+"\")" );
        	//call( this.error_function_name , error);
        },
        
    });
};

Upload.prototype.progressHandling = function (event) {
    var percent = 0;
    var position = event.loaded || event.position;
    var total = event.total;
    var progress_bar_id = "#progress-wrp";
    if (event.lengthComputable) {
        percent = Math.ceil(position / total * 100);
    }
    // update progressbars classes so it fits your code
    $(progress_bar_id + " .progress-bar").css("width", +percent + "%");
    $(progress_bar_id + " .status").text(percent + "%");
};