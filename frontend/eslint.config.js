import js from '@eslint/js'
import prettier from '@vue/eslint-config-prettier'
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript'
import pluginVue from 'eslint-plugin-vue'

export default defineConfigWithVueTs(
  { ignores: ['dist/**', 'node_modules/**'] },
  js.configs.recommended,
  pluginVue.configs['flat/recommended'],
  vueTsConfigs.recommended,
  prettier,
  {
    rules: {
      'vue/multi-word-component-names': 'off',
      // TypeScript optional props already say "may be absent"; requiring a
      // runtime default as well just adds noise.
      'vue/require-default-prop': 'off',
      '@typescript-eslint/no-explicit-any': 'error',
      // Renderer methods share one signature; not every override needs the
      // context, and a leading underscore says so.
      '@typescript-eslint/no-unused-vars': [
        'error',
        { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
      ],
    },
  },
)
