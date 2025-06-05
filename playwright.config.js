/**
 * External dependencies
 */

import path from 'path';
import { defineConfig, devices } from '@playwright/test';

process.env.WP_ARTIFACTS_PATH ??= path.join(process.cwd(), 'artifacts');
process.env.STORAGE_STATE_PATH ??= path.join(
	process.env.WP_ARTIFACTS_PATH,
	'storage-states/admin.json'
);

const config = defineConfig({
	reporter: process.env.CI ? [['github']] : [['list']],
	forbidOnly: !!process.env.CI,
	workers: 1,
	retries: process.env.CI ? 2 : 0,
	timeout: parseInt(process.env.TIMEOUT || '', 10) || 100_000,
	reportSlowTests: null,
	testDir: './specs',
	outputDir: path.join(process.env.WP_ARTIFACTS_PATH, 'test-results'),
	snapshotPathTemplate:
		'{testDir}/{testFileDir}/__snapshots__/{arg}-{projectName}{ext}', // TODO: may be better named
	globalSetup: path.join('./tests/global-setup.js'),
	use: {
		baseURL: 'http://content-aggregator.js',
		port: 3000,
		headless: true,
		viewport: {
			width: 960,
			height: 700,
		},
		ignoreHTTPSErrors: true,
		locale: 'fr-FR',
		contextOptions: {
			reducedMotion: 'reduce',
			strictSelectors: true,
		},
		storageState: process.env.STORAGE_STATE_PATH,
		actionTimeout: 10_000,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
	},
	webServer: {
		command: 'npm run dev',
		port: 3000,
		timeout: 120_000,
		reuseExistingServer: true,
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
});

export default config;
