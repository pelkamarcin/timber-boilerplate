import '@styles/style.scss';
import { onDomReady } from '@js/core';
import { animations_module } from '@js/modules/animations-module';
import { mobile_menu } from '@js/modules/mobile-menu';
// import { example_module } from '@js/modules/example-module';

onDomReady(() => {
    document.documentElement.classList.remove('is-page-loading', 'no-js');

    animations_module();
    mobile_menu();
    // example_module();
});
