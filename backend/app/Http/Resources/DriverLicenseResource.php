<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverLicenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\DriverLicense $this */
        $user = $request->user();

        // Staff can see the full number; customer responses stay masked.
        $isStaff = $user && ($user->isAdmin() || $user->isBranchManager() || $user->isStaff());
        $documentNumber = $this->getDocumentNumber();
        $maskedDocumentNumber = $this->maskedDocumentNumber();
        $displayDocumentNumber = $isStaff ? $documentNumber : $maskedDocumentNumber;

        return [
            'id'                => $this->id,
            'document_type'     => $this->document_type,
            'status'            => $this->effectiveStatus(),
            'stored_status'     => $this->status,

            // Keep license_number aliases for existing frontend/API clients.
            'document_number'        => $displayDocumentNumber,
            'document_number_masked' => $maskedDocumentNumber,
            'license_number'         => $displayDocumentNumber,
            'license_number_masked'  => $maskedDocumentNumber,

            'full_name'         => $this->full_name,
            'date_of_birth'     => $this->date_of_birth?->toDateString(),
            'license_category'  => $this->license_category,
            'issue_date'        => $this->issue_date?->toDateString(),
            'expiry_date'       => $this->expiry_date?->toDateString(),
            'issuing_authority' => $this->issuing_authority,
            'issuing_country'   => $this->issuing_country,
            'university_name'   => $this->university_name,
            'department'        => $this->department,

            // Never expose raw storage paths — only indicate whether docs exist.
            'has_front_document' => !empty($this->front_document_path),
            'has_back_document'  => !empty($this->back_document_path),

            // Secure document URLs (served via authenticated backend endpoint).
            'front_document_url' => $this->front_document_path
                ? "/api/licenses/{$this->id}/document/front"
                : null,
            'back_document_url'  => $this->back_document_path
                ? "/api/licenses/{$this->id}/document/back"
                : null,

            'rejection_reason'  => $this->rejection_reason,
            'days_until_expiry' => $this->daysUntilExpiry(),
            'submitted_at'      => $this->submitted_at?->toISOString(),
            'verified_at'       => $this->verified_at?->toISOString(),

            'reviewer'          => $this->whenLoaded('reviewer', fn () => [
                'id'   => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'role' => $this->reviewer->role,
            ]),

            'customer'          => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),

            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
