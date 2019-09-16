/***
 * A collection of common functions, used in the Ajax/API processing
 */





/**
 * Clears all rows but the first (the header row)
 * @param obj	The table object to clear
 * @return n/a
 */
function clearTable(obj){

	//alert(obj.rows.length);
	if(obj.rows.length > 1){

		for(var x=obj.rows.length; x > 1;x--){
			obj.tBodies[0].deleteRow(x-1);
		}
	}
}


/**
 * Ajax/API interface to delete a database record
 * @param area	Area to delete from, aka table name (usually)
 * @param id	ID of the record to delete
 * @param callback_func_name	 Function to call on success, ex: "myCallBack('myarg')"
 * @return n/a
 */
function deleteItem(confirmmsg, area,id,callback_func_name){

	if(confirm(confirmmsg)){
<<<<<<< HEAD


		var loadurl =	'api/api.php'+
							"?get="+area+"&"+
							"mode=xml&"+
							"action=delete&"+
							"id="+id;

		//alert($('#'+area+'-delete-img-'+id).attr("src"));
		//alert(loadurl+" "+callback_func_name);
		$('#'+area+'-delete-img-'+id).attr("src", "images/ajax-loader.gif");


		$.ajax({
			url: loadurl,
			type: "POST",
			success: function(data){


				$('#'+area+'-delete-img-'+id).attr("src", "images/garbCan_grey.gif");

				//alert("Response: "+data);
				try{
					var xmldoc = getXMLDoc(data);

					var tag = xmldoc.getElementsByTagName("Error");

					// LOWERCASE BUG PATCH
					if(tag.length == 0){

						tag = xmldoc.getElementsByTagName("error");
					}

					if(tag.length > 0){

						// GET THE FIRST TAG
						tag = tag[0];
						var resultcode = tag.getAttribute("code");


						//tmparr[x].textContent

						alert("ERROR("+resultcode+"): "+tag.textContent);

						return
					// SUCCESS
					}
				}catch(ex){}


				eval(callback_func_name);


			}

		});

	}
}


function getInternetExplorerVersion()
// Returns the version of Internet Explorer or a -1
// (indicating the use of another browser).
{
  var rv = -1; // Return value assumes failure.
  if (navigator.appName == 'Microsoft Internet Explorer')
  {
    var ua = navigator.userAgent;
    var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
    if (re.exec(ua) != null)
      rv = parseFloat( RegExp.$1 );
  }
  return rv;
}

function getXMLDoc(xml_data){


	var ver = getInternetExplorerVersion();


	if(ver < 0 && window.DOMParser){
		parser=new DOMParser();
		xmlDoc=parser.parseFromString(xml_data,"text/xml");
	}else{ // Internet Explorer

		xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async="false";
		xmlDoc.loadXML(xml_data);
	}

	return xmlDoc;
}




/**
 * handleEditXML - Parses API's XML response to editing data
 * If successful, it will redirect-to/refresh the edit form, with the ID
 * Falls back to the Error tag, if it cannot figure out what the result is
 * @param xmldata	Raw xml text data
 * @param baseurl	The base add/edit URL with everthing but the ID at the end (we append id to the end)
 * 					Example: "processor.php?editing_item_id="
 * @param uiobj		The jquery ui object (where the tabs inject the content, usually stored as account_uiobj or admin_uiobj)
 * @param success_callback_func A string of the function to call after receiving a success message

 * @return	n/a
 */
