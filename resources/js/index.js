import '../scss/style.scss';
import {addBrowserClasses} from './_utils.js';
import {animations_module} from './modules/animations-module.js';
import {mobile_menu} from './modules/mobile-menu.js';


document.addEventListener('DOMContentLoaded', function () {
    document.getElementsByClassName('is-page-loading')[0].classList.remove('is-page-loading');
    document.getElementsByClassName('no-js')[0].classList.remove('no-js');

    addBrowserClasses();

    animations_module();
    mobile_menu();
    // example_module();
});
