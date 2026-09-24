interface AnalyticsEvent {
    eventName: string;
    payload: Record<string, unknown>;
}

const queue: AnalyticsEvent[] = [];

export const analytics = {
    track(eventName: string, payload: Record<string, unknown> = {}): void {
        queue.push({ eventName, payload });
        console.debug('[analytics]', eventName, payload);
    },
    flush(): void {
        queue.length = 0;
    },
};
