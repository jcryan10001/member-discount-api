import { useEffect, useState } from 'react'
import type { ReactNode } from 'react'
import { pingHealth } from '../api/client'
import { Spinner } from './ui'

const RETRY_MS = 2000
const MAX_ATTEMPTS = 40 // ~80s ceiling, comfortably covering a free-tier cold start

/**
 * The backend runs on a free tier that sleeps after about 15 minutes idle, with
 * a 30 to 60 second cold start. Instead of a blank screen or a scary error on
 * first load, this gate pings /api/health on boot and retries until the server
 * wakes, showing a calm, deliberate loading state. A warm server passes straight
 * through; the message only appears if the first ping is slow.
 */
export default function ColdStartGate({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<'waking' | 'ready' | 'error'>('waking')
  const [slow, setSlow] = useState(false)

  useEffect(() => {
    const controller = new AbortController()
    let cancelled = false
    const slowTimer = setTimeout(() => setSlow(true), 1200)

    async function wake() {
      for (let attempt = 0; attempt < MAX_ATTEMPTS && !cancelled; attempt++) {
        if (await pingHealth(controller.signal)) {
          if (!cancelled) setStatus('ready')
          return
        }
        await new Promise((resolve) => setTimeout(resolve, RETRY_MS))
      }
      if (!cancelled) setStatus('error')
    }
    void wake()

    return () => {
      cancelled = true
      controller.abort()
      clearTimeout(slowTimer)
    }
  }, [])

  if (status === 'ready') return <>{children}</>

  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 px-6 text-center">
      {status === 'waking' ? (
        <>
          <Spinner className="size-8 text-indigo-600" />
          {slow && (
            <div className="max-w-sm">
              <p className="font-medium text-slate-700">Waking up the demo server...</p>
              <p className="mt-1 text-sm text-slate-500">
                It runs on a free tier that sleeps when idle, so the first load can take 30 to 60
                seconds. Thanks for your patience.
              </p>
            </div>
          )}
        </>
      ) : (
        <div className="max-w-sm">
          <p className="font-medium text-slate-700">The demo server isn't responding yet.</p>
          <p className="mt-1 text-sm text-slate-500">It may still be starting up.</p>
          <button
            onClick={() => window.location.reload()}
            className="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
          >
            Try again
          </button>
        </div>
      )}
    </div>
  )
}
