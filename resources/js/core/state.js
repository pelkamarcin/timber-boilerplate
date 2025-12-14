'use strict';

const listeners = new Map();

export const subscribe = (event, callback) => {
    const existing = listeners.get(event) || new Set();
    existing.add(callback);
    listeners.set(event, existing);

    return () => {
        existing.delete(callback);
    };
};

export const emit = (event, payload) => {
    (listeners.get(event) || []).forEach((callback) => callback(payload));
};
