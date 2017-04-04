module.exports = function(grunt) {

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-cssmin');	
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify'); 
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('dependencies-builder');

	var dir_scripts	= 'public/packages/core/html/js/';
	var dir_styles	= 'public/packages/core/html/css/';

	var qinoa_ui_src = grunt.dependencies.build(dir_scripts+'src/dependencies.json', dir_scripts+'src/*.js', ['jquery.js', 'jquery-ui.js', 'qinoa.api.js']);	
		
	var uglify_jquery_files = {};
	uglify_jquery_files[dir_scripts+'jquery.min.js'] = [dir_scripts+'src/jquery.js'];
	uglify_jquery_files[dir_scripts+'jquery-ui.min.js'] = [dir_scripts+'src/jquery-ui.js'];

	var uglify_qinoa_files = {};
	uglify_qinoa_files[dir_scripts+'qinoa.api.min.js'] = [dir_scripts+'src/qinoa.api.js'];
	uglify_qinoa_files[dir_scripts+'qinoa-ui.min.js'] = [dir_scripts+'qinoa-ui.pack.js'];	

	
	grunt.initConfig({
		//	pkg: grunt.file.readJSON('package.json'),

		jshint: {
		
			options: {
				reporter: require('jshint-html-reporter'),
				reporterOutput: 'jshint-report.html'
			},
			
			all: [
				'Gruntfile.js',
				'package.json',
				dir_scripts+'src/qinoa*.js'
			]

		},
		/*
		sass: {
			dist: {
				options: {
					style: 'expanded'
				},
				files: [{
					"expand": true,
					"cwd": "src/styles/",
					"src": ["*.scss"],
					"dest": "dist/styles/",
					"ext": ".css"
				}]
			},
			dev: {
			} 
		},
		*/    

		cssmin: { 
			jquery_css: {
				src: dir_styles+'src/jquery-ui*.css',
				dest: dir_styles+'jquery-ui.min.css'		
			}
		},

		concat: {
		
			options: {
				stripBanners: true
			},

			qinoa_js: {
				src: qinoa_ui_src,
				dest: dir_scripts+'qinoa-ui.pack.js'
			},
			
			qinoa_css: {
				src: dir_styles+'src/qinoa-ui.css',
				dest: dir_styles+'qinoa-ui.min.css'
			},
			
			jquery_css: {
				src: dir_styles+'src/jquery-ui*.css',
				dest: dir_styles+'jquery-ui.min.css'		
			}
		},

		uglify: {
		
			options: {
				preserveComments: 'some',
				mangle: true,
				quoteStyle: 3
				//screwIE8: true
			},

			jquery_js: {
				files : uglify_jquery_files		
			},
			
			qinoa_js: {
				files : uglify_qinoa_files
			}
		},

			
		watch: {

			scripts: {
				files: dir_scripts+'src/*.js',
				tasks: ['concat', 'uglify', 'jshint']
			},
    
			styles: {
				files: dir_styles+'src/*.css',
				tasks: ['concat']
			}
     
		}

	});

	grunt.registerTask('all', ['concat'], ['compile']);
	grunt.registerTask('default', ['watch']);
	grunt.registerTask('compile', ['concat:qinoa_js', 'uglify:qinoa_js']);

};
