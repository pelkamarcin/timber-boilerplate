export const mobile_menu = (): void => {
    const burgerBt = document.querySelector<HTMLElement>('[data-js="mainmenu-burger"]');
    const mainmenuElem = document.querySelector<HTMLElement>('[data-js="mainmenu"]');

    if (!burgerBt || !mainmenuElem) {
        return;
    }

    burgerBt.addEventListener('click', () => {
        burgerBt.classList.toggle('is-active');
        mainmenuElem.classList.toggle('is-active');
    });
};
