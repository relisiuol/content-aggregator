import defaultConfig from '@wordpress/scripts/config/webpack.config.js';
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
		path: resolve( __dirname, 'dist' ),
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
	],
};
