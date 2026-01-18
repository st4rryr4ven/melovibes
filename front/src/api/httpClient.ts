/**
 * HTTP client helpers for the Melovibes frontend.
 *
 * This module centralizes:
 * - Base API URL resolution from Vite environment variables.
 * - Fetch calls with cookies enabled (credentials: 'include').
 * - Consistent error handling via {@link ApiError}.
 * - JSON and JSON-LD parsing helpers for API Platform responses.
 */

export class ApiError extends Error {
  /**
   * HTTP status code returned by the API.
   */
  status: number

  /**
   * Parsed response payload (JSON/JSON-LD or text) when available.
   */
  payload?: unknown

  /**
   * @param message Human-readable error message.
   * @param status HTTP status code.
   * @param payload Parsed response payload when available.
   */
  constructor(message: string, status: number, payload?: unknown) {
    super(message)
    this.status = status
    this.payload = payload
  }
}

/**
 * Base URL of the API (e.g. http://localhost:8000/api).
 * Must be provided by Vite as VITE_API_URL.
 */
export const API_URL = String(import.meta.env.VITE_API_URL ?? '')

export type ApiJsonInit = RequestInit & {
  /**
   * JSON payload that will be stringified and sent as request body.
   */
  json?: unknown

  /**
   * Overrides the Content-Type header when {@link ApiJsonInit.json} is provided.
   */
  contentType?: string
}

type UnknownRecord = Record<string, unknown>

function isRecord(v: unknown): v is UnknownRecord {
  return typeof v === 'object' && v !== null
}

function buildUrl(path: string): string {
  const base = API_URL.endsWith('/') ? API_URL.slice(0, -1) : API_URL
  const p = path.startsWith('/') ? path : `/${path}`
  return `${base}${p}`
}

async function parseBody(res: Response): Promise<unknown> {
  const contentType = res.headers.get('content-type') ?? ''
  const isJson = contentType.includes('application/json') || contentType.includes('application/ld+json')

  if (isJson) {
    try {
      return await res.json()
    } catch {
      return null
    }
  }

  try {
    return await res.text()
  } catch {
    return null
  }
}

function extractMessage(payload: unknown, fallback: string): string {
  if (!isRecord(payload)) return fallback

  const message = payload.message
  if (typeof message === 'string' && message.trim() !== '') return message

  const error = payload.error
  if (typeof error === 'string' && error.trim() !== '') return error

  const hydraDescription = payload['hydra:description']
  if (typeof hydraDescription === 'string' && hydraDescription.trim() !== '') return hydraDescription

  return fallback
}

/**
 * Performs an API request and returns the parsed response body.
 *
 * - Automatically prepends {@link API_URL} to the provided path.
 * - Sends cookies (credentials: 'include').
 * - Throws {@link ApiError} on non-2xx responses.
 *
 * @typeParam T Expected parsed response type.
 * @param path API path relative to {@link API_URL}.
 * @param init Fetch options and JSON helpers.
 * @returns Parsed response body (JSON/JSON-LD or text).
 * @throws ApiError When the response status is not OK.
 */
export async function apiJson<T>(path: string, init: ApiJsonInit = {}): Promise<T> {
  const { json, headers, contentType, ...rest } = init

  const mergedHeaders: Record<string, string> = {}
  if (headers) {
    for (const [k, v] of Object.entries(headers as Record<string, string>)) {
      mergedHeaders[k] = v
    }
  }

  if (json !== undefined && !('Content-Type' in mergedHeaders)) {
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
    const fallback = `Request failed with status ${res.status}`
    throw new ApiError(extractMessage(payload, fallback), res.status, payload)
  }

  return (await parseBody(res)) as T
}

/**
 * Performs an API request where the response body is not used.
 *
 * @param path API path relative to {@link API_URL}.
 * @param init Fetch options and JSON helpers.
 * @throws ApiError When the response status is not OK.
 */
export async function apiVoid(path: string, init: ApiJsonInit = {}): Promise<void> {
  await apiJson<unknown>(path, init)
}

/**
 * Extracts a collection array from an API Platform response.
 *
 * Supports both:
 * - JSON-LD format: "hydra:member"
 * - Custom format: "member"
 *
 * @typeParam T Item type.
 * @param path API path relative to {@link API_URL}.
 * @param init Fetch options and JSON helpers.
 * @returns Array of items or an empty array when the payload does not match a collection.
 * @throws ApiError When the response status is not OK.
 */
export async function apiCollection<T>(path: string, init: ApiJsonInit = {}): Promise<T[]> {
  const data = await apiJson<unknown>(path, init)
  if (!isRecord(data)) return []

  const member = data.member
  if (Array.isArray(member)) return member as T[]

  const hydraMember = data['hydra:member']
  if (Array.isArray(hydraMember)) return hydraMember as T[]

  return []
}
