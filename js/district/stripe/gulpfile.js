var gulp = require('gulp'),
    uglify = require('gulp-uglify');

gulp.task('js', function() {

    return gulp.src(['stripe.js'])
        .pipe(uglify({
            mangle: false
        }))
        .pipe(gulp.dest('build'));

});

gulp.task('watch', function() {

    gulp.watch(['stripe.js'], ['js']);

});

gulp.task('default', ['js', 'watch']);
