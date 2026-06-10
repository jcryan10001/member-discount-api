import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import { Spinner } from './ui'

/**
 * Guards a route subtree. While the session is being restored, shows a spinner;
 * unauthenticated users go to /login; non-admins are bounced from admin-only
 * routes. The API enforces the same rules; this is only about UX.
 */
export default function ProtectedRoute({ adminOnly = false }: { adminOnly?: boolean }) {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Spinner className="size-8 text-indigo-600" />
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />
  if (adminOnly && !user.is_admin) return <Navigate to="/" replace />

  return <Outlet />
}
