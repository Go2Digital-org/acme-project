/**
 * Real-time Admin Notifications for ACME Corp CSR Platform
 * 
 * This script handles WebSocket connections and real-time notifications
 * for the Filament admin dashboard.
 */

class AdminNotificationManager {
    constructor() {
        this.connection = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 5000;
        this.userRole = null;
        
        this.init();
    }

    init() {
        this.userRole = this.getUserRole();
        this.setupWebSocket();
        this.setupNotificationHandlers();
    }

    getUserRole() {
        // Get user role from Laravel blade or meta tag
        const userRole = document.querySelector('meta[name="user-role"]')?.content;
        return userRole || 'employee';
    }

    setupWebSocket() {
        try {
            // Using Laravel Echo with Pusher
            this.connection = window.Echo.channel('admin-dashboard')
                .listen('donation.large', (data) => this.handleLargeDonation(data))
                .listen('campaign.milestone', (data) => this.handleCampaignMilestone(data))
                .listen('organization.verification', (data) => this.handleOrganizationVerification(data))
                .listen('security.alert', (data) => this.handleSecurityAlert(data))
                .listen('system.maintenance', (data) => this.handleSystemMaintenance(data))
                .listen('dashboard.update', (data) => this.handleDashboardUpdate(data))
                .listen('campaign.approval_needed', (data) => this.handleApprovalNeeded(data))
                .listen('payment.failed', (data) => this.handlePaymentFailed(data))
                .listen('compliance.issues', (data) => this.handleComplianceIssues(data));

            // Also listen to role-specific channel
            if (this.userRole && this.userRole !== 'employee') {
                window.Echo.channel(`admin-role-${this.userRole}`)
                    .listen('custom.notification', (data) => this.handleCustomNotification(data));
            }

            console.log('Admin notifications WebSocket connected');
            this.reconnectAttempts = 0;
            
        } catch (error) {
            console.error('Failed to setup WebSocket:', error);
            this.scheduleReconnect();
        }
    }

    handleLargeDonation(data) {
        this.showNotification({
            title: 'Large Donation Received!',
            message: `${data.donor} donated $${data.amount.toLocaleString()} to ${data.campaign}`,
            type: 'success',
            icon: 'heroicon-o-banknotes',
            persistent: true,
            actions: [{
                label: 'View Details',
                action: () => this.navigateTo(`/admin/donations/${data.donation_id}`)
            }]
        });

        // Update dashboard metrics in real-time
        this.updateDashboardMetric('total-raised', data.amount, 'add');
        this.updateDashboardMetric('recent-donations', 1, 'add');
        
        // Play notification sound for large donations
        this.playNotificationSound('large-donation');
    }

    handleCampaignMilestone(data) {
        const milestoneMessages = {
            '25': 'reached 25% of its goal!',
            '50': 'is halfway to its goal!',
            '75': 'is 75% funded!',
            '100': 'has reached its goal!',
            'goal_exceeded': 'has exceeded its goal!'
        };

        const message = milestoneMessages[data.milestone] || `reached a milestone: ${data.milestone}`;
        
        this.showNotification({
            title: 'Campaign Milestone!',
            message: `${data.campaign_title} ${message}`,
            type: data.milestone === '100' || data.milestone === 'goal_exceeded' ? 'success' : 'info',
            icon: 'heroicon-o-trophy',
            actions: [{
                label: 'View Campaign',
                action: () => this.navigateTo(`/admin/campaigns/${data.campaign_id}`)
            }]
        });

        // Update campaign progress in real-time if on campaigns page
        this.updateCampaignProgress(data.campaign_id, data.progress_percentage);
    }

    handleOrganizationVerification(data) {
        const actionMessages = {
            'pending': 'needs verification',
            'verified': 'has been verified',
            'rejected': 'verification was rejected'
        };

        this.showNotification({
            title: 'Organization Update',
            message: `${data.organization_name} ${actionMessages[data.action] || data.action}`,
            type: data.action === 'verified' ? 'success' : 'warning',
            icon: 'heroicon-o-building-office-2',
            actions: [{
                label: 'Review',
                action: () => this.navigateTo(`/admin/organizations/${data.organization_id}`)
            }]
        });
    }

