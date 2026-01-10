import { API_URL } from '@/util/apiStore'

export class ApiError extends Error {
  status: number
  payload?: unknown

  constructor(message: string, status: number, payload?: unknown) {
    super(message)
    this.status = status
    this.payload = payload
  }
}

export async function apiJson<T>(
  path: string,
  init: RequestInit & { json?: unknown } = {}
): Promise<T> {
  const { json, headers, ...rest } = init
  const res = await fetch(API_URL + path, {
    credentials: 'include',
    headers: {
      ...(json ? { 'Content-Type': 'application/json' } : {}),
      ...(headers ?? {})
    },
    body: json !== undefined ? JSON.stringify(json) : rest.body,
    ...rest
  })

  const contentType = res.headers.get('content-type') ?? ''

  const parseBody = async (): Promise<unknown> => {
    if (contentType.includes('application/json') || contentType.includes('application/ld+json')) {
      return res.json().catch(() => null)
    }
    return res.text().catch(() => null)
  }

  if (!res.ok) {
    const payload = await parseBody()
    const message =
      typeof payload === 'object' && payload && 'message' in payload
        ? String(payload.message)
        : `Request failed with status ${res.status}`
    throw new ApiError(message, res.status, payload)
  }

  return (await parseBody()) as T
}
