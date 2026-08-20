import { create } from 'zustand';
import authApi from '../api/authApi';

export const useAuthStore = create((set) => ({
  user: JSON.parse(localStorage.getItem('auth_user') || 'null'),
  isAuthenticated: !!localStorage.getItem('auth_user'),
  isLoading: false,
  isInitializing: true,
  error: null,

  initAuth: async () => {
    const user = JSON.parse(localStorage.getItem('auth_user') || 'null');
    if (!user) {
      set({ user: null, isAuthenticated: false, isInitializing: false });
      return;
    }

    try {
      set({ isLoading: true, isInitializing: true });
      const response = await authApi.me();
      const freshUser = response.data?.user || response.data;
      
      localStorage.setItem('auth_user', JSON.stringify(freshUser));
      set({
        user: freshUser,
        isAuthenticated: true,
        isLoading: false,
        isInitializing: false,
        error: null,
      });
    } catch (err) {
      console.warn('Session expired or invalid:', err);
      localStorage.removeItem('auth_user');
      set({
        user: null,
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
      const { user } = response.data || {};
      
      if (user) {
        localStorage.setItem('auth_user', JSON.stringify(user));
      }
      
      set({
        user,
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
      const { user } = response.data || {};
      
      if (user) {
        localStorage.setItem('auth_user', JSON.stringify(user));
      }
      
      set({
        user,
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
      localStorage.removeItem('auth_user');
      set({
        user: null,
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
    localStorage.removeItem('auth_user');
    set({
      user: null,
      isAuthenticated: false,
      isLoading: false,
      isInitializing: false,
      error: null,
    });
  },

  clearError: () => set({ error: null }),
}));

export default useAuthStore;
