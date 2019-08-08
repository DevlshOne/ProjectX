// console.log('Form Builder script loaded');
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
        f.fldPosX = ui.position.left;
        f.fldPosY = ui.position.top;
        // f.saveToDB();
    }
};
function frmField(index, o) {
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
}
frmField.prototype = {
    constructor: frmField,
    saveToDB: function() {
        $.getJSON('api/api.php?get=form_builder&mode=json&action=saveField&field=' + JSON.stringify(this), function(response) {
        });
        // this.populate();
        // changeScreen(this.campID, this.screenNum);
    },
    create: function() {
        let newLI = '<div style="width: auto;" id="fieldWrapper_' + this.idx + '">\n' +
            // '<div class="fldHeader">\n' +
            // '<div class="fldActions">\n' +
            // '<div class="fldTitle">[' + this.screenNum + ':' + this.idx + '] - ' + this.txtLabel + '</div>\n' +
            // '<input type="button" value="Remove" onclick="removeField(' + this.idx + '); return false;" class="fldActionButton" />\n' +
            // '<input type="button" value="Edit" onclick="editField(' + this.idx + '); return false;" class="fldActionButton" />\n' +
            // '<input type="button" value="Save" onclick="saveField(' + this.idx + '); return false;" class="fldActionButton" />\n' +
            // '</div>\n' +
            // '</div>\n' +
            // '<div class="fldTitle">' + this.txtLabel + '</div>\n' +
            // '<div class="field"></div>\n' +
            '</div>\n';
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
            '<td><label class="fafLabel" for="field_step">Field Step : </label><input id="field_step' + this.idx + '" name="field_step" type="number" value = "' + this.callStep + '"/></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="field_type">Type : </label><select id="field_type' + this.idx + '" onchange="changeFieldType(' + this.idx + ', $(this).val());return false;" name="field_type"><option value="0">Text</option><option value="1">Dropdown</option><option value="2">Textarea</option></select></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="name">Label : </label><input class="pct75" id="name' + this.idx + '"  name="name" type="text" value="' + this.txtLabel + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="label_width">Label Width : </label><input class="pct75" id="label_width' + this.idx + '"  name="label_width" type="number" min="0" max="500" value="' + this.lblWidth + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="label_height">Label Height : </label><input class="pct75" id="label_height' + this.idx + '"  name="label_height" type="number" min="0" max="500" value="' + this.lblHeight + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="label_x">Label X : </label><input class="pct75" id="label_x' + this.idx + '"  name="label_x" type="number" min="0" max="1024" value="' + this.lblPosX + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="label_y">Label Y : </label><input class="pct75" id="label_y' + this.idx + '"  name="label_y" type="text" value="' + this.lblPosY + '" /></td>' +
            '</tr>' +
            // '<tr>' +
            // '<td><label class="fafLabel" for="db_field">Field Name : </label><input class="pct75" id="name' + this.idx + '" name="name" type="text" value="' + this.name + '" /></td>' +
            // '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="field_width">Field Width : </label><input class="pct75" id="field_width' + this.idx + '" name="field_width" type="number" min="0" max="500" value="' + this.fldWidth + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="field_height">Field Height : </label><input class="pct75" id="field_height' + this.idx + '" name="field_height" type="number" min="0" max="500" value="' + this.fldHeight + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="field_x">Field X : </label><input class="pct75" id="field_x' + this.idx + '" name="field_x" type="number" min="0" max="1024" value="' + this.fldPosX + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="field_y">Field Y : </label><input class="pct75" id="field_y' + this.idx + '" name="field_y" type="number" min="0" max="1024" value="' + this.fldPosY + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="is_required">Required : </label><input id="is_required' + this.idx + '" name="is_required" type="checkbox" value="' + this.isRequired + '" /></td>' +
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
            '<td><label class="fafLabel" for="db_field">DB Field : </label><input class="pct75" id="db_field' + this.idx + '" name="tool_tip" type="text" value="' + this.dbField + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td><label class="fafLabel" for="variables">Variables : </label><input class="pct75" id="variables' + this.idx + '" name="variables" type="text" value="' + this.fldVariables + '" /></td>' +
            '</tr>' +
            '</table>' +
            '<script>$(function(){$("#field_type' + this.idx + '").val(' + this.fldType + ');$("#field_step' + this.idx + '").val(' + this.callStep + ');});</script>' +
            '</form>';
        $(fldRendering).empty().append(fieldAsForm);
        $('#field_type' + this.idx).change();
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
                fldObj.attr('required', this.isRequired);
                lblObj.attr('value', this.txtLabel);
                lblObj.text(this.txtLabel);
                lblObj.attr('title', this.toolTip);
                fldObj.attr('placeholder', this.placeHolder);
                fldObj.addClass(this.cssName);
                fldObj.attr('name', this.fldName);
                lblObj.attr('for', this.fldName);
                fldObj.attr('id', this.fldName);
                fldObj.attr('value', this.fldValue);
                fldObj.attr('maxlength', this.fldMaxLength);
                if(this.lblPosX < 0 && this.lblPosY < 0) {
                    lblObj.css('display','none');
                    this.isHidden = 1;
                }
                fldObj.css('display')
                // console.log('Placing label wrapper ' + this.txtLabel + ' at ' + this.lblPosY + 'px from top, ' + this.lblPosX + 'px from left.');
                // console.log('Placing field wrapper ' + this.txtLabel + ' at ' + this.fldPosY + 'px from top, ' + this.fldPosX + 'px from left.');
                // somewhere in here, there has to be some figuring done on where to place these - even with 0,0s and funky offset labels
                // how many rows do we create? how many columns? what fields span more than one column? how do we know when to move to the next row?
                // how far should we allow the offset of a label? is this really the smart way to perform this task?
                // do we even render the ones that are "hidden"?
                // lots of questions
                $(fldPreview).append(lblObj, fldObj);
                lblObj.css({
                    width: this.lblWidth,
                    height: this.lblHeight,
                    top: this.lblPosY + 'px',
                    left: this.lblPosX + 'px',
                    position: 'absolute'
                });
                fldObj.css({
                    width: this.fldWidth,
                    height: this.fldHeight,
                    top: this.fldPosY + 'px',
                    left: this.fldPosX + 'px',
                    position: 'absolute'
                });
                // debugger;
                break;
            case '1' :
                // This is a dropdown field, so let's create it and then populate it
                fldFormat = '<select></select>';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.addClass('fieldPreview');
                lblObj.addClass('labelPreview');
                fldObj.attr('required', this.isRequired);
                lblObj.attr('value', this.txtLabel);
                lblObj.text(this.txtLabel);
                lblObj.attr('title', this.toolTip);
                fldObj.attr('placeholder', this.placeHolder);
                fldObj.addClass(this.cssName);
                fldObj.attr('name', this.fldName);
                lblObj.attr('for', this.fldName);
                fldObj.attr('id', this.fldName);
                fldObj.attr('value', this.fldValue);
                fldObj.attr('maxlength', this.fldMaxLength);
                if(this.lblPosX < 0 && this.lblPosY < 0) {
                    lblObj.css('display','none');
                    this.isHidden = 1;
                }
                let arrOptions = this.fldOptions.split(';');
                jQuery.each(arrOptions, function(i, v) {
                    fldObj.append('<option>' + v + '</option>');
                });
                // console.log('Placing label wrapper ' + this.txtLabel + ' at ' + this.lblPosY + 'px from top, ' + this.lblPosX + 'px from left.');
                // console.log('Placing field wrapper ' + this.txtLabel + ' at ' + this.fldPosY + 'px from top, ' + this.fldPosX + 'px from left.');
                // somewhere in here, there has to be some figuring done on where to place these - even with 0,0s and funky offset labels
                $(fldPreview).append(lblObj, fldObj);
                lblObj.css({
                    width: this.lblWidth,
                    height: this.lblHeight,
                    top: this.lblPosY + 'px',
                    left: this.lblPosX + 'px',
                    position: 'absolute'
                });
                fldObj.css({
                    width: this.fldWidth,
                    height: this.fldHeight,
                    top: this.fldPosY + 'px',
                    left: this.fldPosX + 'px',
                    position: 'absolute'
                });
                // debugger;
                break;
        }
    },
    populate: function() {
        // let fldRendering = $('#dropZone').children('div.fldHolder').eq(this.idx);
        let fldRendering = $('#fieldWrapper_' + this.idx);
        let fldFormat = '';
        let lblFormat = '';
        let fldObj = {};
        let lblObj = {};
        switch(this.fldType) {
            case '0' :
                // This is a text field, so let's create it and then populate it
                // fldFormat = '<input type="text" />';
                // lblFormat = '<label></label>';
                fldFormat = '<div></div>';
                lblFormat = '<div></div>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.attr('tabindex', this.idx);
                fldObj.attr('required', this.isRequired);
                lblObj.attr('value', this.txtLabel);
                lblObj.text('LBL:' + this.txtLabel);
                fldObj.text('INP:' + this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                // lblObj.css('margin-right', '10px');
                lblObj.attr('title', this.toolTip);
                fldObj.attr('placeholder', this.placeHolder);
                fldObj.addClass(this.cssName);
                fldObj.attr('name', this.fldName);
                // lblObj.attr('for', this.fldName);
                fldObj.attr('id', this.fldName);
                fldObj.attr('value', this.fldValue);
                fldObj.attr('maxlength', this.fldMaxLength);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                $(fldRendering).empty().append(lblObj, fldObj);
                lblObj.wrap('<div class="dragMe" data-fieldID="' + this.idx + '" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe" id="inp' + this.idx + '"></div>');
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
                break;
            case '1' :
                // This is a dropdown field, so let's create it and then populate it
                // fldFormat = '<select></select>';
                // lblFormat = '<label></label>';
                fldFormat = '<div></div>';
                lblFormat = '<div></div>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.attr('tabindex', this.idx);
                fldObj.attr('required', this.isRequired);
                lblObj.attr('value', this.txtLabel);
                lblObj.text('LBL:' + this.txtLabel);
                fldObj.text('SEL:' + this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                // lblObj.css('margin-right', '10px');
                lblObj.attr('title', this.toolTip);
                fldObj.attr('placeholder', this.placeHolder);
                fldObj.addClass(this.cssName);
                fldObj.attr('name', this.fldName);
                // lblObj.attr('for', this.fldName);
                fldObj.attr('id', this.fldName);
                fldObj.attr('value', this.fldValue);
                fldObj.attr('maxlength', this.fldMaxLength);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                if(this.isHidden) {
                    // lblObj.css('display', 'none');
                }
                // let arrOptions = this.fldOptions.split(';');
                // jQuery.each(arrOptions, function(i, v) {
                //     fldObj.append('<option>' + v + '</option>');
                // });
                $(fldRendering).empty().append(lblObj, fldObj);
                lblObj.wrap('<div class="dragMe" data-fieldID="' + this.idx + '" id="lbl' + this.idx + '"></div>');
                fldObj.wrap('<div title="Double-Click to Edit" data-fieldID="' + this.idx + '" ondblclick="editField(' + this.idx + '); return false;" class="dragMe" id="sel' + this.idx + '"></div>');
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
                break;
        }
        $('div.dragMe').each(function(i) {
            $(this).draggable(fieldWrapperDragOptions);
        });
    }
};