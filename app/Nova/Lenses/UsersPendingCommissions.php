<?php

namespace App\Nova\Lenses;

use App\Nova\Actions\PayCommissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Lenses\Lens;
use Laravel\Nova\Http\Requests\LensRequest;

class UsersPendingCommissions extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Pending Commissions';

    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        return $request->withOrdering($request->withFilters(
            $query->select(self::columns())
                ->join('commissions', 'users.id', '=', 'commissions.user_id')
                ->join('earnings', 'earnings.id', '=', 'commissions.earning_id')
                ->when(!$request->user()->isAdmin(), function ($query) use ($request) {
                    return $query->where('commissions.user_id', $request->user()->id);
                })
                ->where(function ($query) {
                    return $query->whereNull('commissions.paid_at')
                        ->orWhereNull('commissions.payout_id');
                })
                ->orderBy('amount', 'desc')
                ->groupBy('commissions.user_id')
          ));
    }

    /**
     * Get the columns that should be selected.
     *
     * @return array
     */
    protected static function columns()
    {
        return [
            'users.id',
            'users.username',
            'users.paypal_email',
            'users.minimum_payout',
            \DB::raw('sum(commissions.amount) as amount'),
        ];
    }

    /**
     * Get the fields available to the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make('ID'),
            Text::make('Referrer', 'username'),
            Text::make('PayPal Email'),
            Number::make('Pending Amount', 'amount')
                  ->displayUsing(function ($price) {
                      return '$' . number_format($price / 100, 2);
                  }),
        ];
    }

    /**
     * Get the filters available for the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available on the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [

        ];
    }

    /**
     * Get the URI key for the lens.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'users-pending-commissions';
    }
}
