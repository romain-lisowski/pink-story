<template>
  <div>
    <nav
      class="lg:hidden right-100 fixed h-screen w-full text-xl bg-primary overflow-y-scroll z-20 transform transition-transform duration-300 ease-in-out"
      :class="!openMenu ? '' : 'translate-x-full'"
    >
      <a
        class="flex justify-end py-4 pr-8 cursor-pointer"
        @click="openMenu = !openMenu"
        ><ui-font-awesome-icon icon="times" class="h-8"
      /></a>

      <ul class="text-center font-bold" @click="openMenu = !openMenu">
        <li>
          <router-link
            :to="{ name: 'Home' }"
            class="p-4 block"
            :class="
              currentPageUri === '/' ? activeMenuClasses : inactiveMenuClasses
            "
            >{{ t('discover') }}</router-link
          >
        </li>
        <li>
          <router-link
            :to="{ name: 'Search' }"
            class="p-4 block"
            :class="
              currentPageUri === '/search'
                ? activeMenuClasses
                : inactiveMenuClasses
            "
            >{{ t('search') }}</router-link
          >
        </li>
        <li v-show="isSignedIn">
          <router-link
            :to="{ name: 'Write' }"
            class="p-4 block"
            :class="
              currentPageUri === '/write'
                ? activeMenuClasses
                : inactiveMenuClasses
            "
            >{{ t('write') }}</router-link
          >
        </li>
        <li v-show="isSignedIn">
          <router-link
            :to="{ name: 'User' }"
            :class="
              currentPageUri === '/user'
                ? activeMenuClasses
                : inactiveMenuClasses
            "
            class="p-4 block"
          >
            {{ t('settings') }}
          </router-link>
        </li>
        <li v-if="!isSignedIn">
          <a
            class="p-4 block hover:bg-accent rounded-lg cursor-pointer"
            :class="inactiveMenuClasses"
            @click="openAuthPanel = true"
          >
            {{ t('sign-in') }}
          </a>
        </li>
        <li v-else>
          <a class="p-4 block hover:bg-accent cursor-pointer" @click="signOut">
            {{ t('signOut') }}
          </a>
        </li>
      </ul>
    </nav>

    <header
      class="fixed top-0 w-full h-16 lg:h-20 px-2 sm:px-4 md:px-6 lg:px-8 xl:px-12 flex items-center justify-center bg-primary bg-opacity-75 border-b border-primary-inverse border-opacity-5 z-10"
    >
      <a
        class="ml-6 md:ml-0 pt-2 lg:hidden text-2xl cursor-pointer"
        @click="openMenu = !openMenu"
      >
        <ui-font-awesome-icon icon="bars" class="h-8"
      /></a>

      <router-link
        :to="{ name: 'Home' }"
        class="mx-auto lg:ml-0 flex-shrink-0 text-2xl md:text-3xl lg:text-3xl xl:text-4xl font-bold text-accent hover:text-accent-highlight tracking-tighter"
      >
        PinkStory
      </router-link>

      <nav class="hidden lg:block">
        <ul class="flex items-center justify-center tracking-wide">
          <li>
            <router-link
              :to="{ name: 'Home' }"
              class="p-2 px-4 block font-bold rounded-lg cursor-pointer"
              :class="
                currentPageUri === '/' ? activeMenuClasses : inactiveMenuClasses
              "
              >{{ t('discover') }}</router-link
            >
          </li>
          <li class="pl-2">
            <router-link
              class="p-2 px-4 block font-bold rounded-lg cursor-pointer"
              :class="
                currentPageUri.includes('/search')
                  ? activeMenuClasses
                  : inactiveMenuClasses
              "
              :to="{ name: 'Search' }"
              >{{ t('search') }}</router-link
            >
          </li>
          <li v-show="isSignedIn" class="pl-2">
            <router-link
              :to="{ name: 'Write' }"
              class="p-2 px-4 block font-bold rounded-lg cursor-pointer"
              :class="
                currentPageUri.includes('/write')
                  ? activeMenuClasses
                  : inactiveMenuClasses
              "
              >{{ t('write') }}</router-link
            >
          </li>
          <li v-show="isSignedIn" class="pl-2">
            <router-link
              :to="{ name: 'User' }"
              class="p-2 px-4 block font-bold rounded-lg cursor-pointer"
              :class="
                currentPageUri.includes('/user')
                  ? activeMenuClasses
                  : inactiveMenuClasses
              "
            >
              {{ t('settings') }}
            </router-link>
          </li>
          <li v-if="!isSignedIn" class="pl-2">
            <a
              class="p-2 px-4 block font-bold border-2 rounded-lg cursor-pointer"
              :class="inactiveMenuClasses"
              @click="openAuthPanel = true"
            >
              {{ t('sign-in') }}
            </a>
          </li>
          <li v-else class="pl-2">
            <a
              class="p-2 px-4 block font-bold rounded-lg cursor-pointer"
              @click="signOut"
            >
              {{ t('signOut') }}
            </a>
          </li>
        </ul>
      </nav>

      <button
        v-if="isSignedIn"
        class="relative group mr-6 md:mr-0 lg:ml-auto flex-shrink-0 flex items-center justify-center bg-opacity-100 border-opacity-50"
      >
        <span
          class="absolute top-0 right-0 px-1 md:px-1 bg-primary-inverse group-hover:bg-accent-highlight rounded-full leading-snug text-xxs md:text-xs text-primary-inverse font-bold"
          >1</span
        >
        <span
          v-if="currentUser.image"
          class="p-1/2 md:p-1 group-hover:bg-accent border-2 border-accent group-hover:border-opacity-0 rounded-2xl md:rounded-3xl"
        >
          <img
            class="w-8 md:w-12 h-8 md:h-12 rounded-xl md:rounded-2xl"
            :src="currentUser.image"
          />
        </span>
        <span
          v-else
          class="w-10 sm:w-12 h-10 sm:h-12 flex items-center justify-center font-bold bg-accent bg-opacity-100 rounded-full"
          >{{ currentUser.name[0].toUpperCase() }}</span
        >
      </button>

      <Auth
        :open-auth-panel="openAuthPanel"
        @close-auth-panel="openAuthPanel = false"
      />
    </header>
  </div>
