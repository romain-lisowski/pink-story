<template>
  <div class="flex flex-wrap justify-items-start gap-2 sm:gap-4 mb-4 sm:mb-8">
    <span class="py-2 pr-2 text-base sm:text-lg xl:text-2xl text-accent">
      {{ categoryList.title }} :
    </span>
    <div
      v-for="(category, index) in categoryList.children"
      :key="index"
      class="py-3"
    >
      <CategoryListItem :category="category" />
    </div>
  </div>
</template>

<script>
import CategoryListItem from '@/components/category/CategoryListItem.vue'

export default {
  components: {
    CategoryListItem,
  },
  props: {
    categoryList: {
      type: Object,
      required: true,
    },
  },
}
</script>
