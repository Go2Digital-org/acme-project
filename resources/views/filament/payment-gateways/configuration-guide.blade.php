<div class="space-y-6">
    <div class="prose max-w-none">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('filament.payment_gateway_configuration_guide') }}</h3>
        
        <div class="space-y-4 mt-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-medium text-blue-900">{{ __('filament.stripe_configuration') }}</h4>
                <ul class="mt-2 text-sm text-blue-800 space-y-1">
                    <li>• <strong>{{ __('filament.api_key') }}:</strong> {{ __('filament.your_secret_api_key_stripe') }}</li>
                    <li>• <strong>{{ __('filament.webhook_secret') }}:</strong> {{ __('filament.endpoint_signing_secret') }}</li>
                    <li>• <strong>{{ __('filament.webhook_url') }}:</strong> {{ __('filament.your_application_webhook_endpoint') }}</li>
                    <li>• <strong>{{ __('filament.publishable_key') }}:</strong> {{ __('filament.publishable_key_client_side') }}</li>
                </ul>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="font-medium text-green-900">{{ __('filament.mollie_configuration') }}</h4>
                <ul class="mt-2 text-sm text-green-800 space-y-1">
                    <li>• <strong>{{ __('filament.api_key') }}:</strong> {{ __('filament.your_live_test_api_key_mollie') }}</li>
                    <li>• <strong>{{ __('filament.webhook_secret') }}:</strong> {{ __('filament.secret_for_webhook_validation') }}</li>
                    <li>• <strong>{{ __('filament.webhook_url') }}:</strong> {{ __('filament.your_application_webhook_endpoint') }}</li>
                </ul>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-medium text-yellow-900">{{ __('filament.security_notes') }}</h4>
                <ul class="mt-2 text-sm text-yellow-800 space-y-1">
                    <li>• {{ __('filament.all_sensitive_data_encrypted') }}</li>
                    <li>• {{ __('filament.test_configuration_before_activating') }}</li>
                    <li>• {{ __('filament.use_test_mode_during_development') }}</li>
                    <li>• {{ __('filament.keep_api_keys_secure') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>