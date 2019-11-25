

var d, dom, ie, ie4, ie5x, moz, mac, win, lin, old, ie5mac, ie5xwin, op;
d = document;
n = navigator;
na = n.appVersion;
nua = n.userAgent;
win = ( na.indexOf( 'Win' ) != -1 );
mac = ( na.indexOf( 'Mac' ) != -1 );
lin = ( nua.indexOf( 'Linux' ) != -1 );

dom = ( d.getElementById );
op = ( nua.indexOf( 'Opera' ) != -1 );
konq = ( nua.indexOf( 'Konqueror' ) != -1 );
saf = ( nua.indexOf( 'Safari' ) != -1 );
moz = ( nua.indexOf( 'Gecko' ) != -1 && !saf && !konq);
ie = ( d.all && !op );
ie4 = ( ie && !dom );
			
			


function hasCheckedCheckboxes(baseobj){

	// GRAB ARRAY OF CHECKED USERS
	var obj=null;
	for(var x=0, y=0;(obj=getEl(baseobj+x)) != null;x++){

		if(obj.checked == true)return true;
		
	}

	return false;

}


function toggleEnableAllChecks(baseobj, way){

	// GRAB ARRAY OF CHECKED USERS
	var obj=null;
	for(var x=0, y=0;(obj=getEl(baseobj+x)) != null;x++){


		if(way == 0){

			obj.disabled = true;

		}else{
			obj.disabled = false;
		}

	}

	applyUniformity();

}



function toggleAllChecks(baseobj, way){

	// GRAB ARRAY OF CHECKED USERS
	var obj=null;
	for(var x=0, y=0;(obj=getEl(baseobj+x)) != null;x++){


		if(way == 0){

			obj.checked = false;
		}else if(way == 1){

			obj.checked = true;
		}else{
			obj.checked = !obj.checked;
		}

	}

	applyUniformity();

}



function makeHourDD(name,sel,classname){
	
	var out = '<select name="'+name+'" id="'+name+'" ';
	out += (classname)?' class="'+classname+'"':'';
	out += '>';

	for(var x=1;x < 25;x++){
		
		out += '<option value="'+x+'"';
		out += (sel == x)?' SELECTED':'';
		out += '>';
		if(		x==24)	out += 'Midnight';
		else if(x==12)	out += 'Noon';
		else			out += (x%12)+((x>=12)?' PM':' AM');
	}

	out +='</select>';
	return out;
}


function makeNumberDD(name,sel,start,end,inc,zeropad,tag_inject,blankfield){

	//$sel = intval($sel);

	var out = '<select name="'+name+'" id="'+name+'" '+tag_inject+' >';

	out += (blankfield)?'<option value=""></option>':'';

	for(var x=start;x <= end;x += inc){
		
		out+= '<option value="'+((zeropad && x < 10)?'0'+x:x)+'"';
		out+= (sel == x)?' SELECTED ':'';
		out+= '>'+((zeropad && x < 10)?('0'+x):x);
	}
	
	out += '</select>';

	return out;
}


function getMonthDD(name,sel,extra_attr){
	var out = '<select name="'+name+'"  id="'+name+'" '+extra_attr+' >';
	for(var x=1;x <= 12;x++){
		
		out +='<option value="'+x+'"';
		
		if(x == sel)out += ' selected ';
		
		out +='>'+x+'</option>';
	}
	out +='</select>';
	
	return out;
}


function getDayDD(name,sel,extra_attr){
	var out = '<select name="'+name+'"  id="'+name+'" '+extra_attr+' >';
	for(var x=1;x <= 31;x++){
		
		out +='<option value="'+x+'"';
		if(x == sel)out += ' selected ';
		out +='>'+x+'</option>';
	}
	
	out +='</select>';
	return out;
}

function getYearDD(name,sel,extra_attr){
	
	var today = new Date();
	var year = today.getFullYear();
	
	var out = '<select name="'+name+'" id="'+name+'" '+extra_attr+' >';
	for(var x=1970;x < (year+1);x++){
		
		out +='<option value="'+x+'"';
		if(x == sel)out += ' selected ';
		out +='>'+x+'</option>';
	}
	out +='</select>';
	return out;
}














function isEmail(email) {
  var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
}



function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}



function renderTimeFormatted(input){

	var tmptime = parseInt(input);
	var tmphours = Math.floor(tmptime/3600);
	
	// REMOVE HOURS
	tmptime -= (tmphours * 3600);

	var tmpmin = Math.floor(tmptime/60);

	tmptime -= (tmpmin * 60);

	var tmpsec = (tmptime % 60);
	var out = 	((tmphours > 0)?tmphours+':':'')+
					((tmpmin < 10)?'0'+tmpmin:tmpmin)+':'+
					((tmpsec < 10)?'0'+tmpsec:tmpsec);

	return out;

}



function recheck(msg,obj){
	if(msg)alert(msg);
	
	if(!document.layers)try{obj.select();}catch(e){ }
	return false;
}

function getEl(id){return document.getElementById(id);}

function go(url){ window.location=url;}


/**
 * getFormValues(Form Object, Validation function (string))
 * @param fobj		Form Object
 * @param valFunc	Validation function as string, example 'validateMyField' if you have a function called validateMyField(name,value,formobj)
 * @return		array of name/value on form validation failure,
 * 				or Returns the string of name/value combos on success
 */
