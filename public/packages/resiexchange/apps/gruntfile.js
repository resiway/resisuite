module.exports = function(grunt) {

	grunt.loadNpmTasks('grunt-contrib-cssmin');	
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify'); 
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-clean');
    
	grunt.initConfig({
        clean: {
            options: {
                'force': true
            },            
            all: ['../../../../cache/*']
        },
        
		concat: {
			options: {
				stripBanners: true
			},            
			css: {
				src: 'assets/css/resiexchange/*.css',
				dest: 'assets/css/resiexchange.css'		
			},        
			resiexchange_controllers: {
				src: 'src/controllers/*.js',
				dest: 'src/resiexchange.controllers.js'
			},            
			resiexchange_filters: {
				src: 'src/filters/*.js',
				dest: 'src/resiexchange.filters.js'
			},
			resiexchange_services: {
				src: 'src/services/*.js',
				dest: 'src/resiexchange.services.js'
			},            
			resiexchange_all: {
				src: [
                        'src/resiexchange.utils.js',                 
                        'src/resiexchange.module.js', 
                        'src/resiexchange.services.js', 
                        'src/resiexchange.routes.js', 
                        'src/resiexchange.filters.js', 
                        'src/resiexchange.controllers.js'
                    ],
				dest: 'resiexchange.js'
			}
			
		},

		cssmin: { 
			bootstrap_css: {
				src: 'assets/css/bootstrap/bootstrap.css',
				dest: 'assets/css/bootstrap.min.css'		
			},

			resiexchange_css: {
				src: 'assets/css/resiexchange.css',
				dest: 'assets/css/resiexchange.min.css'		
			}

		},
        
		uglify: {
		
			options: {
				preserveComments: 'some',
				mangle: true,
				quoteStyle: 3
			},

			resiexchange_all: {
                files: {
                    'resiexchange.min.js': ['resiexchange.js']
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
	grunt.registerTask('default', ['clean', 'concat', 'cssmin', 'uglify']);
};