/**
 * Avatar utilities.
 *
 * The backend uses a deterministic avatar URL based on a SHA-256 hash of the email.
 * In local development, a proxy route may be used to avoid CORS or mixed-content issues.
 */

/**
 * Remote avatar service base URL.
 */
export const AVATAR_BASE_URL =
  'https://webinfo.iutmontp.univ-montp2.fr/~mezencey/my-avatar/public/avatar/'

/**
 * Computes the SHA-256 hash of a string and returns it as hex.
 *
 * @param message Input string.
 * @returns Hex-encoded SHA-256 digest.
 */
async function sha256(message: string): Promise<string> {
  const msgBuffer = new TextEncoder().encode(message)
  const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer)
  const hashArray = Array.from(new Uint8Array(hashBuffer))
  return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('')
}

/**
 * Returns a profile picture URL for an email.
 *
 * In localhost, returns a local proxy route:
 * - /avatar-proxy/{hash}
 *
 * Otherwise, uses the remote avatar service:
 * - {@link AVATAR_BASE_URL}{hash}
 *
 * @param email User email.
 */
export async function getProfilePictureUrl(email: string): Promise<string> {
  const hash = await sha256(email)
  if (typeof window !== 'undefined' && window.location.hostname === 'localhost') {
    return `/avatar-proxy/${hash}`
  }
  return AVATAR_BASE_URL + hash
}

/**
 * Returns a synchronous placeholder URL.
 *
 * @returns Placeholder avatar URL.
 */
export function getProfilePictureUrlSync(): string {
  return AVATAR_BASE_URL + 'placeholder'
}
