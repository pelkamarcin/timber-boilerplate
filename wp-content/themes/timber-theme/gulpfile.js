'use strict';

// GULP CONFIG

var _gulp               = require('gulp');

// Allows to output different builds depending on current environment. .env is located in the root of the repo
var _dotenv             = require('dotenv').config({ path: './../../../.env' });

var _gutil              = require('gulp-util');
var _sass               = require('gulp-sass');

// Restoring on build error
var _plumber            = require('gulp-plumber');

// CSS minification
var _cssnano            = require('gulp-cssnano'); 

// Joining CSS files
var _concat             = require('gulp-concat');

// Build done notifications
var _notify             = require('gulp-notify'); 

// Uglifying JS
var _uglify             = require('gulp-uglify');

// Creates svg sprites from source svg files
var _svgsprite          = require('gulp-svg-sprite'); 

// Checks JS for mistakes. jshint-stylish should be installed too
var _jshint             = require('gulp-jshint'); 

// Autoprefixes SASS
var _autoprefixer       = require('gulp-autoprefixer');

// Generates SASS/JS sourcemaps for easier inbrowser debugging
var _sourcemaps         = require('gulp-sourcemaps');

// These handle merging JS modules 
var _browserify         = require('browserify');
var _source             = require('vinyl-source-stream');
var _buffer             = require('vinyl-buffer');

// Allows to use ES2015 in JS. babel-preset-es2015 should be installed too
var _babelify           = require('babelify');

// Adds browser feature test results to HTML and JS
var _modernizr          = require('gulp-modernizr');

// Source for variables shared between PHP, gulp, JS, and SASS
var _common_config      = require('./common_config.json');

// Injects variables from common_config file to SASS
var _sassvariables      = require('gulp-sass-variables');

// Allows to inject css @imports instead of just linking to them (Sass doesn't have this option by default)
var _sassmoduleimporter = require('sass-module-importer');

// Lints SCSS
var _sasslint           = require('gulp-sass-lint');


// TASKS CONFIG

// Standalone JS  libraries to copy to production build
var libs_copied_from_nodemodules  = [
  'node_modules/jquery/dist/jquery.min.js'
];


// Environment variables
// if defined in a .env file or if defined as a gulp commandline argument (gulp --env=true)
var is_env_dev = true;
if (typeof process.env.ENV !== 'undefined') {
  is_env_dev = (process.env.ENV === 'dev');
}
if (typeof _gutil.env.env !== 'undefined') {
  is_env_dev = (_gutil.env.env === 'dev');
}


// SVG sprite config
var svgsprite_config  = {
  mode           : {
    symbol       : {
      dest       : '.',
      prefix     : '.u-svg-%s',
      dimensions : '-size',
      sprite     : 'img/sprite.svg',
      render     : {
        scss     : {
          dest   : 'scss/autogenerated/_svg-sprite.scss'
        }
      }
    }
  }
};


// Injecting common_config variables into SASS
var common_config_sass_vars = {};
for (var variable in _common_config) {
  common_config_sass_vars['$_' + variable] = _common_config[variable];
}
common_config_sass_vars['$_is-env-dev'] = is_env_dev;


// TASKS

_gulp.task('svg-sprites', function() {
  _gulp.src('img/svg-sprite-source/*.svg')
    .pipe(_svgsprite(svgsprite_config)).on('error', function(error){ console.log(error); })
    .pipe(_gulp.dest('./'))
    .pipe(is_env_dev ? _notify('SVG sprite created successfully!') : _gutil.noop() );
});


// Lint sass. Configuration found in .sass-lint.yml
_gulp.task('sass-lint', function () {
  return _gulp.src('scss/**/*.scss')
    .pipe(_sasslint())
    .pipe(_sasslint.format());
});


_gulp.task('styles', ['sass-lint'], function() {
  _gulp.src(['scss/style.scss'])
  .pipe(_plumber())
  .pipe( is_env_dev ? _sourcemaps.init() : _gutil.noop() )
  .pipe(_sassvariables(common_config_sass_vars))
  .pipe(_sass({ 
    style: 'expanded',
    includePaths: [__dirname + '/node_modules/susy/sass'],
    importer: _sassmoduleimporter()
  }))
  .pipe(_autoprefixer())
  .pipe(_cssnano({
    zindex: false
  }))
  .pipe(_concat('style.css'))
  .pipe( is_env_dev ? _sourcemaps.write() : _gutil.noop() )
  .pipe(_gulp.dest('./'))
  .pipe(is_env_dev ? _notify('CSS compiled and concatenated successfully!') : _gutil.noop() );
});


_gulp.task('jshint', function(){
  var src  = [
    'Gulpfile.js',
    'js/modules/*.js'
  ];

  _gulp.src(src)
    .pipe(_jshint())
    .pipe(_jshint.reporter(require('jshint-stylish')));
});


_gulp.task('modernizr', function(){
  var src = [
    './js/scripts.min.js',
    './style.css'
  ];

  _gulp.src(src)
    .pipe(_modernizr('modernizr.min.js', {
      options: ['setClasses'],
      classPrefix: 'js-supports-'
    }))
    .pipe(_uglify())
    .pipe(_gulp.dest('js'))
    .on('error', _gutil.log);
});


_gulp.task('scripts', function(){
  var bundler = _browserify('js/main.js');

  bundler.transform(_babelify, {
      presets: ['es2015']
    })
    .bundle()
    .on('error', _gutil.log)
    .pipe(_source('scripts.min.js'))
    .pipe(_buffer())
    // don't uglify if we're in local environment, do uglify if it's something other
    .pipe(is_env_dev ? _gutil.noop() : _uglify())
    .pipe(_gulp.dest('js'))
    .pipe(is_env_dev ? _notify('JS compiled and concatenated successfully!') : _gutil.noop());
});


_gulp.task('copy_to_libs', () => _gulp
  .src(libs_copied_from_nodemodules)
  .pipe(_gulp.dest('js/libs'))
);


_gulp.task('default', [
  'copy_to_libs',
  'styles',
  'scripts',
  'svg-sprites',
  'modernizr'
]);



// WATCH

_gulp.task('watch', ['default'], function(){
  // Gulpfile.js:
  _gulp.watch('Gulpfile.js', [
    'jshint'
  ]);

  // Scripts:
  _gulp.watch(['js/main.js', 'js/modules/*.js'], [
    'jshint',
    'scripts'
  ]);

  // Styles:
  _gulp.watch('scss/**/*.scss', [
    'styles'
  ]);

  // SVG:
  _gulp.watch('img/svg-sprite-source/*.svg', [
    'svg-sprites'
  ]);  
});