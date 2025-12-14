'use strict';

export const http = {
    async json(url, options = {}) {
        const response = await fetch(url, {
            headers: {'Accept': 'application/json'},
            ...options,
        });

        if (!response.ok) {
            throw new Error(`Request failed: ${response.status}`);
        }

        return response.json();
    },
};
