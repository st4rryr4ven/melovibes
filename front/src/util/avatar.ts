export const AVATAR_BASE_URL =
  'https://webinfo.iutmontp.univ-montp2.fr/~mezencey/my-avatar/public/avatar/'

async function sha256(message: string): Promise<string> {
  const msgBuffer = new TextEncoder().encode(message)
  const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer)
  const hashArray = Array.from(new Uint8Array(hashBuffer))
  return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('')
}

export async function getProfilePictureUrl(email: string): Promise<string> {
  const hash = await sha256(email)
  if (typeof window !== 'undefined' && window.location.hostname === 'localhost') {
    return `/avatar-proxy/${hash}`
  }
  return AVATAR_BASE_URL + hash
}

export function getProfilePictureUrlSync(): string {
  return AVATAR_BASE_URL + 'placeholder'
}
