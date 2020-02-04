/***
 * A collection of common functions, used in the Ajax/API processing
 */

/**
 * Clears all rows but the first (the header row)
 * @param obj    The table object to clear
 * @return n/a
 */
function clearTable(obj) {
    //alert(obj.rows.length);
    if (obj.rows.length > 1) {
        for (var x = obj.rows.length; x > 1; x--) {
            obj.tBodies[0].deleteRow(x - 1);
        }
    }
}

/**
 * Ajax/API interface to delete a database record
 * @param area    Area to delete from, aka table name (usually)
 * @param id    ID of the record to delete
 * @param callback_func_name     Function to call on success, ex: "myCallBack('myarg')"
 * @return n/a
 */
function deleteItem(confirmmsg, area, id, callback_func_name) {
    if (confirm(confirmmsg)) {
        var loadurl = 'api/api.php' +
            "?get=" + area + "&" +
            "mode=xml&" +
            "action=delete&" +
            "id=" + id;
        //alert($('#'+area+'-delete-img-'+id).attr("src"));
        //alert(loadurl+" "+callback_func_name);
        $('#' + area + '-delete-img-' + id).attr("src", "images/ajax-loader.gif");
        $.ajax({
            url: loadurl,
            type: "POST",
            success: function (data) {
                $('#' + area + '-delete-img-' + id).attr("src", "images/delete.png");
                //alert("Response: "+data);
                try {
                    var xmldoc = getXMLDoc(data);
                    var tag = xmldoc.getElementsByTagName("Error");
                    // LOWERCASE BUG PATCH
                    if (tag.length == 0) {
                        tag = xmldoc.getElementsByTagName("error");
                    }
                    if (tag.length > 0) {
                        // GET THE FIRST TAG
                        tag = tag[0];
                        var resultcode = tag.getAttribute("code");
                        //tmparr[x].textContent
                        alert("ERROR(" + resultcode + "): " + tag.textContent);
                        return
                        // SUCCESS
                    }
                } catch (ex) {
                }
                eval(callback_func_name);
            }
        });
    }
}

/**
 * Ajax/API interface to copy a campaign's forms to another campaign
 * @param area    Area to delete from, aka table name (usually)
 * @param id    ID of the record to delete
 * @param callback_func_name     Function to call on success, ex: "myCallBack('myarg')"
 * @return n/a
 */
