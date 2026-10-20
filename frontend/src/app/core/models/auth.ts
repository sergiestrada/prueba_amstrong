export interface LoginPayload {
  email: string;
  password: string;
}

export interface User {
  id: number;
  nombre: string;
  apellido: string | null;
  email: string;
  rol: string | null;
  rol_id: number;
}

export interface LoginResponse {
  message: string;
  token: string;
  token_type: 'Bearer';
  user: User;
}

export interface MeResponse {
  user: User;
}