    handleSecurityAlert(data) {
        this.showNotification({
            title: 'Security Alert',
            message: `${data.event}: ${data.details.message || 'Suspicious activity detected'}`,
            type: 'danger',
            icon: 'heroicon-o-shield-exclamation',
            persistent: data.severity === 'high',
            actions: [{
                label: 'Investigate',
                action: () => this.navigateTo('/admin/audit-logs')
            }]
        });

        // Flash the admin panel for high-severity alerts
        if (data.severity === 'high') {
            this.flashSecurityAlert();
        }
    }

    handleSystemMaintenance(data) {
        this.showNotification({
            title: 'System Maintenance Scheduled',
            message: `${data.type} maintenance scheduled for ${new Date(data.scheduled_for).toLocaleString()}`,
            type: 'warning',
            icon: 'heroicon-o-wrench-screwdriver',
            persistent: true
        });
    }

    handleDashboardUpdate(data) {
        // Update real-time dashboard metrics
        this.updateDashboardMetric(data.metric, data.value, 'set', data.metadata);
        
        // Update charts if applicable
        if (data.metadata.chart_data) {
            this.updateChartData(data.metric, data.metadata.chart_data);
        }
    }

    handleApprovalNeeded(data) {
        this.showNotification({
            title: 'Campaign Needs Approval',
            message: `${data.campaign_title} by ${data.manager} requires approval`,
            type: 'warning',
            icon: 'heroicon-o-clock',
            actions: [{
                label: 'Review Now',
                action: () => this.navigateTo(`/admin/campaigns/${data.campaign_id}`)
            }]
        });

        // Update pending approvals counter
        this.updateApprovalsBadge(1);
    }

    handlePaymentFailed(data) {
        this.showNotification({
            title: 'Payment Failed',
            message: `$${data.amount} payment failed: ${data.reason}`,
            type: 'danger',
            icon: 'heroicon-o-x-circle',
            persistent: true,
            actions: [{
                label: 'Investigate',
                action: () => this.navigateTo(`/admin/donations/${data.donation_id}`)
            }]
        });
    }

    handleComplianceIssues(data) {
        this.showNotification({
            title: 'Compliance Issues Detected',
            message: `${data.organization_name}: ${data.issues.join(', ')}`,
            type: data.severity === 'high' ? 'danger' : 'warning',
            icon: 'heroicon-o-exclamation-triangle',
            actions: [{
                label: 'Review Organization',
                action: () => this.navigateTo(`/admin/organizations/${data.organization_id}`)
            }]
        });
    }

    handleCustomNotification(data) {
        this.showNotification({
            title: data.title,
            message: data.message,
            type: data.type,
            icon: data.data.icon || 'heroicon-o-bell',
            persistent: data.type === 'danger',
            actions: data.data.actions || []
        });
    }

    showNotification(notification) {
        // Use Filament's notification system
        if (window.$filament) {
            window.$filament.notify(notification.type, notification.title, notification.message);
        } else {
            // Fallback to browser notifications
            this.showBrowserNotification(notification);
        }

        // Add to notification center
        this.addToNotificationCenter(notification);
    }

    showBrowserNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: '/favicon.ico',
                tag: 'admin-notification'
            });
        }
    }

    addToNotificationCenter(notification) {
        const notificationCenter = document.getElementById('admin-notification-center');
        if (!notificationCenter) return;

        const notificationElement = document.createElement('div');
        notificationElement.className = `notification notification-${notification.type}`;
        notificationElement.innerHTML = `
            <div class="notification-content">
                <h4>${notification.title}</h4>
                <p>${notification.message}</p>
                <small>${new Date().toLocaleTimeString()}</small>
            </div>
            ${notification.actions ? this.renderNotificationActions(notification.actions) : ''}
        `;

        notificationCenter.prepend(notificationElement);
        
        // Auto-remove after 10 seconds unless persistent
        if (!notification.persistent) {
            setTimeout(() => {
                notificationElement.remove();
            }, 10000);
        }
    }

    renderNotificationActions(actions) {
        return `
            <div class="notification-actions">
                ${actions.map(action => `
                    <button onclick="${action.action}" class="notification-action-btn">
                        ${action.label}
                    </button>
                `).join('')}
            </div>
        `;
    }

    updateDashboardMetric(metric, value, operation = 'set', metadata = {}) {
        const metricElement = document.querySelector(`[data-metric="${metric}"]`);
        if (!metricElement) return;

        let currentValue = parseFloat(metricElement.textContent.replace(/[^0-9.-]+/g, '')) || 0;
        let newValue;

        switch (operation) {
            case 'add':
                newValue = currentValue + value;
                break;
            case 'subtract':
                newValue = currentValue - value;
                break;
            case 'set':
            default:
                newValue = value;
                break;
        }

        // Format the value based on metric type
        const formattedValue = this.formatMetricValue(metric, newValue);
        metricElement.textContent = formattedValue;

        // Add animation
        metricElement.classList.add('metric-updated');
        setTimeout(() => metricElement.classList.remove('metric-updated'), 2000);
    }

    formatMetricValue(metric, value) {
        if (metric.includes('amount') || metric.includes('raised') || metric.includes('donation')) {
            return '$' + value.toLocaleString();
        } else if (metric.includes('percentage') || metric.includes('rate')) {
            return value.toFixed(1) + '%';
        } else {
            return Math.floor(value).toLocaleString();
        }
    }

    updateCampaignProgress(campaignId, progressPercentage) {
        const progressBars = document.querySelectorAll(`[data-campaign-id="${campaignId}"] .progress-bar`);
        progressBars.forEach(bar => {
            bar.style.width = `${progressPercentage}%`;
            bar.setAttribute('aria-valuenow', progressPercentage);
        });
    }

    updateApprovalsBadge(increment) {
        const badge = document.querySelector('.approvals-badge');
        if (badge) {
            const currentCount = parseInt(badge.textContent) || 0;
            badge.textContent = currentCount + increment;
            badge.classList.add('badge-updated');
            setTimeout(() => badge.classList.remove('badge-updated'), 2000);
        }
    }

    updateChartData(chartId, newData) {
        // Update Chart.js charts with new data
        const chart = window.Chart.getChart(chartId);
        if (chart) {
            chart.data = newData;
            chart.update('none'); // Update without animation for real-time feel
        }
    }

    flashSecurityAlert() {
        document.body.classList.add('security-alert-flash');
        setTimeout(() => {
            document.body.classList.remove('security-alert-flash');
        }, 3000);
    }

    playNotificationSound(type) {
        const audio = new Audio(`/sounds/notification-${type}.mp3`);
        audio.volume = 0.3;
        audio.play().catch(() => {
            // Ignore audio playback errors (user hasn't interacted with page yet)
        });
    }

    navigateTo(url) {
        window.location.href = url;
    }

    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            return;
        }

        this.reconnectAttempts++;
        
        setTimeout(() => {
            console.log(`Attempting to reconnect... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            this.setupWebSocket();
        }, this.reconnectDelay * this.reconnectAttempts);
    }

    disconnect() {
        if (this.connection) {
            this.connection.disconnect();
            this.connection = null;
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize for admin users
    const userRole = document.querySelector('meta[name="user-role"]')?.content;
    const adminRoles = ['super_admin', 'csr_admin', 'finance_admin', 'hr_manager'];
    
    if (adminRoles.includes(userRole)) {
        window.adminNotifications = new AdminNotificationManager();
        
        // Request notification permissions
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.adminNotifications) {
        window.adminNotifications.disconnect();
    }
});

// CSS for notification animations
const notificationCSS = `
    .metric-updated {
        animation: metricPulse 2s ease-in-out;
    }
    
    @keyframes metricPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); color: #10b981; }
        100% { transform: scale(1); }
    }
    
    .badge-updated {
        animation: badgePulse 2s ease-in-out;
    }
    
    @keyframes badgePulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); background-color: #f59e0b; }
        100% { transform: scale(1); }
    }
    
    .security-alert-flash {
        animation: securityFlash 3s ease-in-out;
    }
    
    @keyframes securityFlash {
        0%, 100% { background-color: inherit; }
        25%, 75% { background-color: rgba(239, 68, 68, 0.1); }
        50% { background-color: rgba(239, 68, 68, 0.2); }
    }
    
    .notification {
        border-left: 4px solid #3b82f6;
        background: white;
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s ease-out;
    }
    
    .notification-success { border-color: #10b981; }
    .notification-warning { border-color: #f59e0b; }
    .notification-danger { border-color: #ef4444; }
    .notification-info { border-color: #3b82f6; }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .notification-actions {
        margin-top: 0.5rem;
    }
    
    .notification-action-btn {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        cursor: pointer;
        font-size: 0.875rem;
        margin-right: 0.5rem;
    }
    
    .notification-action-btn:hover {
        background: #2563eb;
    }
`;

// Inject CSS
const style = document.createElement('style');
style.textContent = notificationCSS;
document.head.appendChild(style);