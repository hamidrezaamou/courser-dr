<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListPagination
{
    public const OPTIONS = [15, 25, 50, 100];

    public static function perPage(Request $request, int $default = 25): int
    {
        $n = (int) $request->input('per_page', $default);

        return in_array($n, self::OPTIONS, true) ? $n : $default;
    }

    /**
     * @param  Collection<int, mixed>  $items
     */
    public static function paginate(Collection $items, Request $request, int $default = 25): LengthAwarePaginator
    {
        $perPage = self::perPage($request, $default);
        $page = max(1, (int) $request->input('page', 1));
        $total = $items->count();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
