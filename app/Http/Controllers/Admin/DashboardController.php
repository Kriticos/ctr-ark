<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Procedure;
use App\Models\ProcedureAudit;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $allowedSectorIds = $user?->isAdmin()
            ? null
            : ($user?->sectors()->pluck('sectors.id')->map(fn ($id) => (int) $id)->all() ?? []);

        $proceduresQuery = Procedure::query();
        if ($allowedSectorIds !== null) {
            $proceduresQuery->whereHas('sectors', function ($query) use ($allowedSectorIds): void {
                $query->whereIn('sectors.id', $allowedSectorIds);
            });
        }

        $totalProcedures = (clone $proceduresQuery)->count();
        $newProcedures = (clone $proceduresQuery)->where('created_at', '>=', now()->subDays(30))->count();
        $pendingApproval = (clone $proceduresQuery)
            ->whereNotIn('status', [Procedure::STATUS_APPROVED, Procedure::STATUS_PUBLISHED])
            ->count();
        $inReviewProcedures = (clone $proceduresQuery)->where('status', Procedure::STATUS_IN_REVIEW)->count();
        $publishedProcedures = (clone $proceduresQuery)->where('status', Procedure::STATUS_PUBLISHED)->count();
        $onlineUsers = User::onlineUsers();

        $proceduresBySector = $this->proceduresBySector($allowedSectorIds);
        $statusDistribution = $this->statusDistribution($proceduresQuery);
        $topViewedProcedures = $this->topViewedProcedures($allowedSectorIds);
        $topViewedBySector = $this->topViewedProcedureBySector($allowedSectorIds);
        $recentActivities = $this->recentActivities($allowedSectorIds);

        return view('admin.dashboard', [
            'onlineUsers' => $onlineUsers,
            'totalProcedures' => $totalProcedures,
            'sectorCoverage' => $proceduresBySector->count(),
            'newProcedures' => $newProcedures,
            'pendingApproval' => $pendingApproval,
            'inReviewProcedures' => $inReviewProcedures,
            'publishedProcedures' => $publishedProcedures,
            'proceduresBySector' => $proceduresBySector,
            'statusDistribution' => $statusDistribution,
            'topViewedProcedures' => $topViewedProcedures,
            'topViewedBySector' => $topViewedBySector,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * @param  array<int>|null  $allowedSectorIds
     * @return Collection<int, array{name:string,total:int}>
     */
    private function proceduresBySector(?array $allowedSectorIds): Collection
    {
        return Sector::query()
            ->active()
            ->withCount(['linkedProcedures as procedures_count' => function ($query) use ($allowedSectorIds): void {
                if ($allowedSectorIds !== null) {
                    $query->whereHas('sectors', function ($sectorQuery) use ($allowedSectorIds): void {
                        $sectorQuery->whereIn('sectors.id', $allowedSectorIds);
                    });
                }
            }])
            ->orderByDesc('procedures_count')
            ->orderBy('name')
            ->get()
            ->filter(fn (Sector $sector) => (int) $sector->procedures_count > 0)
            ->map(fn (Sector $sector) => [
                'name' => $sector->name,
                'total' => (int) $sector->procedures_count,
            ])
            ->values();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Procedure>  $proceduresQuery
     * @return Collection<int, array{status:string,total:int}>
     */
    private function statusDistribution($proceduresQuery): Collection
    {
        $labels = [
            Procedure::STATUS_DRAFT => 'Rascunho',
            Procedure::STATUS_IN_REVIEW => 'Em aprovacao',
            Procedure::STATUS_APPROVED => 'Aprovado',
            Procedure::STATUS_PUBLISHED => 'Publicado',
        ];

        $totals = (clone $proceduresQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect($labels)
            ->map(fn (string $label, string $status) => [
                'status' => $label,
                'total' => (int) ($totals[$status] ?? 0),
            ])
            ->values();
    }

    /**
     * @param  array<int>|null  $allowedSectorIds
     * @return Collection<int, array{title:string,views:int}>
     */
    private function topViewedProcedures(?array $allowedSectorIds): Collection
    {
        $query = ProcedureAudit::query()
            ->join('procedures', 'procedures.id', '=', 'procedure_audits.procedure_id')
            ->where('procedure_audits.action', 'viewed')
            ->whereNull('procedures.deleted_at');

        if ($allowedSectorIds !== null) {
            $query->whereExists(function ($subQuery) use ($allowedSectorIds): void {
                $subQuery->selectRaw('1')
                    ->from('procedure_sector')
                    ->whereColumn('procedure_sector.procedure_id', 'procedures.id')
                    ->whereIn('procedure_sector.sector_id', $allowedSectorIds);
            });
        }

        return $query
            ->selectRaw('procedures.title, COUNT(*) as views')
            ->groupBy('procedures.id', 'procedures.title')
            ->orderByDesc('views')
            ->limit(7)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->title,
                'views' => (int) $row->views,
            ]);
    }

    /**
     * @param  array<int>|null  $allowedSectorIds
     * @return Collection<int, array{sector:string,procedure:string,views:int}>
     */
    private function topViewedProcedureBySector(?array $allowedSectorIds): Collection
    {
        $query = ProcedureAudit::query()
            ->join('procedures', 'procedures.id', '=', 'procedure_audits.procedure_id')
            ->join('procedure_sector', 'procedure_sector.procedure_id', '=', 'procedures.id')
            ->join('sectors', 'sectors.id', '=', 'procedure_sector.sector_id')
            ->where('procedure_audits.action', 'viewed')
            ->whereNull('procedures.deleted_at');

        if ($allowedSectorIds !== null) {
            $query->whereIn('sectors.id', $allowedSectorIds);
        }

        return $query
            ->selectRaw('sectors.id as sector_id, sectors.name as sector_name, procedures.title as procedure_title, COUNT(*) as views')
            ->groupBy('sectors.id', 'sectors.name', 'procedures.id', 'procedures.title')
            ->orderBy('sectors.name')
            ->orderByDesc('views')
            ->get()
            ->groupBy('sector_id')
            ->map(function (Collection $items) {
                $top = $items->first();

                return [
                    'sector' => $top->sector_name,
                    'procedure' => $top->procedure_title,
                    'views' => (int) $top->views,
                ];
            })
            ->values();
    }

    /**
     * @param  array<int>|null  $allowedSectorIds
     * @return Collection<int, ProcedureAudit>
     */
    private function recentActivities(?array $allowedSectorIds): Collection
    {
        $query = ProcedureAudit::query()
            ->with(['procedure', 'user'])
            ->whereNotIn('action', ['viewed', 'seed_test_tutorial']);

        if ($allowedSectorIds !== null) {
            $query->whereHas('procedure.sectors', function ($sectorQuery) use ($allowedSectorIds): void {
                $sectorQuery->whereIn('sectors.id', $allowedSectorIds);
            });
        }

        return $query
            ->latest()
            ->limit(8)
            ->get();
    }
}
