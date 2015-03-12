'use strict';

module.exports = function (grunt) {
  var critical = require('critical');
  grunt.initConfig({

      pkg: grunt.file.readJSON('package.json'),

      watch: {
          options: {
              livereload: true
          },
          sass: {
              files: ['scss/{,**/}*.{scss,sass}'],
              tasks: ['sass:dev', 'notify:finished'],
              options: {
                  livereload: false
              }
          },
          css: {
              files: ['css/{,**/}*.css'],
              options: {
                  livereload: true
              }
          },
          js: {
              files: ['js/{,**/}*.js', '!js/{,**/}*.min.js'],
              tasks: ['uglify:dev']
          },
          files: {
              files: ['{,**/}*.{html}'],
              options: {
                  livereload: true
              }
          }
      },

      sass: {                                 // task
          dist: {
              options: {
                  includePaths: require('node-neat').includePaths,
                  style: 'compressed',
                  sourceMap: false,
                  imagePath: '../images'
              },
              files: {
                  'tmp/main.min.css': 'scss/main.scss'
              }
          },
          dev: {
              options: {
                  includePaths: require('node-neat').includePaths,
                  style: 'nested',
                  sourceMap: true,
                  imagePath: '../images'
              },
              files: {
                  'css/main.css': 'scss/main.scss'
              }
          }
      },
      svgmin: {
          options: {
              plugins: [
                  {removeViewBox: false},
                  {removeUselessStrokeAndFill: false},
                  {convertPathData: {straightCurves: false}},
                  {removeXMLProcInst: false}
              ]
          },
          dist: {
              files: [{
                  expand: true,
                  cwd: 'images/src',
                  src: ['*.svg'],
                  dest: 'images',
                  ext: '.svg'
              }]
          }
      },

    uglify: {
      dev: {
        options: {
          mangle: false,
          compress: false,
          beautify: true
        },
        files: [{
          expand: true,
          flatten: true,
          cwd: 'js',
          dest: 'js',
          src: ['src/{,**/}*.js', '!src/{,**/}*.min.js'],
          rename: function(dest, src) {
            var folder = src.substring(0, src.lastIndexOf('/'));
            var filename = src.substring(src.lastIndexOf('/'), src.length);
            filename = filename.substring(0, filename.lastIndexOf('.'));
            return dest + '/' + folder + filename + '.min.js';
          }
        }],

      },
      dist: {
        options: {
          mangle: true,
          compress: true,
        },

          files: {
              'js/main.js': ['js/src/libs/*.js', 'js/src/plugins/*.js', 'js/src/*.js']
          }
      }
    },
    imagemin: {
        dynamic: {
            files: [{
                expand: true,
                cwd: 'images/src',
                src: ['**/*.{png,jpg,gif}'],
                dest: 'images'
            }]
        }
    },

      //Simple notifications
      notify: {
          finished: {
              options: {
                  enabled: true,
                  message: 'Compiled',
              }
          },
          finishedbuild: {
              options: {
                  enabled: true,
                  message: 'Build Complete',
              }
          },
          finishedsvg: {
              options: {
                  enabled: true,
                  message: 'SVGs converted',
              }
          }
      },
      //Combines media queries to minimize file size (runs on $ grunt build)
      cmq: {
          your_target: {
              files: {
                  'tmp': ['tmp/main.min.css']
              }
          }
      },

      //Removes duplicate css to minimize file size (runs on $ grunt build)
      cssshrink: {
          your_target: {
              files: {
                  'css': ['tmp/main.min.css']
              }
          }
      },
      critical: {
          test: {
              options: {
                  css: [
                      'css/main.css'
                  ],
                  width: 320,
                  height: 70
              },
              src: 'index.html',
              dest: 'index-critical.html'
          }
      }
  });

  //Loads your tasks from packages.json
    require('jit-grunt')(grunt,{
        notify_hooks: 'grunt-notify',
        cmq: 'grunt-combine-media-queries',
        critical: 'grunt-critical'
    });

  grunt.registerTask('build', [
    'sass:dist',
    'cmq',
    'cssshrink',
    //'svgmin',
    'uglify:dist',
    'imagemin',
    'critical'
    //'jshint'
  ]);

  grunt.registerTask('default', 'watch');

};
