module.exports = function( grunt ) {
    'use strict';
    var pkgInfo = grunt.file.readJSON('package.json');
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        rtlcss: {
            options: {
                // rtlcss options
                config: {
                    preserveComments: true,
                    greedy: true
                },
                // generate source maps
                map: false
            },
            dist: {
                files: [
                    { // Front end compatibility
                        expand: true,
                        cwd: '',
                        src: [
                            '*.css',
                            '!*.min.css',
                            '!*-rtl.css'
                        ],
                        dest: '',
                        ext: '-rtl.css'
                    },
                    { // Front end compatibility
                        expand: true,
                        cwd: 'assets/css',
                        src: [
                            '*.css',
                            '!*.min.css',
                            '!*-rtl.css'
                        ],
                        dest: 'assets/css',
                        ext: '-rtl.css'
                    }
                ]
            }
        },

        // SASS
        sass: {
            options: {
                precision: 10,
                unixNewlines: true
                //noCache: true
                //sourcemap: 'auto'
            },
            dist: {
                options: {
                    style: 'expanded',
                   // sourcemap: 'auto'
                },

                files: [
					{
						"assets/css/admin.css": "assets/scss/admin.scss",
						"assets/css/frontend.css": "assets/scss/frontend.scss",
					}
                ]
            }
        },

        // Minified all css files.
        cssmin: {
            target: {
                files: [
					{
						expand: true,
						cwd: 'assets/css',
						src: ['*.css', '!*.min.css'],
						dest: 'assets/css',
						ext: '.min.css'
					} 
                ]
            }
        },

        uglify: {
            my_target: {
                files: [
					{
						'assets/js/frontend.min.js': ['assets/js/frontend.js']
					}
                ]
            }
		},
		
        // Watch changes for assets.
        watch: {
			css: {
				files: [
					"assets/scss/**/*.scss",
					"assets/scss/*.scss",
				],
				tasks: [
					'sass'
				]
			}
        },

        copy: {
            main: {
                options: {
                    mode: true
                },
                src: [
                    '**',
                    '!node_modules/**',
                    '!build/**',
                    '!css/sourcemap/**',
                    '!.git/**',
                    '!bin/**',
                    '!.gitlab-ci.yml',
                    '!bin/**',
                    '!tests/**',
                    '!phpunit.xml.dist',
                    '!*.sh',
                    '!*.map',
                    '!Gruntfile.js',
                    '!package.json',
                    '!.gitignore',
                    '!phpunit.xml',
                    '!README.md',
                    '!sass/**',
                    '!codesniffer.ruleset.xml',
                    '!vendor/**',
                    '!composer.json',
                    '!composer.lock',
                    '!package-lock.json',
                    '!phpcs.xml.dist'
                ],
                dest: 'press-search/'
            }
        },

        compress: {
            main: {
                options: {
					archive: 'press-search-' + pkgInfo.version + '.zip',
					mode: 'zip'
                },
                files: [
                    {
                        src: [
                            './press-search/**'
                        ]
                    }
                ]
            }
        },

        clean: {
            main: ["press-search"],
            zip: ["*.zip"]

        },

        makepot: {
            target: {
                options: {
					domainPath: '/',
					potFilename: 'languages/press-search.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
                }
            }
        },

        addtextdomain: {
            options: {
				textdomain: 'press-search'
            },
            target: {
                files: {
                    src: [
                        '*.php',
                        '**/*.php',
                        '!node_modules/**',
                        '!php-tests/**',
                        '!bin/**',
                    ]
                }
            }
        },

        bumpup: {
            options: {
                updateProps: {
                    pkg: 'package.json'
                }
            },
            file: 'package.json'
        },

		replace: {
            theme_main: {
                src: ['press-search.php'],
                overwrite: true,
                replacements: [
                    {
                        from: /Version: \bv?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?(?:\+[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\b/g,
                        to: 'Version: <%= pkg.version %>'
                    }
                ]
            }
        }

    });


	// Load NPM tasks to be used here
	grunt.loadNpmTasks('grunt-autoprefixer');
    grunt.loadNpmTasks( 'grunt-contrib-watch' );
    grunt.loadNpmTasks( 'grunt-postcss' );
    grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-rtlcss');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-bumpup');
	grunt.loadNpmTasks('grunt-text-replace');


	// Register tasks
	grunt.registerTask('default', [
		'watch',
		'css'
	]);
	grunt.registerTask( 'css', [
		'sass'
	]);


	// To release new version just runt 2 commands below
	// Re create everything: grunt release --ver=<version_number>
	// Zip file installable: grunt zipfile

	grunt.registerTask('zipfile', ['clean:zip', 'copy:main', 'compress:main', 'clean:main']);
	grunt.registerTask('release', function (ver) {
		var newVersion = grunt.option('ver');
		if (newVersion) {
			// Replace new version
			newVersion = newVersion ? newVersion : 'patch';
			grunt.task.run('bumpup:' + newVersion);
			grunt.task.run('replace');

			// i18n
			grunt.task.run(['addtextdomain', 'makepot']);
			// re create css file and min
			grunt.task.run([ 'css', 'uglify', 'rtlcss', 'cssmin' ]);
		}
	});

	grunt.registerTask('re-css', function (ver) {
		grunt.task.run([ 'css', 'uglify', 'rtlcss', 'cssmin' ]);
	});

};