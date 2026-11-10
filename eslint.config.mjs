import eslint from '@eslint/js'
import { defineConfig, globalIgnores } from 'eslint/config'
import jsdoc from 'eslint-plugin-jsdoc'
import eslintPluginPrettier from 'eslint-plugin-prettier'
import simpleImportSort from 'eslint-plugin-simple-import-sort'
import globals from 'globals'
import tseslint from 'typescript-eslint'

export default defineConfig(
  globalIgnores([
    '**/node_modules/**',
    '**/vendor/**',
    '**/coverage/**',
    '**/playwright-report/**',
    '**/test-results/**',
    'public/**',
    'site/**',
    'var/**'
  ]),

  eslint.configs.recommended,

  {
    files: ['**/*.ts'],
    extends: [
      tseslint.configs.strictTypeChecked,
      tseslint.configs.stylisticTypeChecked
    ],
    languageOptions: {
      parserOptions: {
        projectService: true,
        tsconfigRootDir: import.meta.dirname
      }
    },
    rules: {
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/explicit-module-boundary-types': 'error',
      '@typescript-eslint/consistent-type-imports': 'error'
    }
  },

  {
    files: ['**/*.mjs'],
    languageOptions: { globals: { ...globals.node } }
  },

  {
    plugins: {
      prettier: eslintPluginPrettier,
      jsdoc,
      'simple-import-sort': simpleImportSort
    },
    rules: {
      ...eslintPluginPrettier.configs.recommended.rules,
      'prettier/prettier': 'error',
      'no-restricted-exports': [
        'error',
        {
          restrictDefaultExports: {
            direct: true,
            named: true,
            defaultFrom: true
          }
        }
      ],
      'simple-import-sort/imports': 'error',
      'simple-import-sort/exports': 'error'
    }
  },

  {
    files: ['frontend/src/**/*.ts'],
    rules: {
      'jsdoc/require-jsdoc': [
        'error',
        {
          publicOnly: true,
          require: { FunctionDeclaration: true, ArrowFunctionExpression: false }
        }
      ],
      'jsdoc/check-syntax': 'error',
      'jsdoc/no-bad-blocks': 'error',
      'jsdoc/no-blank-blocks': 'error',
      'jsdoc/require-asterisk-prefix': 'error',
      'jsdoc/sort-tags': 'error'
    }
  },

  {
    // Tool configuration files must use default exports.
    files: [
      '*.config.ts',
      '*.config.mjs',
      'eslint.config.mjs',
      'commitlint.config.mjs'
    ],
    rules: { 'no-restricted-exports': 'off' }
  },

  {
    files: ['frontend/tests/**/*.ts', 'e2e/**/*.ts'],
    rules: {
      '@typescript-eslint/no-non-null-assertion': 'off',
      '@typescript-eslint/restrict-template-expressions': 'off'
    }
  }
)
