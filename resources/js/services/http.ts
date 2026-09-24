export const http = {
    async json<T = unknown>(url: string, options: RequestInit = {}): Promise<T> {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            ...options,
        });

        if (!response.ok) {
            throw new Error(`Request failed: ${response.status}`);
        }

        return response.json() as Promise<T>;
    },
};
