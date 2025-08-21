import defaultConfig from '@wordpress/scripts/config/webpack.config.js';
import CopyPlugin from 'copy-webpack-plugin';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath( import.meta.url );
const __dirname = dirname( __filename );

export default {
	...defaultConfig,

	entry: {
		'content-aggregator': './assets/js/content-aggregator.js',
	},

	output: {
		path: resolve( __dirname, 'build' ),
		filename: 'js/[name].min.js',
		publicPath: '../',
	},

	plugins: [
		...defaultConfig.plugins.filter(
			( p ) =>
				p?.constructor?.name !== 'MiniCssExtractPlugin' &&
				p?.constructor?.name !== 'RtlCssPlugin'
		),
		new MiniCssExtractPlugin( {
			filename: 'css/[name].min.css',
		} ),
		new CopyPlugin( {
			patterns: [
				{
					from: 'node_modules/select2/dist/css/select2.min.css',
					to: 'css/select2.min.css',
				},
				{
					from: 'node_modules/select2/dist/js/select2.min.js',
					to: 'js/select2.min.js',
				},
			],
		} ),
	],
};
