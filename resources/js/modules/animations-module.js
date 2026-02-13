'use strict';

import enterView from 'enter-view';
import jQuery from 'jquery';

window.$ = window.jQuery = jQuery;

export const animations_module = () => {
    if (jQuery('.u-animated-block').length === 0) {
        return;
    }

    enterView({
        selector: '.u-animated-block',
        offset: 0.05,
        enter: handleInView,
        exit: handleOutView,
    });

    function handleInView(element) {
        jQuery(element).addClass('is-in-view-first is-in-view');
    }

    function handleOutView(element) {
        jQuery(element).removeClass('is-in-view');
    }
};
