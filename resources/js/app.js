import './bootstrap';
import Chart from 'chart.js/auto';

import { createIcons, icons } from 'lucide';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();


createIcons({ icons });

