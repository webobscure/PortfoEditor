import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

/**
 * Editor and gallery chunks are lazy so the dashboard — the first thing a
 * returning user sees — is not paying for the editor's weight.
 */
export const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: () => ({ name: 'dashboard' }),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/auth/LoginPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/pages/auth/RegisterPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/portfolios',
      name: 'dashboard',
      component: () => import('@/pages/DashboardPage.vue'),
      meta: { auth: true },
    },
    {
      path: '/templates',
      name: 'templates',
      component: () => import('@/pages/TemplatesPage.vue'),
      meta: { auth: true },
    },
    {
      path: '/portfolios/new',
      name: 'portfolio-create',
      component: () => import('@/pages/portfolio/CreatePortfolioPage.vue'),
      meta: { auth: true },
    },
    {
      path: '/portfolios/:id/edit',
      name: 'editor',
      component: () => import('@/pages/portfolio/EditorPage.vue'),
      meta: { auth: true },
      props: (route) => ({ id: Number(route.params.id) }),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/pages/NotFoundPage.vue'),
    },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  await auth.initialise()

  if (to.meta.auth && !auth.isAuthenticated) {
    return { name: 'login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  return true
})
