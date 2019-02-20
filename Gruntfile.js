module.exports = function (grunt) {

	// require `load-grunt-tasks`, which loads all grunt tasks defined in package.json
	require('load-grunt-tasks')(grunt);
	// load tasks defined in the `/grunt-configs` folder
	grunt.loadTasks('grunt-configs');

	// Function to load the options for each grunt module
	var loadConfig = function (path) {
		var glob = require('glob');
		var object = {};
		var key;

		glob.sync('*', {cwd: path}).forEach(function(option) {
			key = option.replace(/\.js$/,'');
			object[key] = require(path + option);
		});

		return object;
	};

	var config = {
		pkg: grunt.file.readJSON('package.json'),
		env: process.env
	};

	grunt.util._.extend(config, loadConfig('./grunt-configs/options/'));

	grunt.initConfig(config);
};


// module.exports = function(grunt) {
// 	grunt.initConfig({
// 		sass: {
// 			dist: {
// 				files: {
// 					"assets/css/admin.css": "assets/scss/admin.scss",
// 					"assets/css/frontend.css": "assets/scss/frontend.scss",
// 				}
// 			}
// 		},
// 		watch: {
// 			css: {
// 				files: [
// 					"assets/scss/**/*.scss",
// 					"assets/scss/*.scss",
// 				],
// 				tasks: ["sass"]
// 			}
// 		}
// 	});
// 	grunt.loadNpmTasks("grunt-contrib-sass");
// 	grunt.loadNpmTasks("grunt-contrib-watch");
// 	grunt.registerTask("default", [
// 		'watch',
// 		'css'
// 	]);
// 	grunt.registerTask( 'css', [
// 		'sass'
//     ]);
// };
