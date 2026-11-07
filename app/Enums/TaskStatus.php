<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Blocked = 'blocked';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'A fazer',
            self::InProgress => 'Em andamento',
            self::InReview => 'Em revisão',
            self::Blocked => 'Bloqueada',
            self::Done => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
