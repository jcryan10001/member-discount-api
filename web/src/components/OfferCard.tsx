import type { Offer } from '../api/types'
import { formatDate, titleCase } from '../lib/format'
import { Badge } from './ui'

interface Props {
  offer: Offer
  onRedeem: (offer: Offer) => void
  redeeming: boolean
}

export default function OfferCard({ offer, onRedeem, redeeming }: Props) {
  const isExpired = new Date(offer.expires_at) < new Date()
  const isFull = offer.max_redemptions !== null && offer.redemption_count >= offer.max_redemptions

  return (
    <div className="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <div>
          <div className="text-sm font-medium text-slate-500">{offer.brand?.name}</div>
          <h3 className="mt-0.5 text-lg font-semibold text-slate-900">{offer.title}</h3>
        </div>
        <Badge tone="indigo">{offer.discount_description}</Badge>
      </div>

      <p className="mt-2 flex-1 text-sm text-slate-600">{offer.description}</p>

      <div className="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
        <span>For {titleCase(offer.sector)} workers</span>
        <span aria-hidden>·</span>
        <span className={isExpired ? 'text-rose-600' : ''}>
          {isExpired ? 'Expired' : `Ends ${formatDate(offer.expires_at)}`}
        </span>
        {isFull && (
          <>
            <span aria-hidden>·</span>
            <span className="text-rose-600">Fully claimed</span>
          </>
        )}
      </div>

      <button
        onClick={() => onRedeem(offer)}
        disabled={redeeming}
        className="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300"
      >
        {redeeming ? 'Redeeming...' : 'Redeem offer'}
      </button>
    </div>
  )
}
