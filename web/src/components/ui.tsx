import type { ReactNode } from 'react'

export function Spinner({ className = '' }: { className?: string }) {
  return (
    <svg className={`animate-spin ${className}`} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
      <path
        className="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"
      />
    </svg>
  )
}

type AlertTone = 'error' | 'success' | 'info' | 'warning'

const alertTones: Record<AlertTone, string> = {
  error: 'bg-rose-50 text-rose-800 ring-rose-200',
  success: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
  info: 'bg-sky-50 text-sky-800 ring-sky-200',
  warning: 'bg-amber-50 text-amber-900 ring-amber-200',
}

export function Alert({ tone = 'info', children }: { tone?: AlertTone; children: ReactNode }) {
  return <div className={`rounded-lg px-4 py-3 text-sm ring-1 ${alertTones[tone]}`}>{children}</div>
}

type BadgeTone = 'slate' | 'emerald' | 'amber' | 'rose' | 'indigo'

const badgeTones: Record<BadgeTone, string> = {
  slate: 'bg-slate-100 text-slate-700',
  emerald: 'bg-emerald-100 text-emerald-800',
  amber: 'bg-amber-100 text-amber-800',
  rose: 'bg-rose-100 text-rose-800',
  indigo: 'bg-indigo-100 text-indigo-800',
}

export function Badge({ tone = 'slate', children }: { tone?: BadgeTone; children: ReactNode }) {
  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${badgeTones[tone]}`}
    >
      {children}
    </span>
  )
}
