

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
			
			

function isEmail(email) {
  var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
}



function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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

       } 

   } 

   
   str = str.substr(0,(str.length - 1)); 

   
   return str; 

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

