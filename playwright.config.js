import { createRequire } from 'node:module';

process.env.WP_BASE_URL ??= 'http://localhost:8888';

const require = createRequire( import.meta.url );
const defaultConfig = require( '@wordpress/scripts/config/playwright.config.js' );

export default {
	...defaultConfig,
	use: {
		...defaultConfig.use,
		baseURL: 'http://localhost:8888/',
	},
	webServer: {
		...defaultConfig.webServer,
		command: 'pnpm run build && pnpm run wp-env start',
		port: 8888,
		timeout: 240_000,
	},
};