function handleEditXML(xmldata, success_callback_func){

	// PASSING IN XML DOCUMENT OBJECT
	if(typeof xmldata == "object"){
		var xmldoc = xmldata;
	// PASSING IN XML STRING
	}else{

		var xmldoc = getXMLDoc(xmldata);///(new DOMParser()).parseFromString(xmldata, "text/xml");

	}

	var tagarr = xmldoc.getElementsByTagName("EditMode");

	// LOWERCASE BUG PATCH
	if(tagarr.length == 0){

		tagarr = xmldoc.getElementsByTagName("editmode");
	}

	if(tagarr.length > 0){

		// GET THE FIRST TAG
		var tag = tagarr[0];

		var result = tag.getAttribute("result");
		if(result == 'success'){


			//if(uiobj){
			//	$('#'+uiobj).load(baseurl+tag.getAttribute("id"));
			//}


			if(success_callback_func){
				eval(success_callback_func);
			}

			var outmsg;

			// CHECK FOR INNER ERROR TAGZ
			var tmparr = tag.getElementsByTagName("error");

			if(tmparr.length <= 0){
				tmparr = tag.getElementsByTagName("Error");
			}

			if(tmparr.length > 0){

				outmsg = "Save appears successful. However, the following warning(s) were issued:\n";

				for(var x=0;x < tmparr.length;x++){

					outmsg += "* "+tmparr[x].textContent+ "\n";

				}

			}else{


				outmsg = "Successfully saved";

			}

			return {"result":tag.getAttribute("id"), "message":outmsg };

		}else{
			var reason = tag.getAttribute("reason");

			return {"result":-1, "message":"Error: Attempt to Add/Edit Failed.\nReason:"+reason };
		}

	}else{

		tagarr = xmldoc.getElementsByTagName("error");
		if(tagarr.length > 0){

			var tag = tagarr[0];


			return {"result":-2, "message":"Error returned: "+ ((tag.text!=undefined)?tag.text:tag.textContent) };

		}else{

			return {"result":-3, "message":"Unknown msg returned by server: "+xmldata };
		}

	}


	//loadInPanel('');
	//alert("Post successful: "+msg);

	return {"result":-4, "message":"An unknown error occurred while handling server ajax response."};

}






/**
 * loadAjaxData - loads ajax data and calls a callback function on success
 * @param loadurl	The full URL to the API including query strings
 * @param callback_func_name	The function name to call back, without the args,
 * 								Example: function named parseXMLStuff(xmldoc), you would pass in "parseXMLStuff"
 * @return Nothing! Call back should handle everything you need!
 */
function loadAjaxData(loadurl,callback_func_name) {
	$.ajax({
		url: loadurl,
		type: "POST",
		success: function(data){
			var xmldoc = getXMLDoc(data);////(new DOMParser()).parseFromString(data, "text/xml");
			eval(callback_func_name+'(xmldoc)');
		}
	});
}



/**
 * PARSE xmldoc, RENDER THE TABLE
 * @param area
 * @param baseurl
 * @param tableFormat
 * @param xmldoc
 * @return Total count of items (not limited by the limit field, the full total, or if not usign page system, total records returned)
 */
