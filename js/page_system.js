function hidePageSystem(area_name, account_id) {
    var prevobj, pageobj, nextobj;
    if (typeof account_id != 'undefined') {
        prevobj = getEl(area_name + '_' + account_id + '_prev_td');
        pageobj = getEl(area_name + '_' + account_id + '_page_td');
        nextobj = getEl(area_name + '_' + account_id + '_next_td');
    } else {
        prevobj = getEl(area_name + '_prev_td');
        pageobj = getEl(area_name + '_page_td');
        nextobj = getEl(area_name + '_next_td');
    }
    prevobj.innerHTML = '';
    pageobj.innerHTML = '';
    nextobj.innerHTML = '';
}

/**
 * AJAX/Javascript page system
 * @param area_name
 * @param index_name
 * @param totalcount
 * @param index
 * @param pagesize
 * @param callback_func_name
 * @return
 */


function makePageSystem(area_name, index_name, totalcount, index, pagesize, callback_func_name) {

    var pages = Math.ceil(totalcount / pagesize);
    var curpage = index;

    //alert("pages: "+pages+" curpage:"+curpage);

    var prevobj = getEl(area_name + '_prev_td');
    var pageobj = getEl(area_name + '_page_td');
    var nextobj = getEl(area_name + '_next_td');

    var html = ''; // TEMP/BUFFER VAR

    /****/

    // POPULATE 'PREV' CELL
    if (index > 0) {

        html = '<a href="#" onclick="' + index_name + '--;' + index_name + ' = (' + index_name + ' < 0)?0:' + index_name + ';eval(\'' + callback_func_name + '\');return false;">' +
            '<span class="page_system_prev_inner"><img src="images/arrow_previous.png" width="22" height="22" border="0"></span>' +
            '</a>';

        prevobj.innerHTML = html;

        // OR NOT...
    } else {

        prevobj.innerHTML = '<div style="width:20px"><img src="images/spacer.gif" width="22" height="22" border="0"></div>';

    }


    /****/

    // POPULATE 'NEXT' CELL
    if (curpage < (pages - 1)) {

        html = '<a href="#" onclick="' + index_name + '++;' + index_name + ' = (' + index_name + ' >= ' + (pages - 1) + ')?' + (pages - 1) + ':' + index_name + '; eval(\'' + callback_func_name + '\');return false;">' +
            '<span class="page_system_next_inner"><img src="images/arrow_next.png" width="22" height="22" border="0"></span>' +
            '</a>';

        nextobj.innerHTML = html;

    } else {

        nextobj.innerHTML = '<div style="width:22px"><img src="images/spacer.gif" width="22" height="22" border="0"></div>';
    }


    /****/

    // GENERATE/POPULATE PAGE DROPDOWN
    html = '<select id="' + index_name + '_dropdown" name="' + index_name + '_dropdown" onchange="' + index_name + '=this.value;eval(\'' + callback_func_name + '\');">';

    // LESS THAN 1000 = SIMPLE PAGE DROPDOWN
    if (pages < 1000) {

        for (var x = 0; x < pages; x++) {

            html += '<option value="' + x + '"';
            if (curpage == x)
                html += ' SELECTED ';

            html += '>Page (' + (x + 1) + ')</option>';
        }


        // CUSTOM PAGE DROPDOWN
    } else {


        // SOMETHING COOLs

        var range = 20;

        var x = 1;
        var stepping = 1;
        var start = (index > 10) ? (index - 10) : index;
        start = (start < 1) ? 1 : start;

        // PRE PAGE - RANGES
        if (start > 1) {

            if (start < 20) {

                stepping = 1;

            } else {

                // 10 steps
                stepping = Math.round(start / 20);
                stepping = (stepping < 1) ? 1 : stepping;

            }


            //alert("start stepping: "+stepping);

            for (x = 1; x < start; x += stepping) {

                // SAFETY NET
                if (x > pages) break;

                html += '<option value="' + x + '"';

                if (index == (x)) {

                    html += ' SELECTED';

                }

                html += '>Page (' + (x) + ')';

            }
        }


        // DO THE MAIN "center block" OF NUMBERS
        for (x = start; x < (start + range); x++) {

            // SHOULDNT HIT THIS, BUT ITS A SAFETY NET
            if (x > pages) break;

            html += '<option value="' + x + '"';

            if (index == (x)) {

                html += ' SELECTED';

            }

            html += '>Page (' + (x) + ')';
        }


        // POST PAGE - RANGES
        if (x < pages) { // MORE PAGES LEFT TO PRINT

            stepping = Math.round((pages - x) / 20);
            stepping = (stepping < 1) ? 1 : stepping;

            //alert("end stepping: "+stepping);

            for (; x < pages; x += stepping) {

                if (x > pages) break;
                html += '<option value="' + x + '"';

                if (index == (x)) {

                    html += ' SELECTED';

                }

                html += '>Page (' + (x) + ')';
            }


        }


    } // END CUSTOM PAGE SYSTEM

    html += '</select>';

    pageobj.innerHTML = html;

}


function resetPageSystem(index_name) {
    eval(index_name + '=0;');
    try {
        getEl(index_name + '_dropdown').selectedIndex = 0;
    } catch (e) {
    }
}