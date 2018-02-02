module.exports = function(grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoPublishing/views/';

    sass.taopublishing = { };
    sass.taopublishing.files = { };
    sass.taopublishing.files[root + 'css/auth-selector.css'] = root + 'scss/auth-selector.scss';

    watch.taopublishingsass = {
        files : [root + 'scss/**/*.scss'],
        tasks : ['sass:taopublishing', 'notify:taopublishingsass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taopublishingsass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taopublishingsass', ['sass:taopublishing']);
};
