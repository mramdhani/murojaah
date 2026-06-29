import { ref, watch, computed } from 'vue'

export type ThemeId = 'emerald' | 'pink' | 'lavender' | 'ocean' | 'sunset' | 'forest' | 'kabah' | 'madinah' | 'alaqsa'

export interface Theme {
  id: ThemeId
  name: string
  emoji: string
  colors: {
    primary: string
    primaryLight: string
    primaryDark: string
    primary50: string
    primary100: string
    primary900: string
  }
}

export const themes: Record<ThemeId, Theme> = {
  emerald: {
    id: 'emerald',
    name: 'Hijau Emerald',
    emoji: '🌿',
    colors: {
      primary: '#059669',
      primaryLight: '#10B981',
      primaryDark: '#047857',
      primary50: '#ECFDF5',
      primary100: '#D1FAE5',
      primary900: '#064E3B',
    }
  },
  pink: {
    id: 'pink',
    name: 'Pink Manis',
    emoji: '🌸',
    colors: {
      primary: '#D01C66',
      primaryLight: '#EC4899',
      primaryDark: '#9D174D',
      primary50: '#FDF2F8',
      primary100: '#FCE7F3',
      primary900: '#500724',
    }
  },
  lavender: {
    id: 'lavender',
    name: 'Lavender',
    emoji: '💜',
    colors: {
      primary: '#7C3AED',
      primaryLight: '#8B5CF6',
      primaryDark: '#5B21B6',
      primary50: '#F5F3FF',
      primary100: '#EDE9FE',
      primary900: '#2E1065',
    }
  },
  ocean: {
    id: 'ocean',
    name: 'Biru Laut',
    emoji: '🌊',
    colors: {
      primary: '#0284C7',
      primaryLight: '#38BDF8',
      primaryDark: '#0369A1',
      primary50: '#F0F9FF',
      primary100: '#E0F2FE',
      primary900: '#0C4A6E',
    }
  },
  sunset: {
    id: 'sunset',
    name: 'Senja Jingga',
    emoji: '🌅',
    colors: {
      primary: '#EA580C',
      primaryLight: '#F97316',
      primaryDark: '#C2410C',
      primary50: '#FFF7ED',
      primary100: '#FFEDD5',
      primary900: '#431407',
    }
  },
  forest: {
    id: 'forest',
    name: 'Hutan Teduh',
    emoji: '🌲',
    colors: {
      primary: '#065F46',
      primaryLight: '#047857',
      primaryDark: '#064E3B',
      primary50: '#ECFDF5',
      primary100: '#D1FAE5',
      primary900: '#022C22',
    }
  },
  kabah: {
    id: 'kabah',
    name: 'Ka\'bah',
    emoji: '🕋',
    colors: {
      primary: '#D4AF37', // Ka'bah Gold Accent
      primaryLight: '#F3E5AB', // Light gold
      primaryDark: '#AA7C11', // Dark gold
      primary50: '#1F1F1F', // Dark gray card (matches dark background)
      primary100: '#2A2A2A',
      primary900: '#111111', // Jet Black
    }
  },
  madinah: {
    id: 'madinah',
    name: 'Madinah',
    emoji: '🕌',
    colors: {
      primary: '#047857', // Nabawi Green
      primaryLight: '#10B981',
      primaryDark: '#064E3B',
      primary50: '#ECFDF5',
      primary100: '#D1FAE5',
      primary900: '#022C22',
    }
  },
  alaqsa: {
    id: 'alaqsa',
    name: 'Al-Aqsa',
    emoji: '✨',
    colors: {
      primary: '#C27D38', // Qubbatus Sakhrah Gold
      primaryLight: '#D49D42',
      primaryDark: '#9F5B1A',
      primary50: '#F0F4F8', // Light blue/gray base (looks like night sky backdrop)
      primary100: '#D9E2EC',
      primary900: '#102A43', // Royal Deep Blue
    }
  }
}

const STORAGE_KEY = 'murojaah_theme_id'

export const useTheme = () => {
  const currentThemeId = useState<ThemeId>('app_theme_id', () => 'emerald')
  const { isLoggedIn } = useAuth()
  const { apiFetch } = useApi()

  const currentTheme = computed<Theme>(() => themes[currentThemeId.value] || themes.emerald)

  const applyTheme = (themeId: ThemeId) => {
    if (!import.meta.client) return
    const theme = themes[themeId] || themes.emerald
    const root = document.documentElement
    
    root.style.setProperty('--color-primary', theme.colors.primary)
    root.style.setProperty('--color-primary-light', theme.colors.primaryLight)
    root.style.setProperty('--color-primary-dark', theme.colors.primaryDark)
    root.style.setProperty('--color-primary-50', theme.colors.primary50)
    root.style.setProperty('--color-primary-100', theme.colors.primary100)
    root.style.setProperty('--color-primary-900', theme.colors.primary900)
    root.style.setProperty('--shadow-glow', `0 0 20px ${theme.colors.primary}26`)
    
    root.setAttribute('data-theme', theme.id)
  }

  const setTheme = async (themeId: ThemeId) => {
    if (!isLoggedIn.value) return false
    
    currentThemeId.value = themeId
    applyTheme(themeId)

    // Save to localStorage immediately (optimistic)
    if (import.meta.client) {
      localStorage.setItem(STORAGE_KEY, themeId)
    }

    // Sync to server so it's available across all devices
    try {
      await apiFetch('/auth/me', {
        method: 'PATCH',
        body: JSON.stringify({ theme: themeId }),
      })
    } catch (e) {
      console.warn('Failed to sync theme to server:', e)
    }

    return true
  }

  const initTheme = async () => {
    if (!import.meta.client) return

    if (isLoggedIn.value) {
      // Logged-in: fetch theme from server first (cross-device sync)
      try {
        const res = await apiFetch<{ data: { theme?: string } }>('/auth/me')
        const serverTheme = (res?.data?.theme ?? 'emerald') as ThemeId
        if (themes[serverTheme]) {
          currentThemeId.value = serverTheme
          applyTheme(serverTheme)
          // Also update localStorage to reflect server value
          localStorage.setItem(STORAGE_KEY, serverTheme)
          return
        }
      } catch (e) {
        // Fallback to localStorage if network is unavailable
        console.warn('Could not fetch theme from server, using local:', e)
      }

      // Fallback: use localStorage
      const saved = localStorage.getItem(STORAGE_KEY) as ThemeId | null
      if (saved && themes[saved]) {
        currentThemeId.value = saved
        applyTheme(saved)
      } else {
        applyTheme('emerald')
      }
    } else {
      // Guest: always use default emerald
      currentThemeId.value = 'emerald'
      applyTheme('emerald')
    }
  }

  // Watch login state: if user logs out, reset theme to emerald
  watch(isLoggedIn, async (newVal) => {
    if (!newVal) {
      currentThemeId.value = 'emerald'
      applyTheme('emerald')
    } else {
      // On login, fetch theme from server
      await initTheme()
    }
  })

  return {
    currentThemeId,
    currentTheme,
    setTheme,
    initTheme,
    themesList: Object.values(themes)
  }
}
