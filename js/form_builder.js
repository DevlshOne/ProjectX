function isEmpty(obj) {
    for(let key in obj) {
        if(obj.hasOwnProperty(key)) return false;
    }
    return true;
}


let _formBuilder = {
    doInit: function() {

    },
    renderTitle: function() {

    },
    renderTopNav: function() {

    },
    fbForm: function() {

    },
};
const fieldWrapperDragOptions = {
    refreshPositions: true,
    containment: '#dropZone',
    cursor: 'move',
    revert: 'invalid',
    // helper: 'clone',
    stop: function (e, ui) {
        let fieldID = ui.helper.attr('data-fieldID');
        // console.log('dbID #' + fieldID + ' dropped at X:' + ui.position.left + ', Y:' + ui.position.top);
        let f = formFields[fieldID];
        if($(ui.helper[0]).hasClass('dragL')) {
            f.lblPosX = ui.position.left;
            f.lblPosY = ui.position.top;
        } else {
            f.fldPosX = ui.position.left;
            f.fldPosY = ui.position.top;
        }
    }
};
function frmField(index, o) {
    if(isEmpty(o)) {
        o = {};
    }
    this.isRequired = o.is_required;
    this.txtLabel = o.name;
    this.lblWidth = o.label_width;
    this.lblHeight = o.label_height;
    this.toolTip = o.tool_tip;
    this.placeHolder = o.place_holder;
    this.cssName = o.css_class;
    this.fldName = o.db_field;
    this.fldValue = o.value;
    this.fldType = o.field_type;
    this.fldMaxLength = o.max_length;
    this.fldWidth = o.field_width;
    this.fldHeight = o.field_height;
    this.fldSpecial = o.special_mode;
    this.fldOptions = o.options;
    this.dbTable = o.db_table;
    this.dbField = o.db_field;
    this.fldVariables = o.variables;
    this.callStep = o.field_step;
    this.lblPosX = o.label_x;
    this.lblPosY = o.label_y;
    this.fldPosX = o.field_x;
    this.fldPosY = o.field_y;
    this.isHidden = o.is_hidden;
    this.isLocked = o.is_locked;
    this.idx = index;
    this.screenNum = o.screen_num;
    this.campID = o.campaign_id;
    this.dbID = o.id;
    for(let objProperty in this) {
        if(this[objProperty] === undefined) {
            this[objProperty] = '';
        }
    }
}
frmField.prototype = {
    constructor: frmField,
    saveToDB: function() {
        $.post('api/api.php?get=form_builder&mode=json&action=saveField&field=' + JSON.stringify(this), function() {
        })
            .done(function() {
                return 1;
            })
            .fail(function() {
                return 0;
            });
    },
    create: function() {
        let newLI = '<div style="width: auto;" id="fieldWrapper_' + this.idx + '"></div>';
        $('#dropZone').append(newLI);
    },
    markDeleted: function() {

    },
    edit: function() {
        // let fldRendering = $('ul#dropZone li').eq(this.idx).children('div.field');
        let fldRendering = $('#editBox');
        let fieldAsForm = '<form id="fieldAsForm' + this.idx + '">' +
            '<table class="pct100 tightTable">' +
            '<tr>' +
            '<td><label class="fafLabel" for="field_step">Screen Number : </label><input id="screen_num' + this.idx + '" name="screen_num" type="number" value = "' + $('#screenTabs').tabs("option", "active") + '" readonly="readonly" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="field_type">Type : </label><select id="field_type' + this.idx + '" onchange="changeFieldType(' + this.idx + ', $(this).val());return false;" name="field_type"><option value="0">Text</option><option value="1">Dropdown</option><option value="2">Checkbox</option><option value="3">Image</option><option value="4">Label</option><option value="5">Button</option><option value="6">Textarea</option></select></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="name">Label : </label><input class="pct75" id="name' + this.idx + '"  name="name" type="text" value="' + this.txtLabel + '" /></td>' +
            '</tr>' +
            '<tr>' +
            	'<td class="pad_top">'+
            	
            		'<table border="0" width="100%">'+
            		'<tr><td>'+
            	
		            	'<table border="0"><tr><td><label class="fafLabel" for="label_width">Label Size : </label></td><td>'+
		            	 '<input id="label_width' + this.idx + '"  name="label_width" type="number" min="-100" max="2048"  value="' + this.lblWidth + '" /> x <input  id="label_height' + this.idx + '"  name="label_height" type="number" min="-100" max="2048" value="' + this.lblHeight + '" /> (in pixels)'+
		            	 '</td></tr></table>'+
		            	 
		            '</td><td>'+
		            
			            '<table border="0"><tr><td><label class="fafLabel" for="field_width">Field Size : </label></td><td>'+
	            		'<input  id="field_width' + this.idx + '" name="field_width" type="number" min="0" max="2048" value="' + this.fldWidth + '" /> x <input  id="field_height' + this.idx + '" name="field_height" type="number" min="0" max="2048" value="' + this.fldHeight + '" /> (in pixels)'+
	            		'</td></tr></table>'+
            			
		            '</td></tr>'+
	            	'<tr><td>'+	
	            	
		            	'<table border="0"><tr><td><label class="fafLabel" for="label_x">Label Position : </label></td><td>'+
		    			'(<input id="label_x' + this.idx + '"  name="label_x" min="-100" max="2048" type="number" value="' + this.lblPosX + '" />,<input  id="label_y' + this.idx + '" min="-100" max="2048"  name="label_y" type="number" value="' + this.lblPosY + '" />) (x,y coords)'+
		    			'</td></tr></table>'+
		    			
	            	'</td><td>'+
	            	
		            	'<table border="0"><tr><td><label class="fafLabel" for="field_x">Field Position : </label></td><td>'+
	            		'(<input id="field_x' + this.idx + '" name="field_x" type="number" min="-100" max="2048" value="' + this.fldPosX + '" />,<input  id="field_y' + this.idx + '" name="field_y" type="number" min="0" max="2048" value="' + this.fldPosY + '" />) (x,y coords)'+
	            		'</td></tr></table>'+
	            	
	            	'</td></tr></table>'+
		            	
	            '</td>' +
            '</tr>' +
            '<tr>' +
            	'<td class="pad_top"><label class="fafLabel" for="field_step">Field Step : </label><input id="field_step' + this.idx + '" name="field_step" type="number" value = "' + this.callStep + '"/></td>' +
            '</tr>' +
            '<tr>' +
            	'<td><label class="fafLabel" for="is_required">Required : </label><input id="is_required' + this.idx + '" name="is_required" type="checkbox" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="tool_tip">Tooltip : </label><input class="pct75" id="tool_tip' + this.idx + '" name="tool_tip" type="text" value="' + this.toolTip + '" />' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="place_holder">Placeholder : </label><input class="pct75" id="place_holder' + this.idx + '" name="place_holder" type="text" value="' + this.placeHolder + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="css_class">Class : </label><input class="pct75" id="css_class' + this.idx + '" name="css_class" type="text" value="' + this.cssName + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="value">Default Value : </label><input class="pct75" id="value' + this.idx + '" name="value" type="text" value="' + this.fldValue + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="max_length">Max Length : </label><input class="pct75" id="max_length' + this.idx + '" name="max_length" type="number" min="0" max="500" value="' + this.fldMaxLength + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="special_mode">Special : </label><input class="pct75" id="special_mode' + this.idx + '" name="special_mode" type="text" value="' + this.fldSpecial + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="options">Options : </label><input class="pct75" id="options' + this.idx + '" name="options" type="text" value="' + this.fldOptions + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="db_table">DB Table : </label><input class="pct75" id="db_table' + this.idx + '" name="db_table" type="text" value="' + this.dbTable + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="db_field">DB Field : </label><input class="pct75" id="db_field' + this.idx + '" name="db_field" type="text" value="' + this.dbField + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="variables">Variables : </label><input class="pct75" id="variables' + this.idx + '" name="variables" type="text" value="' + this.fldVariables + '" /></td>' +
            '</tr>' +
            '</table>' +
            '<input type="hidden" id="campaign_id' + this.idx + '" name="campaign_id" value="' + this.campID + '" />' +
            '</form>' +
            '<script>' +
            '  $("#fieldAsForm' + this.idx + '").ready(function() {' +
            '      $("#field_type' + this.idx + '").val(' + this.fldType + ');' +
            '      $("#field_step' + this.idx + '").val(' + this.callStep + ');' +
            '      $("#value' + this.idx + '").val("' + this.fldValue + '");' +
            '      $("#tool_tip' + this.idx + '").val(' + this.tooltip + ');' +
            '      $("#place_holder' + this.idx + '").val(' + this.placeHolder + ');' +
            '      $("#css_class' + this.idx + '").val("' + this.cssName + '");' +
            '      $("#special_mode' + this.idx + '").val("' + this.fldSpecial + '");' +
            '      $("#options' + this.idx + '").val("' + this.fldOptions + '");' +
            '      $("#db_table' + this.idx + '").val("' + this.dbTable + '");' +
            '      $("#db_field' + this.idx + '").val("' + this.dbField + '");' +
            '      $("#variables' + this.idx + '").val("' + this.fldVariables + '");' +
            '      $("#campaign_id' + this.idx + '").val(' + this.campID + ');' +
            '      $("#is_required' + this.idx + '").prop("checked", function() {' +
            '        return (this.isRequired == 0 ? "checked" : "");' +
            '      });' +
            '  });' +
            '</script>'
        $(fldRendering).empty().append(fieldAsForm);
    },
    reposition: function() {

    },
    preview: function() {
        let fldPreview = $('#previewBox');
        let fldFormat = '';
        let lblFormat = '';
        let fldObj = {};
        let lblObj = {};
        switch(this.fldType) {
            case '0' :
                // This is a text field, so let's create it and then populate it
                fldFormat = '<input type="text" />';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                lblObj.addClass('labelPreview');
                break;
            case '1' :
                // This is a dropdown field, so let's create it and then populate it
                fldFormat = '<select></select>';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                lblObj.addClass('labelPreview');
                let arrOptions = this.fldOptions.split(';');
                jQuery.each(arrOptions, function(i, v) {
                    fldObj.append('<option>' + v + '</option>');
                });
                break;
            case '2' :
                // This is a checkbox field, so let's create it and then populate it
                fldFormat = '<input type="checkbox" />';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                lblObj.addClass('labelPreview');
                break;
            case '3' :
                // This is an image field, so let's create it and then populate it
                // not sure which field holds the SRC, but it needs to be implemented
                fldFormat = '<img />';
                lblFormat = '<div style="display:none;"></div>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                lblObj.addClass('labelPreview');
                fldObj.attr('src', this.fldValue);
                break;
            case '4' :
                // This is a label field, so let's create it and then populate it
                // not sure which field holds the SRC, but it needs to be implemented
                fldFormat = '<label></label>';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                lblObj.addClass('labelPreview');
                break;
            case '5' :
                // This is a button field, so let's create it and then populate it
                fldFormat = '<input type="button" />';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                break;
            case '6' :
                // This is a label field, so let's create it and then populate it
                fldFormat = '<textarea></textarea>';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                lblObj.addClass('labelPreview');
                break;
        }
        fldObj.addClass(this.cssName);
        if(this.fldType !== 3) {
            fldObj.prop('required', function() {
                return this.isRequired == 1;
            });
            lblObj.attr('value', this.txtLabel);
            lblObj.text(this.txtLabel);
            fldObj.attr('value', this.fldValue);
            lblObj.attr('title', this.toolTip);
            fldObj.attr('placeholder', this.placeHolder);
            fldObj.attr('name', this.fldName);
            lblObj.attr('for', this.fldName);
            fldObj.attr('id', this.fldName);
            fldObj.attr('maxlength', this.fldMaxLength);
            $(fldPreview).append(lblObj, fldObj);
            if(this.lblPosX < 0 && this.lblPosY < 0) {
                lblObj.css('display','none');
                this.isHidden = 1;
            }
            lblObj.css({
                width: this.lblWidth,
                height: this.lblHeight,
                top: this.lblPosY + 'px',
                left: this.lblPosX + 'px',
                position: 'absolute'
            });
        } else {
            $(fldPreview).append(fldObj);
        }
        fldObj.css({
            width: this.fldWidth,
            height: this.fldHeight,
            top: this.fldPosY + 'px',
            left: this.fldPosX + 'px',
            position: 'absolute'
        });
    },
    populate: function() {
        // let fldRendering = $('#dropZone').children('div.fldHolder').eq(this.idx);
        let fldRendering = $('#fieldWrapper_' + this.idx);
        let fldFormat = '<div></div>';
        let lblFormat = '<div></div>';
        let fldObj = $(fldFormat);
        let lblObj = $(lblFormat);
        lblObj.css('padding', '7px');
        lblObj.css('font-variant', 'all-small-caps');
        fldObj.css('padding', '7px');
        fldObj.css('font-variant', 'all-small-caps');
        fldObj.css('font-weight', '800');
        switch(this.fldType) {
            case '0' :
                // This is a text field, so let's create it and then populate it
                // fldObj.attr('required', this.isRequired);
                // fldObj.attr('value', this.fldValue);
                // fldObj.attr('maxlength', this.fldMaxLength);
                lblObj.text(this.txtLabel);
                fldObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                fldObj.attr('name', this.fldName);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                $(fldRendering).empty().append(lblObj, fldObj);
                fldObj.addClass('textField');
                fldObj.css('background-size', this.fldWidth + 'px ' + this.fldHeight + 'px');
                lblObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragL" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edi" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragF" id="inp' + this.idx + '"></div>');
                break;
            case '1' :
                // This is a dropdown field, so let's create it and then populate it
                lblObj.text(this.txtLabel);
                fldObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                fldObj.attr('name', this.fldName);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                $(fldRendering).empty().append(lblObj, fldObj);
                fldObj.addClass('selectField');
                fldObj.css('background-size', this.fldWidth + 'px ' + this.fldHeight + 'px');
                lblObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragL" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragF" id="sel' + this.idx + '"></div>');
                break;
            case '2' :
                // This is a checkbox field, so let's create it and then populate it
                lblObj.text(this.txtLabel);
                fldObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                fldObj.attr('name', this.fldName);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                $(fldRendering).empty().append(lblObj, fldObj);
                fldObj.addClass('checkboxField');
                fldObj.css('background-size', this.fldWidth + 'px ' + this.fldHeight + 'px');
                lblObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragL" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragF" id="chk' + this.idx + '"></div>');
                break;
            case '3' :
                // This is an image field, so let's create it and then populate it
                lblObj.text(this.txtLabel);
                fldObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                fldObj.attr('name', this.fldName);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                fldObj.attr('src', this.fldValue);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                $(fldRendering).empty().append(lblObj, fldObj);
                fldObj.addClass('imageField');
                fldObj.css('background-size', this.fldWidth + 'px ' + this.fldHeight + 'px');
                lblObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragL" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragF" id="img' + this.idx + '"></div>');
                break;
            case '4' :
                // This is a label field, so let's create it and then populate it
                lblObj.text(this.txtLabel);
                fldObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                fldObj.attr('name', this.fldName);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                $(fldRendering).empty().append(lblObj, fldObj);
                fldObj.addClass('labelField');
                fldObj.css('background-size', this.fldWidth + 'px ' + this.fldHeight + 'px');
                lblObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragL" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragF" id="lblf' + this.idx + '"></div>');
                break;
            case '5' :
                // This is a button field, so let's create it and then populate it
                // NO LABEL with buttons
                fldObj.text(this.txtLabel);
                fldObj.attr('name', this.fldName);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                fldObj.val(this.fldValue);
                $(fldRendering).empty().append(fldObj);
                fldObj.addClass('buttonField');
                fldObj.css('background-size', this.fldWidth + 'px ' + this.fldHeight + 'px');
                // lblObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragL" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragF" id="btn' + this.idx + '"></div>');
                break;
            case '6' :
                // This is a textarea field, so let's create it and then populate it
                lblObj.text(this.txtLabel);
                fldObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                fldObj.attr('name', this.fldName);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                $(fldRendering).empty().append(lblObj, fldObj);
                fldObj.addClass('textareaField');
                fldObj.css('background-size', this.fldWidth + 'px ' + this.fldHeight + 'px');
                lblObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragL" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe dragF" id="txt' + this.idx + '"></div>');
                break;
        }
        fldObj.closest('.dragMe').css({
            top: this.fldPosY + 'px',
            left: this.fldPosX + 'px',
            position: 'absolute'
        });
        lblObj.closest('.dragMe').css({
            top: this.lblPosY + 'px',
            left: this.lblPosX + 'px',
            position: 'absolute'
        });
        $('div.dragMe').each(function(i) {
            $(this).draggable(fieldWrapperDragOptions);
        });
    }
};