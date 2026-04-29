import defaultConfig from '@wordpress/scripts/config/eslint.config.cjs';

export default [
	...defaultConfig,
	{
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'content-aggregator' },
			],
		},
	},
];
