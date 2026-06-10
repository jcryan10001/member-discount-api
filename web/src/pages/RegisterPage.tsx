import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/AuthContext'
import { SECTORS } from '../api/types'
import type { Sector } from '../api/types'
import { titleCase } from '../lib/format'
import { Alert } from '../components/ui'

const inputClass =
  'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500'

export default function RegisterPage() {
  const { user, register } = useAuth()
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [sector, setSector] = useState<Sector>('healthcare')
  const [apiError, setApiError] = useState<ApiError | null>(null)
  const [generalError, setGeneralError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  if (user) return <Navigate to="/" replace />

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setApiError(null)
    setGeneralError(null)
    setSubmitting(true)
    try {
      await register({ name, email, password, sector })
      navigate('/')
    } catch (err) {
      if (err instanceof ApiError) setApiError(err)
      else setGeneralError('Registration failed.')
    } finally {
      setSubmitting(false)
    }
  }

  const fieldError = (field: string) => apiError?.fieldError(field)
  const topError = generalError ?? (apiError && !apiError.errors ? apiError.message : null)

  return (
    <div className="flex min-h-screen items-center justify-center px-4 py-12">
      <div className="w-full max-w-sm">
        <h1 className="text-center text-2xl font-bold text-slate-900">Create an account</h1>
        <p className="mt-1 text-center text-sm text-slate-500">New members start unverified.</p>

        <form
          onSubmit={handleSubmit}
          className="mt-8 space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          {topError && <Alert tone="error">{topError}</Alert>}

          <label className="block">
            <span className="text-sm font-medium text-slate-700">Name</span>
            <input
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              className={inputClass}
            />
            {fieldError('name') && (
              <p className="mt-1 text-xs text-rose-600">{fieldError('name')}</p>
            )}
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">Email</span>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className={inputClass}
            />
            {fieldError('email') && (
              <p className="mt-1 text-xs text-rose-600">{fieldError('email')}</p>
            )}
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">Password</span>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className={inputClass}
            />
            {fieldError('password') && (
              <p className="mt-1 text-xs text-rose-600">{fieldError('password')}</p>
            )}
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">Sector</span>
            <select
              value={sector}
              onChange={(e) => setSector(e.target.value as Sector)}
              className={inputClass}
            >
              {SECTORS.map((option) => (
                <option key={option} value={option}>
                  {titleCase(option)}
                </option>
              ))}
            </select>
          </label>

          <button
            type="submit"
            disabled={submitting}
            className="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:bg-slate-300"
          >
            {submitting ? 'Creating...' : 'Create account'}
          </button>
          <p className="text-center text-sm text-slate-500">
            Already have an account?{' '}
            <Link to="/login" className="font-medium text-indigo-600 hover:underline">
              Sign in
            </Link>
          </p>
        </form>
      </div>
    </div>
  )
}
