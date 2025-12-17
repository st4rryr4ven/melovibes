import type {JwtResponse} from '@/types'

export const apiStore = {
    apiUrl: "https://localhost/site_de_musique/api/public/api/",

    getAll(ressource: string): Promise<unknown> {
        return fetch(this.apiUrl + ressource)
            .then(response => response.json())
            .then(data => data.member);
    },

    login(login: string, password: string): Promise<JwtResponse> {
        return fetch(this.apiUrl + 'auth', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({login, password})
        })
            .then(res => {
                if (!res.ok) throw new Error('Login failed');
                return res.json() as Promise<JwtResponse>;
            });
    },
    logout(): Promise<void> {
        return fetch(this.apiUrl + 'token/invalidate', {
            method: 'POST',
            credentials: 'include',
            headers: {'Content-Type': 'application/json'},
        }).then(res => {
            if (!res.ok) throw new Error('Logout failed');
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

    refresh(): Promise<JwtResponse> {
        return fetch(this.apiUrl + 'token/refresh', {
            method: 'POST',
            credentials: 'include',
            headers: {'Content-Type': 'application/json'},
        }).then(res => {
            if (!res.ok) throw new Error('Token refresh failed');
            return res.json() as Promise<JwtResponse>;
        });
    }
}
