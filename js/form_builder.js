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
        let fieldAsForm = '<form class="pct100">' +
            '<label for="is_required">Required : </label><input name="is_required" type="checkbox" value="' + this.isRequired + '" />' +
            '<label for="field_step">Step : </label><select id="field_step" name="field_step"><option>-1</option><option>0</option></select>' +
            '<label for="name">Label : </label><input name="name" type="text" value="' + this.txtLabel + '" />' +
            '<label for="tool_tip">Tooltip : </label><input name="tool_tip" type="text" value="' + this.toolTip + '" />' +
            '<label for="place_holder">Placeholder : </label><input name="place_holder" type="text" value="' + this.placeHolder + '" />' +
            '<label for="css_class">Class : </label><input name="css_class" type="text" value="' + this.cssName + '" />' +
            '<label for="db_field">Name : </label><input name="db_field" type="text" value="' + this.dbField + '" />' +
            '<label for="value">Value : </label><input name="value" type="text" value="' + this.fldValue + '" />' +
            '<label for="field_type">Type : </label><select id="field_type" name="field_type"><option value="0">Text</option><option value="1">Dropdown</option><option value="2">Textarea</option></select>' +
            '<label for="max_length">Max Length : </label><input name="max_length" type="text" value="' + this.fldMaxLength + '" />' +
            '<label for="field_width">Width : </label><input name="field_width" type="text" value="' + this.fldWidth + '" />' +
            '<label for="field_height">Height : </label><input name="field_height" type="text" value="' + this.fldHeight + '" />' +
            '<label for="special_mode">Special : </label><input name="special_mode" type="text" value="' + this.fldSpecial + '" />' +
            '<label for="options">Options : </label><input name="options" type="text" value="' + this.fldOptions + '" />' +
            '<label for="db_table">DB Table : </label><input name="db_table" type="text" value="' + this.dbTable + '" />' +
            '<label for="db_field">DB Field : </label><input name="tool_tip" type="text" value="' + this.dbField + '" />' +
            '<label for="variables">Variables : </label><input name="variables" type="text" value="' + this.fldVariables + '" />' +

            '<script>$(function(){$("#field_type").val(' + this.fldType + ');$("#field_step").val(' + this.callStep + ');});</script>' +
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