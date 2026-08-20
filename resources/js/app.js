import './bootstrap';
import { initAllCharts, watchForCharts } from './chart-manager';

import { createIcons, icons } from 'lucide';
import Alpine from 'alpinejs';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

window.Alpine = Alpine;
window.Notyf = Notyf;

Alpine.start();
createIcons({ icons });

document.addEventListener('DOMContentLoaded', () => {
    initAllCharts();
    watchForCharts();
});

// If you use Livewire's SPA navigation, re-init on page swap too:
document.addEventListener('livewire:navigated', () => initAllCharts());