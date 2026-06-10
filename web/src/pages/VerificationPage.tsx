import { useState } from 'react'
import type { FormEvent } from 'react'
import { ApiError, api } from '../api/client'
import { useAuth } from '../auth/AuthContext'
import { Alert, Badge } from '../components/ui'

const statusTone = { verified: 'emerald', pending: 'amber', rejected: 'rose' } as const

const inputClass =
  'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500'

export default function VerificationPage() {
  const { user, refresh } = useAuth()
  const [proof, setProof] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [submitted, setSubmitted] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const status = user?.verification_status ?? 'pending'

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    setSubmitting(true)
    try {
      await api.post('/verification', { proof_reference: proof })
      setSubmitted(true)
      setProof('')
      await refresh()
    } catch (err) {
      setError(
        err instanceof ApiError
          ? (err.fieldError('proof_reference') ?? err.message)
          : 'Submission failed.',
      )
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="max-w-xl">
      <h1 className="text-2xl font-bold text-slate-900">Verification</h1>
      <div className="mt-2 flex items-center gap-2 text-sm text-slate-600">
        <span>Current status:</span>
        <Badge tone={statusTone[status]}>{status}</Badge>
      </div>

      {status === 'verified' ? (
        <div className="mt-6">
          <Alert tone="success">You're verified. You can redeem offers for your sector.</Alert>
        </div>
      ) : (
        <form
          onSubmit={handleSubmit}
          className="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          <p className="text-sm text-slate-600">
            Submit proof of eligibility for your sector. In this demo it's a free-text reference
            (e.g. <em>"NHS staff ID: ABC-12345"</em>); a production system would accept a document
            upload.
          </p>
          {submitted && (
            <Alert tone="info">Thanks, your request has been submitted for review.</Alert>
          )}
          {error && <Alert tone="error">{error}</Alert>}
          <label className="block">
            <span className="text-sm font-medium text-slate-700">Proof reference</span>
            <input
              value={proof}
              onChange={(e) => setProof(e.target.value)}
              required
              placeholder="NHS staff ID: ABC-12345"
              className={inputClass}
            />
          </label>
          <button
            type="submit"
            disabled={submitting}
            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:bg-slate-300"
          >
            {submitting ? 'Submitting...' : 'Submit for verification'}
          </button>
        </form>
      )}
    </div>
  )
}
