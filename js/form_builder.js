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
function frmField(index, isRequired, txtLabel, lblWidth, lblHeight, toolTip, placeHolder, cssName, varName, fldValue, fldType, fldMaxLength, fldWidth, fldHeight, fldSpecial, fldOptions, dbTable, dbField, fldVariables, callStep, fldPosX, fldPosY, lblPosX, lblPosY, isHidden, isLocked, screenNum) {
    this.isRequired = isRequired;
    this.txtLabel = txtLabel;
    this.lblWidth = lblWidth;
    this.lblHeight = lblHeight;
    this.toolTip = toolTip;
    this.placeHolder = placeHolder;
    this.cssName = cssName;
    this.fldName = varName;
    this.fldValue = fldValue;
    this.fldType = fldType;
    this.fldMaxLength = fldMaxLength;
    this.fldWidth = fldWidth;
    this.fldHeight = fldHeight;
    this.fldSpecial = fldSpecial;
    this.fldOptions = fldOptions;
    this.dbTable = dbTable;
    this.dbField = dbField;
    this.fldVariables = fldVariables;
    this.callStep = callStep;
    this.lblPosX = lblPosX;
    this.lblPosY = lblPosY;
    this.fldPosX = fldPosX;
    this.fldPosY = fldPosY;
    this.isHidden = isHidden;
    this.isLocked = isLocked;
    this.idx = index;
    this.screenNum = screenNum;
}
frmField.prototype = {
    constructor: frmField,
    saveToDB: function() {

    },
    create: function(i) {
        let newLI = '<li class="ui-state-default fldHolder">\n' +
            '<div class="fldHeader">\n' +
            '<div class="fldTitle">Screen - ' + this.screenNum + ' - Field ' + i + '</div>\n' +
            '<div class="fldActions">\n' +
            '<input type="button" value="Remove" onclick="removeField($(this).closest(\'li div.field\')); return false;" class="fldActionButton"/>\n' +
            '<input type="button" value="Edit" onclick="editField($(this).closest(\'li div.field\')); return false;" class="fldActionButton"/>\n' +
            '<input type="button" value="Preview" onclick="previewField($(this).closest(\'li div.field\')); return false;" class="fldActionButton" />\n' +
            '</div>\n' +
            '</div>\n' +
            '<div class="field"></div>\n' +
            '</li>\n';
        $('ul#dropZone').append(newLI);
    },
    populate: function(i) {
        let fldRendering = $('ul#dropZone li').eq(i).children('div.field');
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