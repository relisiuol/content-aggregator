import { createRequire } from 'node:module';

const require = createRequire( import.meta.url );
const defaultConfig = require( '@wordpress/scripts/config/playwright.config.js' );

export default {
	...defaultConfig,
	webServer: {
		...defaultConfig.webServer,
		command: 'pnpm run build && pnpm run wp-env start',
		timeout: 240_000,
	},
};
