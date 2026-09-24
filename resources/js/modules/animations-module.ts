export const animations_module = (): void => {
    const elements = document.querySelectorAll<HTMLElement>('.u-animated-block');

    if (elements.length === 0) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in-view-first', 'is-in-view');
                } else {
                    entry.target.classList.remove('is-in-view');
                }
            });
        },
        {
            rootMargin: '-5% 0px',
        }
    );

    elements.forEach((el) => observer.observe(el));
};
