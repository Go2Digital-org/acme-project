import axios, { AxiosInstance, AxiosResponse, AxiosError } from 'axios';
import { useNotificationStore } from '@/stores/notification';
import type { ApiResponse, ValidationErrors } from '@/types';

class ApiClient {
  private client: AxiosInstance;
  private static instance: ApiClient;

  private constructor() {
    this.client = axios.create({
      baseURL: '/api',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      timeout: 15000, // 15 seconds
    });

    this.setupInterceptors();
  }

  public static getInstance(): ApiClient {
    if (!ApiClient.instance) {
      ApiClient.instance = new ApiClient();
    }
    return ApiClient.instance;
  }

  private setupInterceptors(): void {
    // Request interceptor for auth token
    this.client.interceptors.request.use(
      (config) => {
        const token = this.getAuthToken();
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor for error handling
    this.client.interceptors.response.use(
      (response: AxiosResponse) => response,
      (error: AxiosError) => {
        return this.handleError(error);
      }
    );
  }

  private getAuthToken(): string | null {
    // Get token from Laravel session or localStorage
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token || null;
  }

  private async handleError(error: AxiosError): Promise<never> {
    const notificationStore = useNotificationStore();
    
    if (error.response) {
      const { status, data } = error.response;
      
      switch (status) {
        case 401:
          // Unauthorized - redirect to login
          notificationStore.showToast('error', 'Authentication Required', 'Please log in to continue.');
          window.location.href = '/login';
          break;
          
        case 403:
          // Forbidden
          notificationStore.showToast('error', 'Access Denied', 'You do not have permission to perform this action.');
          break;
          
        case 422:
          // Validation error - let the component handle it
          break;
          
        case 429:
          // Rate limited
          notificationStore.showToast('warning', 'Rate Limited', 'Too many requests. Please try again later.');
          break;
          
        case 500:
          // Server error
          notificationStore.showToast('error', 'Server Error', 'An internal server error occurred. Please try again.');
          break;
          
        default:
          // Generic error
          const message = (data as any)?.message || 'An unexpected error occurred';
          notificationStore.showToast('error', 'Error', message);
      }
    } else if (error.request) {
      // Network error
      notificationStore.showToast('error', 'Network Error', 'Unable to connect to the server. Please check your connection.');
    }

    return Promise.reject(error);
  }

  // Generic request methods
  async get<T = any>(url: string, params?: Record<string, any>): Promise<T> {
    const response = await this.client.get<ApiResponse<T>>(url, { params });
    return response.data.data;
  }

  async post<T = any>(url: string, data?: any): Promise<T> {
    const response = await this.client.post<ApiResponse<T>>(url, data);
    return response.data.data;
  }

  async put<T = any>(url: string, data?: any): Promise<T> {
    const response = await this.client.put<ApiResponse<T>>(url, data);
    return response.data.data;
  }

  async patch<T = any>(url: string, data?: any): Promise<T> {
    const response = await this.client.patch<ApiResponse<T>>(url, data);
    return response.data.data;
  }

  async delete<T = any>(url: string): Promise<T> {
    const response = await this.client.delete<ApiResponse<T>>(url);
    return response.data.data;
  }

  // Specific API endpoints
  async uploadFile(url: string, file: File, onProgress?: (progress: number) => void): Promise<any> {
    const formData = new FormData();
    formData.append('file', file);

    const config = onProgress ? {
      onUploadProgress: (progressEvent: any) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        onProgress(percentCompleted);
      }
    } : {};

    const response = await this.client.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      ...config
    });

    return response.data.data;
  }

  // Real-time connection for WebSockets (if implemented)
  setupRealTimeConnection(): void {
    // This would be expanded to handle WebSocket connections
    // For now, we'll use polling for real-time updates
    console.log('Real-time connection would be set up here');
  }
}

// Export singleton instance
export const apiClient = ApiClient.getInstance();

// Export convenience functions for use in stores
export const api = {
  get: <T = any>(url: string, params?: Record<string, any>) => apiClient.get<T>(url, params),
  post: <T = any>(url: string, data?: any) => apiClient.post<T>(url, data),
  put: <T = any>(url: string, data?: any) => apiClient.put<T>(url, data),
  patch: <T = any>(url: string, data?: any) => apiClient.patch<T>(url, data),
  delete: <T = any>(url: string) => apiClient.delete<T>(url),
  upload: (url: string, file: File, onProgress?: (progress: number) => void) => 
    apiClient.uploadFile(url, file, onProgress)
};