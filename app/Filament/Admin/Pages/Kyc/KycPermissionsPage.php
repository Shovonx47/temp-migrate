<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Kyc;

use App\Enums\KycPermission;
use App\Enums\SupermanNavigationGroup;
use App\Enums\UserType;
use App\Models\KycRolePermission;
use App\Models\User;
use App\Services\KycGate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class KycPermissionsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Kyc;

    protected static ?string $navigationLabel = 'KYC Permissions';

    protected static ?string $title = 'KYC Admin Permissions';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'kyc/permissions';

    protected string $view = 'filament.admin.pages.kyc.kyc-permissions';

    /** Only the Super Admin (no permission row) can manage permissions */
    public static function canAccess(): bool
    {
        return KycGate::isSuperAdmin();
    }

    // ─── Grant Form ─────────────────────────────────────────────────────────────

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('admin_user_id')
                    ->label('Select Admin')
                    ->placeholder('Choose an admin to grant/update permissions')
                    ->options(fn () => User::query()
                        ->where('type', UserType::Superman)
                        ->where('id', '!=', auth()->id()) // can't edit your own permissions
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                CheckboxList::make('permissions')
                    ->label('Grant these KYC Permissions')
                    ->options(KycPermission::class)
                    ->columns(3)
                    ->helperText('Checked = granted. Unchecked = revoked. If ALL are unchecked, the admin becomes a Super Admin (all permissions implicitly).'),
            ]);
    }

    public function grantPermissions(): void
    {
        $data = $this->form->getState();

        $adminId     = $data['admin_user_id'];
        $permissions = $data['permissions'] ?? [];

        $actor = Filament::auth()->user();

        if (empty($permissions)) {
            // Granting zero permissions = make Super Admin = delete the row
            KycRolePermission::where('user_id', $adminId)->delete();
            KycGate::clearCache($adminId);

            Notification::make()
                ->title('Super Admin Access Granted')
                ->body('All restrictions removed — this admin now has all KYC permissions.')
                ->success()
                ->send();
        } else {
            KycRolePermission::updateOrCreate(
                ['user_id' => $adminId],
                [
                    'permissions' => $permissions,
                    'granted_by'  => $actor->id,
                ]
            );
            KycGate::clearCache($adminId);

            Notification::make()
                ->title('Permissions Saved')
                ->body('KYC permissions updated for this admin.')
                ->success()
                ->send();
        }

        $this->form->fill();
    }

    // ─── Table of existing grants ────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->query(KycRolePermission::query()->with(['user', 'grantedBy'])->latest())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Admin')
                    ->weight('bold')
                    ->description(fn (KycRolePermission $record) => $record->user?->email),

                TextColumn::make('permissions')
                    ->label('Granted Permissions')
                    ->state(fn (KycRolePermission $record) => implode(', ',
                        array_map(
                            fn ($p) => KycPermission::tryFrom($p)?->getLabel() ?? $p,
                            $record->permissions ?? []
                        )
                    ))
                    ->wrap()
                    ->placeholder('Super Admin — All Permissions'),

                TextColumn::make('grantedBy.name')
                    ->label('Granted By')
                    ->placeholder('—'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('revoke_all')
                    ->label('Make Super Admin')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Remove All Restrictions')
                    ->modalDescription('This will delete the permission row, making this admin a Super Admin with all KYC permissions.')
                    ->action(function (KycRolePermission $record) {
                        $userId = $record->user_id;
                        $record->delete();
                        KycGate::clearCache($userId);

                        Notification::make()
                            ->title('Made Super Admin')
                            ->body('All KYC permission restrictions removed.')
                            ->success()
                            ->send();
                    }),

                Action::make('edit_permissions')
                    ->label('Edit Permissions')
                    ->icon(Heroicon::OutlinedPencil)
                    ->color('warning')
                    ->fillForm(fn (KycRolePermission $record) => [
                        'admin_user_id' => $record->user_id,
                        'permissions'   => $record->permissions ?? [],
                    ])
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Permissions')
                            ->options(KycPermission::class)
                            ->columns(3),
                    ])
                    ->action(function (array $data, KycRolePermission $record) {
                        $record->update([
                            'permissions' => $data['permissions'] ?? [],
                            'granted_by'  => auth()->id(),
                        ]);
                        KycGate::clearCache($record->user_id);

                        Notification::make()
                            ->title('Permissions Updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->heading('Current Permission Grants')
            ->description('Admins listed here have restricted KYC access. Admins NOT listed are Super Admins with full access.');
    }
}
