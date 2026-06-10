import { Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext'
import ColdStartGate from './components/ColdStartGate'
import Layout from './components/Layout'
import ProtectedRoute from './components/ProtectedRoute'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import OffersPage from './pages/OffersPage'
import RedemptionsPage from './pages/RedemptionsPage'
import VerificationPage from './pages/VerificationPage'
import AdminVerificationsPage from './pages/admin/AdminVerificationsPage'

export default function App() {
  return (
    // Wake the free-tier server first, then mount auth + routes.
    <ColdStartGate>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />

          {/* Authenticated member area */}
          <Route element={<ProtectedRoute />}>
            <Route element={<Layout />}>
              <Route path="/" element={<OffersPage />} />
              <Route path="/redemptions" element={<RedemptionsPage />} />
              <Route path="/verify" element={<VerificationPage />} />
            </Route>
          </Route>

          {/* Admin area */}
          <Route element={<ProtectedRoute adminOnly />}>
            <Route element={<Layout />}>
              <Route path="/admin/verifications" element={<AdminVerificationsPage />} />
            </Route>
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </ColdStartGate>
  )
}
