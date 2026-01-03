// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
	integrations: [
		starlight({
			title: 'Valicomb',
			expressiveCode: {
				themes: ['github-dark', 'github-light'],
			},
			customCss: ['./src/styles/custom.css'],
			head: [
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
						{ label: 'Introduction', slug: 'getting-started/introduction' },
						{ label: 'Installation', slug: 'getting-started/installation' },
                        { label: 'Core Concepts', slug: 'getting-started/core-concepts' },
						{ label: 'Quick Start', slug: 'getting-started/quick-start' },
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
						{ label: 'Defining Rules', slug: 'guides/defining-rules' },
						{ label: 'Custom Rules', slug: 'guides/custom-rules' },
						{ label: 'Error Messages', slug: 'guides/error-messages' },
						{ label: 'Internationalization', slug: 'guides/i18n' },
						{ label: 'Nested & Array Fields', slug: 'guides/nested-arrays' },
						{ label: 'Security Features', slug: 'guides/security' },
					],
				},
				{
					label: 'API Reference',
					items: [
						{ label: 'Validator Class', slug: 'api/validator' },
						{ label: 'Rule Registry', slug: 'api/rule-registry' },
						{ label: 'Error Manager', slug: 'api/error-manager' },
						{ label: 'Language Manager', slug: 'api/language-manager' },
					],
				},
				{
					label: 'Development',
					items: [
						{ label: 'Composer Commands', slug: 'development/commands' },
						{ label: 'Contributing', slug: 'development/contributing' },
						{ label: 'Changelog', slug: 'development/changelog' },
					],
				},
			],
		}),
	],
});
