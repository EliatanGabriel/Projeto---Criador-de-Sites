<?php

namespace App\Domain\Shared;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class UserScope
{
    public static function isOwner(User $user): bool
    {
        return $user->hasRole('OWNER');
    }

    public static function projects(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole('OWNER')) {
            return;
        }
        if ($user->hasRole('DEVELOPER')) {
            $query->where(function ($q) use ($user) {
                $q->where('technical_owner_id', $user->id)
                    ->orWhereHas('members', fn ($qq) => $qq->where('user_id', $user->id))
                    ->orWhereHas('tasks', fn ($qq) => $qq->where('assignee_id', $user->id));
            });
            return;
        }
        if ($user->hasRole('COMMERCIAL')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('demand', fn ($qq) => $qq->where('commercial_owner_id', $user->id))
                    ->orWhereHas('client', function ($qq) use ($user) {
                        $qq->where('client_id', function ($sub) use ($user) {
                            $sub->select('client_id')->from('opportunities')->where('responsible_id', $user->id)
                                ->union(fn ($s) => $s->select('client_id')->from('demands')->where('commercial_owner_id', $user->id));
                        });
                    });
            });
            return;
        }
        $query->whereRaw('1 = 0');
    }

    public static function tasks(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole('OWNER')) {
            return;
        }
        if ($user->hasRole('DEVELOPER')) {
            $query->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                    ->orWhereHas('project', function ($pq) use ($user) {
                        $pq->where('technical_owner_id', $user->id)
                            ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
                    });
            });
            return;
        }
        $query->where('assignee_id', $user->id);
    }

    public static function myTasks(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole('OWNER') || $user->hasRole('COMMERCIAL')) {
            $query->where('assignee_id', $user->id);
            return;
        }
        self::tasks($query, $user);
    }

    public static function demands(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole('OWNER')) {
            return;
        }
        if ($user->hasRole('DEVELOPER')) {
            $query->where(function ($q) use ($user) {
                $q->where('technical_owner_id', $user->id)
                    ->orWhereExists(function ($qq) use ($user) {
                        $qq->selectRaw(1)->from('projects')
                            ->whereColumn('projects.demand_id', 'demands.id')
                            ->where(function ($p) use ($user) {
                                $p->where('projects.technical_owner_id', $user->id)
                                    ->orWhereExists(fn ($m) => $m->selectRaw(1)->from('project_members')->whereColumn('project_members.project_id', 'projects.id')->where('project_members.user_id', $user->id))
                                    ->orWhereExists(fn ($t) => $t->selectRaw(1)->from('tasks')->whereColumn('tasks.project_id', 'projects.id')->where('tasks.assignee_id', $user->id));
                            });
                    });
            });
            return;
        }
        $query->where(function ($q) use ($user) {
            $q->where('commercial_owner_id', $user->id)->orWhere('created_by', $user->id);
        });
    }

    public static function websites(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole('OWNER')) {
            return;
        }
        $query->whereHas('projects', function ($q) use ($user) {
            if ($user->hasRole('COMMERCIAL')) {
                $q->whereHas('demand', fn ($qq) => $qq->where('commercial_owner_id', $user->id))
                    ->orWhereHas('client', function ($qq) use ($user) {
                        $qq->where('client_id', function ($sub) use ($user) {
                            $sub->select('client_id')->from('opportunities')->where('responsible_id', $user->id);
                        });
                    });
            } elseif ($user->hasRole('DEVELOPER')) {
                $q->where(function ($qq) use ($user) {
                    $qq->where('technical_owner_id', $user->id)
                        ->orWhereHas('members', fn ($qqq) => $qqq->where('user_id', $user->id))
                        ->orWhereHas('tasks', fn ($qqq) => $qqq->where('assignee_id', $user->id));
                });
            } else {
                $q->whereRaw('1=0');
            }
        });
    }

    public static function financialByClient(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole('OWNER')) {
            return;
        }
        $query->where(function ($q) use ($user) {
            $q->whereExists(function ($sub) use ($user) {
                $sub->selectRaw(1)->from('opportunities')->whereColumn('opportunities.client_id', 'financial_transactions.client_id')->where('opportunities.responsible_id', $user->id);
            })->orWhereExists(function ($sub) use ($user) {
                $sub->selectRaw(1)->from('demands')->whereColumn('demands.client_id', 'financial_transactions.client_id')->where('demands.commercial_owner_id', $user->id);
            })->orWhereExists(function ($sub) use ($user) {
                $sub->selectRaw(1)->from('projects')->whereColumn('projects.id', 'financial_transactions.project_id')->where('projects.technical_owner_id', $user->id);
            });
        });
    }

    public static function alertWebsites(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole('OWNER')) {
            return;
        }
        $query->whereIn('website_id', function ($q) use ($user) {
            $q->select('id')->from('websites')->whereNull('deleted_at')
                ->where(function ($qq) use ($user) {
                    if ($user->hasRole('COMMERCIAL')) {
                        $qq->whereIn('client_id', function ($qqq) use ($user) {
                            $qqq->select('client_id')->from('opportunities')->where('responsible_id', $user->id)
                                ->union(fn ($qqq) => $qqq->select('client_id')->from('demands')->where('commercial_owner_id', $user->id));
                        });
                    } elseif ($user->hasRole('DEVELOPER')) {
                        $qq->whereExists(function ($qqq) use ($user) {
                            $qqq->selectRaw(1)->from('website_projects AS wp')
                                ->join('projects AS p', 'p.id', '=', 'wp.project_id')
                                ->whereColumn('wp.website_id', 'websites.id')
                                ->where(function ($w) use ($user) {
                                    $w->where('p.technical_owner_id', $user->id)
                                        ->orWhereExists(fn ($m) => $m->selectRaw(1)->from('project_members')->whereColumn('project_members.project_id', 'p.id')->where('project_members.user_id', $user->id))
                                        ->orWhereExists(fn ($t) => $t->selectRaw(1)->from('tasks')->whereColumn('tasks.project_id', 'p.id')->where('tasks.assignee_id', $user->id)->whereNull('tasks.deleted_at'));
                                });
                        });
                    } else {
                        $qq->whereRaw('1=0');
                    }
                });
        });
    }
}
