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
    this.dbID = o.id;
}
frmField.prototype = {
    constructor: frmField,
    saveToDB: function() {

    },
    create: function() {
        let newLI = '<li class="ui-state-default fldHolder">\n' +
            '<div class="fldHeader">\n' +
            '<div class="fldTitle">[' + this.screenNum + ':' + this.idx + '] - ' + this.txtLabel + '</div>\n' +
            '<div class="fldActions">\n' +
            '<input type="button" value="Remove" onclick="removeField(' + this.idx + '); return false;" class="fldActionButton" />\n' +
            '<input type="button" value="Edit" onclick="editField(' + this.idx + '); return false;" class="fldActionButton" />\n' +
            '<input type="button" value="Preview" onclick="previewField(' + this.idx + '); return false;" class="fldActionButton" />\n' +
            '</div>\n' +
            '</div>\n' +
            '<div class="field"></div>\n' +
            '</li>\n';
        $('ul#dropZone').append(newLI);
    },
    edit: function() {
        let fldRendering = $('ul#dropZone li').eq(this.idx).children('div.field');
        let fieldAsForm = '<form id="fieldAsForm' + this.idx + '">' +
            '<table class="pct100 tightTable">' +
            '<tr>' +
            '<td><label class="fafLabel" for="is_required">Required : </label><input id="is_required' + this.idx + '" name="is_required" type="checkbox" value="' + this.isRequired + '" /></td>' +
            '<td><label class="fafLabel" for="field_step">Step : </label><select id="field_step' + this.idx + '" name="field_step"><option>-1</option><option>0</option></select></td>' +
            '<td><label class="fafLabel" for="field_type">Type : </label><select id="field_type' + this.idx + '" name="field_type"><option value="0">Text</option><option value="1">Dropdown</option><option value="2">Textarea</option></select></td>' +
            '<td>&nbsp;</td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="name">Label : </label><input class="pct75" id="name' + this.idx + '"  name="name" type="text" value="' + this.txtLabel + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="tool_tip">Tooltip : </label><input class="pct75" id="tool_tip' + this.idx + '" name="tool_tip" type="text" value="' + this.toolTip + '" />' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="place_holder">Placeholder : </label><input class="pct75" id="place_holder' + this.idx + '" name="place_holder" type="text" value="' + this.placeHolder + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="css_class">Class : </label><input class="pct75" id="css_class' + this.idx + '" name="css_class" type="text" value="' + this.cssName + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="db_field">Name : </label><input class="pct75" id="db_field' + this.idx + '" name="db_field" type="text" value="' + this.dbField + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="value">Value : </label><input class="pct75" id="value' + this.idx + '" name="value" type="text" value="' + this.fldValue + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="max_length">Max Length : </label><input class="pct75" id="max_length' + this.idx + '" name="max_length" type="text" value="' + this.fldMaxLength + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="field_width">Width : </label><input class="pct75" name="field_width" type="text" value="' + this.fldWidth + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="field_height">Height : </label><input class="pct75" name="field_height" type="text" value="' + this.fldHeight + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="special_mode">Special : </label><input class="pct75" name="special_mode" type="text" value="' + this.fldSpecial + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="options">Options : </label><input class="pct75" name="options" type="text" value="' + this.fldOptions + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="db_table">DB Table : </label><input class="pct75" name="db_table" type="text" value="' + this.dbTable + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="db_field">DB Field : </label><input class="pct75" name="tool_tip" type="text" value="' + this.dbField + '" /></td>' +
            '</tr>' +
            '<tr>' +
            '<td colspan="4"><label class="fafLabel" for="variables">Variables : </label><input class="pct75" name="variables" type="text" value="' + this.fldVariables + '" /></td>' +
            '</tr>' +

            '</table>' +

            '<script>$(function(){$("#field_type' + this.idx + '").val(' + this.fldType + ');$("#field_step' + this.idx + '").val(' + this.callStep + ');});</script>' +
            '</form>';
        $(fldRendering).empty().append(fieldAsForm);

    },
    reposition: function() {

    },
    populate: function() {
        let fldRendering = $('ul#dropZone li').eq(this.idx).children('div.field');
        // fldRendering.hide();
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
                fldObj.attr('tabindex', this.idx);
                fldObj.attr('required', this.isRequired);
                lblObj.attr('value', this.txtLabel);
                lblObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                lblObj.css('margin-right', '10px');
                lblObj.attr('title', this.toolTip);
                fldObj.attr('placeholder', this.placeHolder);
                fldObj.addClass(this.cssName);
                fldObj.attr('name', this.fldName);
                lblObj.attr('for', this.fldName);
                fldObj.attr('id', this.fldName);
                fldObj.attr('value', this.fldValue);
                fldObj.attr('maxlength', this.fldMaxLength);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                // fldObj.data('dbTable', this.dbTable);
                // fldObj.data('dbField', this.dbField);
                // fldObj.data('callStep', this.callStep);
                // lblObj.data('posX', this.lblPosX);
                // lblObj.data('posY', this.lblPosY);
                // fldObj.data('posX', this.fldPosX);
                // fldObj.data('posY', this.fldPosY);
                // fldObj.data('isHidden', this.isHidden);
                // fldObj.data('isLocked', this.isLocked);
                $(fldRendering).empty().append(lblObj, fldObj);
                break;
            case '1' :
                // This is a dropdown field, so let's create it and then populate it
                fldFormat = '<select></select>';
                lblFormat = '<label></label>';
                fldObj = $(fldFormat);
                lblObj = $(lblFormat);
                fldObj.attr('tabindex', this.idx);
                fldObj.attr('required', this.isRequired);
                lblObj.attr('value', this.txtLabel);
                lblObj.text(this.txtLabel);
                lblObj.css('width', this.lblWidth);
                lblObj.css('height', this.lblHeight);
                lblObj.css('margin-right', '10px');
                lblObj.attr('title', this.toolTip);
                fldObj.attr('placeholder', this.placeHolder);
                fldObj.addClass(this.cssName);
                fldObj.attr('name', this.fldName);
                lblObj.attr('for', this.fldName);
                fldObj.attr('id', this.fldName);
                fldObj.attr('value', this.fldValue);
                fldObj.attr('maxlength', this.fldMaxLength);
                fldObj.css('width', this.fldWidth);
                fldObj.css('height', this.fldHeight);
                let arrOptions = this.fldOptions.split(';');
                jQuery.each(arrOptions, function(i, v) {
                    fldObj.append('<option>' + v + '</option>');
                });
                // fldObj.data('dbTable', this.dbTable);
                // fldObj.data('dbField', this.dbField);
                // fldObj.data('callStep', this.callStep);
                // lblObj.data('posX', this.lblPosX);
                // lblObj.data('posY', this.lblPosY);
                // fldObj.data('posX', this.fldPosX);
                // fldObj.data('posY', this.fldPosY);
                // fldObj.data('isHidden', this.isHidden);
                // fldObj.data('isLocked', this.isLocked);
                $(fldRendering).empty().append(lblObj, fldObj);
                break;
        }
    }
}