const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
   .vue({ version: 3 }) // Khai báo dùng Vue 3
   .postCss('resources/css/app.css', 'public/css', [
       // Anh có thể thêm TailwindCSS ở đây nếu muốn
   ])
   .webpackConfig({
       resolve: {
           extensions: ['.*', '.wasm', '.mjs', '.js', '.jsx', '.json', '.vue'],
       },
   })
   .alias({
       '@': 'resources/js', // Khai báo alias @ trỏ tới thư mục JS
   });
