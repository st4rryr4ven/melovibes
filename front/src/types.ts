export interface User {
    id: number;
    login: string;
    email: string;
    roles: string[]
}

export interface UpdateUserPayload {
    email?: string
    plainPassword?: string
    currentPlainPassword: string
}

export interface LoginResult {
    success: boolean
    error?: string
}
