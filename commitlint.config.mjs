export default {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'subject-case': [0],
    'body-max-line-length': [0],
    'footer-max-line-length': [0],
    'scope-enum': [
      2,
      'always',
      [
        'keys',
        'ui',
        'actions',
        'nav',
        'config',
        'i18n',
        'install',
        'api',
        'e2e',
        'ci',
        'docs',
        'agents',
        'deps',
        'release',
        'main'
      ]
    ]
  }
}
