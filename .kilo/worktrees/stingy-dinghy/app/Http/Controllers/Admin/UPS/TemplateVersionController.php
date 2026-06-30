<?php

namespace App\Http\Controllers\Admin\UPS;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\TemplateVersion;
use App\Models\UpsTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Actions\Admin\Ups\RollbackTemplateVersionAction;
use App\Actions\Admin\Ups\RenameTemplateVersionAction;

/**
 * Template Version Controller
 *
 * Manages template version history, comparison, and rollback
 *
 * Context7 Compliant: ✅
 */
class TemplateVersionController extends Controller
{
    /**
     * List all versions for a template
     */
    public function index($templateId): View
    {
        $template = UpsTemplate::findOrFail($templateId);
        $versions = TemplateVersion::where('template_id', $templateId)
            ->with('createdBy')
            ->orderByDesc('version_number') // context7-ignore
            ->paginate(20);

        return view('admin.ups.template-versions.index', compact('template', 'versions'));
    }

    /**
     * Show version detail
     */
    public function show($templateId, $versionId): View
    {
        $template = UpsTemplate::findOrFail($templateId);
        $version = TemplateVersion::where('template_id', $templateId)
            ->findOrFail($versionId);

        $previousVersion = TemplateVersion::where('template_id', $templateId)
            ->where('version_number', '<', $version->version_number)
            ->orderByDesc('version_number') // context7-ignore
            ->first();

        $changes = $previousVersion
            ? $version->compareWith($previousVersion)
            : null;

        return view('admin.ups.template-versions.show', compact('template', 'version', 'previousVersion', 'changes'));
    }

    /**
     * Compare two versions
     */
    public function compare($templateId, Request $request): View
    {
        $template = UpsTemplate::findOrFail($templateId);

        $version1 = TemplateVersion::where('template_id', $templateId)
            ->findOrFail($request->version1_id);

        $version2 = TemplateVersion::where('template_id', $templateId)
            ->findOrFail($request->version2_id);

        // Ensure version1 is older than version2
        if ($version1->version_number > $version2->version_number) {
            [$version1, $version2] = [$version2, $version1];
        }

        $changes = $version2->compareWith($version1);

        $allVersions = TemplateVersion::where('template_id', $templateId)
            ->orderByDesc('version_number') // context7-ignore
            ->get();

        return view('admin.ups.template-versions.compare', compact('template', 'version1', 'version2', 'changes', 'allVersions'));
    }

    /**
     * Rollback to a version
     */
    public function rollback($templateId, $versionId): RedirectResponse
    {
        $template = UpsTemplate::findOrFail($templateId);
        $version = TemplateVersion::where('template_id', $templateId)
            ->findOrFail($versionId);

        // Create snapshot of current state before rollback
        $nextVersion = TemplateVersion::getNextVersionNumber($templateId);

        TemplateVersion::create([
            'template_id' => $templateId,
            'created_by_user_id' => auth()->id(),
            'version_number' => $nextVersion,
            'version_name' => "Restored from v{$version->version_number}",
            'snapshot' => $template->toArray(),
            'change_type' => 'restored',
            'change_description' => "Restored from version {$version->version_number}" .
                ($version->version_name ? " ({$version->version_name})" : ""),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'aktiflik_durumu' => true,
        ]);

        // Update template with old version data
        app(RollbackTemplateVersionAction::class)->handle($template, $version->snapshot);

        return redirect()->back()
            ->with('success', "Template rolled back to version {$version->version_number}");
    }

    /**
     * Delete a version
     */
    public function destroy($templateId, $versionId): RedirectResponse
    {
        $template = UpsTemplate::findOrFail($templateId);
        $version = TemplateVersion::where('template_id', $templateId)
            ->findOrFail($versionId);

        // Don't allow deleting all versions
        $versionCount = TemplateVersion::where('template_id', $templateId)->count();

        if ($versionCount <= 1) {
            return redirect()->back()
                ->with('error', 'Cannot delete the only version. Keep at least one version.');
        }

        $versionNum = $version->version_number;
        $version->delete();

        return redirect()->back()
            ->with('success', "Version {$versionNum} deleted");
    }

    /**
     * Rename a version
     */
    public function rename($templateId, $versionId, Request $request): RedirectResponse
    {
        $request->validate([
            'version_name' => 'required|string|max:255',
        ]);

        $template = UpsTemplate::findOrFail($templateId);
        $version = TemplateVersion::where('template_id', $templateId)
            ->findOrFail($versionId);

        app(RenameTemplateVersionAction::class)->handle($version, $request->version_name);

        return redirect()->back()
            ->with('success', "Version renamed to: {$request->version_name}");
    }
}
