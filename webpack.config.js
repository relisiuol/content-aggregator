import wpPostcssPluginsPreset from '@wordpress/postcss-plugins-preset';
import { CleanWebpackPlugin } from 'clean-webpack-plugin';
import CopyPlugin from 'copy-webpack-plugin';
import CssMinimizerPlugin from 'css-minimizer-webpack-plugin';
import fs from 'fast-glob';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import { dirname, resolve } from 'path';
import { PurgeCSSPlugin } from 'purgecss-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import { TimeAnalyticsPlugin } from 'time-analytics-webpack-plugin';
import { fileURLToPath } from 'url';
import webpack from 'webpack';
import WebpackBar from 'webpackbar';
import RemoveEmptyScriptsPlugin from 'webpack-remove-empty-scripts';

const __filename = fileURLToPath(import.meta.url);

const __dirname = dirname(__filename);

const isProduction = process.env.NODE_ENV === 'production';

const webpackConfig = TimeAnalyticsPlugin.wrap({
	entry: {
		'content-aggregator': './assets/js/content-aggregator.js',
	},

	output: {
		path: resolve(__dirname, 'dist'),
		filename: 'js/[name].min.js',
		publicPath: '../',
	},

	module: {
		rules: [
			{
				test: /\.(j|t)sx?$/,
				exclude: /node_modules/,
				loader: 'babel-loader',
				options: {
					presets: ['@wordpress/babel-preset-default'],
				},
			},
			{
				test: /.s?css$/,
				use: [
					{
						loader: MiniCssExtractPlugin.loader,
					},
					{
						loader: 'css-loader',
						options: {
							sourceMap: !isProduction,
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							postcssOptions: {
								ident: 'postcss',
								sourceMap: !isProduction,
								plugins: wpPostcssPluginsPreset,
							},
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sourceMap: !isProduction,
						},
					},
				],
			},
			{
				test: /\.(jpe?g|png|gif|webp)$/,
				type: 'asset/resource',
				generator: {
					filename: './images/[name][ext]',
				},
			},
			{
				test: /\.svg$/,
				exclude: resolve(__dirname, 'assets/fonts'),
				type: 'asset/resource',
				generator: {
					filename: './svg/[name][ext]',
				},
			},
			{
				test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
				exclude: resolve(__dirname, 'assets/svg'),
				type: 'asset/resource',
				generator: {
					filename: './fonts/[name][ext]',
				},
			},
		],
	},

	devServer: isProduction
		? undefined
		: {
				devMiddleware: {
					writeToDisk: true,
				},
				allowedHosts: 'auto',
				host: 'localhost',
				port: 8887,
				proxy: [
					{
						context: ['/'],
						target: 'http://content-aggregator.local',
					},
				],
			},

	plugins: [
		new WebpackBar(),
		new CleanWebpackPlugin(),
		new RemoveEmptyScriptsPlugin(),
		new MiniCssExtractPlugin({
			filename: 'css/[name].min.css',
		}),
		new PurgeCSSPlugin({
			paths: () =>
				fs.sync(
					[
						'./content-aggregator.php',
						'./dist/js/*',
						'./src/**/*.php',
					],
					{
						ignore: ['node_modules', 'vendor'],
					}
				),
		}),
		new CopyPlugin({
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
		}),
	],

	externals: {
		jquery: 'jQuery',
	},

	optimization: {
		minimize: true,
		minimizer: [
			new TerserPlugin({
				extractComments: false,
			}),
			new CssMinimizerPlugin(),
			new ImageMinimizerPlugin({
				minimizer: {
					implementation: ImageMinimizerPlugin.sharpMinify,
					options: {
						encodeOptions: {
							jpeg: {
								quality: 100,
								progressive: true,
							},
							webp: {
								quality: 75,
								lossless: true,
							},
							png: {
								quality: 90,
							},
							gif: {
								progressive: true,
							},
						},
					},
				},
			}),
		],
	},
});

if (isProduction) {
	webpackConfig.plugins.push(
		new webpack.optimize.TerserPlugin(),
		new CssMinimizerPlugin(),
		new ImageMinimizerPlugin({
			minimizer: {
				implementation: ImageMinimizerPlugin.sharpMinify,
				options: {
					encodeOptions: {
						jpeg: {
							quality: 100,
							progressive: true,
						},
						webp: {
							quality: 75,
							lossless: true,
						},
						png: {
							quality: 90,
						},
						gif: {
							progressive: true,
						},
					},
				},
			},
		})
	);
}

export default webpackConfig;
