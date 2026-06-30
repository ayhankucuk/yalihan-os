<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\UpsVersion;
use App\Services\Response\ResponseService;
use App\Services\Ups\UpsVersioningService;
use Illuminate\Http\Request;

/**
 * UPS Version Controller
 *
 * Context7 Compliance: Version history + rollback
 */
class UpsVersionController extends Controller
{
    public function __construct(
        private UpsVersioningService $versioningService
    ) {}

    public function index(Request $request)
    {
        $versions = UpsVersion::query()
            ->with('createdBy:id,name')
            ->when($request->entity_type, fn($q, $type) => $q->where('entity_type', $type))
            ->when($request->entity_id, fn($q, $id) => $q->where('entity_id', $id))
            ->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(50);

        return view('admin.ups.versions.index', [
            'versions' => $versions,
            'filters' => $request->only(['entity_type', 'entity_id']),
        ]);
    }

    public function rollback(Request $request, UpsVersion $version)
    {
        $validated = $request->validate([
            'confirm' => 'required|boolean|accepted',
        ]);

        $result = $this->versioningService->rollbackToVersion($version->id);

        return ResponseService::success($result, 'Rollback completed successfully');
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
        ]);

        $history = $this->versioningService->getVersionHistory(
            $validated['entity_type'],
            $validated['entity_id']
        );

        return ResponseService::success(['history' => $history]);
    }
}
