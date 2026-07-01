export const useMurojaahDrawer = () => {
  const isOpen = useState('isMurojaahDrawerOpen', () => false)
  const mode = useState<'learning' | 'listening'>('murojaahDrawerMode', () => 'learning')

  const open = (nextMode: 'learning' | 'listening' = 'learning') => {
    mode.value = nextMode
    isOpen.value = true
  }

  const close = () => { isOpen.value = false }
  const toggle = (nextMode?: 'learning' | 'listening') => {
    if (nextMode) {
      mode.value = nextMode
    }
    isOpen.value = !isOpen.value
  }

  return { isOpen, mode, open, close, toggle }
}