function parseXMLData(area,tableFormat,xmldoc){

	var obj;

	var tagname = "";
	var callback_func_name = "";
	var delete_area = "";
	var delete_message_varname = "delmsg";


	///var ui_load_panel = (ui_override)?ui_override:account_uiobj;

	//alert(ui_load_panel.panel);


	//var ui_load_panel;

	delete_message_varname = area+"_delmsg";

	switch(area){
	default:

		obj = getEl(area+'_table');

		tagname = area.charAt(0).toUpperCase() + area.substr(1);
		delete_area = area+"s";
		callback_func_name = "load"+tagname+"s()";

		break;

//	case 'campaign':
//
//		obj = getEl(area+'_table');
//
//
//		tagname = "Campaign";
//		delete_area = area+"s";
//		callback_func_name = "loadCampaigns()";
//
//		break;
	}


	var totalcount = 0;
	var special_tag;
	var special_idx=0; // USED TO KEEP TRACK OF EACH RECORD
	var special_stack = new Array();
	var tmparr;

	// DETECT AND USE PAGE SYSTEM RELATED INFO (total count)
	try{

		var tmptags = xmldoc.getElementsByTagName(tagname + "s");
		totalcount = tmptags[0].getAttribute("totalcount");

	}catch(e){}




	// GRAB ALL DATA TAGS
	var dataarr = xmldoc.getElementsByTagName(tagname);
	if(totalcount <= 0){

		// IF TOTAL COUNT WASNT POPULATED ABOVE, MANUALLY SET TO THE TOTAL RECORD SIZE
		totalcount = dataarr.length;
	}


	// REMOVE ALL ROWS BUT THE HEADER
	clearTable(obj);

	//alert("area:"+area+" "+obj+" "+obj.rows.item(0).cells);

	if(dataarr.length == 0){



		var colspan = obj.rows.item(0).cells.length;

		var lastRow = obj.rows.length;
		var row = obj.insertRow(lastRow);
		var cell = row.insertCell(0);


		cell.colSpan = colspan;
		cell.className = "align_center";
		cell.innerHTML = "<i>No Records found.</i>";
	}

	var clsname;
	for(var x=0;x < dataarr.length;x++){


		var lastRow = obj.rows.length;
		var row = obj.insertRow(lastRow);


		clsname = 'row'+(x%2);

		// STORE RECORD ID ON THE TR ELEMENT, SO EACH CELL CAN ACCESS IT AS THE PARENT
		row.setAttribute("record_id",dataarr[x].getAttribute('id'));

		var cell;
		var newDate,tmptime,datestring;
		var cur_name,cur_class,cur_data,priv_name;



		for(var y=0; y < tableFormat.length;y++){

			//alert("INSERT CELL - "+y+" tableFormat:"+tableFormat[y]);

			if(!tableFormat[y])continue;




			cell = row.insertCell(y);


			//alert("Format: "+tableFormat[y][0]+" "+tableFormat[y][1]);

			cur_name = tableFormat[y][0];
			cur_class= (tableFormat[y][1])?' '+tableFormat[y][1]:'';// INCLUDES THE SPACE FOR CSS CLASSES

			// SPECIAL MODE, currently only delete, but could do other things later
			if(cur_name.charAt(0) == '['){

				// EXTRACT SPECIAL TAG DETAILS
				special_tag = cur_name.substring(1,cur_name.length-1);



				if(special_tag.indexOf("get:") == 0){




					tmparr = special_tag.split(":");

					// PUSH THE SPECIAL TAG TO AN ARRAY, ALONG WITH ADDITIONAL INFO
					special_stack[special_idx] = 'get:'+tmparr[1];

					if(tmparr.length > 2){

						for(var i = 2;i < tmparr.length;i++){

							special_stack[special_idx] += ':'+dataarr[x].getAttribute(tmparr[i]);

						}

					}else{

						// default append the current record ID
						special_stack[special_idx] += ':'+dataarr[x].getAttribute("id");

					}



					cell.innerHTML = '<div id="'+area+'_special_data_load_'+special_idx+'">[loading...]</div>';

					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){


						handleListClick(tagname, this.parentNode.getAttribute('record_id'));


					}

					// AFTER PUSHING, INCREMENT THE POINTER
					special_idx++;


				// MAKE A CHECKBOX
				}else if(special_tag.indexOf("checkbox:") == 0){

					tmparr = special_tag.split(":");


					cell.innerHTML = '<input type="checkbox" name="'+tmparr[1]+x+'" id="'+tmparr[1]+x+'" value="'+dataarr[x].getAttribute(tmparr[2])+'">';
					cell.className = clsname+' '+cur_class;

				// Render field, with a label after it
				}else if(special_tag.indexOf("postlabel:") == 0){

					tmparr = special_tag.split(":");


					cell.innerHTML = dataarr[x].getAttribute(tmparr[1]) + tmparr[2];
					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){


						handleListClick(tagname, this.parentNode.getAttribute('record_id'));


					}



				// RENDER TIME
				}else if(special_tag.indexOf("time:") == 0){

					tmparr = special_tag.split(":");

					newDate = new Date();

					tmptime = dataarr[x].getAttribute(tmparr[1]);

					newDate.setTime( tmptime * 1000 );


					var tmphrs = newDate.getHours();
					var tmpmin = newDate.getMinutes();

					tmpmin = (tmpmin < 10)?'0'+tmpmin:tmpmin;
					if(tmphrs >= 12){
						datestring = ((tmphrs==12)?tmphrs:(tmphrs-12))+":"+tmpmin+"pm";
					}else{
						datestring = (tmphrs)+":"+tmpmin+"am";

					}

					datestring += " "+(newDate.getMonth()+1)+"/"+newDate.getDate()+"/"+newDate.getFullYear();

					cell.innerHTML = (tmptime > 0)?datestring:'n/a';
					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){


						handleListClick(tagname, this.parentNode.getAttribute('record_id'));

					}


				}else if(special_tag.indexOf("render:") >= 0){

					tmparr = special_tag.split(":");


					if(tmparr[1] == 'who'){

						var cell_text = "-"

						if(dataarr[x].getAttribute('type') == 'campaign'){

							special_stack[special_idx] = 'get:campaign_name:'+dataarr[x].getAttribute('who');

							cell_text =  '<div id="'+area+'_special_data_load_'+special_idx+'">[loading...]</div>';

							special_idx++;


						}else if(dataarr[x].getAttribute('type') == 'user'){

							cell_text =  dataarr[x].getAttribute('who');

						}

						cell.innerHTML = cell_text;
						cell.className = clsname+' hand'+cur_class;
						cell.onclick = function(){


							handleListClick(tagname, this.parentNode.getAttribute('record_id'));


						}

					}else{

					}



				}else if(special_tag.indexOf("priv") == 0){



					///alert(dataarr[x].getAttribute('priv'));


					switch(parseInt(dataarr[x].getAttribute('priv'))){
					default:
					case 1:
						priv_name = "Trainee";
						break;
					case 6:
					case 5:

						priv_name = "Administrator";

						break;
					case 4:
					case 3:
					case 2:

						priv_name = "Caller";
						break;

					}

					cell.innerHTML = priv_name;
					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){


						handleListClick(tagname, this.parentNode.getAttribute('record_id'));


					}

				}else{

					cell.innerHTML = '<a href="#" onclick="deleteItem('+delete_message_varname+',\''+delete_area+'\','+dataarr[x].getAttribute('id')+', \''+callback_func_name+'\' );return false;">'+
									'<img id="'+delete_area+'-delete-img-'+dataarr[x].getAttribute('id')+'" src="images/delete.png" width="24" height="24" onmouseover="this.src=\'images/delete.png\'" onmouseout="this.src=\'images/delete.png\'" border="0" />'+
									'</a>';
					cell.className = ''+cur_class;

				}





			}else{

				cur_data = dataarr[x].getAttribute(cur_name);


				cell.innerHTML = (cur_data)?cur_data:'&nbsp;';
				cell.className = clsname+' hand'+cur_class;
				cell.onclick = function(){

					handleListClick(tagname, this.parentNode.getAttribute('record_id'));



				}
			}




		} // END FIELD LIST

	} // END OF XML TAGS




	// SECONDARY AJAX - POST PROCESSING - MAKE A SECOND AJAX CALL TO RETRIEVE AND RENDER INFO
	if(special_idx > 0){


		//console.dir(special_stack);

		secondaryAjaxPOST(area,special_stack);



	}


	applyUniformity();

	return totalcount;
} // END parseXMLData()


