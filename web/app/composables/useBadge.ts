export interface Badge {
  level: string
  name: string
  arabic: string
  meaning: string
  icon: string
  image: string
  color: string
  bgGradient: string
  minAyah: number
  maxAyah: number
  juzRange: string
  description: string
}

const badges: Badge[] = [
  {
    level: '1',
    name: 'Nur',
    arabic: 'نُور',
    meaning: 'Cahaya',
    icon: '🔆',
    image: '/images/badge-1.png?v=2',
    color: '#7C3AED',
    bgGradient: 'linear-gradient(135deg, #EEF2FF, #C7D2FE)',
    minAyah: 0,
    maxAyah: 1039,
    juzRange: '1 - 5 Juz',
    description: 'Tahap awal hafalan. Melambangkan awal perjalanan saat cahaya Al-Qur\'an mulai menerangi hati dan membangun semangat.',
  },
  {
    level: '2',
    name: 'Taqwa',
    arabic: 'تَقْوَى',
    meaning: 'Ketakwaan',
    icon: '🌙',
    image: '/images/badge-2.png?v=2',
    color: '#059669',
    bgGradient: 'linear-gradient(135deg, #D1FAE5, #A7F3D0)',
    minAyah: 1040,
    maxAyah: 2079,
    juzRange: '6 - 10 Juz',
    description: 'Tahap konsistensi. Menunjukkan hafalan yang mulai membentuk kedisiplinan diri dan kedekatan dengan Al-Qur’an.',
  },
  {
    level: '3',
    name: 'Tsabat',
    arabic: 'ثَبَات',
    meaning: 'Keteguhan',
    icon: '⚓',
    image: '/images/badge-3.png?v=2',
    color: '#D97706',
    bgGradient: 'linear-gradient(135deg, #FEF3C7, #FCD34D)',
    minAyah: 2080,
    maxAyah: 3119,
    juzRange: '11 - 15 Juz',
    description: 'Tahap keteguhan. Butuh konsistensi dan murojaah yang lebih kuat karena jumlah hafalan sudah semakin bertambah.',
  },
  {
    level: '4',
    name: 'Itqan',
    arabic: 'إِتْقَان',
    meaning: 'Ketelitian',
    icon: '💎',
    image: '/images/badge-4.png?v=2',
    color: '#0891B2',
    bgGradient: 'linear-gradient(135deg, #CFFAFE, #A5F3FC)',
    minAyah: 3120,
    maxAyah: 4159,
    juzRange: '16 - 20 Juz',
    description: 'Tahap pematangan. Fokus pada kualitas hafalan agar lancar, mantap, dan berkualitas, bukan sekadar kuantitas.',
  },
  {
    level: '5',
    name: 'Ihsan',
    arabic: 'إِحْسَان',
    meaning: 'Kebaikan Terbaik',
    icon: '🌟',
    image: '/images/badge-5.png?v=2',
    color: '#9333EA',
    bgGradient: 'linear-gradient(135deg, #F3E8FF, #E9D5FF)',
    minAyah: 4160,
    maxAyah: 5199,
    juzRange: '21 - 25 Juz',
    description: 'Tahap kualitas terbaik. Fokus lebih dalam pada penyempurnaan bacaan, adab, dan penghayatan Al-Qur\'an.',
  },
  {
    level: '6',
    name: 'Hafizh',
    arabic: 'حَافِظ',
    meaning: 'Penghafal Qur\'an',
    icon: '👑',
    image: '/images/badge-6.png?v=2',
    color: '#B45309',
    bgGradient: 'linear-gradient(135deg, #FEF3C7, #FDE68A)',
    minAyah: 5200,
    maxAyah: Infinity,
    juzRange: '26 - 30 Juz',
    description: 'Tahap penyempurna. Tingkat akhir bagi penjaga Al-Qur\'an secara utuh yang telah menyelesaikan seluruh 30 juz.',
  },
]

export function useBadge() {
  const getBadge = (totalAyah: number): Badge => {
    for (const b of badges) {
      if (totalAyah >= b.minAyah && totalAyah <= b.maxAyah) return b
    }
    return badges[0]
  }

  const getNextBadge = (totalAyah: number): Badge | null => {
    for (const b of badges) {
      if (totalAyah < b.minAyah) return b
    }
    return null
  }

  const getProgressToNext = (totalAyah: number): number => {
    const current = getBadge(totalAyah)
    const next = getNextBadge(totalAyah)
    if (!next) return 100
    const range = next.minAyah - current.minAyah
    const progress = totalAyah - current.minAyah
    return Math.min(Math.round((progress / range) * 100), 100)
  }

  const getAyahsToNext = (totalAyah: number): number => {
    const next = getNextBadge(totalAyah)
    if (!next) return 0
    return next.minAyah - totalAyah
  }

  return {
    badges,
    getBadge,
    getNextBadge,
    getProgressToNext,
    getAyahsToNext,
  }
}
