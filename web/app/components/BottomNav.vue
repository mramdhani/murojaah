<template>
  <nav class="bottom-nav" v-if="showNav">
    <NuxtLink to="/" class="bottom-nav__item" :class="{ active: route.path === '/' }">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      <span>Beranda</span>
    </NuxtLink>

    <NuxtLink to="/surahs" class="bottom-nav__item" :class="{ active: route.path.startsWith('/surahs') }">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      <span>Surat</span>
    </NuxtLink>

    <!-- Murojaah Highlight Button -->
    <button type="button" class="bottom-nav__murojaah" @click="open">
      <div class="bottom-nav__murojaah-inner">
        <img src="/logo-white.png" alt="Murojaah" class="bottom-nav__murojaah-logo" />
      </div>
      <span>Murojaah</span>
    </button>

    <NuxtLink to="/history" class="bottom-nav__item" :class="{ active: route.path === '/history' }">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
      <span>Riwayat</span>
    </NuxtLink>

    <NuxtLink to="/profile" class="bottom-nav__item bottom-nav__item--profile" :class="{ active: route.path === '/profile' }">
      <span class="bottom-nav__avatar-wrap" v-if="user?.avatar">
        <img :src="user.avatar" alt="" class="bottom-nav__avatar" referrerpolicy="no-referrer" />
      </span>
      <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      <span>Profil</span>
    </NuxtLink>
  </nav>
</template>

<script setup lang="ts">
const route = useRoute()
const { user } = useAuth()
const { open } = useMurojaahDrawer()

const showNav = computed(() => {
  return !route.path.startsWith('/remote')
})
</script>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: calc(var(--bottom-nav-height) + var(--safe-bottom));
  padding-bottom: var(--safe-bottom);
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: center;
  justify-content: space-around;
  z-index: 100;
  backdrop-filter: blur(12px);
  background: rgba(255, 255, 255, 0.92);
}

.bottom-nav__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  font-size: 0.6875rem;
  font-weight: 500;
  transition: color var(--transition-fast);
  text-decoration: none;
  position: relative;
}

.bottom-nav__item.active {
  color: var(--color-primary);
}

.bottom-nav__item svg {
  width: 22px;
  height: 22px;
}

/* --- Murojaah Highlight Button --- */
.bottom-nav__murojaah {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  text-decoration: none;
  background: none;
  border: none;
  outline: none;
  cursor: pointer;
  color: var(--color-text-muted);
  font-size: 0.6875rem;
  font-weight: 500;
  margin-top: -16px;
  position: relative;
  padding: 0;
}

.bottom-nav__murojaah-inner {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #064E3B 0%, #047857 100%);
  box-shadow: 0 4px 16px rgba(6, 78, 59, 0.4), 0 0 0 4px rgba(6, 78, 59, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.bottom-nav__murojaah:hover .bottom-nav__murojaah-inner {
  transform: scale(1.08);
  box-shadow: 0 6px 20px rgba(6, 78, 59, 0.5), 0 0 0 4px rgba(6, 78, 59, 0.18);
}

.bottom-nav__murojaah:active .bottom-nav__murojaah-inner {
  transform: scale(0.96);
}

.bottom-nav__murojaah span {
  margin-top: 2px;
  color: #064E3B;
  font-weight: 700;
}

.bottom-nav__murojaah-logo {
  width: 30px;
  height: 30px;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.12));
}

/* Avatar photo in bottom nav */
.bottom-nav__avatar-wrap {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid transparent;
  transition: border-color var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
}

.bottom-nav__item.active .bottom-nav__avatar-wrap {
  border-color: var(--color-primary);
}

.bottom-nav__avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@media (min-width: 768px) {
  .bottom-nav {
    max-width: 540px;
    left: 50%;
    transform: translateX(-50%);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    border-left: 1px solid rgba(0, 0, 0, 0.06);
    border-right: 1px solid rgba(0, 0, 0, 0.06);
  }
}
</style>
