'use strict';
// import common_config from '../../common_config.json';
import enterView from 'enter-view'; // or...
import jQuery from 'jquery';

window.$ = window.jQuery = jQuery;
/**
 * Example module
 */
export const animations_module = () => {

    if (jQuery('.u-animated-block').length > 0) {
        enterView({
            selector: '.u-animated-block',
            offset: 0.05,
            enter: function (element) {
                handleInView(element);
            },
            exit: function (element) {
                handleOutView(element);
            },
        });
    }


    function handleInView(element) {
        jQuery(element).addClass('is-in-view-first is-in-view');
    }

    function handleOutView(element) {

        jQuery(element).removeClass('is-in-view');
    }

};
