import { ref, computed } from 'vue'

interface AuthUser {
  id: number
  name: string
  email: string
  avatar: string | null
  is_guest: boolean
}

let initPromise: Promise<void> | null = null

export const useAuth = () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string

  // Cookie to store the auth token securely across sessions
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 365, // 1 year expiration
    path: '/',
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production'
  })
  
  const user = useState<AuthUser | null>('auth_user', () => null)
  const loading = useState<boolean>('auth_loading', () => false)

  const isLoggedIn = computed(() => !!token.value && user.value && !user.value.is_guest)
  const isGuest = computed(() => !!user.value && user.value.is_guest)

  const fetchUser = async () => {
    if (!token.value) return
    try {
      const response = await fetch(`${apiBase}/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token.value}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const json = await response.json()
        user.value = json.data
      } else {
        // Clear token if token is expired or invalid
        token.value = null
        user.value = null
      }
    } catch (e) {
      console.error('Failed to fetch user:', e)
    }
  }

  const initSession = async () => {
    if (initPromise) {
      return initPromise
    }

    initPromise = (async () => {
      loading.value = true
      try {
        if (token.value) {
          await fetchUser()
        }
        
        // If token does not exist (first visit or token cleared), register as a guest
        if (!token.value) {
          const response = await fetch(`${apiBase}/auth/guest`, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            }
          })
          if (response.ok) {
            const json = await response.json()
            token.value = json.token
            user.value = {
              id: json.user.id,
              name: json.user.name,
              email: json.user.email,
              avatar: null,
              is_guest: true
            }
          }
        }
      } catch (e) {
        console.error('Failed to initialize session:', e)
      } finally {
        loading.value = false
        initPromise = null
      }
    })()

    return initPromise
  }

  const loginWithGoogle = () => {
    // Pass current guest user ID so their data merges in the callback
    const guestId = user.value ? user.value.id : ''
    const redirectUrl = `${apiBase}/auth/google/redirect?guest_id=${guestId}`
    if (typeof window !== 'undefined') {
      window.location.href = redirectUrl
    }
  }

  const logout = async () => {
    token.value = null
    user.value = null
    // Re-initialize a new clean guest session immediately
    await initSession()
    navigateTo('/profile')
  }

  return {
    token,
    user,
    loading,
    isLoggedIn,
    isGuest,
    initSession,
    loginWithGoogle,
    logout,
    fetchUser
  }
}
