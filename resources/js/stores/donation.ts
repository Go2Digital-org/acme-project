import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { DonationService } from '@/api/services/donationService';
import type { 
  DonationState, 
  DonationRequest, 
  PaymentMethod, 
  PaymentIntent, 
  Donation
} from '@/types';

export const useDonationStore = defineStore('donation', () => {
  // State
  const loading = ref(false);
  const error = ref<string | null>(null);
  const paymentMethods = ref<PaymentMethod[]>([]);
  const currentDonation = ref<DonationRequest | null>(null);
  const paymentIntent = ref<PaymentIntent | null>(null);

  // Getters
  const defaultPaymentMethod = computed(() => 
    paymentMethods.value.find(pm => pm.isDefault)
  );

  const isProcessing = computed(() => 
    loading.value && !!paymentIntent.value
  );

  // Actions
  const fetchPaymentMethods = async (): Promise<PaymentMethod[]> => {
    loading.value = true;
    error.value = null;

    try {
      paymentMethods.value = await DonationService.getPaymentMethods();
      return paymentMethods.value;
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch payment methods';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createPaymentIntent = async (donationRequest: DonationRequest): Promise<PaymentIntent> => {
    loading.value = true;
    error.value = null;
    currentDonation.value = donationRequest;

    try {
      paymentIntent.value = await DonationService.createPaymentIntent(donationRequest);
      return paymentIntent.value;
    } catch (err: any) {
      error.value = err.message || 'Failed to create payment intent';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const confirmPayment = async (paymentMethodId: string): Promise<Donation> => {
    if (!paymentIntent.value || !currentDonation.value) {
      throw new Error('No payment intent or donation request found');
    }

    loading.value = true;
    error.value = null;

    try {
      const response = await window.axios.post<ApiResponse<Donation>>(
        `/api/payment-intents/${paymentIntent.value.id}/confirm`,
        {
          payment_method: paymentMethodId,
          ...currentDonation.value
        }
      );

      // Clear current state after successful donation
      currentDonation.value = null;
      paymentIntent.value = null;

      return response.data.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Payment confirmation failed';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const addPaymentMethod = async (paymentMethodData: any): Promise<PaymentMethod> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await window.axios.post<ApiResponse<PaymentMethod>>(
        '/api/payment-methods',
        paymentMethodData
      );

      const newPaymentMethod = response.data.data;
      paymentMethods.value.push(newPaymentMethod);

      return newPaymentMethod;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to add payment method';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const clearError = () => {
    error.value = null;
  };

  const reset = () => {
    loading.value = false;
    error.value = null;
    currentDonation.value = null;
    paymentIntent.value = null;
  };

  return {
    // State
    loading,
    error,
    paymentMethods,
    currentDonation,
    paymentIntent,

    // Getters
    defaultPaymentMethod,
    isProcessing,

    // Actions
    fetchPaymentMethods,
    createPaymentIntent,
    confirmPayment,
    addPaymentMethod,
    clearError,
    reset
  };
});