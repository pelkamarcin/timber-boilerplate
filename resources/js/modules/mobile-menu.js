'use strict';
import jQuery from 'jquery';

/**
 * Example module
 */
export const mobile_menu = () => {

    const burgerBt = jQuery('[data-js="mainmenu-burger"]');
    const mainmenuElem = jQuery('[data-js="mainmenu"]');

    burgerBt.click(function () {
        jQuery(this).toggleClass('is-active');
        mainmenuElem.toggleClass('is-active');
    });
};
