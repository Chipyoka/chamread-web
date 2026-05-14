import './bootstrap';
import Chart from 'chart.js/auto';

import { createIcons, icons } from 'lucide';
import Alpine from 'alpinejs';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

window.Alpine = Alpine;
window.Notyf = Notyf;
window.Chart = Chart;

Alpine.start();


createIcons({ icons });

