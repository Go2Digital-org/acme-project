<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_data_subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('email');
            $table->string('consent_status');
            $table->json('consented_purposes')->nullable();
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('consent_withdrawn_at')->nullable();
            $table->timestamp('data_export_requested_at')->nullable();
            $table->timestamp('data_export_completed_at')->nullable();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->timestamp('deletion_completed_at')->nullable();
            $table->string('legal_basis')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'idx_consent_records_subject');
        });

        Schema::create('compliance_consent_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_subject_id')->constrained('compliance_data_subjects')->onDelete('cascade');
            $table->string('purpose');
            $table->string('status');
            $table->string('consent_method')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('consent_data')->nullable();
            $table->string('withdrawal_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['data_subject_id', 'purpose'], 'idx_consent_subject_purpose');
        });

        Schema::create('compliance_data_processing_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_subject_id')->constrained('compliance_data_subjects')->onDelete('cascade');
            $table->string('purpose');
            $table->string('activity_type');
            $table->text('description');
            $table->json('personal_data_categories');
            $table->string('legal_basis');
            $table->string('controller')->nullable();
            $table->string('processor')->nullable();
            $table->json('recipients')->nullable();
            $table->string('retention_period')->nullable();
            $table->json('security_measures')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        Schema::create('compliance_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->string('compliance_status');
            $table->json('event_data');
            $table->json('risk_assessment')->nullable();
            $table->string('compliance_officer')->nullable();
            $table->text('remediation_action')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('remediated_at')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_logs_auditable');
            $table->index(['event_type', 'created_at'], 'idx_audit_logs_event_date');
        });

        Schema::create('compliance_pci_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type');
            $table->string('transaction_id');
            $table->string('masked_card_number')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('compliance_level');
            $table->json('security_measures');
            $table->string('encryption_method')->nullable();
            $table->string('tokenization_method')->nullable();
            $table->boolean('is_cardholder_data_present');
            $table->string('vulnerability_scan_id')->nullable();
            $table->json('security_assessment')->nullable();
            $table->string('processor_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index(['transaction_id', 'created_at'], 'idx_pci_logs_transaction');
            $table->index(['event_type', 'processed_at'], 'idx_pci_logs_event');
        });

        Schema::create('compliance_data_retention_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('policy_name');
            $table->string('data_category');
            $table->string('purpose');
            $table->string('retention_period');
            $table->string('retention_action');
            $table->string('legal_basis');
            $table->json('deletion_criteria')->nullable();
            $table->json('anonymization_rules')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();

            $table->index(['data_category', 'is_active'], 'idx_retention_policies_category');
        });

        Schema::create('compliance_privacy_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('version');
            $table->string('title');
            $table->longText('content');
            $table->string('status');
            $table->json('data_categories')->nullable();
            $table->json('processing_purposes')->nullable();
            $table->json('legal_bases')->nullable();
            $table->json('third_parties')->nullable();
            $table->json('retention_periods')->nullable();
            $table->json('user_rights')->nullable();
            $table->text('contact_information')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'effective_from'], 'idx_privacy_policies_status');
        });

        Schema::create('compliance_policy_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('privacy_policy_id')->constrained('compliance_privacy_policies')->onDelete('cascade');
            $table->string('user_type');
            $table->unsignedBigInteger('user_id');
            $table->string('acceptance_method');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('acceptance_data')->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['user_type', 'user_id'], 'idx_policy_acceptances_user');
            $table->index(['privacy_policy_id', 'accepted_at'], 'idx_policy_acceptances_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_policy_acceptances');
        Schema::dropIfExists('compliance_privacy_policies');
        Schema::dropIfExists('compliance_data_retention_policies');
        Schema::dropIfExists('compliance_pci_logs');
        Schema::dropIfExists('compliance_audit_logs');
        Schema::dropIfExists('compliance_data_processing_activities');
        Schema::dropIfExists('compliance_consent_records');
        Schema::dropIfExists('compliance_data_subjects');
    }
};
