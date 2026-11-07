<?php

namespace App\Enums;

enum OpportunityStatus: string
{
    case Nova = 'nova';
    case EmContato = 'em_contato';
    case EmNegociacao = 'em_negociacao';
    case AguardandoCliente = 'aguardando_cliente';
    case Ganha = 'ganha';
    case Perdida = 'perdida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Nova => 'Nova',
            self::EmContato => 'Em contato',
            self::EmNegociacao => 'Em negociação',
            self::AguardandoCliente => 'Aguardando cliente',
            self::Ganha => 'Ganha',
            self::Perdida => 'Perdida',
            self::Cancelada => 'Cancelada',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
