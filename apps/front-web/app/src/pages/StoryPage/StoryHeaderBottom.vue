<template>
  <ul
    class="flex justify-center items-center md:px-4 w-full h-20 lg:h-32 text-primary bg-accent"
  >
    <li class="flex flex-col pr-2 sm:pr-8 border-r">
      <span class="text-xs sm:text-base lg:text-xl">{{
        t('reader-reviews')
      }}</span>
      <span class="mt-1 md:mt-0 text-base sm:text-2xl lg:text-4xl font-bold"
        >{{ story.rate ? `${props.story.rate} / 5` : '-' }}
      </span>
    </li>
    <li class="flex flex-col px-2 sm:px-8 border-r">
      <span class="text-xs sm:text-base lg:text-xl">{{
        t('reading-time')
      }}</span>
      <span class="mt-1 md:mt-0 text-base sm:text-2xl lg:text-4xl font-bold"
        >-</span
      >
    </li>
    <li class="flex flex-col pl-2 sm:pl-8">
      <span class="text-xs sm:text-base lg:text-xl">{{
        t('first-publication')
      }}</span>
      <span class="mt-1 md:mt-0 text-base sm:text-2xl lg:text-4xl font-bold">{{
        dayJs(story.created_at).format('DD/MM/YYYY HH[h]mm')
      }}</span>
    </li>
  </ul>
</template>

<script>
import { useI18n } from 'vue-i18n'
import dayJs from 'dayjs'

export default {
  props: {
    story: {
      type: Object,
      required: true,
      default: () => ({ rate: 0, content: '', created_at: null }),
    },
  },
  setup() {
    const { t } = useI18n({
      locale: 'fr',
      messages: {
        fr: {
          'reader-reviews': 'Avis des lecteurs',
          'reading-time': 'Temps de lecture',
          'first-publication': 'Première publication',
        },
      },
    })

    return { dayJs, t }
  },
}
</script>
