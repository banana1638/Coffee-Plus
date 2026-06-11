import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('authModal', {
    open: false,
    tab: 'login',

    init(initialTab = null) {
        if (['login', 'register'].includes(initialTab)) {
            this.tab = initialTab;
            this.open = true;
        }
    },

    show(tab = 'login') {
        this.tab = ['login', 'register'].includes(tab) ? tab : 'login';
        this.open = true;
    },

    close() {
        this.open = false;
    },
});

Alpine.start();