/**
 * Wrapper for list click functions
 * @param area_name The XML tag name basically, first letter uppercase (see the top of parseXMLData())
 * @param id The ID of the record to load
 * @return nothing
 */
function handleListClick(area_name, id){
	var cmd;


	cmd = 'handle'+area_name+'ListClick('+id+');';

	eval(cmd);

}





/**
 * Makes an AJAX post, to request the stack worth of data
 * Call back function handles injecting the data
 * @param special_stack
 * @return
 */
function secondaryAjaxPOST(area,special_stack){

	var loadurl =	'api/api.php'+
						"?get=secondary_ajax&"+
						"area="+area+"&"+
						"mode=xml";

	var postdata='';
	for(var x=0;x < special_stack.length;x++){


		postdata += 'special_stack['+x+']='+escape(special_stack[x])+"&";

	}



	$.ajax({
		url: loadurl,
		type: "POST",
		data: postdata,
		success: function(data){

			//alert(data);return;

			var xmldoc = getXMLDoc(data);////(new DOMParser()).parseFromString(data, "text/xml");

			eval('handleSecondaryAjax(\''+area+'\',xmldoc)');

		}
	});


}


function handleSecondaryAjax(area, xmldoc){

	var tag = xmldoc.documentElement;
	var obj=null;
	for(var x=0;(obj = getEl(area+'_special_data_load_'+x)) != null;x++){

		getEl(area+'_special_data_load_'+x).innerHTML = tag.getAttribute('data_'+x);

	}

}



function closeDiv(obj_name){

	var obj = getEl(obj_name);
	obj.innerHTML = '&nbsp;';

	$('#'+obj_name).hide();

}

function loadInDiv(url, obj_name){


	$('#'+obj_name).hide();

	$('#'+obj_name).load(url);

	$('#'+obj_name).show();

}




