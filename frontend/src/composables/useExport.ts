import { ref } from 'vue'

import { isApiError } from '@/api/client'
import { exportsApi } from '@/api/exports'
import { useToastStore } from '@/stores/toast'

const POLL_INTERVAL = 1200
const MAX_POLLS = 60

/**
 * Request an export and hand the user the file.
 *
 * The API is request/poll/download even though the build currently completes
 * inside the first response, so moving the work to a queue changes nothing
 * here — the polling loop is already the normal path.
 */
export function useExport() {
  const busy = ref(false)
  const toast = useToastStore()

  async function download(portfolioId: number, portfolioName: string): Promise<void> {
    if (busy.value) return

    busy.value = true
    const pending = toast.info('Готовим файл', `Упаковываем «${portfolioName}»…`)

    try {
      let record = await exportsApi.create(portfolioId)

      for (let attempt = 0; attempt < MAX_POLLS && !isFinished(record.status); attempt++) {
        await sleep(POLL_INTERVAL)
        record = await exportsApi.get(record.id)
      }

      toast.dismiss(pending)

      if (record.status !== 'completed' || !record.download_url) {
        toast.error(
          'Экспорт не удался',
          record.error ?? 'Не удалось собрать архив. Попробуйте ещё раз.',
          { label: 'Повторить', run: () => void download(portfolioId, portfolioName) },
        )

        return
      }

      // A normal navigation: the response carries Content-Disposition, so the
      // browser saves it rather than rendering it.
      window.location.assign(record.download_url)
      toast.success('Архив готов', 'Портфолио — самодостаточный статический сайт.')
    } catch (error) {
      toast.dismiss(pending)
      toast.error('Экспорт не удался', isApiError(error) ? error.message : 'Попробуйте ещё раз.')
    } finally {
      busy.value = false
    }
  }

  return { busy, download }
}

function isFinished(status: string): boolean {
  return status === 'completed' || status === 'failed'
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => window.setTimeout(resolve, ms))
}
