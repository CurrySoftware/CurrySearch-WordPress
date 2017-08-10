var gulp            = require("gulp"),
    sass            = require("gulp-sass"),
    autoprefixer    = require("gulp-autoprefixer")

gulp.task("scss", function () {
    gulp.src("src/**/*.scss")
        .pipe(sass({
            outputStyle: "compressed"
        }))
        .pipe(autoprefixer({
            browsers : ["last 20 versions"]
        }))
        .pipe(gulp.dest("../curry-search/public/css"))
})


gulp.task("watch", ["scss"], function() {
    gulp.watch("src/**/*", ["scss"])
})

gulp.task("default", ["watch"])

