import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

export function useAuth() {
  const auth = useAuthStore()
  const router = useRouter()
  const route = useRoute()

  const form = reactive({
    email: '',
    password: '',
  })

  const errors = reactive({
    email: '',
    password: '',
  })

  const touched = reactive({
    email: false,
    password: false,
  })

  const remember = ref(false)
  const showPassword = ref(false)
  const isSubmitting = ref(false)
  const errorMessage = ref('')

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  const canSubmit = computed(() => form.email.trim() !== '' && form.password !== '')

  const validateEmail = () => {
    const value = form.email.trim()
    if (!value) {
      errors.email = 'Informe seu e-mail.'
      return false
    }
    if (!emailRegex.test(value)) {
      errors.email = 'Digite um e-mail valido.'
      return false
    }
    errors.email = ''
    return true
  }

  const validatePassword = () => {
    if (!form.password) {
      errors.password = 'Informe sua senha.'
      return false
    }
    if (form.password.length < 3) {
      errors.password = 'Senha muito curta.'
      return false
    }
    errors.password = ''
    return true
  }

  const validateForm = () => {
    touched.email = true
    touched.password = true
    const emailOk = validateEmail()
    const passwordOk = validatePassword()
    return emailOk && passwordOk
  }

  const persistRememberPreference = () => {
    if (typeof localStorage === 'undefined') return

    if (remember.value) {
      localStorage.setItem('hf_remembered_email', form.email.trim())
    } else {
      localStorage.removeItem('hf_remembered_email')
    }
  }

  const updateEmail = (value) => {
    form.email = String(value || '')
    if (touched.email) validateEmail()
  }

  const updatePassword = (value) => {
    form.password = String(value || '')
    if (touched.password) validatePassword()
  }

  const updateRemember = (value) => {
    remember.value = Boolean(value)
  }

  const togglePassword = () => {
    showPassword.value = !showPassword.value
  }

  const onEmailBlur = () => {
    touched.email = true
    validateEmail()
  }

  const onPasswordBlur = () => {
    touched.password = true
    validatePassword()
  }

  const onSubmit = async () => {
    errorMessage.value = ''
    if (!validateForm()) return

    isSubmitting.value = true
    try {
      await auth.login(form.email.trim(), form.password)
      persistRememberPreference()
      const destination = String(route.query.redirect || '/')
      router.replace(destination)
    } catch (error) {
      errorMessage.value = error?.response?.data?.error || 'Nao foi possivel autenticar. Tente novamente.'
    } finally {
      isSubmitting.value = false
    }
  }

  watch(
    () => form.email,
    () => {
      if (errorMessage.value) errorMessage.value = ''
    }
  )

  watch(
    () => form.password,
    () => {
      if (errorMessage.value) errorMessage.value = ''
    }
  )

  onMounted(() => {
    if (auth?.token) {
      router.replace('/')
      return
    }

    if (typeof localStorage === 'undefined') return

    const rememberedEmail = localStorage.getItem('hf_remembered_email')
    if (rememberedEmail) {
      form.email = rememberedEmail
      remember.value = true
    }
  })

  return {
    form,
    errors,
    remember,
    showPassword,
    isSubmitting,
    errorMessage,
    canSubmit,
    updateEmail,
    updatePassword,
    updateRemember,
    togglePassword,
    onEmailBlur,
    onPasswordBlur,
    onSubmit,
  }
}
