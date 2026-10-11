import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'CakePHP Comments',
  description: 'Comments for CakePHP applications: installation, forms, access rules and model integration.',
  base: '/cakephp-comments/',
  cleanUrls: true,
  srcExclude: ['README.md'],
  head: [['link', { rel: 'icon', type: 'image/svg+xml', href: '/cakephp-comments/favicon.svg' }]],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'Reference', link: '/reference/configuration' },
    ],
    sidebar: [
      { text: 'Guide', items: [
        { text: 'Installation', link: '/guide/installation' },
        { text: 'Getting started', link: '/guide/getting-started' },
        { text: 'Comment component', link: '/guide/component' },
        { text: 'Access and identity', link: '/guide/access' },
      ] },
      { text: 'Reference', items: [
        { text: 'Configuration', link: '/reference/configuration' },
        { text: 'Models and migrations', link: '/reference/models' },
        { text: 'Admin', link: '/reference/admin' },
        { text: 'Upgrading', link: '/reference/upgrading' },
      ] },
    ],
    search: { provider: 'local' },
    socialLinks: [{ icon: 'github', link: 'https://github.com/dereuromark/cakephp-comments' }],
    editLink: { pattern: 'https://github.com/dereuromark/cakephp-comments/edit/cake6/docs/:path' },
  },
})
