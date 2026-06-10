import { useEffect, useState } from 'react'
import { ApiError, api } from '../../api/client'
import type { VerificationRequest } from '../../api/types'
import { formatDate, titleCase } from '../../lib/format'
import { Alert, Spinner } from '../../components/ui'

export default function AdminVerificationsPage() {
  const [requests, setRequests] = useState<VerificationRequest[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [actingId, setActingId] = useState<number | null>(null)

  useEffect(() => {
    api
      .get<{ data: VerificationRequest[] }>('/admin/verifications')
      .then((res) => setRequests(res.data))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Failed to load requests.'))
      .finally(() => setLoading(false))
  }, [])

  async function decide(id: number, action: 'approve' | 'reject') {
    setActingId(id)
    setError(null)
    try {
      await api.post(`/admin/verifications/${id}/${action}`)
      setRequests((prev) => prev.filter((request) => request.id !== id))
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Action failed.')
    } finally {
      setActingId(null)
    }
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-slate-900">Pending verifications</h1>
      <p className="mt-1 text-sm text-slate-500">
        Approve to verify a member, or reject the request.
      </p>

      {error && (
        <div className="mt-4">
          <Alert tone="error">{error}</Alert>
        </div>
      )}

      {loading ? (
        <div className="mt-12 flex justify-center">
          <Spinner className="size-7 text-indigo-600" />
        </div>
      ) : requests.length === 0 ? (
        <p className="mt-12 text-center text-slate-500">No pending requests right now.</p>
      ) : (
        <ul className="mt-6 space-y-3">
          {requests.map((request) => (
            <li
              key={request.id}
              className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <div className="font-medium text-slate-900">{request.user?.name}</div>
                  <div className="text-sm text-slate-500">
                    {request.user?.email} ·{' '}
                    {request.user?.sector ? titleCase(request.user.sector) : 'n/a'} ·{' '}
                    {formatDate(request.created_at)}
                  </div>
                  <div className="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    {request.proof_reference}
                  </div>
                </div>
                <div className="flex shrink-0 gap-2">
                  <button
                    disabled={actingId === request.id}
                    onClick={() => decide(request.id, 'approve')}
                    className="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:bg-slate-300"
                  >
                    Approve
                  </button>
                  <button
                    disabled={actingId === request.id}
                    onClick={() => decide(request.id, 'reject')}
                    className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                  >
                    Reject
                  </button>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
