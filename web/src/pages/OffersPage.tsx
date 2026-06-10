import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { ApiError, api } from '../api/client'
import { useAuth } from '../auth/AuthContext'
import type { Offer, Redemption } from '../api/types'
import OfferCard from '../components/OfferCard'
import RedeemDialog from '../components/RedeemDialog'
import type { RedeemResult } from '../components/RedeemDialog'
import { Alert, Spinner } from '../components/ui'
import { titleCase } from '../lib/format'

export default function OffersPage() {
  const { user } = useAuth()
  const [offers, setOffers] = useState<Offer[]>([])
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [redeemingId, setRedeemingId] = useState<number | null>(null)
  const [result, setResult] = useState<RedeemResult | null>(null)

  useEffect(() => {
    api
      .get<{ data: Offer[] }>('/offers')
      .then((res) => setOffers(res.data))
      .catch((err) =>
        setLoadError(err instanceof ApiError ? err.message : 'Failed to load offers.'),
      )
      .finally(() => setLoading(false))
  }, [])

  async function handleRedeem(offer: Offer) {
    setRedeemingId(offer.id)
    try {
      const res = await api.post<{ data: Redemption }>(`/offers/${offer.id}/redeem`)
      setResult({ offer, success: true, code: res.data.code_issued })
      // Reflect the new global count locally.
      setOffers((prev) =>
        prev.map((o) =>
          o.id === offer.id ? { ...o, redemption_count: o.redemption_count + 1 } : o,
        ),
      )
    } catch (err) {
      if (err instanceof ApiError) {
        setResult({ offer, success: false, message: err.message, reason: err.code })
      } else {
        setResult({ offer, success: false, message: 'Something went wrong.' })
      }
    } finally {
      setRedeemingId(null)
    }
  }

  const notVerified = user?.verification_status !== 'verified'

  return (
    <div>
      <div className="flex items-baseline justify-between gap-4">
        <h1 className="text-2xl font-bold text-slate-900">Offers for you</h1>
        {user?.sector && (
          <span className="text-sm text-slate-500">Showing {titleCase(user.sector)} offers</span>
        )}
      </div>

      {notVerified && (
        <div className="mt-4">
          <Alert tone="warning">
            Your account is <strong>{user?.verification_status}</strong>. You can browse offers, but
            you'll need to be verified to redeem them.{' '}
            <Link to="/verify" className="font-medium underline">
              Get verified
            </Link>
          </Alert>
        </div>
      )}

      {loading ? (
        <div className="mt-12 flex justify-center">
          <Spinner className="size-7 text-indigo-600" />
        </div>
      ) : loadError ? (
        <div className="mt-6">
          <Alert tone="error">{loadError}</Alert>
        </div>
      ) : offers.length === 0 ? (
        <p className="mt-12 text-center text-slate-500">No offers available for your sector yet.</p>
      ) : (
        <div className="mt-6 grid gap-4 sm:grid-cols-2">
          {offers.map((offer) => (
            <OfferCard
              key={offer.id}
              offer={offer}
              redeeming={redeemingId === offer.id}
              onRedeem={handleRedeem}
            />
          ))}
        </div>
      )}

      <RedeemDialog result={result} onClose={() => setResult(null)} />
    </div>
  )
}
