<?php

namespace HMsoft\Tools\Features\Audit\Controllers;

use HMsoft\Tools\Features\Audit\Data\AuditLogData;
use HMsoft\Tools\Features\Audit\Models\AuditLog;
use HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService;
use HMsoft\Tools\Features\Response\Facades\CmsResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController
{
    /**
     * Get a paginated list of all audit logs.
     */
    public function index(Request $request)
    {
        // SECURITY CHECK: Ensure only Super Admins or Compliance Officers can access this.
        // You should protect this route with middleware, but this is an extra fail-safe.
        // if (!$request->user() || !$request->user()->is_admin) { // Adjust 'is_admin' to your actual permission check
        //     abort(403, 'Unauthorized access to the cryptographic ledger.');
        // }

        $result = AutoFilterAndSortService::dynamicSearchFromRequest(
            model: AuditLog::class,
            extraOperation: function (\Illuminate\Database\Eloquent\Builder &$query) {
                $query->with('user:id,first_name,last_name,email');
                $query->latest('id');
            },
        );

        // $mappedData = $result['data']->through(function (AuditLog $log) {
        //     return AuditLogData::fromModel($log);
        // });

        return CmsResponse::success(
            data: AuditLogData::filterableCollect($result['data']),
            pagination: $result['pagination']
        );
    }

    /**
     * Get the exact details of a single audit log.
     */
    public function show($id)
    {
        $log = AuditLog::with('user:id,first_name,last_name,email')->findOrFail($id);

        return CmsResponse::success(
            data: AuditLogData::fromModel($log)
        );
    }
}
