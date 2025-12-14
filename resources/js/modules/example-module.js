'use strict';

import {analytics} from '@js/services/analytics';
import {http} from '@js/services/http';

/**
 * Example module
 */

export const example_module = () => {
    analytics.track('example_module_init');

    http.json('/wp-json/wp/v2/posts?_fields=id,title&per_page=1')
        .then((data) => analytics.track('example_module_data', {count: data.length}))
        .catch((error) => console.error(error));
};
