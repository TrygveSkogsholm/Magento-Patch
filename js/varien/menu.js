/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Varien
 * @package     js
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/*ORIGINAL CODE ==========================================

function toggleMenu(el, over)
{
    if (over) {
        Element.addClassName(el, 'over');
    }
    else {
        Element.removeClassName(el, 'over');
    }
}

========================================================*/

var delay = 150; /* milli seconds */
function attachHooks() {
    var menu = document.getElementById("nav");
   
    var menuItems = menu.getElementsByTagName("li");
   
    currentHover = menuItems[0];
   
    for (var i = 0; i < menuItems.length; i++) {
        menuItems[i].onmouseover = function () {activateMenuWithDelay(this);};
        menuItems[i].onmouseout = function () {deactivateMenuWithDelay(this);};
    }
}
 
function activateMenuWithDelay(ele) {
    if(ele.timer) {
        clearTimeout(ele.timer);
    }
    ele.timer = setTimeout(function(){activateShowMenu(ele)}, delay);
}
  
function activateShowMenu(ele) {
    var parent = ele;
    //parent.className = "over";
    Element.addClassName(parent, 'over');  
}
 
function deactivateMenu(ele) {
    var parent = ele;
    // parent.className = " ";

    Element.removeClassName(parent, 'over');
}
 
function deactivateMenuWithDelay(ele) {
    if(ele.timer) {
        clearTimeout(ele.timer);
    }
    ele.timer = setTimeout(function(){deactivateMenu(ele)}, delay);
}
 
function initMenuDelay() {
    attachHooks();
    deactivateMenu();
}
 
function addLoadEvent(func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
         window.onload = func;
    } else {
         window.onload = function() {
         oldonload();
         func();
         }
    }
}
addLoadEvent(attachHooks);
