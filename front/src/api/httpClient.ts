export class ApiError extends Error {
  status: number
  payload?: unknown

  constructor(message: string, status: number, payload?: unknown) {
    super(message)
    this.status = status
    this.payload = payload
  }
}

export const API_URL = String(import.meta.env.VITE_API_URL ?? '')

type ApiJsonInit = RequestInit & {
  json?: unknown
  contentType?: string
}

function buildUrl(path: string): string {
  const base = API_URL.endsWith('/') ? API_URL.slice(0, -1) : API_URL
  const p = path.startsWith('/') ? path : `/${path}`
  return `${base}${p}`
}

async function parseBody(res: Response): Promise<unknown> {
  const contentType = res.headers.get('content-type') ?? ''
  if (contentType.includes('application/json') || contentType.includes('application/ld+json')) {
    return res.json().catch(() => null)
  }
  return res.text().catch(() => null)
}

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
    if (typeof payload === 'object' && payload) {
      if ('message' in payload) {
        message = String((payload as any).message)
      } else if ('error' in payload) {
        message = String((payload as any).error)
      } else if ('hydra:description' in payload) {
        message = String((payload as any)['hydra:description'])
      }
    }
    throw new ApiError(message, res.status, payload)
  }
  return (await parseBody(res)) as T
}

export async function apiVoid(path: string, init: ApiJsonInit = {}): Promise<void> {
  await apiJson<unknown>(path, init)
}

export async function apiCollection<T>(path: string, init: ApiJsonInit = {}): Promise<T[]> {
  const data = await apiJson<any>(path, init)
  if (!data || typeof data !== 'object') return []
  return (data.member ?? data['hydra:member'] ?? []) as T[]
}
