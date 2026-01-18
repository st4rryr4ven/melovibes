/**
 * Standardized error thrown by {@link apiJson} when a request fails.
 *
 * In addition to the message, it exposes the HTTP status code and the parsed payload (if any) so the UI can render
 * a helpful error message.
 */
export class ApiError extends Error {
  status: number
  payload?: unknown

  constructor(message: string, status: number, payload?: unknown) {
    super(message)
    this.status = status
    this.payload = payload
  }
}

/**
 * Base URL of the backend API.
 *
 * It is expected to be configured via Vite environment variable VITE_API_URL.
 */
export const API_URL = String(import.meta.env.VITE_API_URL ?? '')

/**
 * Options for {@link apiJson}.
 *
 * - JSON: automatically JSON-stringified request body.
 * - contentType: overrides the default "application/json" content-type when json is provided.
 */
type ApiJsonInit = RequestInit & {
  json?: unknown
  contentType?: string
}

/**
 * Builds an absolute URL from a relative API path.
 */
function buildUrl(path: string): string {
  const base = API_URL.endsWith('/') ? API_URL.slice(0, -1) : API_URL
  const p = path.startsWith('/') ? path : `/${path}`
  return `${base}${p}`
}

/**
 * Parses a response body as JSON when the content-type indicates JSON, otherwise as text.
 */
async function parseBody(res: Response): Promise<unknown> {
  const contentType = res.headers.get('content-type') ?? ''
  if (contentType.includes('application/json') || contentType.includes('application/ld+json')) {
    return res.json().catch(() => null)
  }
  return res.text().catch(() => null)
}

/**
 * Performs an HTTP request to the backend API and parses the response body.
 *
 * - Automatically prepends {@link API_URL}.
 * - Sends credentials (cookies) for session-based authentication.
 * - When the response is JSON (including Hydra), parses it and returns it.
 * - Throws {@link ApiError} when the response status is not OK.
 */
export async function apiJson<T>(path: string, init: ApiJsonInit = {}): Promise<T> {
  const { json, headers, contentType, ...rest } = init

  const mergedHeaders: Record<string, string> = {
    ...(headers ?? {})
  } as Record<string, string>

  if (json !== undefined && !mergedHeaders['Content-Type']) {
    mergedHeaders['Content-Type'] = contentType ?? 'application/json'
  }

  const res = await fetch(buildUrl(path), {
    credentials: 'include',
    ...rest,
    headers: mergedHeaders,
    body: json !== undefined ? JSON.stringify(json) : rest.body
  })

  if (!res.ok) {
    const payload = await parseBody(res)
    let message = `Request failed with status ${res.status}`

    if (payload !== null && typeof payload === 'object') {
      const obj = payload as Record<string, unknown>
      if (typeof obj.message === 'string' && obj.message.trim() !== '') {
        message = obj.message
      } else if (typeof obj.error === 'string' && obj.error.trim() !== '') {
        message = obj.error
      } else if (typeof obj['hydra:description'] === 'string' && (obj['hydra:description'] as string).trim() !== '') {
        message = obj['hydra:description'] as string
      }
    }

    throw new ApiError(message, res.status, payload)
  }

  return (await parseBody(res)) as T
}

/**
 * Convenience wrapper for endpoints that do not return a useful payload.
 */
export async function apiVoid(path: string, init: ApiJsonInit = {}): Promise<void> {
  await apiJson<unknown>(path, init)
}

/**
 * Fetches a collection endpoint and returns an array of items.
 *
 * Supports both plain JSON arrays and API Platform Hydra collections.
 */
export async function apiCollection<T>(path: string, init: ApiJsonInit = {}): Promise<T[]> {
  const data = await apiJson<unknown>(path, init)
  if (!data || typeof data !== 'object') return []
  const obj = data as Record<string, unknown>
  const member = (obj.member ?? obj['hydra:member']) as unknown
  return (Array.isArray(member) ? member : []) as T[]
}
