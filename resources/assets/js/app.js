
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('example', require('./components/Example.vue'));

const app = new Vue({
    el: '#app'
});

Dropzone.options.datasetForm = {
    acceptedFiles: '.csv'
};

// myDropzone.emit("addedfile", mockFile);
// myDropzone.createThumbnailFromUrl(mockFile, '/your-image.jpg');

$(document).ready(function() {
    console.log('test');
    $('.multiselect').multiselect({
        buttonText: function(options, select) {
            return 'Välj dataset';
        },
        // maxHeight: 200,
        enableFiltering: true
    });
});
