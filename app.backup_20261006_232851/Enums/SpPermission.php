<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SpPermission: string implements HasColor, HasIcon, HasLabel
{
    // Customers
    case CustomersView = 'customers.view';
    case CustomersCreate = 'customers.create';
    case CustomersEdit = 'customers.edit';
    case CustomersDelete = 'customers.delete';
    case CustomersAssign = 'customers.assign';
    case CustomersExport = 'customers.export';

    // Billing & Tariff
    case BillingView = 'billing.view';
    case BillingManage = 'billing.manage';

    // Messaging
    case MessagingView = 'messaging.view';
    case MessagingManage = 'messaging.manage';

    // Employees
    case EmployeesView = 'employees.view';
    case EmployeesCreate = 'employees.create';
    case EmployeesEdit = 'employees.edit';
    case EmployeesDelete = 'employees.delete';

    // Roles
    case RolesView = 'roles.view';
    case RolesManage = 'roles.manage';

    // Reports
    case ReportsView = 'reports.view';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CustomersView => 'View Customers',
            self::CustomersCreate => 'Create Customers',
            self::CustomersEdit => 'Edit Customers',
            self::CustomersDelete => 'Delete Customers',
            self::CustomersAssign => 'Assign Customers',
            self::CustomersExport => 'Export Customers',

            self::BillingView => 'View Billing & Tariff',
            self::BillingManage => 'Manage Tariff & Deposits',

            self::MessagingView => 'View Messaging',
            self::MessagingManage => 'Manage Senders & Messages',

            self::EmployeesView => 'View Employees',
            self::EmployeesCreate => 'Create Employees',
            self::EmployeesEdit => 'Edit Employees',
            self::EmployeesDelete => 'Delete Employees',

            self::RolesView => 'View Roles',
            self::RolesManage => 'Manage Roles',

            self::ReportsView => 'View Reports',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CustomersView, self::BillingView, self::MessagingView,
            self::EmployeesView, self::RolesView, self::ReportsView => 'info',

            self::CustomersCreate, self::EmployeesCreate => 'success',

            self::CustomersEdit, self::EmployeesEdit,
            self::BillingManage, self::MessagingManage, self::RolesManage => 'warning',

            self::CustomersDelete, self::EmployeesDelete => 'danger',

            self::CustomersAssign, self::CustomersExport => 'primary',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::CustomersView, self::EmployeesView, self::RolesView,
            self::BillingView, self::MessagingView, self::ReportsView => Heroicon::OutlinedEye,

            self::CustomersCreate, self::EmployeesCreate => Heroicon::OutlinedPlusCircle,

            self::CustomersEdit, self::EmployeesEdit, self::RolesManage => Heroicon::OutlinedPencil,

            self::CustomersDelete, self::EmployeesDelete => Heroicon::OutlinedTrash,

            self::CustomersAssign => Heroicon::OutlinedUserPlus,
            self::CustomersExport => Heroicon::OutlinedArrowDownTray,
            self::BillingManage => Heroicon::OutlinedBanknotes,
            self::MessagingManage => Heroicon::OutlinedPaperAirplane,
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
            'Customers' => [
                self::CustomersView->value => 'View Customers',
                self::CustomersCreate->value => 'Create Customers',
                self::CustomersEdit->value => 'Edit Customers',
                self::CustomersDelete->value => 'Delete Customers',
                self::CustomersAssign->value => 'Assign Customers',
                self::CustomersExport->value => 'Export Customers',
            ],
            'Billing & Tariff' => [
                self::BillingView->value => 'View Billing & Tariff',
                self::BillingManage->value => 'Manage Tariff & Deposits',
            ],
            'Messaging' => [
                self::MessagingView->value => 'View Messaging',
                self::MessagingManage->value => 'Manage Senders & Messages',
            ],
            'Employees' => [
                self::EmployeesView->value => 'View Employees',
                self::EmployeesCreate->value => 'Create Employees',
                self::EmployeesEdit->value => 'Edit Employees',
                self::EmployeesDelete->value => 'Delete Employees',
            ],
            'Role Management' => [
                self::RolesView->value => 'View Roles',
                self::RolesManage->value => 'Manage Roles',
            ],
            'Reports' => [
                self::ReportsView->value => 'View Reports',
            ],
        ];
    }
}
