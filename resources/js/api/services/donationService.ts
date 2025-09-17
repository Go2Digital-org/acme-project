import { api } from '../client';
import type { 
  Donation, 
  DonationRequest, 
  PaymentMethod, 
  PaymentIntent 
} from '@/types';

export class DonationService {
  /**
   * Create a payment intent for a donation
   */
  static async createPaymentIntent(donationRequest: DonationRequest): Promise<PaymentIntent> {
    return api.post<PaymentIntent>('/payment-intents', donationRequest);
  }

  /**
   * Confirm a payment intent
   */
  static async confirmPayment(
    paymentIntentId: string, 
    paymentMethodId: string,
    donationData: DonationRequest
  ): Promise<Donation> {
    return api.post<Donation>(`/payment-intents/${paymentIntentId}/confirm`, {
      payment_method: paymentMethodId,
      ...donationData
    });
  }

  /**
   * Get user's payment methods
   */
  static async getPaymentMethods(): Promise<PaymentMethod[]> {
    return api.get<PaymentMethod[]>('/payment-methods');
  }

  /**
   * Add a new payment method
   */
  static async addPaymentMethod(paymentMethodData: {
    type: 'card' | 'bank_account';
    stripePaymentMethodId: string;
    isDefault?: boolean;
  }): Promise<PaymentMethod> {
    return api.post<PaymentMethod>('/payment-methods', paymentMethodData);
  }

  /**
   * Remove a payment method
   */
  static async removePaymentMethod(paymentMethodId: string): Promise<void> {
    return api.delete(`/payment-methods/${paymentMethodId}`);
  }

  /**
   * Set default payment method
   */
  static async setDefaultPaymentMethod(paymentMethodId: string): Promise<PaymentMethod> {
    return api.patch<PaymentMethod>(`/payment-methods/${paymentMethodId}/default`, {});
  }

  /**
   * Get donation history for current user
   */
  static async getDonationHistory(filters?: {
    campaignId?: number;
    startDate?: string;
    endDate?: string;
    page?: number;
    perPage?: number;
  }): Promise<{
    donations: Donation[];
    total: number;
    totalAmount: number;
  }> {
    return api.get('/donations/history', filters);
  }

  /**
   * Get donations for a specific campaign
   */
  static async getCampaignDonations(campaignId: number, options?: {
    includeAnonymous?: boolean;
    limit?: number;
    page?: number;
  }): Promise<{
    donations: Donation[];
    total: number;
    totalAmount: number;
  }> {
    return api.get(`/campaigns/${campaignId}/donations`, options);
  }

  /**
   * Cancel a recurring donation
   */
  static async cancelRecurringDonation(donationId: number): Promise<void> {
    return api.post(`/donations/${donationId}/cancel-recurring`, {});
  }

  /**
   * Update a recurring donation amount
   */
  static async updateRecurringDonation(
    donationId: number, 
    newAmount: number
  ): Promise<Donation> {
    return api.patch(`/donations/${donationId}/recurring`, {
      amount: newAmount
    });
  }

  /**
   * Get donation receipt
   */
  static async getDonationReceipt(donationId: number): Promise<{
    receiptUrl: string;
    receiptNumber: string;
  }> {
    return api.get(`/donations/${donationId}/receipt`);
  }

  /**
   * Process refund for a donation
   */
  static async requestRefund(
    donationId: number, 
    reason: string, 
    amount?: number
  ): Promise<{
    refundId: string;
    status: string;
    amount: number;
  }> {
    return api.post(`/donations/${donationId}/refund`, {
      reason,
      amount
    });
  }

  /**
   * Get top donors for a campaign (leaderboard)
   */
  static async getTopDonors(campaignId: number, limit: number = 10): Promise<{
    donors: {
      name: string;
      amount: number;
      isAnonymous: boolean;
      donatedAt: string;
    }[];
  }> {
    return api.get(`/campaigns/${campaignId}/top-donors`, { limit });
  }

  /**
   * Get donation statistics for current user
   */
  static async getDonationStats(): Promise<{
    totalDonated: number;
    campaignsSupported: number;
    averageDonation: number;
    recurringDonations: number;
    monthlyStats: { month: string; amount: number }[];
  }> {
    return api.get('/donations/stats');
  }

  /**
   * Check donation limits and eligibility
   */
  static async checkDonationEligibility(
    campaignId: number, 
    amount: number
  ): Promise<{
    eligible: boolean;
    reason?: string;
    maxAmount?: number;
    suggestedAmount?: number;
  }> {
    return api.post('/donations/check-eligibility', {
      campaignId,
      amount
    });
  }

  /**
   * Subscribe to donation updates for a campaign
   */
  static subscribeToDonationUpdates(
    campaignId: number, 
    callback: (donation: Donation) => void
  ): () => void {
    // This would implement WebSocket or Server-Sent Events
    // For now, we'll use polling for recent donations
    const interval = setInterval(async () => {
      try {
        const { donations } = await this.getCampaignDonations(campaignId, { 
          limit: 1 
        });
        
        if (donations.length > 0) {
          callback(donations[0]);
        }
      } catch (error) {
        console.warn('Failed to fetch donation updates:', error);
      }
    }, 15000); // Poll every 15 seconds

    return () => clearInterval(interval);
  }

  /**
   * Validate donation amount based on campaign rules
   */
  static validateDonationAmount(
    amount: number, 
    campaignTargetAmount: number, 
    campaignCurrentAmount: number,
    minAmount: number = 1,
    maxAmount: number = 10000
  ): {
    isValid: boolean;
    errors: string[];
    warnings: string[];
  } {
    const errors: string[] = [];
    const warnings: string[] = [];

    if (amount < minAmount) {
      errors.push(`Minimum donation amount is ${minAmount}`);
    }

    if (amount > maxAmount) {
      errors.push(`Maximum donation amount is ${maxAmount}`);
    }

    const remainingAmount = campaignTargetAmount - campaignCurrentAmount;
    if (amount > remainingAmount && remainingAmount > 0) {
      warnings.push(`This donation exceeds the remaining target amount of ${remainingAmount}`);
    }

    if (amount > campaignTargetAmount * 0.1) {
      warnings.push('Large donation - you will be highlighted as a major contributor');
    }

    return {
      isValid: errors.length === 0,
      errors,
      warnings
    };
  }
}