<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Service;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use stdClass;

final readonly class DonationExportService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportToCSV(array $filters = [], ?string $locale = null): string
    {
        $donations = $this->getDonationsForExport($filters);
        $headers = $this->getCSVHeaders($locale ?? app()->getLocale());

        $csvContent = $this->arrayToCSV($headers);

        foreach ($donations as $donation) {
            $row = [
                $donation->id,
                Carbon::parse($donation->created_at)->format('Y-m-d H:i:s'),
                $donation->amount,
                $donation->currency ?? 'EUR',
                $this->translateStatus($donation->status, $locale ?? app()->getLocale()),
                $donation->payment_method ?? '',
                $donation->campaign_title ?? '',
                $donation->anonymous ? $this->translate('yes', $locale ?? app()->getLocale()) : $this->translate('no', $locale ?? app()->getLocale()),
                $donation->user_name ?? '',
                $donation->notes ?? '',
                $donation->transaction_id ?? '',
                $donation->completed_at ? Carbon::parse($donation->completed_at)->format('Y-m-d H:i:s') : '',
            ];

            $csvContent .= $this->arrayToCSV($row);
        }

        return $csvContent;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, stdClass>
     */
    private function getDonationsForExport(array $filters): Collection
    {
        // Get all donations with filters applied, but without pagination
        $query = DB::table('donations')
            ->leftJoin('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->leftJoin('users', 'donations.user_id', '=', 'users.id')
            ->select(
                'donations.id',
                'donations.created_at',
                'donations.amount',
                'donations.currency',
                'donations.status',
                'donations.payment_method',
                'donations.anonymous',
                'donations.notes',
                'donations.transaction_id',
                'donations.completed_at',
                'campaigns.title as campaign_title',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as employee_name"),
            );

        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('donations.status', $filters['status']);
        }

        if (isset($filters['campaign_id']) && $filters['campaign_id'] !== '') {
            $query->where('donations.campaign_id', $filters['campaign_id']);
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $query->where('donations.user_id', $filters['user_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->whereDate('donations.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->whereDate('donations.created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['payment_method']) && $filters['payment_method'] !== '') {
            $query->where('donations.payment_method', $filters['payment_method']);
        }

        if (isset($filters['anonymous'])) {
            $query->where('donations.anonymous', (bool) $filters['anonymous']);
        }

        return $query->orderBy('donations.created_at', 'desc')->get();
    }

    /**
     * @return list<string>
     */
    private function getCSVHeaders(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return [
            $this->translate('id', $locale),
            $this->translate('created_at', $locale),
            $this->translate('amount', $locale),
            $this->translate('currency', $locale),
            $this->translate('status', $locale),
            $this->translate('payment_method', $locale),
            $this->translate('campaign', $locale),
            $this->translate('anonymous', $locale),
            $this->translate('employee', $locale),
            $this->translate('notes', $locale),
            $this->translate('transaction_id', $locale),
            $this->translate('completed_at', $locale),
        ];
    }

    /**
     * @param  array<mixed>  $array
     */
    private function arrayToCSV(array $array): string
    {
        $output = fopen('php://temp', 'r+');

        if ($output === false) {
            throw new RuntimeException('Failed to create temporary file for CSV export');
        }

        fputcsv($output, $array, ',', '"', '\\');
        rewind($output);
        $csvLine = stream_get_contents($output);
        fclose($output);

        return $csvLine ?: '';
    }

    private function translateStatus(string $status, string $locale): string
    {
        $statusTranslations = [
            'en' => [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'completed' => 'Completed',
                'failed' => 'Failed',
                'cancelled' => 'Cancelled',
                'refunded' => 'Refunded',
            ],
            'nl' => [
                'pending' => 'In behandeling',
                'processing' => 'Verwerken',
                'completed' => 'Voltooid',
                'failed' => 'Mislukt',
                'cancelled' => 'Geannuleerd',
                'refunded' => 'Terugbetaald',
            ],
            'fr' => [
                'pending' => 'En attente',
                'processing' => 'Traitement',
                'completed' => 'Terminé',
                'failed' => 'Échoué',
                'cancelled' => 'Annulé',
                'refunded' => 'Remboursé',
            ],
        ];

        return $statusTranslations[$locale][$status] ?? $status;
    }

    private function translate(string $key, string $locale): string
    {
        $translations = [
            'en' => [
                'id' => 'ID',
                'created_at' => 'Created At',
                'amount' => 'Amount',
                'currency' => 'Currency',
                'status' => 'Status',
                'payment_method' => 'Payment Method',
                'campaign' => 'Campaign',
                'anonymous' => 'Anonymous',
                'employee' => 'Employee',
                'notes' => 'Notes',
                'transaction_id' => 'Transaction ID',
                'completed_at' => 'Completed At',
                'yes' => 'Yes',
                'no' => 'No',
            ],
            'nl' => [
                'id' => 'ID',
                'created_at' => 'Aangemaakt op',
                'amount' => 'Bedrag',
                'currency' => 'Valuta',
                'status' => 'Status',
                'payment_method' => 'Betaalmethode',
                'campaign' => 'Campagne',
                'anonymous' => 'Anoniem',
                'employee' => 'Medewerker',
                'notes' => 'Opmerkingen',
                'transaction_id' => 'Transactie ID',
                'completed_at' => 'Voltooid op',
                'yes' => 'Ja',
                'no' => 'Nee',
            ],
            'fr' => [
                'id' => 'ID',
                'created_at' => 'Créé le',
                'amount' => 'Montant',
                'currency' => 'Devise',
                'status' => 'Statut',
                'payment_method' => 'Méthode de paiement',
                'campaign' => 'Campagne',
                'anonymous' => 'Anonyme',
                'employee' => 'Employé',
                'notes' => 'Notes',
                'transaction_id' => 'ID de transaction',
                'completed_at' => 'Terminé le',
                'yes' => 'Oui',
                'no' => 'Non',
            ],
        ];

        return $translations[$locale][$key] ?? $key;
    }
}
