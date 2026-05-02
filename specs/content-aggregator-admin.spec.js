import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

const TEST_IMAGE =
	'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

const runWpCli = ( ...args ) => {
	return execFileSync(
		'pnpm',
		[ 'exec', 'wp-env', 'run', 'tests-cli', 'wp', ...args ],
		{
			cwd: process.cwd(),
			encoding: 'utf8',
		}
	);
};

const extractWpCliField = ( output ) => {
	return output
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.find( ( line ) => /^\d+$/.test( line ) );
};

const extractWpCliJson = ( output ) => {
	const json = output
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.find( ( line ) => line.startsWith( '{' ) || line.startsWith( '[' ) );

	return JSON.parse( json );
};

const createTestImage = ( filePath ) => {
	fs.writeFileSync( filePath, Buffer.from( TEST_IMAGE, 'base64' ) );
};

const insertSource = ( source ) => {
	const encodedSource = Buffer.from( JSON.stringify( source ) ).toString(
		'base64'
	);

	runWpCli(
		'eval',
		`$source = json_decode(base64_decode('${ encodedSource }'), true); global $wpdb; $wpdb->insert($wpdb->prefix . 'content_aggregator_sources', $source);`
	);
};

test.describe( 'Content Aggregator admin', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await requestUtils.deleteAllMedia();
		runWpCli( 'db', 'query', 'DELETE FROM wp_content_aggregator_sources' );
	} );

	test( 'loads the sources screen and add source form', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( 'admin.php', 'page=content-aggregator' );

		await expect(
			page.getByRole( 'heading', { name: /Sources/i } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Add', exact: true } )
		).toHaveAttribute( 'href', /page=content-aggregator-add-edit/ );

		await admin.visitAdminPage(
			'admin.php',
			'page=content-aggregator-add-edit'
		);

		await expect(
			page.getByRole( 'heading', { name: /Add source/i } )
		).toBeVisible();
		await expect(
			page.locator( '#content_aggregator_source-name' )
		).toBeVisible();
		await expect(
			page.locator( '#content_aggregator_source-url' )
		).toBeVisible();
		await expect(
			page.locator( '#content_aggregator_source-scrap_url' )
		).toBeVisible();
		await expect(
			page.locator( '#content_aggregator_source-type' )
		).toBeVisible();
		await expect(
			page.locator( '#content_aggregator_source-categories + .select2' )
		).toBeVisible();
	} );

	test( 'shows validation errors when submitting an empty source', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=content-aggregator-add-edit'
		);

		await page.getByRole( 'button', { name: 'Save' } ).click();

		await expect( page.getByText( 'Name is required.' ) ).toBeVisible();
		await expect(
			page.getByText( 'URL is missing or invalid.', { exact: true } )
		).toBeVisible();
		await expect(
			page.getByText( 'Source URL is missing or invalid.' )
		).toBeVisible();
	} );

	test( 'auto-detect fills the source type and source URL', async ( {
		admin,
		page,
	} ) => {
		await page.route(
			'**/wp-admin/admin-ajax.php',
			async ( route, request ) => {
				const data = new URLSearchParams( request.postData() || '' );
				if ( data.get( 'action' ) !== 'content_aggregator' ) {
					await route.continue();
					return;
				}

				await route.fulfill( {
					contentType: 'application/json',
					body: JSON.stringify( {
						success: true,
						data: {
							type: '1',
							url: 'https://example.com/wp-json/wp/v2/posts?_embed',
						},
					} ),
				} );
			}
		);

		await admin.visitAdminPage(
			'admin.php',
			'page=content-aggregator-add-edit'
		);

		await page
			.locator( '#content_aggregator_source-url' )
			.fill( 'https://example.com' );

		await expect(
			page.locator( '#content_aggregator_source-type' )
		).toHaveValue( '1' );
		await expect(
			page.locator( '#content_aggregator_source-scrap_url' )
		).toHaveValue( 'https://example.com/wp-json/wp/v2/posts?_embed' );
		await expect(
			page.locator( '#content_aggregator_source-type option:checked' )
		).toHaveText( 'WordPress' );
	} );

	test( 'creates a source from the admin form', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const imagePath = test.info().outputPath( 'featured-image.png' );
		createTestImage( imagePath );
		const image = await requestUtils.uploadMedia( imagePath );
		const sourceName = `E2E source ${ Date.now() }`;
		const userAgent = 'Content-Aggregator-E2E/1.0';

		await admin.visitAdminPage(
			'admin.php',
			'page=content-aggregator-add-edit'
		);
		await page
			.locator( '#content_aggregator_source-name' )
			.fill( sourceName );
		await page
			.locator( '#content_aggregator_source-url' )
			.fill( 'http://tests-wordpress' );
		await page
			.locator( '#content_aggregator_source-scrap_url' )
			.fill( 'http://tests-wordpress?rest_route=/wp/v2/posts&_embed' );
		await page
			.locator( '#content_aggregator_source-type' )
			.selectOption( '1' );
		await page
			.locator( '#content_aggregator_source-user_agent' )
			.fill( userAgent );
		await page
			.locator( '#content_aggregator_source-categories' )
			.selectOption( '1' );
		await page
			.locator(
				'input[name="content_aggregator_source[featured_image]"]'
			)
			.evaluate( ( input, id ) => {
				input.value = String( id );
			}, image.id );
		await page.locator( '#content_aggregator_source-enabled' ).check();
		await page.getByRole( 'button', { name: 'Save' } ).click();

		await admin.visitAdminPage( 'admin.php', 'page=content-aggregator' );
		await expect(
			page.getByRole( 'link', { name: sourceName } )
		).toBeVisible();

		const encodedSourceName =
			Buffer.from( sourceName ).toString( 'base64' );
		const persistedSource = extractWpCliJson(
			runWpCli(
				'eval',
				`$name = base64_decode('${ encodedSourceName }'); global $wpdb; echo wp_json_encode($wpdb->get_row($wpdb->prepare("SELECT type, unique_title, user_agent, enabled FROM {$wpdb->prefix}content_aggregator_sources WHERE name = %s", $name), ARRAY_A));`
			)
		);

		expect( persistedSource ).toMatchObject( {
			type: '1',
			unique_title: '0',
			user_agent: userAgent,
			enabled: '1',
		} );
	} );

	test( 'edits an existing source from the admin form', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const imagePath = test.info().outputPath( 'edited-featured-image.png' );
		createTestImage( imagePath );
		const image = await requestUtils.uploadMedia( imagePath );
		const originalName = `Editable source ${ Date.now() }`;
		const updatedName = `${ originalName } updated`;
		const updatedUserAgent = 'Content-Aggregator-Edited-E2E/1.0';

		insertSource( {
			name: originalName,
			url: 'http://tests-wordpress',
			scrap_url: 'http://tests-wordpress?rest_route=/wp/v2/posts&_embed',
			unique_title: 0,
			type: '1',
			user_agent: 'Content-Aggregator-Original-E2E/1.0',
			categories: '1',
			post_status: 'publish',
			post_title_template: '__TITLE__',
			post_date_template: '__DATE__',
			content_template: '__CONTENT__',
			featured_image: image.id,
			last_check: '2000-01-01 00:00:00',
			last_news: '',
			redirect: 0,
			enabled: 1,
		} );

		const sourceId = extractWpCliField(
			runWpCli(
				'db',
				'query',
				`SELECT id FROM wp_content_aggregator_sources WHERE name = '${ originalName }';`,
				'--skip-column-names'
			)
		);

		await admin.visitAdminPage(
			'admin.php',
			`page=content-aggregator-add-edit&id=${ sourceId }`
		);
		await page
			.locator( '#content_aggregator_source-name' )
			.fill( updatedName );
		await page.locator( '#content_aggregator_source-unique_title' ).check();
		await page
			.locator( '#content_aggregator_source-user_agent' )
			.fill( updatedUserAgent );
		await page.locator( '#content_aggregator_source-enabled' ).uncheck();
		await page.getByRole( 'button', { name: 'Save' } ).click();

		const persistedSource = extractWpCliJson(
			runWpCli(
				'eval',
				`global $wpdb; echo wp_json_encode($wpdb->get_row($wpdb->prepare("SELECT name, unique_title, user_agent, enabled FROM {$wpdb->prefix}content_aggregator_sources WHERE id = %d", ${ sourceId }), ARRAY_A));`
			)
		);

		expect( persistedSource ).toMatchObject( {
			name: updatedName,
			unique_title: '1',
			user_agent: updatedUserAgent,
			enabled: '0',
		} );
	} );

	test( 'imports posts from a WordPress JSON source when cron runs', async ( {
		requestUtils,
	} ) => {
		const imagePath = test.info().outputPath( 'fallback-image.png' );
		createTestImage( imagePath );
		const image = await requestUtils.uploadMedia( imagePath );
		const title = `Imported item ${ Date.now() }`;
		const sourceDate = '2026-04-01T10:00:00';

		await requestUtils.createPost( {
			title,
			content: '<p>Imported body</p>',
			status: 'publish',
			date_gmt: sourceDate,
		} );

		const source = {
			name: 'E2E import source',
			url: 'http://tests-wordpress',
			scrap_url: 'http://tests-wordpress?rest_route=/wp/v2/posts&_embed',
			unique_title: 0,
			type: '1',
			user_agent:
				'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
			categories: '1',
			post_status: 'publish',
			post_title_template: '__DATE__ :: __TITLE__',
			post_date_template: '__DATE__',
			content_template: '__NOW__ :: __DATE__ :: __CONTENT__',
			featured_image: image.id,
			last_check: '2000-01-01 00:00:00',
			last_news: '',
			redirect: 0,
			enabled: 1,
		};
		insertSource( source );
		runWpCli(
			'eval',
			'\\Content_Aggregator\\Cron::get_instance()->execute_cron_job();'
		);

		const importedPostId = extractWpCliField(
			runWpCli(
				'post',
				'list',
				'--post_type=post',
				'--meta_key=content_aggregator_source',
				'--field=ID'
			)
		);

		expect( importedPostId ).toBeDefined();
		expect(
			runWpCli( 'post', 'get', importedPostId, '--field=post_title' )
		).toContain( `${ sourceDate } :: ${ title }` );
		const importedContent = runWpCli(
			'post',
			'get',
			importedPostId,
			'--field=post_content'
		);
		expect( importedContent ).toContain( 'Imported body' );
		expect( importedContent ).toContain( `:: ${ sourceDate } ::` );
	} );

	test( 'imports posts from an RSS source when cron runs', async ( {
		requestUtils,
	} ) => {
		const imagePath = test.info().outputPath( 'rss-fallback-image.png' );
		createTestImage( imagePath );
		const image = await requestUtils.uploadMedia( imagePath );
		const title = 'E2E RSS fixture item';

		insertSource( {
			name: 'E2E RSS source',
			url: 'http://tests-wordpress',
			scrap_url:
				'http://tests-wordpress/wp-content/plugins/content-aggregator/specs/fixtures/rss.xml',
			unique_title: 0,
			type: '0',
			user_agent:
				'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
			categories: '1',
			post_status: 'publish',
			post_title_template: '__TITLE__',
			post_date_template: '__DATE__',
			content_template: '__CONTENT__',
			featured_image: image.id,
			last_check: '2000-01-01 00:00:00',
			last_news: '',
			redirect: 0,
			enabled: 1,
		} );

		runWpCli(
			'eval',
			'\\Content_Aggregator\\Cron::get_instance()->execute_cron_job();'
		);

		const importedPostId = extractWpCliField(
			runWpCli(
				'post',
				'list',
				'--post_type=post',
				'--meta_key=content_aggregator_source',
				'--field=ID'
			)
		);

		expect( importedPostId ).toBeDefined();
		expect(
			runWpCli( 'post', 'get', importedPostId, '--field=post_title' )
		).toContain( title );
		expect(
			runWpCli( 'post', 'get', importedPostId, '--field=post_content' )
		).toContain( 'RSS fixture body' );
	} );

	test( 'keeps an empty source enabled when expiration is never', async () => {
		runWpCli(
			'option',
			'update',
			'content_aggregator_settings',
			'{"update_interval":"1h","max_update":10,"expiration_date":"never","certificate_path":""}',
			'--format=json'
		);
		insertSource( {
			name: 'E2E never expires source',
			url: 'http://tests-wordpress',
			scrap_url: 'http://tests-wordpress?rest_route=/wp/v2/missing',
			unique_title: 0,
			type: '1',
			user_agent:
				'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
			categories: '1',
			post_status: 'publish',
			post_title_template: '__TITLE__',
			post_date_template: '__DATE__',
			content_template: '__CONTENT__',
			featured_image: 1,
			last_check: '2000-01-01 00:00:00',
			last_news: '',
			redirect: 0,
			enabled: 1,
		} );

		runWpCli(
			'eval',
			'\\Content_Aggregator\\Cron::get_instance()->execute_cron_job();'
		);

		const enabled = extractWpCliField(
			runWpCli(
				'db',
				'query',
				'SELECT enabled FROM wp_content_aggregator_sources WHERE name = "E2E never expires source";',
				'--skip-column-names'
			)
		);

		expect( enabled ).toBe( '1' );
	} );
} );
