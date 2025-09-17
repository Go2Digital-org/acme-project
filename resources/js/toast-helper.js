/**
 * Toast Helper - Unified toast notification system using SweetAlert2
 */

class ToastHelper {
    constructor() {
        this.defaultOptions = {
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', window.Swal?.stopTimer);
                toast.addEventListener('mouseleave', window.Swal?.resumeTimer);
            }
        };
    }

    /**
     * Show success toast
     */
    success(message, options = {}) {
        if (!window.Swal) {
            console.log('Success:', message);
            return;
        }

        const Toast = window.Swal.mixin({
            ...this.defaultOptions,
            ...options
        });

        return Toast.fire({
            icon: 'success',
            title: message
        });
    }

    /**
     * Show error toast
     */
    error(message, options = {}) {
        if (!window.Swal) {
            console.error('Error:', message);
            return;
        }

        const Toast = window.Swal.mixin({
            ...this.defaultOptions,
            timer: 5000, // Errors stay longer
            ...options
        });

        return Toast.fire({
            icon: 'error',
            title: message
        });
    }

    /**
     * Show info toast
     */
    info(message, options = {}) {
        if (!window.Swal) {
            console.log('Info:', message);
            return;
        }

        const Toast = window.Swal.mixin({
            ...this.defaultOptions,
            ...options
        });

        return Toast.fire({
            icon: 'info',
            title: message
        });
    }

    /**
     * Show warning toast
     */
    warning(message, options = {}) {
        if (!window.Swal) {
            console.warn('Warning:', message);
            return;
        }

        const Toast = window.Swal.mixin({
            ...this.defaultOptions,
            timer: 4000,
            ...options
        });

        return Toast.fire({
            icon: 'warning',
            title: message
        });
    }

    /**
     * Show loading toast (no auto-close)
     */
    loading(message, options = {}) {
        if (!window.Swal) {
            console.log('Loading:', message);
            return;
        }

        const Toast = window.Swal.mixin({
            ...this.defaultOptions,
            timer: undefined,
            timerProgressBar: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            ...options
        });

        return Toast.fire({
            icon: 'info',
            title: message,
            html: '<div class="swal2-loading-spinner"></div>',
            showClass: {
                popup: 'swal2-show',
                backdrop: 'swal2-backdrop-show'
            }
        });
    }

    /**
     * Show confirmation dialog
     */
    async confirm(title, text = '', options = {}) {
        if (!window.Swal) {
            return confirm(`${title}\n${text}`);
        }

        const result = await window.Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            ...options
        });

        return result.isConfirmed;
    }

    /**
     * Show delete confirmation dialog
     */
    async confirmDelete(itemName = 'this item', options = {}) {
        return this.confirm(
            'Delete Confirmation',
            `Are you sure you want to delete ${itemName}? This action cannot be undone.`,
            {
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, delete it',
                icon: 'error',
                ...options
            }
        );
    }

    /**
     * Show cancel confirmation dialog
     */
    async confirmCancel(itemName = 'this operation', options = {}) {
        return this.confirm(
            'Cancel Confirmation',
            `Are you sure you want to cancel ${itemName}?`,
            {
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Yes, cancel it',
                ...options
            }
        );
    }

    /**
     * Close all toasts
     */
    closeAll() {
        if (window.Swal) {
            window.Swal.close();
        }
    }
}

// Initialize and export
const toast = new ToastHelper();

// Make it globally available
window.toast = toast;

export default toast;