type Callback<T = unknown> = (payload: T) => void;

const listeners = new Map<string, Set<Callback>>();

export const subscribe = <T = unknown>(event: string, callback: Callback<T>): (() => void) => {
    const existing = listeners.get(event) || new Set<Callback>();
    existing.add(callback as Callback);
    listeners.set(event, existing);

    return () => {
        existing.delete(callback as Callback);
    };
};

export const emit = <T = unknown>(event: string, payload?: T): void => {
    (listeners.get(event) || new Set()).forEach((callback) => callback(payload));
};
