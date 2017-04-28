
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

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

var UKA = UKA || {};

UKA.TagForm = function() {

    function add(datasetId, tag) {
        update('dataset/addTag', {
            datasetId: datasetId,
            tag: tag
        });
    }

    function remove(datasetId, tag) {
        update('dataset/deleteTag', {
            datasetId: datasetId,
            tag: tag
        });
    }

    function update(action, data) {

        $.ajax({
            method: 'POST',
            url: config.baseUrl + action,
            data: data,
            success: function(response) {
                console.log('Success!');
            }
        });
    }

    return {
        add: add,
        remove: remove
    };
}();

Dropzone.autoDiscover = false;

// Dropzone.options.datasetForm = {
//     acceptedFiles: '.csv'
// };

$(document).ready(function() {

    if ($('#datasetForm').length > 0) {
        var myDropzone = new Dropzone("#datasetForm");
        myDropzone.on("addedfile", function(file) {
            console.log(file);
        });
    }

    $('.multiselect').multiselect({
        buttonText: function(options, select) {
            return 'Välj dataset';
        },
        // maxHeight: 200,
        enableFiltering: true
    });

    $(".tags-form").select2({
        tags: true,
        placeholder: 'Välj en eller flera taggar',
        theme: "bootstrap"
        // data: tags
    });

    $('.tags-form').on('select2:select', function (evt) {
        var dataset = $(this).attr('data-dataset-id'),
            tag = {
                id: evt.params.data.id,
                name: evt.params.data.text
            };

        UKA.TagForm.add(dataset, tag);

    });

    $('.tags-form').on('select2:unselect', function (evt) {
        var dataset = $(this).attr('data-dataset-id'),
            tag = {
                id: evt.params.data.id,
                name: evt.params.data.text
            };

        UKA.TagForm.remove(dataset, tag);
    });

});
