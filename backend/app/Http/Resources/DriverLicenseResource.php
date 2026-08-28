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

        // Determine if the requester is the owner or staff/admin.
        $isOwner = $user && (int) $user->id === (int) $this->user_id;
        $isStaff = $user && ($user->isAdmin() || $user->isBranchManager() || $user->isStaff());

        return [
            'id'                => $this->id,
            'status'            => $this->effectiveStatus(),
            'stored_status'     => $this->status,

            // License number: full for admin/staff, masked for customer in lists.
            'license_number'    => ($isStaff) ? $this->license_number : $this->maskedLicenseNumber(),
            'license_number_masked' => $this->maskedLicenseNumber(),

            'full_name'         => $this->full_name,
            'date_of_birth'     => $this->date_of_birth?->toDateString(),
            'license_category'  => $this->license_category,
            'issue_date'        => $this->issue_date?->toDateString(),
            'expiry_date'       => $this->expiry_date?->toDateString(),
            'issuing_authority' => $this->issuing_authority,
            'issuing_country'   => $this->issuing_country,

            // Never expose raw storage paths — only indicate whether docs exist.
            'has_front_document' => !empty($this->front_document_path),
            'has_back_document'  => !empty($this->back_document_path),

            // Secure document URLs (served via authenticated backend endpoint).
            'front_document_url' => $this->front_document_path
                ? url("/api/licenses/{$this->id}/document/front")
                : null,
            'back_document_url'  => $this->back_document_path
                ? url("/api/licenses/{$this->id}/document/back")
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
