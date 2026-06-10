import { createContext, useContext, useEffect, useState } from 'react'
import type { ReactNode } from 'react'
import { api, clearToken, getToken, setToken } from '../api/client'
import type { Sector, User } from '../api/types'

export interface RegisterData {
  name: string
  email: string
  password: string
  sector: Sector
}

interface AuthContextValue {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (data: RegisterData) => Promise<void>
  logout: () => Promise<void>
  refresh: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  // Restore the session on boot if a token is stored.
  useEffect(() => {
    async function restore() {
      if (!getToken()) {
        setLoading(false)
        return
      }
      try {
        const { data } = await api.get<{ data: User }>('/user')
        setUser(data)
      } catch {
        clearToken() // token expired/revoked
      } finally {
        setLoading(false)
      }
    }
    void restore()
  }, [])

  async function login(email: string, password: string) {
    const res = await api.post<{ user: User; token: string }>('/login', { email, password })
    setToken(res.token)
    setUser(res.user)
  }

  async function register(data: RegisterData) {
    const res = await api.post<{ user: User; token: string }>('/register', data)
    setToken(res.token)
    setUser(res.user)
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch {
      // Even if the revoke call fails, drop the local session.
    }
    clearToken()
    setUser(null)
  }

  async function refresh() {
    const { data } = await api.get<{ data: User }>('/user')
    setUser(data)
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, refresh }}>
      {children}
    </AuthContext.Provider>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within an AuthProvider')
  return ctx
}
