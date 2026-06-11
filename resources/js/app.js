import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import('./bootstrap').catch((error) => {
    console.warn('Bootstrap initialization failed.', error);
});
