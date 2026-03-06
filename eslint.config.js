import globals from 'globals';
import pluginJs from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

export default [
  {
    languageOptions: {
      globals: globals.browser,
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
      },
    },
  },
  pluginJs.configs.recommended,
  ...pluginVue.configs['flat/essential'],
  {
    // Override rules that produce false positives in this codebase
    rules: {
      // <Link> (Inertia / Vue Router) is parsed as void by HTML linter — disable
      'vue/no-parsing-error': ['error', { 'x-invalid-end-tag': false }],
      // Vue 3 allows :key on <template v-for> — disable Vue-2-only rule
      'vue/no-template-key': 'off',
      // Named v-model (v-model:content) is valid in Vue 3 — disable
      'vue/valid-v-model': 'off',
    },
    languageOptions: {
      parserOptions: {
        ecmaVersion: 'latest',
      },
    },
  },
];
