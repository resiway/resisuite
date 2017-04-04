module.exports = function(grunt) {

	grunt.loadNpmTasks('grunt-contrib-cssmin');	
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify'); 
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.initConfig({

		concat: {
			options: {
				stripBanners: true
			},            
			css: {
				src: 'assets/css/resiway/*.css',
				dest: 'assets/css/resiway.css'		
			},        
			resiway_controllers: {
				src: 'src/controllers/*.js',
				dest: 'src/resiway.controllers.js'
			},            
			resiway_filters: {
				src: 'src/filters/*.js',
				dest: 'src/resiway.filters.js'
			},
			resiway_services: {
				src: 'src/services/*.js',
				dest: 'src/resiway.services.js'
			},            
			resiway_all: {
				src: [
                        'src/resiway.utils.js',                 
                        'src/resiway.module.js', 
                        'src/resiway.services.js', 
                        'src/resiway.routes.js', 
                        'src/resiway.filters.js', 
                        'src/resiway.controllers.js'
                    ],
				dest: 'resiway.js'
			}
			
		},

		cssmin: { 

			css: {
				src: 'assets/css/resiway.css',
				dest: 'assets/css/resiway.min.css'		
			}

		},
        
		uglify: {
		
			options: {
				preserveComments: 'some',
				mangle: true,
				quoteStyle: 3
			},

			resiway_all: {
                files: {
                    'resiway.min.js': ['resiway.js']
                }
			}
          
		},

			
		watch: {
/*
			scripts: {
				files: dir_scripts+'src/*.js',
				tasks: ['concat', 'uglify', 'jshint']
			},
    
			styles: {
				files: dir_styles+'src/*.css',
				tasks: ['concat']
			}
*/     
		}

	});

    /*
	grunt.registerTask('all', ['concat'], ['compile']);
	grunt.registerTask('default', ['watch']);
	grunt.registerTask('compile', ['concat:qinoa_js', 'uglify:qinoa_js']);
*/
	grunt.registerTask('default', ['concat', 'cssmin', 'uglify']);
};
