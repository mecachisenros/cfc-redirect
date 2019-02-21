const path = require( 'path' )
const webpack = require( 'webpack' )
const { VueLoaderPlugin } = require( 'vue-loader' )
const UglifyJSPlugin = require( 'uglifyjs-webpack-plugin' )
const BrowserSyncPlugin = require( 'browser-sync-webpack-plugin' )

/**
 * Path constants.
 */
const PLUGIN_PATH =  path.resolve( __dirname )
const PROXY_TARGET = 'cfc044.andreimondoc.com' // remote server for proxy, remove if developing locally
const SRC = path.resolve( PLUGIN_PATH, 'assets/src' ) // source path
const BUILD = path.resolve( PLUGIN_PATH, 'assets/dist' ) // build path

/**
 * Webpack config.
 */
module.exports = {
	entry: {
		public: path.resolve( SRC, 'app.js' ) // entry file
	},
	output: {
		path: BUILD,
		filename: 'bundle.js', // output file
		publicPath: path.join( path.relative( '../../../', 'assets/dist/' ), '/' )
	},
	module: {
		rules: [
			{
				test: /\.vue$/,
				exclude: /node_modules/,
				loader: 'vue-loader'
			},
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loader: 'babel-loader',
				options: {
					plugins: [
						'@babel/plugin-transform-runtime',
						'@babel/syntax-dynamic-import'
					],
					presets: ['@babel/preset-env']
				}
			},
			{
				test: /\.css$/,
				use: [
					'vue-style-loader',
					'css-loader'
				]
			}
		]
	},
	resolve: {
		alias:{
			Utils: path.resolve( SRC, 'Utils/' ),
			Components: path.resolve( SRC, 'components/' )
		},
		extensions: [ '.js', '.vue' ]
	},
	plugins: [
		new VueLoaderPlugin(),
		new UglifyJSPlugin(),
		new BrowserSyncPlugin( {
			port: 3000,
			files: [ `${SRC}/*.js`, `${SRC}/**/*.vue`, `${PLUGIN_PATH}/**/*.php` ],
			proxy: {
				target: PROXY_TARGET, // remove if developing locally
			},
			open: false,
		} )
	]
}