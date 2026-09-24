export const onDomReady = (callback: () => void): void => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
};

export const toggleBodyClass = (className: string, force?: boolean): void => {
    document.body.classList.toggle(className, force);
};
