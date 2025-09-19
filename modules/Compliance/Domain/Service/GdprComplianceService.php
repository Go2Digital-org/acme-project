<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Service;

use Illuminate\Database\Eloquent\Model;
use Modules\Compliance\Domain\Model\ConsentRecord;
use Modules\Compliance\Domain\Model\DataSubject;
use Modules\Compliance\Domain\ValueObject\ConsentStatus;
use Modules\Compliance\Domain\ValueObject\DataProcessingPurpose;

class GdprComplianceService
{
    /**
     * @param  array<string, mixed>  $purposes
     * @param  array<string, mixed>  $consentData
     */
    public function recordConsent(
        Model $subject,
        array $purposes,
        string $consentMethod,
        string $ipAddress,
        string $userAgent,
        array $consentData = []
    ): DataSubject {
        $dataSubject = $this->findOrCreateDataSubject($subject);

        $dataSubject->consent_status = ConsentStatus::GIVEN;
        $dataSubject->consented_purposes = array_values(array_map(
            fn (DataProcessingPurpose $purpose) => $purpose->value,
            $purposes
        ));
        $dataSubject->consent_given_at = now();
        $dataSubject->consent_withdrawn_at = null;
        $dataSubject->save();

        foreach ($purposes as $purpose) {
            ConsentRecord::create([
                'data_subject_id' => $dataSubject->id,
                'purpose' => $purpose,
                'status' => ConsentStatus::GIVEN,
                'consent_method' => $consentMethod,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'consent_data' => $consentData,
            ]);
        }

        return $dataSubject;
    }

    /**
     * @param  array<string, mixed>  $purposes
     */
    public function withdrawConsent(
        Model $subject,
        ?array $purposes = null,
        ?string $reason = null
    ): DataSubject {
        $dataSubject = $this->findOrCreateDataSubject($subject);

        if ($purposes === null) {
            // Withdraw all consent
            $dataSubject->withdrawConsent();

            ConsentRecord::where('data_subject_id', $dataSubject->id)
                ->where('status', ConsentStatus::GIVEN)
                ->get()
                ->each(fn (ConsentRecord $record) => $record->withdraw($reason));
        } else {
            // Withdraw specific purposes
            $remainingPurposes = array_diff(
                $dataSubject->consented_purposes ?? [],
                array_map(fn (DataProcessingPurpose $p) => $p->value, $purposes)
            );

            $dataSubject->consented_purposes = $remainingPurposes;

            if ($remainingPurposes === []) {
                $dataSubject->withdrawConsent();
            } else {
                $dataSubject->save();
            }

            foreach ($purposes as $purpose) {
                ConsentRecord::where('data_subject_id', $dataSubject->id)
                    ->where('purpose', $purpose)
                    ->where('status', ConsentStatus::GIVEN)
                    ->get()
                    ->each(fn (ConsentRecord $record) => $record->withdraw($reason));
            }
        }

        return $dataSubject;
    }

    public function hasValidConsent(Model $subject, DataProcessingPurpose $purpose): bool
    {
        $dataSubject = $this->findDataSubject($subject);

        if (! $dataSubject instanceof DataSubject) {
            return false;
        }

        return $dataSubject->hasConsentFor($purpose);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportPersonalData(Model $subject): array
    {
        $dataSubject = $this->findOrCreateDataSubject($subject);
        $dataSubject->requestDataExport();

        $personalData = [
            'subject_information' => [
                'id' => $subject->getKey(),
                'type' => $subject::class,
                'email' => $dataSubject->email,
                'consent_status' => $dataSubject->consent_status->value,
                'consented_purposes' => $dataSubject->consented_purposes,
                'consent_given_at' => $dataSubject->consent_given_at?->toISOString(),
                'data_export_requested_at' => $dataSubject->data_export_requested_at?->toISOString(),
            ],
            'consent_records' => $dataSubject->consentRecords->map(fn (ConsentRecord $record): array => [
                'purpose' => $record->purpose->value,
                'status' => $record->status->value,
                'consent_method' => $record->consent_method,
                'given_at' => $record->created_at->toISOString(),
                'consent_data' => $record->consent_data,
            ])->toArray(),
            'processing_activities' => $dataSubject->processingActivities->map(fn ($activity): array => [
                'purpose' => $activity->purpose->value,
                'activity_type' => $activity->activity_type,
                'description' => $activity->description,
                'legal_basis' => $activity->legal_basis,
                'processed_at' => $activity->processed_at->toISOString(),
            ])->toArray(),
        ];

        // Add model-specific data
        if (method_exists($subject, 'getGdprExportData')) {
            $personalData['model_data'] = $subject->getGdprExportData();
        }

        $dataSubject->completeDataExport();

        return $personalData;
    }

    public function deletePersonalData(Model $subject): bool
    {
        $dataSubject = $this->findDataSubject($subject);

        if (! $dataSubject instanceof DataSubject) {
            return false;
        }

        $dataSubject->requestDeletion();

        // Delete consent records
        $dataSubject->consentRecords()->delete();

        // Delete processing activities
        $dataSubject->processingActivities()->delete();

        // Anonymize or delete model-specific data
        if (method_exists($subject, 'anonymizeGdprData')) {
            $subject->anonymizeGdprData();
        }

        $dataSubject->completeDeletion();
        $dataSubject->delete();

        return true;
    }

    public function anonymizePersonalData(Model $subject): bool
    {
        $dataSubject = $this->findDataSubject($subject);

        if (! $dataSubject instanceof DataSubject) {
            return false;
        }

        // Anonymize data subject
        $dataSubject->email = 'anonymized-' . $dataSubject->id . '@example.com';
        $dataSubject->legal_basis = 'anonymized';
        $dataSubject->save();

        // Anonymize consent records
        $dataSubject->consentRecords()->update([
            'ip_address' => null,
            'user_agent' => null,
            'consent_data' => null,
        ]);

        // Anonymize model-specific data
        if (method_exists($subject, 'anonymizeGdprData')) {
            $subject->anonymizeGdprData();
        }

        return true;
    }

    private function findOrCreateDataSubject(Model $subject): DataSubject
    {
        return DataSubject::firstOrCreate([
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
        ], [
            'email' => $subject->email ?? 'unknown@example.com',
            'consent_status' => ConsentStatus::PENDING,
        ]);
    }

    private function findDataSubject(Model $subject): ?DataSubject
    {
        return DataSubject::where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey())
            ->first();
    }
}
