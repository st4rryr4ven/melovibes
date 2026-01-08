import type {UpdateUserPayload, User} from '@/types'

export const API_URL = import.meta.env.VITE_API_URL;
export const AVATAR_BASE_URL =
    "https://webinfo.iutmontp.univ-montp2.fr/~mezencey/my-avatar/public/avatar/";

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

export function getProfilePictureUrlSync(): string {
    return AVATAR_BASE_URL + 'placeholder';
}

export const apiStore = {
    apiUrl: API_URL,

    login(login: string, password: string): Promise<User> {
        return fetch(this.apiUrl + 'auth', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include',
            body: JSON.stringify({login, password})
        }).then(async res => {
            if (!res.ok) {
                const error = await res.json().catch(() => ({}));
                throw new Error(error.message || 'Login failed');
            }
            return res.json() as Promise<User>;
        });
    },

    logout(): Promise<void> {
        return fetch(this.apiUrl + 'token/invalidate', {
            method: 'POST',
            credentials: 'include'
        }).then(res => {
            if (!res.ok) {
                throw new Error('Logout failed');
            }
        });
    },

    register(user: { login: string; password: string; email: string }): Promise<void> {
        return fetch(this.apiUrl + 'users/register', {
            method: 'POST',
            headers: {'Content-Type': 'application/ld+json'},
            credentials: 'include',
            body: JSON.stringify({
                login: user.login,
                plainPassword: user.password,
                email: user.email
            })
        }).then(res => {
            if (!res.ok) {
                throw new Error('Registration failed');
            }
        });
    },

    refresh(): Promise<void> {
        return fetch(this.apiUrl + 'token/refresh', {
            method: 'POST',
            credentials: 'include'
        }).then(res => {
            if (!res.ok) {
                throw new Error('Refresh failed');
            }
        });
    },

    updateUser(id: number, data: any): Promise<any> {
        return fetch(this.apiUrl + 'users/' + id, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include', // send cookies
            body: JSON.stringify(data)
        }).then(async res => {
            if (!res.ok) {
                const error = await res.json().catch(() => null);
                if (res.status === 401) throw new Error('Authentification échouée. Veuillez vous reconnecter.');
                throw new Error(error?.message || `Update failed with status ${res.status}`);
            }
            return res.json();
        });
    },

    deleteUser(id: number): Promise<void> {
        return fetch(this.apiUrl + 'users/' + id, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include'
        }).then(async res => {
            if (!res.ok) {
                const error = await res.json().catch(() => null);
                if (res.status === 401) throw new Error('Authentification échouée. Veuillez vous reconnecter.');
                throw new Error(error?.message || 'Delete failed');
            }
        });
    }
};
