'use strict';

const queue = [];

export const analytics = {
    track(eventName, payload = {}) {
        queue.push({eventName, payload});
        console.debug('[analytics]', eventName, payload);
    },
    flush() {
        queue.length = 0;
    }
};
