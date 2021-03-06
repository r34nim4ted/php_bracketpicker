var elixir = require('laravel-elixir'),
    gulp = require('gulp'),
    bower = require('gulp-bower'),
    qunit = require('gulp-qunit'),
    phpunit = require('gulp-phpunit'),
    phplint = require('phplint').lint;
/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.task('bower');
    mix.copy('bower_components/jquery/dist/jquery.min.js','resources/assets/js/jquery.js');
    mix.copy('bower_components/bootstrap/dist/js/bootstrap.min.js','resources/assets/js/bootstrap.js');
    mix.copy('bower_components/typeahead.js/dist/typeahead.jquery.min.js', 'resources/assets/js/typeahead.js');
    mix.copy('bower_components/summernote/dist/summernote.js','resources/assets/js/');
    mix.copy('bower_components/tinycolor/dist/tinycolor-min.js','resources/assets/js/');
    mix.copy('bower_components/pick-a-color/build/1.2.3/js/pick-a-color-1.2.3.min.js','resources/assets/js/pick-a-color.js');
    //mix.copy('bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js','resources/assets/js/datatables.js');

    mix.copy('bower_components/bootstrap/less/*.less','resources/assets/less/bootstrap/');
    mix.copy('bower_components/bootstrap/less/mixins/*.less','resources/assets/less/bootstrap/mixins/');
    mix.copy('bower_components/font-awesome/less/*.less','resources/assets/less/fontawesome/');
    mix.copy('bower_components/summernote/dist/summernote.css','resources/assets/css/');
    mix.copy('bower_components/pick-a-color/build/1.2.3/css/pick-a-color-1.2.3.min.css','resources/assets/css/pick-a-color.css');
    //mix.copy('bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css','resources/assets/css/datatables.css');

    //mix.copy('bower_compenents/font-awesome/fonts/*', 'public/assets/fonts');

    mix.less('frontend.less');
    mix.styles('summernote.css');
    mix.styles('typeahead-bootstrap.css');
    mix.styles('printbracket.css');
    mix.styles('pick-a-color.css');
    //mix.styles('datatables.css');
    mix.scripts(['pick-a-color.js','tinycolor-min.js'],'public/js/color.js');
    mix.browserify('summernote.js');
    mix.browserify('jquery.js');
    mix.browserify('bootstrap.js');
    //mix.browserify('datatables.js');
    mix.browserify('typeahead.js');
    mix.browserify('master_bracket.js');
    mix.browserify('bracket_select.js');
    mix.browserify('user_search.js');
    mix.browserify('team_list.js');
    mix.browserify('team_create.js');
    mix.version([
            'js/master_bracket.js',
            'js/team_list.js',
            'js/team_create.js',
            'js/summernote.js',
            'js/color.js',
            'js/bracket_select.js',
            'js/user_search.js',
            'js/jquery.js',
            'js/bootstrap.js',
            'js/typeahead.js',
            'css/frontend.css',
            'css/pick-a-color.css',
            'css/printbracket.css',
            'css/typeahead-bootstrap.css'
    ]);
});

gulp.task('bower', function(){
  return bower();
});

gulp.task('phpunit',function() {
  return gulp.src('').pipe(phpunit());
});

gulp.task('qunit',function() {
  return gulp.src('tests/index.html').pipe(qunit());
});

gulp.task('phplint', function () {
    return phplint(['src/**/*.php'], {limit: 10});
});
gulp.task('test', ['phplint','phpunit','qunit']);
