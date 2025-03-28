/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * WordPress dependencies
 */
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 *
 * @param {import('@playwright/test').FullConfig} config
 * @return {Promise<void>}
 */
async function globalSetup(config) {
	const { storageState, baseURL } = config.projects[0].use;
	const storageStatePath =
		typeof storageState === 'string' ? storageState : undefined;
	const requestContext = await request.newContext({
		baseURL,
	});
	const requestUtils = new RequestUtils(requestContext, {
		storageStatePath,
	});
	await requestUtils.setupRest();
	await requestContext.dispose();
}

export default globalSetup;
