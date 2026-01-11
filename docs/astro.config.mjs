// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';


// https://astro.build/config
export default defineConfig({
    site: 'https://frostybee.github.io',
    base: '/valicomb',
	integrations: [
		starlight({
			title: 'Valicomb',
            favicon: '/favicon.svg',
			customCss: ['./src/styles/custom.css'],
			head: [
				{
					tag: 'link',
					attrs: {
						rel: 'preconnect',
						href: 'https://fonts.googleapis.com',
					},
				},
				{
					tag: 'link',
					attrs: {
						rel: 'preconnect',
						href: 'https://fonts.gstatic.com',
						crossorigin: true,
					},
				},
				{
					tag: 'link',
					attrs: {
						rel: 'stylesheet',
						href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap',
					},
				},
				{
					tag: 'script',
					content: `
						(function() {
							if (!localStorage.getItem('starlight-theme')) {
								localStorage.setItem('starlight-theme', 'dark');
							}
						})();
					`,
				},
			],
			description: 'Simple, Modern PHP Validation Library',
			social: [
				{
					icon: 'github',
					label: 'GitHub',
					href: 'https://github.com/frostybee/valicomb',
				},
			],
			sidebar: [
				{
					label: 'Getting Started',
					items: [
						{ label: 'Installation', slug: 'getting-started/installation' },
						{ label: 'Quick Start', slug: 'getting-started/quick-start' },
						{ label: 'Core Concepts', slug: 'getting-started/core-concepts' },
						{ label: 'Defining Rules', slug: 'getting-started/defining-rules' },
						{ label: 'Fluent API', slug: 'getting-started/fluent-api' },
					],
				},
				{
					label: 'Validation Rules',
					items: [
						{ label: 'Overview', slug: 'rules/overview' },
						{ label: 'String Rules', slug: 'rules/string' },
						{ label: 'Numeric Rules', slug: 'rules/numeric' },
						{ label: 'Network Rules', slug: 'rules/network' },
						{ label: 'Array Rules', slug: 'rules/array' },
						{ label: 'Date Rules', slug: 'rules/date' },
						{ label: 'Length Rules', slug: 'rules/length' },
						{ label: 'Comparison Rules', slug: 'rules/comparison' },
						{ label: 'Conditional Rules', slug: 'rules/conditional' },
						{ label: 'Type Rules', slug: 'rules/type' },
					],
				},
				{
					label: 'Guides',
					items: [
						{ label: 'Custom Rules', slug: 'guides/custom-rules' },
						{ label: 'Error Messages', slug: 'guides/error-messages' },
						{ label: 'Internationalization', slug: 'guides/i18n' },
						{ label: 'Nested & Array Fields', slug: 'guides/nested-arrays' },
						{ label: 'Security Features', slug: 'guides/security' },
					],
				},
				{
					label: 'Development',
					items: [
						{ label: 'Composer Commands', slug: 'development/commands' },
						{ label: 'Contributing', slug: 'development/contributing' },
						{ label: 'Changelog', slug: 'development/changelog' },
						{ label: 'Valitron Issues Fixed', slug: 'development/valitron-fixes' },
					],
				},
			],
		}),
	],
});
