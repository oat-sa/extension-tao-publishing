module.exports = function(grunt) {
    'use strict';

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    var paths = {
        'tao' : root + '/tao/views/js',
        'taoPublishing' : root + '/taoPublishing/views/js',
        'taoPublishingCss' : root + '/taoPublishing/views/css'
    };

    /**
     * Remove bundled and bundling files
     */
    clean.taopublishingbundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.taopublishingbundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : paths,
            modules : [{
                name: 'taoPublishing/controller/routes',
                include : ext.getExtensionsControllers(['taoPublishing']),
                exclude : ['mathJax'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taopublishingbundle = {
        files: [
            { src: [ out + '/taoPublishing/controller/routes.js'],  dest: root + '/taoPublishing/views/js/controllers.min.js' },
            { src: [ out + '/taoPublishing/controller/routes.js.map'],  dest: root + '/taoPublishing/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('copy', copy);
    grunt.config('requirejs', requirejs);

    // bundle task
    grunt.registerTask('taopublishingbundle', ['clean:taopublishingbundle', 'requirejs:taopublishingbundle', 'copy:taopublishingbundle']);
};
