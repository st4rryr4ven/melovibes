export interface User {
  id: number;
  login: string;
  email: string;
  roles: string[];
  favoriteMusic?: Music[];
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

export interface Music {
  id: number;
  title: string;
  artists: { id: number; name: string }[];
  genre: string[];
  link?: string;
  picture?: string;
  popularity?: number;
  isValidated: boolean;
}

