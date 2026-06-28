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

    <NuxtLink to="/progress" class="bottom-nav__item" :class="{ active: route.path.startsWith('/progress') }">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="20" x2="18" y2="10"/>
        <line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6" y1="20" x2="6" y2="14"/>
      </svg>
      <span>Progress</span>
    </NuxtLink>

    <NuxtLink to="/history" class="bottom-nav__item" :class="{ active: route.path === '/history' }">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
      <span>Riwayat</span>
    </NuxtLink>

    <NuxtLink to="/profile" class="bottom-nav__item" :class="{ active: route.path === '/profile' }">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v-2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      <span>Profil</span>
    </NuxtLink>
  </nav>
</template>

<script setup lang="ts">
const route = useRoute()

const showNav = computed(() => {
  // Hide bottom nav on remote/fullscreen page
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
  background: var(--color-bg-card);
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
  padding: 6px 16px;
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  font-size: 0.6875rem;
  font-weight: 500;
  transition: color var(--transition-fast);
  text-decoration: none;
}

.bottom-nav__item.active {
  color: var(--color-primary);
}

.bottom-nav__item svg {
  width: 22px;
  height: 22px;
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
