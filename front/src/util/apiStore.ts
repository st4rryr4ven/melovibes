import type {User} from '@/types'
import {storeAuthentification} from "@/stores/storeAuthentification.ts";

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

  async login(login: string, password: string): Promise<User> {
    const res = await fetch(this.apiUrl + 'auth', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({login, password})
    });
    if (!res.ok) {
      const error = await res.json().catch(() => ({}));
      throw new Error(error.message || 'Login failed');
    }
    return await (await res.json() as Promise<User>);
  },

  async logout(): Promise<any> {
    const res = await fetch(this.apiUrl + 'token/invalidate', {
      method: 'POST',
      credentials: 'include'
    });
    if (!res.ok) {
      throw new Error('Logout failed');
    }
    return res;
  },

  async register(user: { login: string; password: string; email: string }): Promise<any> {
    const res = await fetch(this.apiUrl + 'users/register', {
      method: 'POST',
      headers: {'Content-Type': 'application/ld+json'},
      body: JSON.stringify({
        login: user.login,
        plainPassword: user.password,
        email: user.email
      })
    });
    if (!res.ok) {
      throw new Error('Registration failed');
    }
    return res;
  },

  async refresh(): Promise<any> {
    const res = await fetch(this.apiUrl + 'token/refresh', {
      method: 'POST',
      credentials: 'include'
    });
    if (!res.ok) {
      throw new Error('Refresh failed');
    }
    return res;
  },

  async updateUser(id: number, data: any): Promise<any> {
    const res = await fetch(this.apiUrl + 'users/' + id, {
      method: 'PATCH',
      headers: {'Content-Type': 'application/merge-patch+json'},
      credentials: 'include',
      body: JSON.stringify(data)
    });
    if (!res.ok) {
      const error = await res.json().catch(() => null);
      if (res.status === 401) throw new Error('Authentification échouée. Veuillez vous reconnecter.');
      throw new Error(error?.message || `Update failed with status ${res.status}`);
    }
    storeAuthentification.init();
    return res;
  },

  async deleteUser(id: number): Promise<any> {
    const res = await fetch(this.apiUrl + 'users/' + id, {
      method: 'DELETE',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include'
    });
    if (!res.ok) {
      const error = await res.json().catch(() => null);
      if (res.status === 401) throw new Error('Authentification échouée. Veuillez vous reconnecter.');
      throw new Error(error?.message || 'Delete failed');
    }
    return res;
  },

  async me(): Promise<User> {
    const res = await fetch(this.apiUrl + 'me', {
      method: 'GET',
      credentials: 'include'
    });
    if (!res.ok) throw new Error('Not authenticated');
    return await (await res.json() as Promise<User>);
  },


  async getAll(resource: string): Promise<User[]> {
    const res = await fetch(this.apiUrl + resource, {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include'
    })

    if (!res.ok) {
      if (res.status === 401) {
        throw new Error('Non authentifié')
      }
      throw new Error(`GET ${resource} failed`)
    }

    const data = await res.json()
    return data.member ?? data['hydra:member'] ?? []
  },

  async getOne(resource: string, id: number): Promise<any> {
    const res = await fetch(`${this.apiUrl}${resource}/${id}`, {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
    });

    if (!res.ok) {
      if (res.status === 401) {
        throw new Error('Non authentifié');
      }
      throw new Error(`GET ${resource}/${id} échoué`);
    }

    const data = await res.json();
    return data;
  },

  async patch(resource: string, payload: object): Promise<any> {
    const res = await fetch(`${this.apiUrl}${resource}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
      credentials: 'include',
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      console.error(await res.text()); // <-- montre le message exact de ApiPlatform
      if (res.status === 401) throw new Error('Non authentifié');
      if (res.status === 403) throw new Error('Interdit (403) - vérifier rôle utilisateur');
      throw new Error(`PATCH ${resource} failed`);
    }

    return await res.json();
  },

  async delete(resource: string): Promise<void> {
    const res = await fetch(`${this.apiUrl}${resource}`, {
      method: 'DELETE',
      credentials: 'include',
    });

    if (!res.ok) {
      if (res.status === 401) throw new Error('Non authentifié');
      throw new Error(`DELETE ${resource} failed`);
    }
  }





};