function getFormValues(fobj,valFunc){ 

   var str = ""; 
   var valueArr = null; 
   var val = ""; 
   var cmd = ""; 

   for(var i = 0;i < fobj.elements.length;i++){ 

	   
	   //alert(fobj.elements[i].type+' '+fobj.elements[i].name+" "+fobj.elements[i].value);
	   if(!fobj.elements[i].name)continue;
	   
	   
       switch(fobj.elements[i].type){ 
	
		case "email":
		case "text": 
		case "textarea":
		case "password":
				if(valFunc){ 

					// use single quotes for argument so that the value of 
					// fobj.elements[i].value is treated as a string not a literal 

					cmd = valFunc + "("+'fobj.elements[i].name'+"," + 'fobj.elements[i].value' + ","+'fobj'+")"; 

					val = eval(cmd);

					if(!val){

						var outarr = new Array();
						outarr[0] = fobj.elements[i].name;
						outarr[1] = fobj.elements[i].value;
						
						return outarr;
					}
				} 

				// NAME = VALUE &
				str += fobj.elements[i].name+"="+escape(fobj.elements[i].value)+"&"; 

				break; 


       case "hidden":
    	   
    	   
    	   // NAME = VALUE &
    	   str += fobj.elements[i].name + "=" + escape(fobj.elements[i].value) + "&"; 
    	   break;
       case "checkbox":
    	   
    	   //alert(fobj.elements[i].name + " "+fobj.elements[i].value)
    	   
    	   // NAME = VALUE &
    	   if(fobj.elements[i].checked){
    		   str += fobj.elements[i].name + "=" + escape(fobj.elements[i].value) + "&";
    	   }
    	   
    	   break;
       case "radio":

    	   // NAME = VALUE &
    	   if(fobj.elements[i].checked){
    		   str += fobj.elements[i].name + "=" + escape(fobj.elements[i].value) + "&";
    	   }
    	   
    	   break;
       case "select-one": 

    	   if(valFunc){ 

               //use single quotes for argument so that the value of 

               //fobj.elements[i].value is treated as a string not a literal 

               cmd = valFunc + "("+'fobj.elements[i].name'+"," + 'fobj.elements[i].value' +","+'fobj'+")";

               val = eval(cmd); 
               
               if(!val){

            	   var outarr = new Array();
            	   outarr[0] = fobj.elements[i].name;
            	   outarr[1] = fobj.elements[i].value;
            	  return outarr;
               }
           } 
    	   
    	   
    	   //alert()
    	   
            str += fobj.elements[i].name+ "=" +fobj.elements[i].value + "&"; 

            break; 
       case "select-multiple":
    	   
    	   //alert(fobj.elements[i].value);
    	   
    	   var valarr = getSelectValues(fobj.elements[i]);
    	   
    	   for(var x=0;x < valarr.length;x++){
    	   
    	   
    		   str += fobj.elements[i].name+ "=" +valarr[x]+ "&";
    		   
    	   }

           break;  
       } 

   } 

   
   str = str.substr(0,(str.length - 1)); 

  // alert(str);
   
   return str; 

}

function getSelectValues(select){
	var result = [];
	var options = select && select.options;
	var opt;

	for (var i=0, iLen=options.length; i<iLen; i++) {
		opt = options[i];

		if (opt.selected) {
			result.push(opt.value || opt.text);
		}
	}
	return result;
}



function ieDisplay(objid,way){
	
	
	var obj = document.getElementById(objid);
	
	try{
		
		obj.style.display = (way)?'inline':'none';
		
	}catch(e){
		
		window.status='JS Exception ('+e+')';	

	}
}


function zeroFill( number, width ){ 
  width -= number.toString().length;
  
  if ( width > 0 ){ 
	  return new Array( width + (/\./.test( number ) ? 2 : 1) ).join( '0' ) + number; 
  } 
  return number; 
} 





function makeTimebar(basename,mode=0, curDate,stack, extra_attr){
	
	
	
	// Mode 0 = full
	// Mode 1 = date
	// mode 2 = hour

	var output = "";
	
	var hour = curDate.getHours() + 1;
	var min = curDate.getMinutes();
	var month = curDate.getMonth() + 1;
	var day = curDate.getDate();
	var year = curDate.getFullYear();
	
	var ampm = (hour > 11)?"pm":"am";
	
	if(mode == 0 || mode == 2){

		/// makeNumberDD($name,$sel,$start,$end,$inc,$zeropad,$tag_inject)
		output += makeNumberDD(basename+'hour',	((hour%12==0)?12:(hour%12)),	1,12,1,0,extra_attr)+' : ';	// Hours
		output += makeNumberDD(basename+'min',	min,0,59,1,1,0,extra_attr);		// minutes
		output += '<select name="'+basename+'timemode'+'" '+extra_attr+' id="'+basename+'timemode'+'"><option value="am"'+((hour < 12)?' SELECTED':'')+'>AM<option value="pm"'+((hour >= 12 && hour < 24)?' SELECTED':'')+'>PM</select>';
	}

	if(mode != 2){
		output += (mode == 0)?' &nbsp; ':'';
		output += ((stack) ? '<br>' : '')+
							getMonthDD(basename+'month',month,extra_attr)+'/'+
							getDayDD(basename+'day',day,extra_attr)+'/'+
							getYearDD(basename+'year',year,extra_attr);
	}
	
	return output;
}
