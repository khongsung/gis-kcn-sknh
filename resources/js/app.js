import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/inertia-vue3'
import axios from 'axios';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

createInertiaApp({
  resolve: name => require(`./Pages/${name}.vue`),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})