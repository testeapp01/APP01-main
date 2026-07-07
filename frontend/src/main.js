import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import './styles.css'
import api from './services/api'
import router from './router'
import ToastProvider from './components/ToastProvider.vue'
import BaseButton from './components/ui/BaseButton.vue'
import BaseCard from './components/ui/BaseCard.vue'
import BaseTable from './components/ui/BaseTable.vue'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)

// register small global components
app.component('ToastProvider', ToastProvider)
app.component('BaseButton', BaseButton)
app.component('BaseCard', BaseCard)
app.component('BaseTable', BaseTable)

// provide configured api instance globally
app.config.globalProperties.$api = api
app.provide('api', api)

// Theme persistence: read from localStorage and apply dark class
const savedTheme = localStorage.getItem('theme')
if (savedTheme === 'dark') {
	document.documentElement.classList.add('dark')
}

app.config.globalProperties.$theme = {
	current: savedTheme || 'light',
	toggle() {
		const isDark = document.documentElement.classList.toggle('dark')
		this.current = isDark ? 'dark' : 'light'
		localStorage.setItem('theme', this.current)
	}
}

const auth = useAuthStore(pinia)
auth.hydrateSession().finally(() => {
	app.mount('#app')
})
