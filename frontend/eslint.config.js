import pluginVue from 'eslint-plugin-vue'

export default [
  ...pluginVue.configs['flat/recommended'],
  {
    files: ['**/*.{js,mjs,cjs,vue}'],
    rules: {
      'no-console': 'off',
      'vue/multi-word-component-names': 'off'
    }
  },
  {
    ignores: ['dist/**', 'node_modules/**', 'test-results/**']
  }
]
