export interface AyahBookmark {
  surah: number
  ayah: number
  verse_key: string
  page: number
  surah_name?: string
  text?: string
  translation?: string
  created_at: string
}

const STORAGE_KEY = 'murojaah_ayah_bookmarks'

export const useAyahBookmarks = () => {
  const bookmarks = useState<AyahBookmark[]>('ayahBookmarks', () => [])
  const loaded = useState<boolean>('ayahBookmarksLoaded', () => false)

  const persist = () => {
    if (process.server) return
    localStorage.setItem(STORAGE_KEY, JSON.stringify(bookmarks.value))
  }

  const loadBookmarks = () => {
    if (process.server || loaded.value) return
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      bookmarks.value = raw ? JSON.parse(raw) : []
    } catch {
      bookmarks.value = []
    } finally {
      loaded.value = true
    }
  }

  const isBookmarked = (verseKey: string) =>
    bookmarks.value.some(item => item.verse_key === verseKey)

  const addBookmark = (bookmark: Omit<AyahBookmark, 'created_at'> & { created_at?: string }) => {
    loadBookmarks()
    const next: AyahBookmark = {
      ...bookmark,
      created_at: bookmark.created_at || new Date().toISOString()
    }
    bookmarks.value = [next, ...bookmarks.value.filter(item => item.verse_key !== next.verse_key)]
    persist()
  }

  const removeBookmark = (verseKey: string) => {
    loadBookmarks()
    bookmarks.value = bookmarks.value.filter(item => item.verse_key !== verseKey)
    persist()
  }

  const toggleBookmark = (bookmark: Omit<AyahBookmark, 'created_at'> & { created_at?: string }) => {
    if (isBookmarked(bookmark.verse_key)) {
      removeBookmark(bookmark.verse_key)
      return false
    }

    addBookmark(bookmark)
    return true
  }

  return {
    bookmarks,
    loadBookmarks,
    isBookmarked,
    addBookmark,
    removeBookmark,
    toggleBookmark
  }
}