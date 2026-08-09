import { create } from 'zustand';
import authApi from '../api/authApi';

export const useAuthStore = create((set, get) => ({
  user: JSON.parse(localStorage.getItem('auth_user') || 'null'),
  token: localStorage.getItem('auth_token') || null,
  isAuthenticated: !!localStorage.getItem('auth_token'),
  isLoading: false,
  isInitializing: true,
  error: null,

  initAuth: async () => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      set({ user: null, token: null, isAuthenticated: false, isInitializing: false });
      return;
    }

    // If already authenticated with valid state, skip re-fetch
    const currentState = get();
    if (currentState.isAuthenticated && currentState.user && currentState.token === token) {
      set({ isInitializing: false });
      return;
    }

    try {
      set({ isLoading: true });
      const response = await authApi.me();
      const user = response.data?.user || response.data;
      
      localStorage.setItem('auth_user', JSON.stringify(user));
      set({
        user,
        token,
        isAuthenticated: true,
        isLoading: false,
        isInitializing: false,
        error: null,
      });
    } catch (err) {
      console.warn('Authentication token expired or invalid:', err);
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      set({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
        isInitializing: false,
        error: null,
      });
    }
  },

  login: async (credentials) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authApi.login(credentials);
      const { user, token } = response.data || {};
      
      if (token) {
        localStorage.setItem('auth_token', token);
        localStorage.setItem('auth_user', JSON.stringify(user));
      }
      
      set({
        user,
        token,
        isAuthenticated: true,
        isLoading: false,
        isInitializing: false,
        error: null,
      });
      return response;
    } catch (err) {
      const message = err.message || 'Login failed. Please check your credentials.';
      set({ isLoading: false, error: message });
      throw err;
    }
  },

  register: async (registerData) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authApi.register(registerData);
      const { user, token } = response.data || {};
      
      if (token) {
        localStorage.setItem('auth_token', token);
        localStorage.setItem('auth_user', JSON.stringify(user));
      }
      
      set({
        user,
        token,
        isAuthenticated: true,
        isLoading: false,
        isInitializing: false,
        error: null,
      });
      return response;
    } catch (err) {
      const message = err.message || 'Registration failed. Please try again.';
      set({ isLoading: false, error: message });
      throw err;
    }
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await authApi.logout();
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      set({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
        error: null,
      });
    }
  },

  updateProfile: async (profileData) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authApi.updateProfile(profileData);
      const updatedUser = response.data?.user || response.data;
      
      localStorage.setItem('auth_user', JSON.stringify(updatedUser));
      set({
        user: updatedUser,
        isLoading: false,
        error: null,
      });
      return response;
    } catch (err) {
      const message = err.message || 'Profile update failed.';
      set({ isLoading: false, error: message });
      throw err;
    }
  },

  resetAuth: () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    set({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      isInitializing: false,
      error: null,
    });
  },

  clearError: () => set({ error: null }),
}));

export default useAuthStore;
