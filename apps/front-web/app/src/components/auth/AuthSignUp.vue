<template>
  <form class="flex flex-col" @submit.prevent="processForm">
    <p class="font-bold text-4xl sm:text-5xl lg:text-6xl text-accent">
      {{ t('sign-up') }}
    </p>

    <input
      v-model="pseudo"
      :placeholder="t('pseudo')"
      type="text"
      name="pseudo"
      class="mt-5 p-4 text-primary rounded-md bg-primary bg-opacity-100 opacity-100"
    />

    <select
      v-model="gender"
      class="mt-5 p-4 text-primary rounded-md bg-primary bg-opacity-100 opacity-100"
    >
      <option v-for="(gender, index) in genders" :key="index" :value="gender">
        {{ t(gender) }}
      </option>
    </select>

    <input
      v-model="email"
      :placeholder="t('email')"
      type="email"
      name="email"
      class="mt-5 p-4 text-primary rounded-md bg-primary bg-opacity-100 opacity-100"
    />
    <input
      v-model="password"
      :placeholder="t('password')"
      type="password"
      name="password"
      autocomplete="'autocomplete'"
      class="mt-5 p-4 text-primary rounded-md bg-primary bg-opacity-100 opacity-100"
    />
    <input
      v-model="passwordConfirm"
      :placeholder="t('confirm')"
      type="password"
      name="passwordConfirm"
      autocomplete="'autocomplete'"
      class="mt-5 p-4 text-primary rounded-md bg-primary bg-opacity-100 opacity-100"
    />

    <button
      class="mt-8 py-4 text-lg text-primary font-light tracking-wide bg-accent bg-opacity-100 rounded-lg"
      type="submit"
    >
      {{ t('submit') }}
    </button>

    <a
      class="block mt-8 text-xl hover:underline cursor-pointer"
      @click="onClickDisplayLoginBlock"
    >
      {{ t('sign-in') }}
    </a>
  </form>
</template>

<script>
import useApiUserSignUp from '@/composition/api/user/useApiUserSignUp'
import { reactive, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import genderTypes from '@/enums/genderTypes'
import { useStore } from 'vuex'

export default {
  emits: ['display-login-block'],
  setup(props, context) {
    const store = useStore()

    const data = reactive({
      genders: genderTypes,
      pseudo: null,
      gender: genderTypes.UNDEFINED,
      email: null,
      password: null,
      passwordConfirm: null,
    })

    const onClickDisplayLoginBlock = () => {
      context.emit('display-login-block')
    }

    const processForm = () => {
      useApiUserSignUp(store, { ...data })
      onClickDisplayLoginBlock()
    }

    const { t } = useI18n({
      locale: 'fr',
      messages: {
        fr: {
          'sign-up': 'Inscription',
          pseudo: 'Pseudo',
          gender: 'Genre',
          email: 'Email',
          password: 'Mot de passe',
          confirm: 'Confirmer votre mot de passe',
          submit: "S'inscrire",
          'sign-in': 'Déjà inscrit ?',
          UNDEFINED: 'Non défini',
          MALE: 'Homme',
          FEMALE: 'Femme',
          OTHER: 'Autre',
        },
      },
    })

    return {
      ...toRefs(data),
      processForm,
      onClickDisplayLoginBlock,
      t,
    }
  },
}
</script>
