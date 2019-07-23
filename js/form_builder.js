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
function frmField(index, isRequired, txtLabel, lblWidth, lblHeight, toolTip, placeHolder, cssName, varName, fldValue, fldType, fldMaxLength, fldWidth, fldHeight, fldSpecial, fldOptions, dbTable, dbField, fldVariables, callStep, fldPosX, fldPosY, lblPosX, lblPosY, isHidden, isLocked) {
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
    this.index = index;
}
frmField.prototype = {
    constructor: frmField,
    saveToDB: function() {

    },
    populate: function() {
        let fldRendering = $('#dropZone > li').eq(this.index).find('div.field');
        for (let property in this) {
            if (this.hasOwnProperty(property)) {
                fldRendering.data(property, property.valueOf());
                console.log('Populating LI #' + this.index + '.' + property + ' = ' + this.property);
            }
        }
    }
}