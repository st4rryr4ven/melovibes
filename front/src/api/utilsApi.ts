type UnknownRecord = Record<string, unknown>

export function isRecord(v: unknown): v is UnknownRecord {
  return typeof v === 'object' && v !== null
}

export function iriToId(iri: string): number {
  const parts = iri.split('/')
  const last = parts[parts.length - 1] ?? ''
  const n = Number.parseInt(last, 10)
  return Number.isFinite(n) ? n : 0
}
