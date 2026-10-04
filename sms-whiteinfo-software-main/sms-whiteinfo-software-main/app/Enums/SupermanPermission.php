<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SupermanPermission: string implements HasColor, HasIcon, HasLabel
{
    // Users
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersEdit = 'users.edit';
    case UsersDelete = 'users.delete';

    // Resellers (S P)
    case ResellersView = 'resellers.view';
    case ResellersCreate = 'resellers.create';
    case ResellersEdit = 'resellers.edit';
    case ResellersDelete = 'resellers.delete';

    // Customers
    case CustomersView = 'customers.view';
    case CustomersCreate = 'customers.create';
    case CustomersEdit = 'customers.edit';
    case CustomersDelete = 'customers.delete';

    // Internal Staff
    case EmployeesView = 'employees.view';
    case EmployeesCreate = 'employees.create';
    case EmployeesEdit = 'employees.edit';
    case EmployeesDelete = 'employees.delete';

    // Roles & Permissions
    case RolesView = 'roles.view';
    case RolesManage = 'roles.manage';

    // Billing & Tariff
    case BillingView = 'billing.view';
    case BillingManage = 'billing.manage';

    // Messaging & Gateways
    case MessagingView = 'messaging.view';
    case MessagingManage = 'messaging.manage';

    // Reports
    case ReportsView = 'reports.view';

    // System Settings
    case SettingsView = 'settings.view';
    case SettingsManage = 'settings.manage';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::UsersView => 'View Users',
            self::UsersCreate => 'Create Users',
            self::UsersEdit => 'Edit Users',
            self::UsersDelete => 'Delete Users',

            self::ResellersView => 'View S P / Resellers',
            self::ResellersCreate => 'Create S P / Resellers',
            self::ResellersEdit => 'Edit S P / Resellers',
            self::ResellersDelete => 'Delete S P / Resellers',

            self::CustomersView => 'View Customers',
            self::CustomersCreate => 'Create Customers',
            self::CustomersEdit => 'Edit Customers',
            self::CustomersDelete => 'Delete Customers',

            self::EmployeesView => 'View Staff',
            self::EmployeesCreate => 'Create Staff',
            self::EmployeesEdit => 'Edit Staff',
            self::EmployeesDelete => 'Delete Staff',

            self::RolesView => 'View Roles',
            self::RolesManage => 'Manage Roles',

            self::BillingView => 'View Billing & Tariff',
            self::BillingManage => 'Manage Billing & Tariffs',

            self::MessagingView => 'View Messaging',
            self::MessagingManage => 'Manage Messaging & Gateways',

            self::ReportsView => 'View Reports',

            self::SettingsView => 'View Settings',
            self::SettingsManage => 'Manage Settings',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::UsersView, self::ResellersView, self::CustomersView,
            self::EmployeesView, self::RolesView, self::BillingView,
            self::MessagingView, self::ReportsView, self::SettingsView => 'info',

            self::UsersCreate, self::ResellersCreate, self::CustomersCreate,
            self::EmployeesCreate => 'success',

            self::UsersEdit, self::ResellersEdit, self::CustomersEdit,
            self::EmployeesEdit, self::RolesManage, self::BillingManage,
            self::MessagingManage, self::SettingsManage => 'warning',

            self::UsersDelete, self::ResellersDelete, self::CustomersDelete,
            self::EmployeesDelete => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::UsersView, self::ResellersView, self::CustomersView,
            self::EmployeesView, self::RolesView, self::BillingView,
            self::MessagingView, self::ReportsView, self::SettingsView => Heroicon::OutlinedEye,

            self::UsersCreate, self::ResellersCreate, self::CustomersCreate,
            self::EmployeesCreate => Heroicon::OutlinedPlusCircle,

            self::UsersEdit, self::ResellersEdit, self::CustomersEdit,
            self::EmployeesEdit, self::RolesManage => Heroicon::OutlinedPencil,

            self::UsersDelete, self::ResellersDelete, self::CustomersDelete,
            self::EmployeesDelete => Heroicon::OutlinedTrash,

            self::BillingManage => Heroicon::OutlinedBanknotes,
            self::MessagingManage => Heroicon::OutlinedPaperAirplane,
            self::SettingsManage => Heroicon::OutlinedCog6Tooth,
        };
    }

    /**
     * All permissions as string values.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Group permissions into logical categories for forms.
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'User Management' => [
                self::UsersView->value => 'View Users',
                self::UsersCreate->value => 'Create Users',
                self::UsersEdit->value => 'Edit Users',
                self::UsersDelete->value => 'Delete Users',
            ],
            'S P / Reseller Management' => [
                self::ResellersView->value => 'View Resellers',
                self::ResellersCreate->value => 'Create Resellers',
                self::ResellersEdit->value => 'Edit Resellers',
                self::ResellersDelete->value => 'Delete Resellers',
            ],
            'Customer Management' => [
                self::CustomersView->value => 'View Customers',
                self::CustomersCreate->value => 'Create Customers',
                self::CustomersEdit->value => 'Edit Customers',
                self::CustomersDelete->value => 'Delete Customers',
            ],
            'Internal Staff' => [
                self::EmployeesView->value => 'View Staff',
                self::EmployeesCreate->value => 'Create Staff',
                self::EmployeesEdit->value => 'Edit Staff',
                self::EmployeesDelete->value => 'Delete Staff',
            ],
            'Roles & Permissions' => [
                self::RolesView->value => 'View Roles',
                self::RolesManage->value => 'Manage Roles',
            ],
            'Billing & Tariff' => [
                self::BillingView->value => 'View Billing & Tariff',
                self::BillingManage->value => 'Manage Billing & Tariffs',
            ],
            'Messaging & Gateways' => [
                self::MessagingView->value => 'View Messaging',
                self::MessagingManage->value => 'Manage Messaging & Gateways',
            ],
            'Reports & Analytics' => [
                self::ReportsView->value => 'View Reports',
            ],
            'System Settings' => [
                self::SettingsView->value => 'View Settings',
                self::SettingsManage->value => 'Manage Settings',
            ],
        ];
    }
}
