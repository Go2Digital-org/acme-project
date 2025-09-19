@component('mail::message')
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('images/thank-you.png') }}" alt="Thank You" style="width: 200px;">
</div>

# Thank You for Your Generous Donation!

Dear {{ $donation['donor_name'] ?? 'Valued Supporter' }},

Your donation has been successfully processed and is already making a difference.

@component('mail::panel')
## Donation Details

**Amount:** ${{ number_format((float) $donation['amount'], 2) }}
**Campaign:** {{ $campaign['title'] }}
**Organization:** {{ $campaign['organization_name'] }}
**Date:** {{ now()->format('F j, Y g:i A') }}
**Transaction ID:** `{{ $donation['payment_reference'] }}`
@endcomponent

## Your Impact

{{ $impactStatement }}

### Campaign Progress
@component('mail::progress', ['percentage' => $progressPercentage])
${{ number_format((float) $campaign['current_amount'], 2) }} of
${{ number_format((float) $campaign['goal_amount'], 2) }} raised
@endcomponent

@component('mail::button', ['url' => $receiptUrl, 'color' => 'primary'])
Download Tax Receipt
@endcomponent

## Share Your Support

Help spread the word about this campaign:

@component('mail::button', ['url' => route('campaigns.share', $campaign['id']), 'color' => 'success'])
Share on Social Media
@endcomponent

---

### Tax Information
This donation is tax-deductible. Please keep your receipt for tax purposes.
Organization Tax ID: {{ $campaign['organization_tax_id'] ?? 'XX-XXXXXXX' }}

@component('mail::subcopy')
If you have any questions about your donation, please contact our support team at
[support@acme-corp.com](mailto:support@acme-corp.com) or call 1-800-XXX-XXXX.
@endcomponent

Thank you for making a difference!

**The ACME CSR Team**
@endcomponent