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
function frmField(isRequired, txtLabel, toolTip, placeHolder, cssName, varName, fldValue, fldType, fldMaxLength, fldWidth, fldSpecial, fldOptions, dbTable, dbField, fldVariables, callStep) {
    this.isRequired = isRequired;
    this.txtLabel = txtLabel;
    this.lblWidth = 150;
    this.lblHeight = 30;
    this.toolTip = toolTip;
    this.placeHolder = placeHolder;
    this.cssName = cssName;
    this.varName = varName;
    this.fldValue = fldValue;
    this.fldType = fldType;
    this.fldMaxLength = fldMaxLength;
    this.fldWidth = 200;
    this.fldHeight = 30;
    this.fldSpecial = fldSpecial;
    this.fldOptions = fldOptions;
    this.dbTable = dbTable;
    this.dbField = dbField;
    this.fldVariables = fldVariables;
    this.callStep = callStep;
    this.posX = 0;
    this.posY = 0;
    this.hideMe = false;
    this.lockMe = false;
}
frmField.prototype = {
    constructor: frmField,
    saveToDB: function() {

    }
}