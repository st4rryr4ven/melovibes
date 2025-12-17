export interface User {
    id: number;
    login: string;
    email: string;
}

export interface JwtResponse {
    token: string
    refresh_token?: string
}

export interface LoginResult {
    success: boolean
    error?: string
}
