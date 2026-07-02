export const useMurojaahDrawer = () => {
  const isOpen = useState('isMurojaahDrawerOpen', () => false)
  const mode = useState<'learning' | 'listening'>('murojaahDrawerMode', () => 'learning')
  const format = useState<'quiz' | 'mushaf'>('murojaahDrawerFormat', () => 'quiz')

  const open = (
    nextMode: 'learning' | 'listening' = 'learning',
    nextFormat?: 'quiz' | 'mushaf',
  ) => {
    mode.value = nextMode
    if (nextFormat) format.value = nextFormat
    isOpen.value = true
  }

  const close = () => { isOpen.value = false }
  const toggle = (nextMode?: 'learning' | 'listening') => {
    if (nextMode) {
      mode.value = nextMode
    }
    isOpen.value = !isOpen.value
  }

  return { isOpen, mode, format, open, close, toggle }
}