function loadInPanel(url,uiobj){


	//alert("UI:"+uiobj+" Panel: "+uiobj.panel+" "+url);

	$(uiobj.panel).load( url,
		function(){


			try{
				if(uiobj == admin_uiobj){
					onLoadAdminTabStuff("click", uiobj);
				}else{
					onLoadAccountTabStuff("click", uiobj);
				}
			}catch(e){

				onLoadAccountTabStuff("click", uiobj);
			}
			//onLoadTabStuff("click", uiobj);



		}
	);

=======
	
		
		var loadurl =	'api/api.php'+
							"?get="+area+"&"+
							"mode=xml&"+
							"action=delete&"+
							"id="+id;

		//alert($('#'+area+'-delete-img-'+id).attr("src"));
		//alert(loadurl+" "+callback_func_name);
		$('#'+area+'-delete-img-'+id).attr("src", "images/ajax-loader.gif");

		
		$.ajax({
			url: loadurl,
			type: "POST",
			success: function(data){

			
				$('#'+area+'-delete-img-'+id).attr("src", "images/garbCan_grey.gif");
			
				//alert("Response: "+data);
				try{
					var xmldoc = getXMLDoc(data);
					
					var tag = xmldoc.getElementsByTagName("Error");
					
					// LOWERCASE BUG PATCH
					if(tag.length == 0){
						
						tag = xmldoc.getElementsByTagName("error");
					}
			
					if(tag.length > 0){
	
						// GET THE FIRST TAG
						tag = tag[0];
						var resultcode = tag.getAttribute("code");
						
						
						//tmparr[x].textContent
						
						alert("ERROR("+resultcode+"): "+tag.textContent);
						
						return
					// SUCCESS	
					}
				}catch(ex){}
				
				
				eval(callback_func_name);
				

			}

		});

	}
}


function getInternetExplorerVersion()
// Returns the version of Internet Explorer or a -1
// (indicating the use of another browser).
{
  var rv = -1; // Return value assumes failure.
  if (navigator.appName == 'Microsoft Internet Explorer')
  {
    var ua = navigator.userAgent;
    var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
    if (re.exec(ua) != null)
      rv = parseFloat( RegExp.$1 );
  }
  return rv;
}

function getXMLDoc(xml_data){
	
	
	var ver = getInternetExplorerVersion();
	
	
	if(ver < 0 && window.DOMParser){
		parser=new DOMParser();
		xmlDoc=parser.parseFromString(xml_data,"text/xml");
	}else{ // Internet Explorer

		xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async="false";
		xmlDoc.loadXML(xml_data); 
	}  

	return xmlDoc;
}
 
 
 
 
/**
 * handleEditXML - Parses API's XML response to editing data
 * If successful, it will redirect-to/refresh the edit form, with the ID
 * Falls back to the Error tag, if it cannot figure out what the result is
 * @param xmldata	Raw xml text data
 * @param baseurl	The base add/edit URL with everthing but the ID at the end (we append id to the end)
 * 					Example: "processor.php?editing_item_id="
 * @param uiobj		The jquery ui object (where the tabs inject the content, usually stored as account_uiobj or admin_uiobj)
 * @param success_callback_func A string of the function to call after receiving a success message

 * @return	n/a
 */
function handleEditXML(xmldata, success_callback_func){

	// PASSING IN XML DOCUMENT OBJECT
	if(typeof xmldata == "object"){
		var xmldoc = xmldata;
	// PASSING IN XML STRING	
	}else{
	 
		var xmldoc = getXMLDoc(xmldata);///(new DOMParser()).parseFromString(xmldata, "text/xml");
		
	}

	var tagarr = xmldoc.getElementsByTagName("EditMode");
	
	// LOWERCASE BUG PATCH
	if(tagarr.length == 0){
		
		tagarr = xmldoc.getElementsByTagName("editmode");
	}
	
	if(tagarr.length > 0){

		// GET THE FIRST TAG
		var tag = tagarr[0];

		var result = tag.getAttribute("result");
		if(result == 'success'){

			
			//if(uiobj){
			//	$('#'+uiobj).load(baseurl+tag.getAttribute("id"));
			//}
			
			
			if(success_callback_func){
				eval(success_callback_func);
			}
			
			var outmsg;
			
			// CHECK FOR INNER ERROR TAGZ
			var tmparr = tag.getElementsByTagName("error");
			
			if(tmparr.length <= 0){
				tmparr = tag.getElementsByTagName("Error");
			}
			
			if(tmparr.length > 0){
				
				outmsg = "Save appears successful. However, the following warning(s) were issued:\n";
				
				for(var x=0;x < tmparr.length;x++){
					
					outmsg += "* "+tmparr[x].textContent+ "\n";				
					
				}
				
			}else{
			

				outmsg = "Successfully saved";
				
			}

			return {"result":tag.getAttribute("id"), "message":outmsg };
			
		}else{
			var reason = tag.getAttribute("reason");

			return {"result":-1, "message":"Error: Attempt to Add/Edit Failed.\nReason:"+reason };
		}

	}else{

		tagarr = xmldoc.getElementsByTagName("error");
		if(tagarr.length > 0){

			var tag = tagarr[0];

			
			return {"result":-2, "message":"Error returned: "+ ((tag.text!=undefined)?tag.text:tag.textContent) };
			
		}else{

			return {"result":-3, "message":"Unknown msg returned by server: "+xmldata };
		}
		
	}


	//loadInPanel('');
	//alert("Post successful: "+msg);

	return {"result":-4, "message":"An unknown error occurred while handling server ajax response."};

}



 
 
 
/**
 * loadAjaxData - loads ajax data and calls a callback function on success
 * @param loadurl	The full URL to the API including query strings
 * @param callback_func_name	The function name to call back, without the args, 
 * 								Example: function named parseXMLStuff(xmldoc), you would pass in "parseXMLStuff"	
 * @return Nothing! Call back should handle everything you need!
 */