</template>

<script>
import { computed, watch, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStore } from 'vuex'
import { useI18n } from 'vue-i18n'
import Auth from '@/components/auth/Auth.vue'

export default {
  components: {
    Auth,
  },
  setup() {
    const store = useStore()
    const route = useRoute()
    const router = useRouter()

    const openAuthPanel = ref(false)
    const openMenu = ref(false)

    const activeMenuClasses = [
      'bg-primary-inverse',
      'bg-opacity-5',
      'text-primary',
      'hover:text-primary',
    ]
    const inactiveMenuClasses = [
      'text-accent',
      'bg-opacity-100',
      'hover:bg-accent',
      'hover:text-primary-inverse',
    ]

    const isSignedIn = computed(() => {
      return store.getters['auth/isSignedIn']
    })
    const currentUser = computed(() => {
      return store.getters['auth/getCurrentUser']
    })
    const currentPageUri = computed(() => {
      return route.path
    })

    watch(isSignedIn, (value) => {
      if (value && openAuthPanel.value) {
        openAuthPanel.value = false
      }
    })

    const signOut = () => {
      openAuthPanel.value = false
      openMenu.value = false
      store.dispatch('auth/signOut')
      if (route.path !== '/') {
        router.push({ name: 'Home' })
      }
    }

    const { t } = useI18n({
      locale: 'fr',
      messages: {
        fr: {
          discover: 'Découvrir',
          search: 'Rechercher',
          write: 'Ecrire une histoire',
          settings: 'Préférences',
          signOut: 'Déconnexion',
          'sign-in': 'Connexion',
        },
      },
    })

    return {
      openAuthPanel,
      openMenu,
      activeMenuClasses,
      inactiveMenuClasses,
      isSignedIn,
      currentUser,
      currentPageUri,
      signOut,
      t,
    }
  },
}
</script>
