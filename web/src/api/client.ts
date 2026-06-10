// Tiny fetch wrapper around the same-origin Laravel API. All calls go to
// relative `/api/...` paths (no hardcoded backend URL), attaching the stored
// Sanctum Bearer token. Errors are normalised into ApiError so the UI can show
// the API's specific failure reason (e.g. a redemption gate's error code).

const TOKEN_KEY = 'discount_token'

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}

interface ApiErrorBody {
  message?: string
  error?: string // machine-readable code for redemption failures
  errors?: Record<string, string[]> // field validation errors (422)
}

export class ApiError extends Error {
  status: number
  code?: string
  errors?: Record<string, string[]>

  constructor(body: ApiErrorBody, status: number) {
    super(body.message ?? 'Something went wrong.')
    this.status = status
    this.code = body.error
    this.errors = body.errors
  }

  /** First validation message for a field, if any. */
  fieldError(field: string): string | undefined {
    return this.errors?.[field]?.[0]
  }
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = getToken()
  if (token) headers.Authorization = `Bearer ${token}`
  if (body !== undefined) headers['Content-Type'] = 'application/json'

  const res = await fetch(`/api${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })

  if (res.status === 204) return undefined as T

  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new ApiError(data, res.status)
  return data as T
}

export const api = {
  get: <T>(path: string) => request<T>('GET', path),
  post: <T>(path: string, body?: unknown) => request<T>('POST', path, body),
  put: <T>(path: string, body?: unknown) => request<T>('PUT', path, body),
  del: <T>(path: string) => request<T>('DELETE', path),
}

/** Reachability ping used by the cold-start gate to wake the free-tier server. */
export async function pingHealth(signal?: AbortSignal): Promise<boolean> {
  try {
    const res = await fetch('/api/health', { signal })
    return res.ok
  } catch {
    return false
  }
}
