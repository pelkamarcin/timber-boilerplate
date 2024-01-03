import '../scss/style.scss';
import {addBrowserClasses} from './_utils.js';
import {animations_module} from './modules/animations-module.js';
import {example_module} from "./modules/example-module.js";


document.addEventListener('DOMContentLoaded', function () {
    document.getElementsByClassName('is-page-loading')[0].classList.remove('is-page-loading');
    document.getElementsByClassName('no-js')[0].classList.remove('no-js');

    addBrowserClasses();

    animations_module();
    example_module();
});
