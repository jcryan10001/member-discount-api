import type { Offer } from '../api/types'

export interface RedeemResult {
  offer: Offer
  success: boolean
  code?: string
  message?: string
  reason?: string // the API's machine-readable error code
}

/**
 * Shows the outcome of a redemption attempt: either the issued discount code or
 * the specific gate failure (not eligible, expired, already redeemed, and so
 * on). Surfacing that reason is the whole point of the UI.
 */
export default function RedeemDialog({
  result,
  onClose,
}: {
  result: RedeemResult | null
  onClose: () => void
}) {
  if (!result) return null

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
        aria-modal="true"
      >
        {result.success ? (
          <div className="text-center">
            <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.5"
                className="size-6"
                aria-hidden="true"
              >
                <path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </div>
            <h3 className="mt-3 text-lg font-semibold text-slate-900">Offer redeemed</h3>
            <p className="mt-1 text-sm text-slate-600">
              {result.offer.title} · {result.offer.brand?.name}
            </p>
            <div className="mt-4 rounded-lg border border-dashed border-emerald-300 bg-emerald-50 px-4 py-3">
              <div className="text-xs font-medium uppercase tracking-wide text-emerald-700">
                Your discount code
              </div>
              <div className="mt-1 font-mono text-lg font-bold text-emerald-900">{result.code}</div>
            </div>
          </div>
        ) : (
          <div className="text-center">
            <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.5"
                className="size-6"
                aria-hidden="true"
              >
                <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </div>
            <h3 className="mt-3 text-lg font-semibold text-slate-900">Couldn't redeem</h3>
            <p className="mt-2 text-sm text-slate-600">{result.message}</p>
            {result.reason && (
              <p className="mt-3 text-xs font-medium uppercase tracking-wide text-slate-400">
                Reason: {result.reason.replace(/_/g, ' ')}
              </p>
            )}
          </div>
        )}

        <button
          onClick={onClose}
          className="mt-6 w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
        >
          Close
        </button>
      </div>
    </div>
  )
}
