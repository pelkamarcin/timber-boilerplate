'use strict';

import '@styles/style.scss';
import {onDomReady} from '@js/core';
import {addBrowserClasses} from '@js/_utils';
import {animations_module} from '@js/modules/animations-module';
import {mobile_menu} from '@js/modules/mobile-menu';
import {example_module} from '@js/modules/example-module';


onDomReady(() => {
    document.body.classList.remove('is-page-loading', 'no-js');

    addBrowserClasses();
    animations_module();
    mobile_menu();
    example_module();
});
