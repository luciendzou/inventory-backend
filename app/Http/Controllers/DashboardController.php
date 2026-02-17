<?php

namespace App\Http\Controllers;

use App\Models\EntreesStock;
use App\Models\SortieStock;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Indicateurs et widgets dashboard"
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard/sales-overview",
     *     tags={"Dashboard"},
     *     summary="Vue d'ensemble des flux d'entrees/sorties",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         description="Periode par defaut si from/to absent",
     *         @OA\Schema(type="string", enum={"day","week","month","year"}, example="month")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-02-01")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-02-28")
     *     ),
     *     @OA\Parameter(
     *         name="group_by",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day","week","month"}, example="day")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Donnees du graphique",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="labels",
     *                 type="array",
     *                 @OA\Items(type="string", example="01/02")
     *             ),
     *             @OA\Property(
     *                 property="series",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Entrees"),
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(type="integer", example=120)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function salesOverview(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|in:day,week,month,year',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        [$from, $to] = $this->resolveRange($validated['period'] ?? 'month', $validated['from'] ?? null, $validated['to'] ?? null);
        $groupBy = $validated['group_by'] ?? 'day';
        $entrepriseId = $request->user()->id_entreprise;

        $entriesRaw = EntreesStock::query()
            ->join('products', 'products.id_product', '=', 'entrees_stocks.id_product')
            ->where('products.id_entreprise', $entrepriseId)
            ->whereBetween('entrees_stocks.date_reception', [$from->toDateString(), $to->toDateString()])
            ->selectRaw($this->groupExpression('entrees_stocks.date_reception', $groupBy) . ' as bucket')
            ->selectRaw('COALESCE(SUM(entrees_stocks.quantite_entree), 0) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $exitsRaw = SortieStock::query()
            ->join('products', 'products.id_product', '=', 'sortie_stocks.id_product')
            ->where('products.id_entreprise', $entrepriseId)
            ->whereBetween('sortie_stocks.date_sortie', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw($this->groupExpression('sortie_stocks.date_sortie', $groupBy) . ' as bucket')
            ->selectRaw('COALESCE(SUM(sortie_stocks.quantite_sortie), 0) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $labels = [];
        $entriesSeries = [];
        $exitsSeries = [];

        foreach ($this->makeBuckets($from, $to, $groupBy) as $bucket) {
            $labels[] = $bucket['label'];
            $entriesSeries[] = $entriesRaw[$bucket['key']] ?? 0;
            $exitsSeries[] = $exitsRaw[$bucket['key']] ?? 0;
        }

        return response()->json([
            'labels' => $labels,
            'series' => [
                ['name' => 'Entrées', 'data' => $entriesSeries],
                ['name' => 'Sorties', 'data' => $exitsSeries],
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/yearly-breakup",
     *     tags={"Dashboard"},
     *     summary="Repartition annuelle",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=2026)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Widget annuel",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_value", type="integer", example=36358),
     *             @OA\Property(property="change_pct_vs_previous_year", type="integer", example=9),
     *             @OA\Property(
     *                 property="donut",
     *                 type="array",
     *                 @OA\Items(type="integer", example=38)
     *             ),
     *             @OA\Property(
     *                 property="legend",
     *                 type="array",
     *                 @OA\Items(type="string", example="Entrees")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function yearlyBreakup(Request $request)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $previousYear = $year - 1;
        $entrepriseId = $request->user()->id_entreprise;

        $entriesValue = $this->sumEntriesValueForYear($entrepriseId, $year);
        $sortiesValue = $this->sumSortiesValueForYear($entrepriseId, $year);
        $adjustmentsValue = 0.0;

        $totalValue = $entriesValue + $sortiesValue + $adjustmentsValue;
        $previousTotal = $this->sumEntriesValueForYear($entrepriseId, $previousYear)
            + $this->sumSortiesValueForYear($entrepriseId, $previousYear);

        return response()->json([
            'total_value' => (int) round($totalValue),
            'change_pct_vs_previous_year' => $this->percentChange($totalValue, $previousTotal),
            'donut' => $this->donutDistribution([$entriesValue, $sortiesValue, $adjustmentsValue]),
            'legend' => ['Entrées', 'Sorties', 'Ajustements'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/monthly-earnings",
     *     tags={"Dashboard"},
     *     summary="Performance mensuelle",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=false,
     *         description="Format YYYY-MM",
     *         @OA\Schema(type="string", example="2026-02")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Widget mensuel",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="value", type="integer", example=6820),
     *             @OA\Property(property="change_pct_vs_previous_month", type="integer", example=9),
     *             @OA\Property(
     *                 property="sparkline",
     *                 type="array",
     *                 @OA\Items(type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function monthlyEarnings(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $previous = $current->copy()->subMonth();
        $entrepriseId = $request->user()->id_entreprise;

        $currentValue = $this->sumSortiesValueForMonth($entrepriseId, $current);
        $previousValue = $this->sumSortiesValueForMonth($entrepriseId, $previous);

        $sparklineRaw = SortieStock::query()
            ->join('products', 'products.id_product', '=', 'sortie_stocks.id_product')
            ->where('products.id_entreprise', $entrepriseId)
            ->whereBetween('sortie_stocks.date_sortie', [$current->copy()->startOfMonth(), $current->copy()->endOfMonth()])
            ->selectRaw('DAYOFWEEK(sortie_stocks.date_sortie) as dw')
            ->selectRaw('COALESCE(SUM(sortie_stocks.quantite_sortie), 0) as total')
            ->groupBy('dw')
            ->pluck('total', 'dw')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $sparkline = [];
        for ($i = 1; $i <= 7; $i++) {
            $sparkline[] = $sparklineRaw[$i] ?? 0;
        }

        return response()->json([
            'value' => (int) round($currentValue),
            'change_pct_vs_previous_month' => $this->percentChange($currentValue, $previousValue),
            'sparkline' => $sparkline,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/recent-transactions",
     *     tags={"Dashboard"},
     *     summary="Liste des transactions recentes",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="TRX-001"),
     *                 @OA\Property(property="date", type="string", format="date-time", example="2026-02-17T10:12:00Z"),
     *                 @OA\Property(property="type", type="string", example="SORTIE"),
     *                 @OA\Property(property="title", type="string", example="Sortie stock"),
     *                 @OA\Property(property="subtitle", type="string", example="PC Lenovo x2"),
     *                 @OA\Property(property="url", type="string", example="/products/global-history")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function recentTransactions(Request $request)
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $entrepriseId = $request->user()->id_entreprise;

        $entries = EntreesStock::query()
            ->with('product:id_product,nom,id_entreprise')
            ->whereHas('product', fn ($q) => $q->where('id_entreprise', $entrepriseId))
            ->orderByDesc('date_reception')
            ->limit($limit)
            ->get()
            ->map(function (EntreesStock $row) {
                return [
                    'id' => (string) $row->id_entrees_stocks,
                    'date' => Carbon::parse($row->date_reception)->startOfDay()->toISOString(),
                    'type' => 'ENTREE',
                    'title' => 'Entrée stock',
                    'subtitle' => trim(($row->product?->nom ?? 'Produit') . ' x' . $row->quantite_entree),
                    'url' => '/products/global-history',
                ];
            });

        $exits = SortieStock::query()
            ->with('product:id_product,nom,id_entreprise')
            ->whereHas('product', fn ($q) => $q->where('id_entreprise', $entrepriseId))
            ->orderByDesc('date_sortie')
            ->limit($limit)
            ->get()
            ->map(function (SortieStock $row) {
                return [
                    'id' => (string) $row->id_sortie_stock,
                    'date' => Carbon::parse($row->date_sortie)->toISOString(),
                    'type' => 'SORTIE',
                    'title' => 'Sortie stock',
                    'subtitle' => trim(($row->product?->nom ?? 'Produit') . ' x' . $row->quantite_sortie),
                    'url' => '/products/global-history',
                ];
            });

        $transactions = $entries
            ->concat($exits)
            ->sortByDesc('date')
            ->take($limit)
            ->values()
            ->map(function (array $trx, int $index) {
                $trx['id'] = 'TRX-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                return $trx;
            });

        return response()->json($transactions);
    }

    private function resolveRange(string $period, ?string $from, ?string $to): array
    {
        if ($from && $to) {
            return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        }

        $now = now();

        return match ($period) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function groupExpression(string $column, string $groupBy): string
    {
        return match ($groupBy) {
            'week' => "DATE_FORMAT($column, '%x-%v')",
            'month' => "DATE_FORMAT($column, '%Y-%m')",
            default => "DATE_FORMAT($column, '%Y-%m-%d')",
        };
    }

    private function makeBuckets(Carbon $from, Carbon $to, string $groupBy): array
    {
        $items = [];

        if ($groupBy === 'week') {
            $period = CarbonPeriod::create($from->copy()->startOfWeek(), '1 week', $to->copy()->endOfWeek());
            foreach ($period as $date) {
                $items[] = [
                    'key' => $date->format('o-W'),
                    'label' => 'S' . $date->format('W'),
                ];
            }

            return $items;
        }

        if ($groupBy === 'month') {
            $period = CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->endOfMonth());
            foreach ($period as $date) {
                $items[] = [
                    'key' => $date->format('Y-m'),
                    'label' => $date->format('m/Y'),
                ];
            }

            return $items;
        }

        $period = CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->endOfDay());
        foreach ($period as $date) {
            $items[] = [
                'key' => $date->format('Y-m-d'),
                'label' => $date->format('d/m'),
            ];
        }

        return $items;
    }

    private function sumEntriesValueForYear(string $entrepriseId, int $year): float
    {
        return (float) EntreesStock::query()
            ->join('products', 'products.id_product', '=', 'entrees_stocks.id_product')
            ->where('products.id_entreprise', $entrepriseId)
            ->whereYear('entrees_stocks.date_reception', $year)
            ->selectRaw('COALESCE(SUM(entrees_stocks.quantite_entree * COALESCE(products.prix, 0)), 0) as total')
            ->value('total');
    }

    private function sumSortiesValueForYear(string $entrepriseId, int $year): float
    {
        return (float) SortieStock::query()
            ->join('products', 'products.id_product', '=', 'sortie_stocks.id_product')
            ->where('products.id_entreprise', $entrepriseId)
            ->whereYear('sortie_stocks.date_sortie', $year)
            ->selectRaw('COALESCE(SUM(sortie_stocks.quantite_sortie * COALESCE(products.prix, 0)), 0) as total')
            ->value('total');
    }

    private function sumSortiesValueForMonth(string $entrepriseId, Carbon $month): float
    {
        return (float) SortieStock::query()
            ->join('products', 'products.id_product', '=', 'sortie_stocks.id_product')
            ->where('products.id_entreprise', $entrepriseId)
            ->whereBetween('sortie_stocks.date_sortie', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(sortie_stocks.quantite_sortie * COALESCE(products.prix, 0)), 0) as total')
            ->value('total');
    }

    private function percentChange(float $current, float $previous): int
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function donutDistribution(array $values): array
    {
        $sum = array_sum($values);
        if ($sum <= 0) {
            return [0, 0, 0];
        }

        $parts = array_map(fn ($v) => (int) round(($v / $sum) * 100), $values);
        $delta = 100 - array_sum($parts);
        $parts[0] += $delta;

        return $parts;
    }
}
