<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * All KYC permission keys used throughout the system.
 *
 * Super Admin (Superman who has no kyc_role_permissions row, OR who is the
 * designated super-admin) bypasses all checks and has every permission implicitly.
 *
 * Other Superman-type admins need an explicit grant in kyc_role_permissions.
 */
enum KycPermission: string implements HasColor, HasIcon, HasLabel
{
    // Can see KYC pages, customer KYC status, and document thumbnails
    case View = 'kyc.view';

    // Can edit KYC fields (NID number, document replacement) via Manage KYC
    case Edit = 'kyc.edit';

    // Can access the KYC Verification Queue / Review page
    case Review = 'kyc.review';

    // Can click Approve KYC
    case Approve = 'kyc.approve';

    // Can click Reject KYC
    case Reject = 'kyc.reject';

    // Can request field-level resubmission from customer
    case ResubmissionRequest = 'kyc.resubmission';

    // Can view full-resolution uploaded documents (NID front/back, live photo)
    case DocumentView = 'kyc.document.view';

    // Can replace/remove documents on behalf of customer
    case DocumentReplace = 'kyc.document.replace';

    // Can see the KYC Audit Log page
    case AuditView = 'kyc.audit';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::View                => 'KYC View',
            self::Edit                => 'KYC Edit',
            self::Review              => 'KYC Review',
            self::Approve             => 'KYC Approve',
            self::Reject              => 'KYC Reject',
            self::ResubmissionRequest => 'KYC Resubmission Request',
            self::DocumentView        => 'KYC Document View',
            self::DocumentReplace     => 'KYC Document Replace',
            self::AuditView           => 'KYC Audit View',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::View, self::Review, self::AuditView, self::DocumentView => 'info',
            self::Approve                                                  => 'success',
            self::Reject                                                   => 'danger',
            self::Edit, self::DocumentReplace                              => 'warning',
            self::ResubmissionRequest                                      => 'primary',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::View                => Heroicon::OutlinedEye,
            self::Edit                => Heroicon::OutlinedPencil,
            self::Review              => Heroicon::OutlinedClipboardDocumentCheck,
            self::Approve             => Heroicon::OutlinedCheckBadge,
            self::Reject              => Heroicon::OutlinedXCircle,
            self::ResubmissionRequest => Heroicon::OutlinedArrowPath,
            self::DocumentView        => Heroicon::OutlinedPhoto,
            self::DocumentReplace     => Heroicon::OutlinedArrowUpTray,
            self::AuditView           => Heroicon::OutlinedClipboardDocument,
        };
    }

    /**
     * All permissions — used to grant "full access" to an admin.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Group permissions into logical sets for the UI toggle groups.
     *
     * @return array<string, list<self>>
     */
    public static function groups(): array
    {
        return [
            'View & Review' => [self::View, self::Review, self::DocumentView, self::AuditView],
            'Actions'       => [self::Approve, self::Reject, self::ResubmissionRequest],
            'Edit & Replace'=> [self::Edit, self::DocumentReplace],
        ];
    }
}