function loadAjaxData(loadurl,callback_func_name){

	$.ajax({
		url: loadurl,
		type: "POST",
		success: function(data){

			var xmldoc = getXMLDoc(data);////(new DOMParser()).parseFromString(data, "text/xml");

			eval(callback_func_name+'(xmldoc)');

		}
	});


}



/**
 * PARSE xmldoc, RENDER THE TABLE
 * @param area
 * @param baseurl
 * @param tableFormat
 * @param xmldoc
 * @return Total count of items (not limited by the limit field, the full total, or if not usign page system, total records returned)
 */
function parseXMLData(area,tableFormat,xmldoc){

	var obj;

	var tagname = "";
	var callback_func_name = "";
	var delete_area = "";
	var delete_message_varname = "delmsg";

	
	///var ui_load_panel = (ui_override)?ui_override:account_uiobj;
	
	//alert(ui_load_panel.panel);

	
	//var ui_load_panel;
	
	delete_message_varname = area+"_delmsg";
	
	switch(area){
	default:

		obj = getEl(area+'_table');

		tagname = area.charAt(0).toUpperCase() + area.substr(1);
		delete_area = area+"s";
		callback_func_name = "load"+tagname+"s()";

		break;
	
//	case 'campaign':
//
//		obj = getEl(area+'_table');
//	
//		
//		tagname = "Campaign";
//		delete_area = area+"s";
//		callback_func_name = "loadCampaigns()";
//		
//		break;
	}

	
	var totalcount = 0;
	var special_tag;
	var special_idx=0; // USED TO KEEP TRACK OF EACH RECORD
	var special_stack = new Array();
	var tmparr;
	
	// DETECT AND USE PAGE SYSTEM RELATED INFO (total count)
	try{
		
		var tmptags = xmldoc.getElementsByTagName(tagname + "s");
		totalcount = tmptags[0].getAttribute("totalcount");

	}catch(e){}
	
	
	
	
	// GRAB ALL DATA TAGS
	var dataarr = xmldoc.getElementsByTagName(tagname);
	if(totalcount <= 0){
		
		// IF TOTAL COUNT WASNT POPULATED ABOVE, MANUALLY SET TO THE TOTAL RECORD SIZE
		totalcount = dataarr.length;
	}


	// REMOVE ALL ROWS BUT THE HEADER
	clearTable(obj);

	//alert("area:"+area+" "+obj+" "+obj.rows.item(0).cells);
	
	if(dataarr.length == 0){
		
		
		
		var colspan = obj.rows.item(0).cells.length;
		
		var lastRow = obj.rows.length;
		var row = obj.insertRow(lastRow);
		var cell = row.insertCell(0);

		
		cell.colSpan = colspan;
		cell.className = "align_center";
		cell.innerHTML = "<i>No Records found.</i>";
	}
	
	var clsname;
	for(var x=0;x < dataarr.length;x++){


		var lastRow = obj.rows.length;
		var row = obj.insertRow(lastRow);

		
		clsname = 'row'+(x%2);
		
		// STORE RECORD ID ON THE TR ELEMENT, SO EACH CELL CAN ACCESS IT AS THE PARENT
		row.setAttribute("record_id",dataarr[x].getAttribute('id'));

		var cell;
		var newDate,tmptime,datestring;
		var cur_name,cur_class,cur_data,priv_name;

		
		
		for(var y=0; y < tableFormat.length;y++){

			//alert("INSERT CELL - "+y+" tableFormat:"+tableFormat[y]);
			
			if(!tableFormat[y])continue;
			
			
			
			
			cell = row.insertCell(y);

			
			//alert("Format: "+tableFormat[y][0]+" "+tableFormat[y][1]);

			cur_name = tableFormat[y][0];
			cur_class= (tableFormat[y][1])?' '+tableFormat[y][1]:'';// INCLUDES THE SPACE FOR CSS CLASSES

			// SPECIAL MODE, currently only delete, but could do other things later
			if(cur_name.charAt(0) == '['){

				// EXTRACT SPECIAL TAG DETAILS
				special_tag = cur_name.substring(1,cur_name.length-1);
				
				
				
				if(special_tag.indexOf("get:") == 0){
					
					
					
					
					tmparr = special_tag.split(":");
					
					// PUSH THE SPECIAL TAG TO AN ARRAY, ALONG WITH ADDITIONAL INFO
					special_stack[special_idx] = 'get:'+tmparr[1];
					
					if(tmparr.length > 2){
						
						for(var i = 2;i < tmparr.length;i++){
							
							special_stack[special_idx] += ':'+dataarr[x].getAttribute(tmparr[i]);
							
						}
						
					}else{
						
						// default append the current record ID
						special_stack[special_idx] += ':'+dataarr[x].getAttribute("id");
						
					}
						
				
					
					cell.innerHTML = '<div id="'+area+'_special_data_load_'+special_idx+'">[loading...]</div>';
					
					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){
						
						
						handleListClick(tagname, this.parentNode.getAttribute('record_id'));
						
						
					}
					
					// AFTER PUSHING, INCREMENT THE POINTER
					special_idx++;
				
					
				// MAKE A CHECKBOX
				}else if(special_tag.indexOf("checkbox:") == 0){
					
					tmparr = special_tag.split(":");
					
					
					cell.innerHTML = '<input type="checkbox" name="'+tmparr[1]+x+'" id="'+tmparr[1]+x+'" value="'+dataarr[x].getAttribute(tmparr[2])+'">';
					cell.className = clsname+' '+cur_class;
					
				// Render field, with a label after it
				}else if(special_tag.indexOf("postlabel:") == 0){
					
					tmparr = special_tag.split(":");
					
					
					cell.innerHTML = dataarr[x].getAttribute(tmparr[1]) + tmparr[2];
					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){
						
						
						handleListClick(tagname, this.parentNode.getAttribute('record_id'));
						

					}
				

					
				// RENDER TIME
				}else if(special_tag.indexOf("time:") == 0){	
					
					tmparr = special_tag.split(":");
					
					newDate = new Date();
					
					tmptime = dataarr[x].getAttribute(tmparr[1]);
					
					newDate.setTime( tmptime * 1000 );

					
					var tmphrs = newDate.getHours();
					var tmpmin = newDate.getMinutes();
					
					tmpmin = (tmpmin < 10)?'0'+tmpmin:tmpmin;
					if(tmphrs >= 12){
						datestring = ((tmphrs==12)?tmphrs:(tmphrs-12))+":"+tmpmin+"pm";
					}else{
						datestring = (tmphrs)+":"+tmpmin+"am";
						
					}
					
					datestring += " "+(newDate.getMonth()+1)+"/"+newDate.getDate()+"/"+newDate.getFullYear();
					
					cell.innerHTML = (tmptime > 0)?datestring:'n/a';
					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){
						
						
						handleListClick(tagname, this.parentNode.getAttribute('record_id'));

					}
					
					
				}else if(special_tag.indexOf("render:") >= 0){
					
					tmparr = special_tag.split(":");
					
					
					if(tmparr[1] == 'who'){
						
						var cell_text = "-"
						
						if(dataarr[x].getAttribute('type') == 'campaign'){
							
							special_stack[special_idx] = 'get:campaign_name:'+dataarr[x].getAttribute('who');
							
							cell_text =  '<div id="'+area+'_special_data_load_'+special_idx+'">[loading...]</div>';
							
							special_idx++;
							
							
						}else if(dataarr[x].getAttribute('type') == 'user'){
							
							cell_text =  dataarr[x].getAttribute('who');
							
						}
						
						cell.innerHTML = cell_text;
						cell.className = clsname+' hand'+cur_class;
						cell.onclick = function(){
							
							
							handleListClick(tagname, this.parentNode.getAttribute('record_id'));
							

						}
						
					}else{
						
					}
							
							
							
				}else if(special_tag.indexOf("priv") == 0){
					
					
					
					///alert(dataarr[x].getAttribute('priv'));
					
					
					switch(parseInt(dataarr[x].getAttribute('priv'))){
					default:
					case 1:
						priv_name = "Trainee";
						break;
					case 6:
					case 5:

						priv_name = "Administrator";

						break;	
					case 4:
					case 3:
					case 2:

						priv_name = "Caller";
						break;
						
					}
					
					cell.innerHTML = priv_name;
					cell.className = clsname+' hand'+cur_class;
					cell.onclick = function(){
						
						
						handleListClick(tagname, this.parentNode.getAttribute('record_id'));
						

					}
					
				}else{

					cell.innerHTML = '<a href="#" onclick="deleteItem('+delete_message_varname+',\''+delete_area+'\','+dataarr[x].getAttribute('id')+', \''+callback_func_name+'\' );return false;">'+
									'<img id="'+delete_area+'-delete-img-'+dataarr[x].getAttribute('id')+'" src="images/delete.png" width="24" height="24" onmouseover="this.src=\'images/delete.png\'" onmouseout="this.src=\'images/delete.png\'" border="0" />'+
									'</a>';
					cell.className = ''+cur_class;
					
				}
				
					



			}else{

				cur_data = dataarr[x].getAttribute(cur_name);


				cell.innerHTML = (cur_data)?cur_data:'&nbsp;';
				cell.className = clsname+' hand'+cur_class;
				cell.onclick = function(){
					
					handleListClick(tagname, this.parentNode.getAttribute('record_id'));
					
					

				}
			}




		} // END FIELD LIST

	} // END OF XML TAGS

	
	
	
	// SECONDARY AJAX - POST PROCESSING - MAKE A SECOND AJAX CALL TO RETRIEVE AND RENDER INFO
	if(special_idx > 0){
	
	
		//console.dir(special_stack);
		
		secondaryAjaxPOST(area,special_stack);
		
			
			
	}
	
	
	applyUniformity();
	
	return totalcount;
} // END parseXMLData()


