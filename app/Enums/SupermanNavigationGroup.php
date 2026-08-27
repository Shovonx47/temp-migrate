<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SupermanNavigationGroup: string implements HasIcon, HasLabel
{
    // Existing groups
    case Analytics = 'analytics';
    case User = 'user';
    case Messaging = 'messaging';
    case Audience = 'audience';
    case Billing = 'billing';
    case Settings = 'settings';

    // New groups
    case Resellers = 'resellers';
    case Affiliates = 'affiliates';
    case SmsManagement = 'sms_management';
    case Providers = 'providers';
    case Routing = 'routing';
    case SenderCli = 'sender_cli';
    case BangladeshNetwork = 'bangladesh_network';
    case Campaigns = 'campaigns';
    case ProfitManagement = 'profit_management';
    case Dlr = 'dlr';
    case MoInbound = 'mo_inbound';
    case Webhooks = 'webhooks';
    case Reports = 'reports';
    case Monitoring = 'monitoring';
    case Alerts = 'alerts';
    case PaymentGateway = 'payment_gateway';
    case Kyc = 'kyc';
    case Employees = 'employees';
    case RolesPermissions = 'roles_permissions';
    case ApprovalCenter = 'approval_center';
    case AuditLogs = 'audit_logs';
    case ApiManagement = 'api_management';
    case Security = 'security';
    case SystemLogs = 'system_logs';
    case SystemSettings = 'system_settings';
    case ApiDocumentation = 'api_documentation';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::User => 'User Management',
            self::SmsManagement => 'SMS Management',
            self::SenderCli => 'Sender / CLI Management',
            self::BangladeshNetwork => 'Bangladesh Network',
            self::ProfitManagement => 'Profit Management',
            self::Dlr => 'DLR / Delivery',
            self::MoInbound => 'MO / Inbound',
            self::Monitoring => 'Monitoring / NOC',
            self::Alerts => 'Alerts & Notifications',
            self::PaymentGateway => 'Payment Gateway',
            self::Kyc => 'KYC / Verification',
            self::RolesPermissions => 'Roles & Permissions',
            self::ApprovalCenter => 'Approval Center',
            self::AuditLogs => 'Audit & Activity Logs',
            self::ApiManagement => 'API Management',
            self::Security => 'Security & Fraud Control',
            self::SystemLogs => 'System Logs',
            self::SystemSettings => 'System Settings',
            self::ApiDocumentation => 'API Documentation',
            self::Resellers => 'S P',
            self::Providers => 'Provider Settings',
            default => $this->name,
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            // Existing
            self::User => Heroicon::OutlinedUser,
            self::Messaging => Heroicon::OutlinedChatBubbleLeftRight,
            self::Audience => Heroicon::OutlinedUsers,
            self::Billing => Heroicon::OutlinedCreditCard,
            self::Analytics => Heroicon::OutlinedChartBar,
            self::Settings => Heroicon::OutlinedCog,

            // New
            self::Resellers => Heroicon::OutlinedBuildingOffice2,
            self::Affiliates => Heroicon::OutlinedUserPlus,
            self::SmsManagement => Heroicon::OutlinedChatBubbleOvalLeft,
            self::Providers => Heroicon::OutlinedServer,
            self::Routing => Heroicon::OutlinedArrowsRightLeft,
            self::SenderCli => Heroicon::OutlinedPhone,
            self::BangladeshNetwork => Heroicon::OutlinedGlobeAlt,
            self::Campaigns => Heroicon::OutlinedMegaphone,
            self::ProfitManagement => Heroicon::OutlinedCurrencyDollar,
            self::Dlr => Heroicon::OutlinedCheckCircle,
            self::MoInbound => Heroicon::OutlinedInbox,
            self::Webhooks => Heroicon::OutlinedBolt,
            self::Reports => Heroicon::OutlinedDocumentText,
            self::Monitoring => Heroicon::OutlinedComputerDesktop,
            self::Alerts => Heroicon::OutlinedBell,
            self::PaymentGateway => Heroicon::OutlinedBanknotes,
            self::Kyc => Heroicon::OutlinedIdentification,
            self::Employees => Heroicon::OutlinedBriefcase,
            self::RolesPermissions => Heroicon::OutlinedShieldCheck,
            self::ApprovalCenter => Heroicon::OutlinedClipboardDocumentCheck,
            self::AuditLogs => Heroicon::OutlinedClipboardDocument,
            self::ApiManagement => Heroicon::OutlinedCodeBracket,
            self::Security => Heroicon::OutlinedLockClosed,
            self::SystemLogs => Heroicon::OutlinedDocumentMagnifyingGlass,
            self::SystemSettings => Heroicon::OutlinedCog8Tooth,
            self::ApiDocumentation => Heroicon::OutlinedBookOpen,
        };
    }
}
