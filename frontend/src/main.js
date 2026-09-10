import { createApp } from 'vue';
import App from './App.vue';
import './styles.css';

// Google Fonts betöltése (Inter + Montserrat) – csak ha még nem töltötte be az oldal
function injectGoogleFonts() {
  const fontId = 'rocktile-calc-fonts';
  if (document.getElementById(fontId)) return;
  const link = document.createElement('link');
  link.id = fontId;
  link.rel = 'stylesheet';
  link.href =
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap';
  document.head.appendChild(link);
}

function mountCalculator() {
  const mountEl = document.getElementById('rocktile-calculator');
  if (mountEl) {
    injectGoogleFonts();
    createApp(App).mount(mountEl);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountCalculator);
} else {
  mountCalculator();
}
