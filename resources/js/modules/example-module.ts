import { analytics } from '@js/services/analytics';
import { http } from '@js/services/http';

interface WPPost {
    id: number;
    title: { rendered: string };
}

/**
 * Example module - pokazuje użycie typów z WP REST API
 */
export const example_module = (): void => {
    analytics.track('example_module_init');

    http.json<WPPost[]>('/wp-json/wp/v2/posts?_fields=id,title&per_page=1')
        .then((data) => analytics.track('example_module_data', { count: data.length }))
        .catch((error: unknown) => console.error(error));
};
