// Campaign Types
export interface Campaign {
  id: number;
  title: string;
  description: string;
  targetAmount: number;
  currentAmount: number;
  currency: string;
  startDate: string;
  endDate: string;
  status: 'draft' | 'active' | 'completed' | 'cancelled';
  category: string;
  organizationId: number;
  employeeId: number;
  imageUrl?: string;
  donationsCount: number;
  progress: number;
  daysRemaining: number;
  createdAt: string;
  updatedAt: string;
}

// Donation Types
export interface Donation {
  id: number;
  campaignId: number;
  employeeId: number;
  amount: number;
  currency: string;
  message?: string;
  isAnonymous: boolean;
  isRecurring: boolean;
  createdAt: string;
}

export interface DonationRequest {
  campaignId: number;
  amount: number;
  currency: string;
  message?: string;
  isAnonymous: boolean;
  isRecurring: boolean;
  paymentMethodId?: string;
}

// Payment Types
export interface PaymentMethod {
  id: string;
  type: 'card' | 'bank_account' | 'paypal';
  last4?: string;
  brand?: string;
  isDefault: boolean;
}

export interface PaymentIntent {
  id: string;
  clientSecret: string;
  amount: number;
  currency: string;
  status: string;
}

// Search Types
export interface SearchFilters {
  query?: string;
  category?: string;
  organizationId?: number;
  status?: Campaign['status'];
  minAmount?: number;
  maxAmount?: number;
  sortBy?: 'title' | 'created_at' | 'target_amount' | 'progress';
  sortOrder?: 'asc' | 'desc';
}

export interface SearchResults {
  campaigns: Campaign[];
  total: number;
  page: number;
  perPage: number;
  hasMore: boolean;
}

// Enhanced Notification Types
export interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info' | 'donation' | 'campaign' | 'milestone';
  category: 'system' | 'donation' | 'campaign' | 'security' | 'organization' | 'payment' | 'approval' | 'maintenance';
  title: string;
  message: string;
  timestamp: string;
  read: boolean;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  actionUrl?: string;
  actionText?: string;
  icon?: string;
  data?: Record<string, any>;
  persistent?: boolean;
  userId?: number;
  organizationId?: number;
  campaignId?: number;
  donationId?: number;
  expiresAt?: string;
}

export interface NotificationPreferences {
  id: number;
  userId: number;
  emailNotifications: boolean;
  browserNotifications: boolean;
  smsNotifications: boolean;
  categories: {
    donation: {
      email: boolean;
      browser: boolean;
      sms: boolean;
    };
    campaign: {
      email: boolean;
      browser: boolean;
      sms: boolean;
    };
    milestone: {
      email: boolean;
      browser: boolean;
      sms: boolean;
    };
    system: {
      email: boolean;
      browser: boolean;
      sms: boolean;
    };
    security: {
      email: boolean;
      browser: boolean;
      sms: boolean;
    };
  };
  quietHours: {
    enabled: boolean;
    startTime: string;
    endTime: string;
  };
  frequency: 'immediate' | 'hourly' | 'daily' | 'weekly';
}

export interface ToastNotification {
  id: string;
  type: Notification['type'];
  title: string;
  message: string;
  duration?: number;
  persistent?: boolean;
  actions?: NotificationAction[];
}

export interface NotificationAction {
  label: string;
  action: () => void | Promise<void>;
  style?: 'primary' | 'secondary' | 'danger';
}

export interface WebSocketMessage {
  event: string;
  data: Notification;
  channel: string;
  timestamp: string;
}

export interface NotificationFilters {
  category?: Notification['category'][];
  type?: Notification['type'][];
  read?: boolean;
  priority?: Notification['priority'][];
  dateFrom?: string;
  dateTo?: string;
  limit?: number;
  offset?: number;
}

// API Response Types
export interface ApiResponse<T> {
  data: T;
  message?: string;
  success: boolean;
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

// Form Validation Types
export interface ValidationError {
  field: string;
  message: string;
}

export interface ValidationErrors {
  [key: string]: string[];
}

// Component Props Types
export interface DonationFormProps {
  campaignId: number;
  minAmount?: number;
  maxAmount?: number;
  suggestedAmounts?: number[];
  currency?: string;
}

export interface CampaignProgressProps {
  campaignId: number;
  initialProgress?: number;
  showAnimation?: boolean;
  updateInterval?: number;
}

export interface SearchResultsProps {
  initialFilters?: SearchFilters;
  showFilters?: boolean;
  pageSize?: number;
}

export interface NotificationCenterProps {
  maxNotifications?: number;
  showUnreadOnly?: boolean;
}

// Store State Types
export interface DonationState {
  loading: boolean;
  error: string | null;
  paymentMethods: PaymentMethod[];
  currentDonation: DonationRequest | null;
  paymentIntent: PaymentIntent | null;
}

export interface CampaignState {
  campaigns: Campaign[];
  currentCampaign: Campaign | null;
  loading: boolean;
  error: string | null;
}

export interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  loading: boolean;
  filters: NotificationFilters;
  connected: boolean;
  lastSeen: string | null;
}

export interface NotificationPreferencesState {
  preferences: NotificationPreferences | null;
  loading: boolean;
  saving: boolean;
}

// Event Types
export interface DonationCompleteEvent {
  donation: Donation;
  campaign: Campaign;
}

export interface CampaignUpdateEvent {
  campaignId: number;
  progress: number;
  currentAmount: number;
}

export interface NotificationEvent {
  notification: Notification;
}