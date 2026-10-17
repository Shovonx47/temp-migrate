<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'admin_name',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'note',
        'ip_address',
    ];

    /**
     * The customer whose KYC was changed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The admin who made the change.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Convenience logger. Call from anywhere to record a KYC change.
     *
     * @param  \App\Models\User  $customer     The customer being modified
     * @param  string            $action       e.g. 'approved', 'rejected', 'updated', 'document_replaced'
     * @param  string|null       $fieldName    e.g. 'nid_number', 'nid_front', 'kyc_status'
     * @param  mixed             $oldValue
     * @param  mixed             $newValue
     * @param  string|null       $note         Extra context (reason text etc.)
     */
    public static function record(
        User $customer,
        string $action,
        ?string $fieldName = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $note = null,
    ): self {
        $admin = auth()->user();

        return self::create([
            'user_id'    => $customer->id,
            'admin_id'   => $admin?->id,
            'admin_name' => $admin?->name ?? 'System',
            'action'     => $action,
            'field_name' => $fieldName,
            'old_value'  => is_array($oldValue) ? json_encode($oldValue) : (string) ($oldValue ?? ''),
            'new_value'  => is_array($newValue) ? json_encode($newValue) : (string) ($newValue ?? ''),
            'note'       => $note,
            'ip_address' => request()->ip(),
        ]);
    }
}
