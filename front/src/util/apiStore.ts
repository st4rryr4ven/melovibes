import type {User} from '@/types'

export const API_URL = "https://localhost/site_de_musique/api/public/api/"

export const AVATAR_BASE_URL = "https://webinfo.iutmontp.univ-montp2.fr/~mezencey/my-avatar/public/avatar/"

function getTokenFromCookie(): string | null {
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
        const parts = cookie.trim().split('=');
        if (parts.length >= 2 && parts[0] === 'BEARER' && parts[1]) {
            return decodeURIComponent(parts[1]);
        }
    }
    return null;
}

function getAuthHeaders(token?: string | null): HeadersInit {
    const headers: HeadersInit = {'Content-Type': 'application/json'};

    const jwtToken = token || apiStore.currentToken || getTokenFromCookie() || (typeof localStorage !== 'undefined' ? localStorage.getItem('jwt_token') : null);

    if (jwtToken) {
        headers['Authorization'] = `Bearer ${jwtToken}`;
    }

    return headers;
}

async function sha256(message: string): Promise<string> {
    const msgBuffer = new TextEncoder().encode(message);
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}
export async function getProfilePictureUrl(email: string): Promise<string> {
    const hash = await sha256(email);
    if (typeof window !== 'undefined' && window.location.hostname === 'localhost') {
        return `/avatar-proxy/${hash}`;
    }
    return AVATAR_BASE_URL + hash;
}

export function getProfilePictureUrlSync(email: string): string {
    return AVATAR_BASE_URL + 'placeholder';
}

export const apiStore = {
    apiUrl: API_URL,
    currentToken: (typeof localStorage !== 'undefined' ? localStorage.getItem('jwt_token') : null) as string | null,

    getAll(ressource: string): Promise<unknown> {
        return fetch(this.apiUrl + ressource)
            .then(response => response.json())
            .then(data => data.member);
    },

    login(login: string, password: string): Promise<User> {
        return fetch(this.apiUrl + 'auth', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({login, password})
        })
            .then(async res => {
                if (!res.ok) {
                    const error = await res.json().catch(() => ({}));
                    throw new Error(error.message || 'Login failed');
                }
                const data = await res.json();
                if (data.token) {
                    this.currentToken = data.token;
                    if (typeof localStorage !== 'undefined') {
                        localStorage.setItem('jwt_token', data.token);
                    }
                } else {
                    const cookieToken = getTokenFromCookie();
                    if (cookieToken) {
                        this.currentToken = cookieToken;
                        if (typeof localStorage !== 'undefined') {
                            localStorage.setItem('jwt_token', cookieToken);
                        }
                    } else {
                        console.warn('Token not in response body and cookie is not readable (likely HttpOnly). Cookie-based auth will still work.');
                        this.currentToken = null;
                    }
                }
                return {...data, token: data.token || this.currentToken || undefined} as User;
            });
    },

    logout(): Promise<void> {
        return fetch(this.apiUrl + 'token/invalidate', {
            method: 'POST',
            credentials: 'include',
            headers: getAuthHeaders(this.currentToken),
        })
            .then(async res => {
                this.currentToken = null;
                if (typeof localStorage !== 'undefined') {
                    localStorage.removeItem('jwt_token');
                }
                if (res.status === 204 || res.status === 200) return;
                const error = await res.json().catch(() => ({}));
                throw new Error(error.message || `Logout failed: ${res.status}`);
            });
    },

    register(user: { login: string, password: string, email: string }): Promise<Response> {
        return fetch(this.apiUrl + 'users/register', {
            method: 'POST',
            headers: {'Content-Type': 'application/ld+json'},
            body: JSON.stringify({
                login: user.login,
                plainPassword: user.password,
                email: user.email
            })
        })
    },

    refresh(): Promise<User> {
        return fetch(this.apiUrl + 'token/refresh', {
            method: 'POST',
            credentials: 'include',
            headers: {'Content-Type': 'application/json'},
        }).then(async res => {
            if (!res.ok) {
                const error = await res.json().catch(() => ({}));
                throw new Error(error.message || 'Token refresh failed');
            }
            const data = await res.json();
            if (data.token) {
                this.currentToken = data.token;
                if (typeof localStorage !== 'undefined') {
                    localStorage.setItem('jwt_token', data.token);
                }
            }
            return data as User;
        });
    },

    updateUser(id: number, data: any, token?: string | null): Promise<any> {
        const authHeaders = getAuthHeaders(token || this.currentToken);

        return fetch(this.apiUrl + 'users/' + id, {
            method: 'PATCH',
            headers: authHeaders,
            credentials: 'include',
            body: JSON.stringify(data)
        }).then(async res => {
            if (!res.ok) {
                const error = await res.json().catch(() => null);
                const errorMessage = error?.message || `Update failed with status ${res.status}`;

                if (res.status === 401) {
                    throw new Error('Authentification échouée. Veuillez vous reconnecter.');
                }

                throw new Error(errorMessage);
            }
            return res.json();
        });
    },

    deleteUser(id: number, token?: string | null): Promise<void> {
        return fetch(this.apiUrl + 'users/' + id, {
            method: 'DELETE',
            headers: getAuthHeaders(token || this.currentToken),
            credentials: 'include',
        }).then(async res => {
            if (!res.ok) {
                const error = await res.json().catch(() => null);
                throw new Error(error?.message || 'Delete failed');
            }
            this.currentToken = null;
            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem('jwt_token');
            }
        });
    }

}
