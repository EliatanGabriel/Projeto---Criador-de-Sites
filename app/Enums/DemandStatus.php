<?php

namespace App\Enums;

enum DemandStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case NeedsInformation = 'needs_information';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Enviada para análise',
            self::UnderReview => 'Em análise',
            self::NeedsInformation => 'Aguardando informações',
            self::Approved => 'Aprovada',
            self::Rejected => 'Recusada',
            self::Cancelled => 'Cancelada',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canTransitionTo(self $to): bool
    {
        return match (true) {
            $this === self::Draft => in_array($to, [self::Submitted, self::Cancelled], true),
            $this === self::Submitted => in_array($to, [self::UnderReview, self::Cancelled], true),
            $this === self::UnderReview => in_array($to, [self::NeedsInformation, self::Approved, self::Rejected, self::Cancelled], true),
            $this === self::NeedsInformation => in_array($to, [self::Submitted, self::Cancelled], true),
            default => false,
        };
    }

    public function nextActionHint(): string
    {
        return match ($this) {
            self::Draft => 'Comercial: complementar e enviar para análise.',
            self::Submitted => 'OWNER/Técnica: assumir análise.',
            self::UnderReview => 'OWNER/Técnica: aprovar, recusar ou solicitar informações.',
            self::NeedsInformation => 'Comercial: complementar e reenviar.',
            self::Approved => 'Aguardando conversão para projeto (fase posterior).',
            self::Rejected => 'Fluxo encerrado (recusada).',
            self::Cancelled => 'Fluxo encerrado (cancelada).',
        };
    }
}
