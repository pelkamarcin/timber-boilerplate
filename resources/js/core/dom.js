'use strict';

export const onDomReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, {once: true});
        return;
    }

    callback();
};

export const toggleBodyClass = (className, force) => {
    document.body.classList.toggle(className, force);
};
