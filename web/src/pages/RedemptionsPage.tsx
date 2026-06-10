import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { ApiError, api } from '../api/client'
import type { Redemption } from '../api/types'
import { formatDate } from '../lib/format'
import { Alert, Spinner } from '../components/ui'

export default function RedemptionsPage() {
  const [redemptions, setRedemptions] = useState<Redemption[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    api
      .get<{ data: Redemption[] }>('/redemptions')
      .then((res) => setRedemptions(res.data))
      .catch((err) =>
        setError(err instanceof ApiError ? err.message : 'Failed to load your codes.'),
      )
      .finally(() => setLoading(false))
  }, [])

  return (
    <div>
      <h1 className="text-2xl font-bold text-slate-900">My discount codes</h1>
      <p className="mt-1 text-sm text-slate-500">Codes you've redeemed.</p>

      {loading ? (
        <div className="mt-12 flex justify-center">
          <Spinner className="size-7 text-indigo-600" />
        </div>
      ) : error ? (
        <div className="mt-6">
          <Alert tone="error">{error}</Alert>
        </div>
      ) : redemptions.length === 0 ? (
        <p className="mt-12 text-center text-slate-500">
          You haven't redeemed any offers yet.{' '}
          <Link to="/" className="font-medium text-indigo-600 hover:underline">
            Browse offers
          </Link>
        </p>
      ) : (
        <ul className="mt-6 space-y-3">
          {redemptions.map((redemption) => (
            <li
              key={redemption.id}
              className="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
              <div>
                <div className="font-medium text-slate-900">
                  {redemption.offer?.title ?? 'Offer'}
                </div>
                <div className="text-sm text-slate-500">
                  {redemption.offer?.brand?.name} · Redeemed {formatDate(redemption.redeemed_at)}
                </div>
              </div>
              <code className="rounded-lg bg-slate-900 px-3 py-1.5 font-mono text-sm font-semibold text-white">
                {redemption.code_issued}
              </code>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
