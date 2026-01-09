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
export interface Artist {
  id?: number;
  name: string;
  "@id"?: string;
  "@type"?: string;
}

export interface Music {
  id: number;
  title: string;
  artists: Artist[];
  genre: string[];
  picture?: string;
  link?: string;
  popularity?: number;
  isValidated: boolean
}

