import { ref, watch, computed } from 'vue'

export type ThemeId = 'default' | 'mecca' | 'madinah'

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
  default: {
    id: 'default',
    name: 'Default',
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
  mecca: {
    id: 'mecca',
    name: 'Mecca',
    emoji: '🕋',
    colors: {
      primary: '#D4AF37', // Ka'bah Gold Accent
      primaryLight: '#F3E5AB', // Light gold
      primaryDark: '#AA7C11', // Dark gold
      primary50: '#1F1F1F', // Dark gray card
      primary100: '#2A2A2A',
      primary900: '#111111', // Jet Black
    }
  },
  madinah: {
    id: 'madinah',
    name: 'Madinah',
    emoji: '🕌',
    colors: {
      primary: '#0B6A4F', // Nabawi Green
      primaryLight: '#10B981',
      primaryDark: '#064E3B',
      primary50: '#ECFDF5',
      primary100: '#D1FAE5',
      primary900: '#022C22',
    }
  }
}

const STORAGE_KEY = 'murojaah_theme_id'

export const useTheme = () => {
  const currentThemeId = useState<ThemeId>('app_theme_id', () => 'default')
  const { isLoggedIn } = useAuth()
  const { apiFetch } = useApi()

  const currentTheme = computed<Theme>(() => themes[currentThemeId.value] || themes.default)

  const applyTheme = (themeId: ThemeId) => {
    if (!import.meta.client) return
    const theme = themes[themeId] || themes.default
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
    currentThemeId.value = 'default'
    applyTheme('default')
    return true
  }

  const initTheme = async () => {
    if (!import.meta.client) return
    currentThemeId.value = 'default'
    applyTheme('default')
  }

  // Watch login state: reset to default
  watch(isLoggedIn, async (newVal) => {
    currentThemeId.value = 'default'
    applyTheme('default')
  })

  return {
    currentThemeId,
    currentTheme,
    setTheme,
    initTheme,
    themesList: Object.values(themes)
  }
}
