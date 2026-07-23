export interface VerseTiming {
  verse_key: string
  timestamp_from: number
  timestamp_to: number
  duration: number
}

export interface ChapterAudioData {
  audio_url: string
  verse_timings: VerseTiming[]
}

export const QARI_MAP: Record<string, number> = {
  'Maher_AlMuaiqly_64kbps': 159, // Maher Al-Muaiqly
  'Alafasy_64kbps': 7,          // Mishary Rashid Alafasy
  'Ghamadi_40kbps': 13,         // Saad Al-Ghamdi
  'Husary_64kbps': 6            // Mahmoud Khalil Al-Husary
}

export const TIMINGS_CACHE_VERSION = 2

export const useMurottalAudio = () => {
  const fetchTimings = async (qariKey: string, chapter: number): Promise<ChapterAudioData | null> => {
    const qariId = QARI_MAP[qariKey]
    if (!qariId) return null

    const cacheKey = `murottal_timings_v${TIMINGS_CACHE_VERSION}_${qariId}_${chapter}`
    if (process.client) {
      // Clean up old cache keys from previous versions
      const oldKey = `murottal_timings_${qariId}_${chapter}`
      localStorage.removeItem(oldKey)

      const cached = localStorage.getItem(cacheKey)
      if (cached) {
        try {
          return JSON.parse(cached) as ChapterAudioData
        } catch (e) {
          localStorage.removeItem(cacheKey)
        }
      }
    }

    try {
      const url = `https://api.quran.com/api/qdc/audio/reciters/${qariId}/audio_files?chapter=${chapter}&segments=true`
      const res = await fetch(url)
      if (!res.ok) return null
      const data = await res.json()
      if (data.audio_files && data.audio_files.length > 0) {
        const file = data.audio_files[0]
        const payload: ChapterAudioData = {
          audio_url: file.audio_url,
          verse_timings: file.verse_timings || []
        }
        if (process.client && payload.verse_timings.length > 0) {
          localStorage.setItem(cacheKey, JSON.stringify(payload))
        }
        return payload
      }
    } catch (e) {
      console.error(`[Murottal] Failed to fetch timings for Qari ID ${qariId} Surah ${chapter}:`, e)
    }

    return null
  }

  return {
    QARI_MAP,
    fetchTimings
  }
}