/**
 * Wrapper for list click functions
 * @param area_name The XML tag name basically, first letter uppercase (see the top of parseXMLData())
 * @param id The ID of the record to load
 * @return nothing
 */
function handleListClick(area_name, id){
	var cmd;
	
	
	cmd = 'handle'+area_name+'ListClick('+id+');';
	
	eval(cmd);
	
}





/**
 * Makes an AJAX post, to request the stack worth of data
 * Call back function handles injecting the data
 * @param special_stack
 * @return
 */
function secondaryAjaxPOST(area,special_stack){
	
	var loadurl =	'api/api.php'+
						"?get=secondary_ajax&"+
						"area="+area+"&"+
						"mode=xml";
	
	var postdata='';
	for(var x=0;x < special_stack.length;x++){
		
		
		postdata += 'special_stack['+x+']='+escape(special_stack[x])+"&";
		
	}
	
	
	
	$.ajax({
		url: loadurl,
		type: "POST",
		data: postdata,
		success: function(data){

			//alert(data);return;
		
			var xmldoc = getXMLDoc(data);////(new DOMParser()).parseFromString(data, "text/xml");

			eval('handleSecondaryAjax(\''+area+'\',xmldoc)');

		}
	});
	
	
}


function handleSecondaryAjax(area, xmldoc){
	
	var tag = xmldoc.documentElement;
	var obj=null;
	for(var x=0;(obj = getEl(area+'_special_data_load_'+x)) != null;x++){
		
		getEl(area+'_special_data_load_'+x).innerHTML = tag.getAttribute('data_'+x);

	}
	
}



function closeDiv(obj_name){

	var obj = getEl(obj_name);
	obj.innerHTML = '&nbsp;';

	$('#'+obj_name).hide();

}

function loadInDiv(url, obj_name){


	$('#'+obj_name).hide();

	$('#'+obj_name).load(url);

	$('#'+obj_name).show();

}




function loadInPanel(url,uiobj){

	
	//alert("UI:"+uiobj+" Panel: "+uiobj.panel+" "+url);

	$(uiobj.panel).load( url,
		function(){

		
			try{
				if(uiobj == admin_uiobj){
					onLoadAdminTabStuff("click", uiobj);
				}else{
					onLoadAccountTabStuff("click", uiobj);
				}
			}catch(e){
				
				onLoadAccountTabStuff("click", uiobj);
			}
			//onLoadTabStuff("click", uiobj);
		
			

		}
	);
	
>>>>>>> refs/remotes/origin/dmednick_dialer_status_dashboard
}







function setOrder(prepend,field,direction){


	eval(prepend+'orderby="'+field+'";');
	eval(prepend+'orderdir="'+direction+'";');


}