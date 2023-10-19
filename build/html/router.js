// https://dev.to/rohanbagchi/how-to-write-a-vanillajs-router-hk3
const app = document.querySelector("main");
const appContent = app.querySelector('#js-loaded-content');
const spinner = app.querySelector('#spinner');
const menu = document.querySelector('aside');
const menuItems = document.querySelectorAll("aside li a[data-router-navigate]");
const mobileNavTriggerEl = document.querySelector('[data-drawer-target="drawer-navigation"]');

const renderContent = async (page) => {
    const currentPage = app.getAttribute('data-router-current');

    if(!menu.hasAttribute('aria-hidden')){
        // Trigger click event to close mobile nav.
        mobileNavTriggerEl.dispatchEvent(
            new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            })
        );
    }

    if (currentPage === page) {
        // Do not reload the same page.
        return
    }

    // Show loader.
    spinner.classList.remove('hidden');
    spinner.classList.add('flex');
    appContent.classList.add('hidden');

    // Load content.
    const response = await fetch(page);
    appContent.innerHTML = await response.text();
    window.scrollTo(0, 0);

    // Hide loader.
    spinner.classList.remove('flex');
    spinner.classList.add('hidden');
    appContent.classList.remove('hidden');

    app.setAttribute('data-router-current', page);
    // Manage active classes.
    menuItems.forEach(node => {
        node.classList.remove('active')
    });
    document.querySelector('aside li a[data-router-navigate="'+page+'"]').classList.add('active');

    // There might be other nav links on the newly loaded page, make sure they are registered.
    registerNavLinks();

    document.dispatchEvent(new CustomEvent('pageWasLoaded', {
        bubbles: true,
        cancelable: true,
        detail: {
            page: page
        }
    }));
};

const registerNavLinks = () => {
    const nav =  document.querySelectorAll("main a[data-router-navigate]");
    registerNavItems(nav);
};

const registerNavItems = (items) => {
    items.forEach(function (to) {
        to.addEventListener("click", (e) => {
            e.preventDefault();
            renderContent(to.getAttribute('data-router-navigate'));
        });
    });
};

(function boot() {
    registerNavItems(menuItems);
    renderContent('dashboard.html');
})();
