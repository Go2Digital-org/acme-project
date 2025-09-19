<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\ValueObject\PaymentIntent;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Donation\Infrastructure\Service\PaymentGatewayFactory;
use Modules\Shared\Domain\ValueObject\Money;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use Modules\User\Domain\ValueObject\EmailAddress;

/**
 * Process Donation Web Controller.
 *
 * Handles donation form submission and initiates payment processing.
 */
final readonly class ProcessDonationWebController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationRepositoryInterface $donationRepository,
        private PaymentGatewayFactory $gatewayFactory,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(Request $request, Campaign $campaign): RedirectResponse
    {
        // Validate the request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:5|max:50000',
            'message' => 'nullable|string|max:500',
            'is_anonymous' => 'boolean',
            'subscribe_updates' => 'boolean',
        ]);

        try {
            // Get authenticated user
            $user = $this->getAuthenticatedUser($request);

            // User is already authenticated by the trait method

            // Campaign is automatically resolved by Laravel's route model binding
            // Validate campaign can accept donations
            if (! $campaign->canAcceptDonation()) {
                return redirect()->route('campaigns.show', $campaign->slug ?? $campaign->id)
                    ->with('error', 'This campaign is no longer accepting donations');
            }

            // Convert user to domain user
            $domainUser = $this->userRepository->findByEmail(new EmailAddress($user->getEmail()));

            if (! $domainUser instanceof User) {
                return redirect()->route('campaigns.show', $campaign->slug ?? $campaign->id)
                    ->with('error', 'User not found');
            }

            // Validate user can make donations
            $domainUser->validateForDonation();

            // Determine payment gateway
            // Let payment gateway handle payment method selection on their checkout page
            $gateway = $this->gatewayFactory->selectBestGateway(
                paymentMethod: 'card', // Use 'card' for gateway selection compatibility
                currency: 'EUR', // TODO: Make configurable
                amount: (float) $validated['amount'],
            );

            Log::info('Processing donation', [
                'user_id' => $user->getId(),
                'campaign_id' => $campaign->id,
                'amount' => $validated['amount'],
                'gateway' => $gateway->getName(),
                'payment_method' => 'gateway_choice', // Gateway will handle method selection
            ]);

            // Start database transaction
            return DB::transaction(function () use ($validated, $campaign, $domainUser, $gateway) {
                // Create donation (pending status)
                $donation = $this->donationRepository->create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $domainUser->getId(),
                    'amount' => (float) $validated['amount'],
                    'currency' => 'EUR',
                    'notes' => $validated['message'] ?? null,
                    'anonymous' => (bool) ($validated['is_anonymous'] ?? false),
                    'payment_method' => 'card', // Default to card, gateway will handle actual selection
                    'status' => 'pending',
                ]);

                // Create payment intent
                $paymentIntent = new PaymentIntent(
                    donationId: $donation->id,
                    campaignId: $campaign->id,
                    userId: $domainUser->getId(),
                    amount: new Money(
                        amount: (float) $donation->amount,
                        currency: $donation->currency,
                    ),
                    paymentMethod: PaymentMethod::from('card'), // Use card as default
                    description: "Donation to {$campaign->getTitle()}",
                    returnUrl: route('donations.success', $donation->id),
                    cancelUrl: route('donations.cancelled', $donation->id),
                    metadata: [
                        'donation_id' => $donation->id,
                        'campaign_id' => $campaign->id,
                        'user_id' => $domainUser->getId(),
                        'campaign_title' => $campaign->getTitle(),
                        'user_email' => $domainUser->getEmailString(),
                    ],
                );

                // Create payment with gateway
                $paymentResult = $gateway->createPaymentIntent($paymentIntent);

                if (! $paymentResult->isSuccessful() && ! $paymentResult->isPending()) {
                    Log::error('Payment creation failed', [
                        'donation_id' => $donation->id,
                        'gateway' => $gateway->getName(),
                        'error' => $paymentResult->getErrorMessage(),
                    ]);

                    throw new Exception($paymentResult->getErrorMessage());
                }

                // Update donation with payment details
                $intentId = $paymentResult->getIntentId();

                if ($intentId !== null) {
                    $donation->updatePaymentDetails(
                        gatewayPaymentId: $intentId,
                        gatewayData: $paymentResult->getGatewayData(),
                    );
                }

                // Get redirect URL from payment result
                // For most gateways, the checkout URL is in gateway_data
                $gatewayData = $paymentResult->getGatewayData();
                $redirectUrl = $gatewayData['checkout_url']
                    ?? $gatewayData['redirect_url']
                    ?? $gatewayData['next_action']['redirect_url']
                    ?? $paymentResult->getClientSecret();

                if (! $redirectUrl) {
                    Log::error('No redirect URL provided by gateway', [
                        'donation_id' => $donation->id,
                        'gateway' => $gateway->getName(),
                        'gateway_data' => $gatewayData,
                        'payment_result' => [
                            'successful' => $paymentResult->isSuccessful(),
                            'pending' => $paymentResult->isPending(),
                            'intent_id' => $paymentResult->getIntentId(),
                            'client_secret' => $paymentResult->getClientSecret(),
                        ],
                    ]);

                    throw new Exception('Payment gateway did not provide a redirect URL');
                }

                Log::info('Donation created successfully, redirecting to payment', [
                    'donation_id' => $donation->id,
                    'payment_id' => $paymentResult->getIntentId(),
                    'redirect_url' => $redirectUrl,
                ]);

                // Redirect to payment gateway
                return redirect()->away($redirectUrl);
            });
        } catch (Exception $e) {
            Log::error('Donation processing failed', [
                'user_id' => $request->user()?->id,
                'campaign_id' => $campaign->id,
                'amount' => $validated['amount'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Try to create a failed donation record for tracking
            try {
                $user = $this->getAuthenticatedUserOrNull($request);

                if ($user instanceof \Modules\User\Infrastructure\Laravel\Models\User) {
                    $domainUser = $this->userRepository->findByEmail(new EmailAddress($user->getEmail()));

                    if ($domainUser instanceof User) {
                        // Create detailed failure reason for admin visibility
                        $detailedFailureReason = sprintf(
                            'Payment processing failed: %s | Error details: %s | Trace: %s',
                            $e->getMessage(),
                            $e->getCode() ? "Code {$e->getCode()}" : 'No error code',
                            substr($e->getTraceAsString(), 0, 1000) // Limit to first 1000 chars
                        );

                        $failedDonation = $this->donationRepository->create([
                            'campaign_id' => $campaign->id,
                            'user_id' => $domainUser->getId(),
                            'amount' => (float) $validated['amount'],
                            'currency' => 'EUR',
                            'notes' => $validated['message'] ?? null,
                            'anonymous' => (bool) ($validated['is_anonymous'] ?? false),
                            'payment_method' => $validated['payment_method'] ?? 'card',
                            'status' => 'failed',
                            'failure_reason' => $detailedFailureReason,
                        ]);

                        return redirect()->route('donations.failed', $failedDonation->id);
                    }
                }
            } catch (Exception $innerException) {
                Log::error('Could not create failed donation record', [
                    'error' => $innerException->getMessage(),
                ]);
            }

            // Log detailed error for developers
            Log::error('Donation processing error details', [
                'user_id' => $request->user()->id ?? 'Not authenticated',
                'campaign_id' => $campaign->id,
                'amount' => $validated['amount'] ?? 'Not provided',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'gateway_selection' => 'Error occurred during gateway initialization or payment creation',
            ]);

            // Show appropriate error message to user
            $userMessage = $this->getErrorMessageForUser();

            // Fallback to campaign page with error message
            return redirect()->route('campaigns.show', $campaign->slug ?? $campaign->id)
                ->with('error', $userMessage)
                ->withInput();
        }
    }

    private function getErrorMessageForUser(): string
    {
        // Simple user-friendly message - detailed error is already logged for admins
        return 'Payment processing failed. Please contact support or try again later.';
    }
}
