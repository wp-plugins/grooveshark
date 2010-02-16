/*
 * Sidebar setup functions
 */

// Used as the onclick function for playlist radio input elements in the sidebar widget options panel
function groovesharkUpdateChoice(obj) {
   var height = 176 + (22 * obj.className); 
   if (height > 1000) { 
       height = 1000;
   } 
   // The context is needed for jQuery to actually change the value of sidebarWidgetHeight; also, document.getElementById.value won't change it either
   jQuery('#gsSidebarWidgetHeight', obj.parentNode.parentNode.parentNode.parentNode).val(height);
}

function changeSidebarColor(obj) {
    if (!obj) {
        return false;
    }
    var curValue = (+obj.options[obj.selectedIndex].value);
    var context = jQuery(obj.parentNode.parentNode.parentNode);
    var colorArray = getBackgroundRGB(curValue);
    jQuery('#widget-base-color', context)[0].style.backgroundColor = colorArray[0];
    jQuery('#widget-primary-color', context)[0].style.backgroundColor = colorArray[1];
    jQuery('#widget-secondary-color', context)[0].style.backgroundColor = colorArray[2];
}

function getBackgroundRGB(colorSchemeID) {
    var colorArray = new Array();
    switch (colorSchemeID) {
        case 0:
            colorArray[0] = 'rgb(0,0,0)';
            colorArray[1] = 'rgb(255,255,255)';
            colorArray[2] = 'rgb(102,102,102)';
        break;

        case 1:
            colorArray[0] = 'rgb(204,162,12)';
            colorArray[1] = 'rgb(77,34,28)';
            colorArray[2] = 'rgb(204,124,12)';
        break;

        case 2:
            colorArray[0] = 'rgb(135,255,0)';
            colorArray[1] = 'rgb(0,136,255)';
            colorArray[2] = 'rgb(255,0,84)';
        break;

        case 3:
            colorArray[0] = 'rgb(255,237,144)';
            colorArray[1] = 'rgb(53,150,104)';
            colorArray[2] = 'rgb(168,212,111)';
        break;

        case 4:
            colorArray[0] = 'rgb(224,228,204)';
            colorArray[1] = 'rgb(243,134,48)';
            colorArray[2] = 'rgb(167,219,216)';
        break;

        case 5:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(55,125,159)';
            colorArray[2] = 'rgb(246,214,31)';
        break;

        case 6:
            colorArray[0] = 'rgb(69,5,18)';
            colorArray[1] = 'rgb(217,24,62)';
            colorArray[2] = 'rgb(138,7,33)';
        break;

        case 7:
            colorArray[0] = 'rgb(180,213,218)';
            colorArray[1] = 'rgb(129,59,69)';
            colorArray[2] = 'rgb(177,186,191)';
        break;

        case 8:
            colorArray[0] = 'rgb(232,218,94)';
            colorArray[1] = 'rgb(255,71,70)';
            colorArray[2] = 'rgb(255,255,255)';
        break;

        case 9:
            colorArray[0] = 'rgb(153,57,55)';
            colorArray[1] = 'rgb(90,163,160)';
            colorArray[2] = 'rgb(184,18,7)';
        break;

        case 10:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(0,150,9)';
            colorArray[2] = 'rgb(233,255,36)';
        break;

        case 11:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(122,122,122)';
            colorArray[2] = 'rgb(214,214,214)';
        break;

        case 12:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(215,8,96)';
            colorArray[2] = 'rgb(154,154,154)';
        break;

        case 13:
            colorArray[0] = 'rgb(0,0,0)';
            colorArray[1] = 'rgb(255,255,255)';
            colorArray[2] = 'rgb(98,11,179)';
        break;

        case 14:
            colorArray[0] = 'rgb(75,49,32)';
            colorArray[1] = 'rgb(166,152,77)';
            colorArray[2] = 'rgb(113,102,39)';
        break;

        case 15:
            colorArray[0] = 'rgb(241,206,9)';
            colorArray[1] = 'rgb(0,0,0)';
            colorArray[2] = 'rgb(255,255,255)';
        break;

        case 16:
            colorArray[0] = 'rgb(255,189,189)';
            colorArray[1] = 'rgb(221,17,34)';
            colorArray[2] = 'rgb(255,163,163)';
        break;

        case 17:
            colorArray[0] = 'rgb(224,218,74)';
            colorArray[1] = 'rgb(255,255,255)';
            colorArray[2] = 'rgb(249,255,52)';
        break;

        case 18:
            colorArray[0] = 'rgb(87,157,214)';
            colorArray[1] = 'rgb(205,35,31)';
            colorArray[2] = 'rgb(116,191,67)';
        break;

        case 19:
            colorArray[0] = 'rgb(178,194,230)';
            colorArray[1] = 'rgb(1,44,95)';
            colorArray[2] = 'rgb(251,245,211)';
        break;

        case 20:
            colorArray[0] = 'rgb(96,54,42)';
            colorArray[1] = 'rgb(232,194,142)';
            colorArray[2] = 'rgb(72,46,36)';
        break;

        default:
            colorArray[0] = 'rgb(0,0,0)';
            colorArray[1] = 'rgb(255,255,255)';
            colorArray[2] = 'rgb(102,102,102)';
        break;
    }
    return colorArray;
}