function copyFormBuilder(confirmmsg, area, id, callback_func_name) {
    if (confirm(confirmmsg)) {
        var loadurl = 'api/api.php' +
            "?get=" + area + "&" +
            "mode=xml&" +
            "action=delete&" +
            "id=" + id;
        //alert($('#'+area+'-delete-img-'+id).attr("src"));
        //alert(loadurl+" "+callback_func_name);
        $('#' + area + '-delete-img-' + id).attr("src", "images/ajax-loader.gif");
        $.ajax({
            url: loadurl,
            type: "POST",
            success: function (data) {
                $('#' + area + '-delete-img-' + id).attr("src", "images/delete.png");
                //alert("Response: "+data);
                try {
                    var xmldoc = getXMLDoc(data);
                    var tag = xmldoc.getElementsByTagName("Error");
                    // LOWERCASE BUG PATCH
                    if (tag.length == 0) {
                        tag = xmldoc.getElementsByTagName("error");
                    }
                    if (tag.length > 0) {
                        // GET THE FIRST TAG
                        tag = tag[0];
                        var resultcode = tag.getAttribute("code");
                        //tmparr[x].textContent
                        alert("ERROR(" + resultcode + "): " + tag.textContent);
                        return
                        // SUCCESS
                    }
                } catch (ex) {
                }
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
    if (navigator.appName == 'Microsoft Internet Explorer') {
        var ua = navigator.userAgent;
        var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
            rv = parseFloat(RegExp.$1);
    }
    return rv;
}

function getXMLDoc(xml_data) {
    var ver = getInternetExplorerVersion();
    if (ver < 0 && window.DOMParser) {
        parser = new DOMParser();
        xmlDoc = parser.parseFromString(xml_data, "text/xml");
    } else { // Internet Explorer
        xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = "false";
        xmlDoc.loadXML(xml_data);
    }
    return xmlDoc;
}

/**
 * handleEditXML - Parses API's XML response to editing data
 * If successful, it will redirect-to/refresh the edit form, with the ID
 * Falls back to the Error tag, if it cannot figure out what the result is
 * @param xmldata    Raw xml text data
 * @param baseurl    The base add/edit URL with everthing but the ID at the end (we append id to the end)
 *                    Example: "processor.php?editing_item_id="
 * @param uiobj        The jquery ui object (where the tabs inject the content, usually stored as account_uiobj or admin_uiobj)
 * @param success_callback_func A string of the function to call after receiving a success message

 * @return    n/a
 */
function handleEditXML(xmldata, success_callback_func) {
    // PASSING IN XML DOCUMENT OBJECT
    if (typeof xmldata == "object") {
        var xmldoc = xmldata;
        // PASSING IN XML STRING
    } else {
        var xmldoc = getXMLDoc(xmldata);///(new DOMParser()).parseFromString(xmldata, "text/xml");
    }
    var tagarr = xmldoc.getElementsByTagName("EditMode");
    // LOWERCASE BUG PATCH
    if (tagarr.length == 0) {
        tagarr = xmldoc.getElementsByTagName("editmode");
    }
    if (tagarr.length > 0) {
        // GET THE FIRST TAG
        var tag = tagarr[0];
        var result = tag.getAttribute("result");
        if (result == 'success') {
            //if(uiobj){
            //	$('#'+uiobj).load(baseurl+tag.getAttribute("id"));
            //}
            if (success_callback_func) {
                eval(success_callback_func);
            }
            var outmsg;
            // CHECK FOR INNER ERROR TAGZ
            var tmparr = tag.getElementsByTagName("error");
            if (tmparr.length <= 0) {
                tmparr = tag.getElementsByTagName("Error");
            }
            if (tmparr.length > 0) {
                outmsg = "Save appears successful. However, the following warning(s) were issued:\n";
                for (var x = 0; x < tmparr.length; x++) {
                    outmsg += "* " + tmparr[x].textContent + "\n";
                }
            } else {
                outmsg = "Successfully saved";
            }
            return {"result": tag.getAttribute("id"), "message": outmsg};
        } else {
            var reason = tag.getAttribute("reason");
            return {"result": -1, "message": "Error: Attempt to Add/Edit Failed.\nReason:" + reason};
        }
    } else {
        tagarr = xmldoc.getElementsByTagName("error");
        if (tagarr.length > 0) {
            var tag = tagarr[0];
            return {
                "result": -2,
                "message": "Error returned: " + ((tag.text != undefined) ? tag.text : tag.textContent)
            };
        } else {
            return {"result": -3, "message": "Unknown msg returned by server: " + xmldata};
        }
    }
    //loadInPanel('');
    //alert("Post successful: "+msg);
    return {"result": -4, "message": "An unknown error occurred while handling server ajax response."};
}

/**
 * loadAjaxData - loads ajax data and calls a callback function on success
 * @param loadurl    The full URL to the API including query strings
 * @param callback_func_name    The function name to call back, without the args,
 *                                Example: function named parseXMLStuff(xmldoc), you would pass in "parseXMLStuff"
 * @return Nothing! Call back should handle everything you need!
 */
function loadAjaxData(loadurl, callback_func_name, mode) {
    // console.log('loadAjaxData :: ' + loadurl + ' :: ' + callback_func_name);
    $.ajax({
        url: loadurl,
        type: "POST",
        success: function (data) {
            if(mode === 'json') {
                eval(callback_func_name + '(data)');
            } else {
                let xmldoc = getXMLDoc(data);
                ////(new DOMParser()).parseFromString(data, "text/xml");
                eval(callback_func_name + '(xmldoc)');
            }
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
function parseXMLData(area, tableFormat, xmldoc) {
    /** some debugging if you get hosed
     console.log('Area =' + area);
     console.log('tableFormat = ' + tableFormat);
     console.log('xmldoc = ' + xmldoc);
     */
    var obj;
    var tagname = "";
    var callback_func_name = "";
    var delete_area = "";
    var copy_area = "";
    var delete_message_varname = "delmsg";
    ///var ui_load_panel = (ui_override)?ui_override:account_uiobj;
    //alert(ui_load_panel.panel);
    //var ui_load_panel;
    delete_message_varname = area + "_delmsg";
    var copy_message_varname = "copymsg";
    copy_message_varname = area + "_copymsg";
    switch (area) {
        case 'form_builder':
            obj = getEl(area + '_table');
            tagname = area.charAt(0).toUpperCase() + area.substr(1);
            copy_area = area + "s";
            callback_func_name = "load" + tagname + "s()";
            break;
        case 'schedule':
            obj = getEl(area + '_table');
            tagname = area.charAt(0).toUpperCase() + area.substr(1);
            delete_area = "process_tracker_schedules";
            callback_func_name = "load" + tagname + "s()";
            break;                    
        default:
            obj = getEl(area + '_table');
            tagname = area.charAt(0).toUpperCase() + area.substr(1);
            delete_area = area + "s";
            callback_func_name = "load" + tagname + "s()";
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
    var special_idx = 0; // USED TO KEEP TRACK OF EACH RECORD
    var special_stack = new Array();
    var tmparr;

    // DETECT AND USE PAGE SYSTEM RELATED INFO (total count)
    try {
        var tmptags = xmldoc.getElementsByTagName(tagname + "s");
        totalcount = tmptags[0].getAttribute("totalcount");
    } catch (e) {
    }

    var errarr = xmldoc.getElementsByTagName("error");
    if (errarr.length > 0) {
        var msg = "Errors detected: ";
        var tmpcode = 0;
        for (var i = 0; i < errarr.length; i++) {
            tmpcode = errarr[i].getAttribute('code');
            if (tmpcode == '-101') {
                go('index.php');
                break;
            }
            //	msg += errarr[i].nodeValue;
        }
        //alert(msg);
    }

    // GRAB ALL DATA TAGS
    let dataarr = xmldoc.getElementsByTagName(tagname);
    //console.log(tagname);
    //console.log(dataarr);
    if (totalcount <= 0) {
        // IF TOTAL COUNT WASNT POPULATED ABOVE, MANUALLY SET TO THE TOTAL RECORD SIZE
        totalcount = dataarr.length;
    }
    // REMOVE ALL ROWS BUT THE HEADER
    clearTable(obj);
    //alert("area:"+area+" "+obj+" "+obj.rows.item(0).cells);
    if (dataarr.length == 0) {
        var colspan = obj.rows.item(0).cells.length;
        var lastRow = obj.rows.length;
        var row = obj.insertRow(lastRow);
        var cell = row.insertCell(0);
        cell.colSpan = colspan;
        cell.className = "align_center";
        cell.innerHTML = "<i>No Records found.</i>";
    }
    let clsname;
    for (var x = 0; x < dataarr.length; x++) {
        var lastRow = obj.rows.length;
        var row = obj.insertRow(lastRow);
        clsname = 'row' + (x % 2);
        // STORE RECORD ID ON THE TR ELEMENT, SO EACH CELL CAN ACCESS IT AS THE PARENT
        row.setAttribute("record_id", dataarr[x].getAttribute('id'));
        row.setAttribute("color_index", "" + (x % 2));
        var cell;
        var newDate, tmptime, datestring, tmpstr;
        var cur_name, cur_class, cur_data, priv_name;
        for (var y = 0; y < tableFormat.length; y++) {
            //alert("INSERT CELL - "+y+" tableFormat:"+tableFormat[y]);
            if (!tableFormat[y]) continue;
            cell = row.insertCell(y);
            //alert("Format: "+tableFormat[y][0]+" "+tableFormat[y][1]);
            cur_name = tableFormat[y][0];
            cur_class = (tableFormat[y][1]) ? ' ' + tableFormat[y][1] : '';// INCLUDES THE SPACE FOR CSS CLASSES
            // SPECIAL MODE, currently only delete, but could do other things later
            if (cur_name.charAt(0) == '[') {
                // EXTRACT SPECIAL TAG DETAILS
                special_tag = cur_name.substring(1, cur_name.length - 1);
                if (special_tag.indexOf("get:") == 0) {
                    tmparr = special_tag.split(":");
                    // PUSH THE SPECIAL TAG TO AN ARRAY, ALONG WITH ADDITIONAL INFO
                    special_stack[special_idx] = 'get:' + tmparr[1];
                    if (tmparr.length > 2) {
                        for (var i = 2; i < tmparr.length; i++) {
                            special_stack[special_idx] += ':' + dataarr[x].getAttribute(tmparr[i]);
                        }
                    } else {
                        // default append the current record ID
                        special_stack[special_idx] += ':' + dataarr[x].getAttribute("id");
                    }
                    //	alert(special_stack[special_idx]);
                    cell.innerHTML = '<div id="' + area + '_special_data_load_' + special_idx + '">[loading...]</div>';
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // AFTER PUSHING, INCREMENT THE POINTER
                    special_idx++;
                    /**
                     * ['[call_function:manualRecount:Manual Recount:id]', 'align_center']
                     */
                } else if (special_tag.indexOf("call_function:") == 0) {
                    tmparr = special_tag.split(":");// 0 = call_function, 1 = the function name to call, 2 = Button/Link name, 3 = arg1 to pass
                    cell.innerHTML = '<input type="button" value="' + tmparr[2] + '" onclick="' + tmparr[1] + '(' + dataarr[x].getAttribute(tmparr[3]) + ')">';
                    cell.className = clsname + ' ' + cur_class;
                    // MAKE A CHECKBOX
                } else if (special_tag.indexOf("checkbox:") == 0) {
                    tmparr = special_tag.split(":");
                    cell.innerHTML = '<input type="checkbox" name="' + tmparr[1] + x + '" id="' + tmparr[1] + x + '" value="' + dataarr[x].getAttribute(tmparr[2]) + '">';
                    cell.className = clsname + ' ' + cur_class;
                    // Render field, with a label after it
                } else if (special_tag.indexOf("postlabel:") == 0) {
                    tmparr = special_tag.split(":");
                    cell.innerHTML = dataarr[x].getAttribute(tmparr[1]) + tmparr[2];
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // RENDER A PHONE NUMBER ALL PRETTY LIKE, AKA TURN "3334445555" into "(333) 444-5555"
                } else if (special_tag.indexOf("phone:") == 0) {
                    tmparr = special_tag.split(":");
                    var tmpphone = dataarr[x].getAttribute(tmparr[1]);
                    tmpphone = "(" + tmpphone.substr(0, 3) + ") " + tmpphone.substr(3, 3) + "-" + tmpphone.substr(6);
                    cell.innerHTML = tmpphone;
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // RENDER CARRIER BY ITS CARRIER PREFIX
                } else if (special_tag.indexOf("carrier:") == 0) {
                    tmparr = special_tag.split(":");
                    //				alert(dataarr[x].getAttribute(tmparr[1]) );
                    var prefix = dataarr[x].getAttribute(tmparr[1]);
                    //carrier_prefixes
                    //alert("butt: "+prefix);
                    var tmphtml = "-";
                    for (var t = 0; t < carrier_prefixes.length; t++) {
                        if (carrier_prefixes[t][0] == prefix) {
                            tmphtml = carrier_prefixes[t][1];
                            break;
                        }
                    }
                    cell.innerHTML = tmphtml;
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                } else if (special_tag.indexOf("link:") == 0) {
                    var cell_text = "-"
                    tmparr = special_tag.split(":");
                    cell_text = dataarr[x].getAttribute(tmparr[1]);
                    //var url = dataarr[x].getAttribute(tmparr[1]);
                    cell.innerHTML = cell_text;
                    cell.className = clsname + ' underline hand' + cur_class;
                    cell.onclick = function () {
//						alert("tmparr ("+tmparr[0]+","+tmparr[1]+","+tmparr[2]);
//
//						alert(url);
                        window.open(this.innerHTML);
                        //handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // RENDER THE PLAY BUTTON
                } else if (special_tag.indexOf("play_button") == 0) {
                    cell.innerHTML = '<a href="#" onclick="return false"><img src="images/play_button_small.png" width="20" title="Play Recording"></a>';
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));

                    }
                } else if (special_tag.indexOf("date:") == 0) {
                    tmparr = special_tag.split(":");
                    newDate = new Date();
                    tmptime = dataarr[x].getAttribute(tmparr[1]);
                    newDate.setTime(tmptime * 1000);
                    datestring = (newDate.getMonth() + 1) + "/" + newDate.getDate() + "/" + newDate.getFullYear();
                    cell.innerHTML = (tmptime > 0) ? datestring : 'n/a';
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // RENDER CALL DURATION
                } else if (special_tag.indexOf("duration") == 0) {
                    tmparr = special_tag.split(":");
                    //alert("test!");
                    if (tmparr.length > 2) {
                        var tmptime1 = parseInt(dataarr[x].getAttribute(tmparr[1]));
                        var tmptime2 = parseInt(dataarr[x].getAttribute(tmparr[2]));
                        var time_diff = tmptime2 - tmptime1;
                        //alert(tmptime2+" - "+tmptime1+" = "+time_diff);
                        cell.innerHTML = (tmptime1 == 0 || tmptime2 == 0) ? "n/a" : renderTimeFormatted(time_diff); // renderTimeFormatted() IS IN js/functions.js
                        cell.className = clsname + ' hand' + cur_class;
                        cell.onclick = function () {
                            handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    } else {
                        var tmptime1 = parseInt(dataarr[x].getAttribute(tmparr[1]));
                        //alert("tmptime: "+tmptime1);
                        cell.innerHTML = (tmptime1 == 0) ? "n/a" : renderTimeFormatted(tmptime1); // renderTimeFormatted() IS IN js/functions.js
                        cell.className = clsname + ' hand' + cur_class;
                        cell.onclick = function () {
                            handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    }
                    // RENDER PERCENTAGE
                } else if (special_tag.indexOf("percent:") == 0) {
                    tmparr = special_tag.split(":");
                    tmptime = dataarr[x].getAttribute(tmparr[1]);
                    cell.innerHTML = '<img src="percent.php?percent=' + tmptime + '" width="100" height="10" border="0" />';
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }


                } else if(special_tag.indexOf("maxlen:") == 0) {

                	 tmparr = special_tag.split(":");
                     tmpstr = dataarr[x].getAttribute(tmparr[1]);
                     tmptime = parseInt(""+tmparr[2]);
                     cell.innerHTML = htmlEntities((tmpstr.length > tmptime)?tmpstr.substr(0,tmptime).trim()+"...":tmpstr.trim())  ;
                     cell.className = clsname + ' hand' + cur_class;
                     cell.onclick = function () {
                         handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                     }
                    // RENDER TIME
                } else if (special_tag.indexOf("time:") == 0) {
                    tmparr = special_tag.split(":");
                    newDate = new Date();
                    tmptime = dataarr[x].getAttribute(tmparr[1]);
                    newDate.setTime(tmptime * 1000);
                    var tmphrs = newDate.getHours();
                    var tmpmin = newDate.getMinutes();
                    tmpmin = (tmpmin < 10) ? '0' + tmpmin : tmpmin;
                    if (tmphrs >= 12) {
                        datestring = ((tmphrs == 12) ? tmphrs : (tmphrs - 12)) + ":" + tmpmin + "pm";
                    } else {
                    	tmphrs = (tmphrs == 0)?12:tmphrs;
                        datestring = (tmphrs) + ":" + tmpmin + "am";
                    }
                    datestring += " " + (newDate.getMonth() + 1) + "/" + newDate.getDate() + "/" + newDate.getFullYear();
                    cell.innerHTML = (tmptime > 0) ? datestring : 'n/a';
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // RENDER MICRO TIME
                } else if (special_tag.indexOf("microtime:") == 0) {
                    tmparr = special_tag.split(":");
                    newDate = new Date();
                    tmptime = dataarr[x].getAttribute(tmparr[1]);
                    newDate.setTime(tmptime);
                    var tmphrs = newDate.getHours();
                    var tmpmin = newDate.getMinutes();
                    tmpmin = (tmpmin < 10) ? '0' + tmpmin : tmpmin;
                    if (tmphrs >= 12) {
                        datestring = ((tmphrs == 12) ? tmphrs : (tmphrs - 12)) + ":" + tmpmin + "pm";
                    } else {
                        datestring = (tmphrs) + ":" + tmpmin + "am";
                    }
                    1
                    datestring += " " + (newDate.getMonth() + 1) + "/" + newDate.getDate() + "/" + newDate.getFullYear();
                    cell.innerHTML = (tmptime > 0) ? datestring : 'n/a';
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // CONCAT 2 FIELDS TOGETHER WITH A SPACE SEPERATOR
                } else if (special_tag.indexOf("concat:") >= 0) {
                    tmparr = special_tag.split(":");

                    tmpstr = "";


                    for(var z=1;z < tmparr.length;z++){
                    	if(z > 1) tmpstr += " ";
                    	tmpstr += dataarr[x].getAttribute(tmparr[z]);
                    }


                    cell.innerHTML = tmpstr;//dataarr[x].getAttribute(tmparr[1]) + " " + dataarr[x].getAttribute(tmparr[2]);
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                } else if (special_tag.indexOf("button:") >= 0) {
                    // button:Reset Counter:resetScriptCounter:id
                    tmparr = special_tag.split(":");
                    cell_text = '<input type="button" value="' + tmparr[1] + '" onclick="if(confirm(' + tmparr[4] + '))' + tmparr[2] + '(' + dataarr[x].getAttribute(tmparr[3]) + ');">'; //
                    cell.innerHTML = cell_text;
                    cell.className = clsname + ' ' + cur_class;
                    cell.onclick = function () {
                        // do nothing, button click only
                    }
                } else if (special_tag.indexOf("render:") >= 0) {
                    tmparr = special_tag.split(":");
                    if (tmparr[1] == 'who') {
                        var cell_text = "-"
                        if (dataarr[x].getAttribute('type') == 'campaign') {
                            special_stack[special_idx] = 'get:campaign_name:' + dataarr[x].getAttribute('who');
                            cell_text = '<div id="' + area + '_special_data_load_' + special_idx + '">[loading...]</div>';
                            special_idx++;
                        } else if (dataarr[x].getAttribute('type') == 'user') {
                            cell_text = dataarr[x].getAttribute('who');
                        }
                        cell.innerHTML = cell_text;
                        cell.className = clsname + ' hand' + cur_class;
                        cell.onclick = function () {
                            handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    } else if (tmparr[1] == 'recording_url') {
                        var cell_text = "-"
                        cell_text = dataarr[x].getAttribute(tmparr[2]);
                        var recording_url = dataarr[x].getAttribute('recording_url');
                        cell.innerHTML = cell_text;
                        cell.className = clsname + ' underline hand' + cur_class;
                        cell.onclick = function () {
                            //alert("poop");
                            window.open(recording_url);
                            //handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                        /**
                         * Make an input box, and populate with the rendered time
                         */
                    } else if (tmparr[1] == 'editable_hours_from_min') {
                        //	alert(dataarr[x].getAttribute(tmparr[2])
                        var s = (Math.round(parseInt(dataarr[x].getAttribute(tmparr[2])) / 60 * 100) / 100).toString();
                        if (s.indexOf('.') == -1) s += '.';
                        while (s.length < s.indexOf('.') + 3) s += '0';
                        //cell_text = '<input type="text" size="5" name="'+tmparr[2]+'_'+x+'" id="'+tmparr[2]+'_'+x+'" value="'+s+'" > hrs.'; //= '<input type="hidden" name="activity_id_'+x+'" id="activity_id_'+x+'" value="'+dataarr[x].getAttribute('id')+'">'+
                        var minutes_tmp = parseInt(dataarr[x].getAttribute(tmparr[2]));
                        var sel_hour = Math.floor(minutes_tmp / 60);
                        var sel_min = minutes_tmp % 60;
                        // INSERT DROPDOWNS HERE INSTEAD OF A TEXT FIELD....
                        cell_text = '<input type="hidden" name="activity_id_' + x + '" id="activity_id_' + x + '" value="' + dataarr[x].getAttribute('id') + '">' +
                            makeNumberDD('paid_hour_' + x, sel_hour, 0, 24, 1, false, '', false) + "h&nbsp;" +
                            makeNumberDD('paid_min_' + x, sel_min, 0, 59, 1, true, '', false) + 'm<br />' +
                            "(<span id=\"paid_ghetto_time_" + x + "\">" + s + "</span>)";
//									makeTimebar("stime_",1, curDate,false);
//
// 						output += "<br />";
//
//						curDate.setDate(curDate.getDate()+1);
//
//						output += makeTimebar("etime_",1, curDate,false);

                        cell.setAttribute("id", "paid_hours_cell_" + x);
                        cell.setAttribute("nowrap", "true");
                        cell.innerHTML = cell_text;
                        cell.className = clsname + ' ' + cur_class;
                        cell.onclick = function () {
                            //handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                            return false;
                        }
                    } else if (tmparr[1] == 'hours_from_sec') {
                        var total_seconds = 0;
                        if (tmparr.length > 3) {
                            for (var i = 2; i < tmparr.length; i++) {
                                total_seconds += parseInt(dataarr[x].getAttribute(tmparr[i]));
                            }
                        } else {
                            total_seconds += parseInt(dataarr[x].getAttribute(tmparr[2]));
                        }
                        var s = (Math.round(total_seconds / 3600 * 100) / 100).toString();
                        if (s.indexOf('.') == -1) s += '.';
                        while (s.length < s.indexOf('.') + 3) s += '0';
//						// SPLIT ON TEH DOT
//						var tmparr = s.split('.');
//
//						// CONVERT THE 10 BASE MATH TO TIME SHIT
//						var timeshit = parseInt(parseFloat("0."+tmparr[1]) * 60)
//
//						timeshit = (timeshit < 10)?"0"+timeshit:timeshit;
//
//						cell_text =  tmparr[0]+":"+timeshit+" hrs."+
//									'<input type="hidden" name="activity_hours_'+x+'" id="activity_hours_'+x+'" value="'+s+'">';
//

                        cell_text = s + " hrs." +
                            '<input type="hidden" name="activity_hours_' + x + '" id="activity_hours_' + x + '" value="' + s + '">' +
                            '<input type="hidden" name="new_activity_hours_' + x + '" id="new_activity_hours_' + x + '" value="' + s + '">';
                        cell.innerHTML = cell_text;
                        cell.className = clsname + ' hand' + cur_class;
                        cell.onclick = function () {
                            handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    } else if (tmparr[1] == 'breakdown_hours_from_sec') {
                        var outarr = new Array();
                        var tmparr2 = null;
                        var s = null;
                        var outstr = "";
                        for (var i = 2, j = 0; i < tmparr.length; i++) {
                            tmparr2 = tmparr[i].split(",");
                            s = (Math.round(parseInt(dataarr[x].getAttribute(tmparr2[1])) / 3600 * 100) / 100).toString();
                            if (s.indexOf('.') == -1) s += '.';
                            while (s.length < s.indexOf('.') + 3) s += '0';
                            // ARRAY OF [LABEL,VALUE]
                            outarr[j] = [tmparr2[0], s];
                            outstr += tmparr2[0] + "=" + s + "hrs.&nbsp;";
                            if (j % 2 == 1) outstr += "<br />";
                            j++;
                        }
                        cell_text = outstr;
                        cell.innerHTML = cell_text;
                        cell.className = clsname + ' hand' + cur_class;
                        cell.onclick = function () {
                            handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    } else if (tmparr[1] == 'hours_from_min') {
                        var s = (Math.round(parseInt(dataarr[x].getAttribute(tmparr[2])) / 60 * 100) / 100).toString();
                        if (s.indexOf('.') == -1) s += '.';
                        while (s.length < s.indexOf('.') + 3) s += '0';
                        cell_text = s + " hrs." +
                            '<input type="hidden" name="activity_hours_' + x + '" id="activity_hours_' + x + '" value="' + s + '">';
                        cell.innerHTML = cell_text;
                        cell.className = clsname + ' hand' + cur_class;
                        cell.onclick = function () {
                            handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    } else if (tmparr[1] == 'vici_lead') {
                        var cell_text = "-"
                        cell_text = dataarr[x].getAttribute(tmparr[2]);
                        var vici_ip = dataarr[x].getAttribute('vici_ip');
                        cell.innerHTML = cell_text;
                        cell.className = clsname + ' underline hand' + cur_class;
                        cell.onclick = function () {
                            //alert("poop");
                            vici_ip = (!vici_ip || vici_ip == 'null') ? "10.100.0.90" : vici_ip;
                            window.open('http://' + vici_ip + '/vicidial/admin_modify_lead.php?lead_id=' + this.innerHTML + '&archive_search=No');
                            //handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    } else if (tmparr[1] == 'number') {
                        var tmpnum = parseInt(dataarr[x].getAttribute(tmparr[2]));
                        cell.innerHTML = numberWithCommas(tmpnum);
                        cell.className = clsname + ' hand' + cur_class;
                        cell.onclick = function () {
                            handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                        }
                    } else {
                    }
                    /**
                     * EDITABLE "notes" field, single line text field
                     * [textfield:DB FIELD TO POPULATE:text field name:size]
                     */
                } else if (special_tag.indexOf("textfield:") == 0) {
                    tmparr = special_tag.split(":");
                    var cell_text = '<input type="text" ' + ((special_tag.length >= 4) ? ' size="' + tmparr[3] + '" ' : '') +
                        ' name="' + tmparr[2] + '_' + x + '" id="' + tmparr[2] + '_' + x + '" value="' + dataarr[x].getAttribute(tmparr[1]) + '" >';
                    cell.innerHTML = cell_text;
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        //handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                } else if (special_tag.indexOf("priv") == 0) {
                    ///alert(dataarr[x].getAttribute('priv'));
                    switch (parseInt(dataarr[x].getAttribute('priv'))) {
                        default:
                        case 1:
                            priv_name = "Trainee";
                            break;
                        case 6:
                        case 5:
                            priv_name = "Administrator";
                            break;
                        case 4:
                            priv_name = "Manager";
                            break;
                        case 3:
                        case 2:
                            priv_name = "Caller";
                            break;
                    }
                    cell.innerHTML = priv_name;
                    cell.className = clsname + ' hand' + cur_class;
                    cell.onclick = function () {
                        handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                    }
                    // DELETE IS DEFAULT OPTION
                } else if (special_tag.indexOf("copy") == 0) {
                    if(area === 'form_builder') {
                        cell.innerHTML = '<a href="#" onclick="handleForm_builderCopyClick(' + dataarr[x].getAttribute('campaign_id') + ');return false;">' +
                            '<img title="Copy form to another campaign" id="' + copy_area + '-formcopy-img-' + dataarr[x].getAttribute('id') + '" src="images/data_copy_icon.png" width="24" height="24" onmouseover="this.src=\'images/data_copy_icon.png\'" onmouseout="this.src=\'images/data_copy_icon.png\'" border="0" />' +
                            '</a>';
                        cell.className = '' + cur_class;
                    } else {
                        // allow the ability to re-use the copy function for other areas
                    }
                }

                else {
                    cell.innerHTML = '<a href="#" onclick="deleteItem(' + delete_message_varname + ',\'' + delete_area + '\',' + dataarr[x].getAttribute('id') + ', \'' + callback_func_name + '\' );return false;">' +
                        '<img id="' + delete_area + '-delete-img-' + dataarr[x].getAttribute('id') + '" src="images/delete.png" width="24" height="24" onmouseover="this.src=\'images/delete.png\'" onmouseout="this.src=\'images/delete.png\'" border="0" />' +
                        '</a>';
                    cell.className = '' + cur_class;
                }
            } else {
                cur_data = dataarr[x].getAttribute(cur_name);


               // cur_data = priorityProcessing(cur_data);

                cell.innerHTML = (cur_data) ? cur_data : '&nbsp;';
                cell.className = clsname + ' hand' + cur_class;
                cell.onclick = function () {
                    handleListClick(tagname, this.parentNode.getAttribute('record_id'));
                }
            }
        } // END FIELD LIST
    } // END OF XML TAGS
    $('#total_count_div').html(totalcount + " Found");
    // SECONDARY AJAX - POST PROCESSING - MAKE A SECOND AJAX CALL TO RETRIEVE AND RENDER INFO
    if (special_idx > 0) {
        //console.dir(special_stack);
        secondaryAjaxPOST(area, special_stack);
    }
    applyUniformity();
    return totalcount;
} // END parseXMLData()


function priorityProcessing(inputstr){
	inputstr = inputstr.trim();

	var priority = 0;

	if(inputstr.startsWith("!")){
		if(inputstr.startsWith("!!!")){
			priority = 3;
		}else if(inputstr.startsWith("!!")){
			priority = 2;
		}else{
			priority = 1;
		}

		inputstr = inputstr.replace( /^\!+/, '').trim();

		switch(priority){
		default:
		case 1:
			//inputstr = '<span style="background-color:#ffcc00">!</span>'+inputstr;

			inputstr = '<span title="Low Priority"><img src="images/priority_low.png" height="15" border="0" />'+inputstr+'</span>';
			break;
		case 2:
			inputstr = '<span title="Medium Priority"><img src="images/priority_medium.png" height="15" border="0">'+inputstr+'</span>';
			//inputstr = '<span style="background-color:#ff9933">!</span>'+inputstr;
			break;
		case 3:
			inputstr = '<span title="High Priority"><img src="images/priority_high.png" height="15" border="0">'+inputstr+'</span>';
			//inputstr = '<span style="background-color:#ff3300">!</span>'+inputstr;
			break;
		}
	}

	return inputstr;
}


/**
 * Wrapper for list click functions
 * @param area_name The XML tag name basically, first letter uppercase (see the top of parseXMLData())
 * @param id The ID of the record to load
 * @return nothing
 */
function handleListClick(area_name, id) {
    var cmd;
    cmd = 'handle' + area_name + 'ListClick(' + id + ');';
    eval(cmd);
}

/**
 * Makes an AJAX post, to request the stack worth of data
 * Call back function handles injecting the data
 * @param special_stack
 * @return
 */
function secondaryAjaxPOST(area, special_stack) {
    var loadurl = 'api/api.php' +
        "?get=secondary_ajax&" +
        "area=" + area + "&" +
        "mode=xml";
    var postdata = '';
    for (var x = 0; x < special_stack.length; x++) {
        postdata += 'special_stack[' + x + ']=' + encodeURI(special_stack[x]) + "&";
    }
    $.ajax({
        url: loadurl,
        type: "POST",
        data: postdata,
        success: function (data) {
            //alert(data);return;
            var xmldoc = getXMLDoc(data);////(new DOMParser()).parseFromString(data, "text/xml");
            eval('handleSecondaryAjax(\'' + area + '\',xmldoc)');
        }
    });
}

function handleSecondaryAjax(area, xmldoc) {
    var tag = xmldoc.documentElement;
    var obj = null;
    for (var x = 0; (obj = getEl(area + '_special_data_load_' + x)) != null; x++) {
        getEl(area + '_special_data_load_' + x).innerHTML = tag.getAttribute('data_' + x);
    }
}

function closeDiv(obj_name) {
    var obj = getEl(obj_name);
    obj.innerHTML = '&nbsp;';
    $('#' + obj_name).hide();
}

function loadInDiv(url, obj_name) {
    $('#' + obj_name).hide();
    $('#' + obj_name).load(url);
    $('#' + obj_name).show();
}

function loadInPanel(url, uiobj) {
    //alert("UI:"+uiobj+" Panel: "+uiobj.panel+" "+url);
    $(uiobj.panel).load(url,
        function () {
            try {
                if (uiobj == admin_uiobj) {
                    onLoadAdminTabStuff("click", uiobj);
                } else {
                    onLoadAccountTabStuff("click", uiobj);
                }
            } catch (e) {
                onLoadAccountTabStuff("click", uiobj);
            }
            //onLoadTabStuff("click", uiobj);
        }
    );
}

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function setOrder(prepend, field, direction) {
    eval(prepend + 'orderby="' + field + '";');
    eval(prepend + 'orderdir="' + direction + '";');
}
